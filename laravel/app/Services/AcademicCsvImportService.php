<?php

namespace App\Services;

use App\Models\CsvImport;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

class AcademicCsvImportService
{
    public function __construct(private MlPredictionService $predictionService) {}

    /** @return array<int, string> */
    public function expectedHeaders(): array
    {
        return config('permaneceuni.academic_features');
    }

    public function validateUploadedFile(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'O arquivo CSV não pôde ser lido.']);
        }

        try {
            $headers = $this->readHeaders($handle);
        } finally {
            fclose($handle);
        }

        $missingHeaders = array_values(array_diff($this->expectedHeaders(), $headers));
        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'Este CSV não corresponde à base acadêmica. Colunas ausentes: '.implode(', ', $missingHeaders),
            ]);
        }

        $profileColumns = array_values(config('permaneceuni.student_profile_columns'));
        $providedProfileColumns = array_values(array_intersect($profileColumns, $headers));
        if ($providedProfileColumns !== [] && count($providedProfileColumns) !== count($profileColumns)) {
            throw ValidationException::withMessages([
                'file' => 'Para identificar os estudantes, inclua juntas as colunas opcionais Student name e Student registration.',
            ]);
        }
    }

    public function process(CsvImport $import): void
    {
        $path = Storage::disk('local')->path($import->file_path);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('O arquivo enviado não está mais disponível para processamento.');
        }

        $import->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
        $headers = $this->readHeaders($handle);
        $headerPositions = array_flip($headers);
        $processedRows = 0;
        $importedRows = 0;
        $failedRows = 0;
        $rowErrors = [];
        $lineNumber = 1;

        try {
            while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $lineNumber++;
                if ($values === [null] || $values === []) {
                    continue;
                }

                $processedRows++;
                $record = $this->recordForHeaders($headers, $values);

                try {
                    $features = $this->featuresFromRecord($record, $headerPositions);
                    $observedOutcome = $this->observedOutcomeFromRecord($record, $headerPositions);
                    $identity = $this->identityFromRecord($record, $headerPositions, $import, $lineNumber);
                    $prediction = $this->predictionService->predict($features);
                    $this->persistStudentAndPrediction($import, $features, $prediction, $identity, $observedOutcome);
                    $importedRows++;
                } catch (InvalidArgumentException $exception) {
                    $failedRows++;
                    if (count($rowErrors) < 30) {
                        $rowErrors[] = ['row' => $lineNumber, 'message' => $exception->getMessage()];
                    }
                }

                if ($processedRows % 25 === 0) {
                    $import->update([
                        'processed_rows' => $processedRows,
                        'imported_rows' => $importedRows,
                        'failed_rows' => $failedRows,
                        'metadata' => ['row_errors' => $rowErrors],
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }

        $import->update([
            'status' => 'completed',
            'processed_rows' => $processedRows,
            'imported_rows' => $importedRows,
            'failed_rows' => $failedRows,
            'metadata' => ['row_errors' => $rowErrors],
            'finished_at' => now(),
        ]);
    }

    /** @param resource $handle
     * @return array<int, string>
     */
    private function readHeaders($handle): array
    {
        $headers = fgetcsv($handle, null, ',', '"', '');
        if ($headers === false) {
            throw ValidationException::withMessages(['file' => 'O CSV está vazio.']);
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);

        return array_map(static fn ($header): string => trim((string) $header), $headers);
    }

    /** @param array<int, string> $headers
     * @param  array<int, string|null>  $values
     * @return array<string, string|null>
     */
    private function recordForHeaders(array $headers, array $values): array
    {
        $record = [];
        foreach ($headers as $index => $header) {
            $record[$header] = $values[$index] ?? null;
        }

        return $record;
    }

    /** @param array<string, string|null> $record
     * @param  array<string, int>  $headerPositions
     * @return array<string, int|float>
     */
    private function featuresFromRecord(array $record, array $headerPositions): array
    {
        $features = [];
        foreach (config('permaneceuni.academic_features') as $feature) {
            if (! array_key_exists($feature, $headerPositions)
                || ! isset($record[$feature])
                || ! is_numeric($record[$feature])) {
                throw new InvalidArgumentException("A coluna {$feature} contém valor ausente ou não numérico.");
            }

            $number = (float) $record[$feature];
            if (! is_finite($number)) {
                throw new InvalidArgumentException("A coluna {$feature} contém valor inválido.");
            }

            $features[$feature] = $number;
        }

        return $features;
    }

    /** @param array<string, string|null> $record
     * @param  array<string, int>  $headerPositions
     */
    private function observedOutcomeFromRecord(array $record, array $headerPositions): ?string
    {
        $targetHeader = config('permaneceuni.academic_target');
        $providedHeader = collect(array_keys($headerPositions))
            ->first(static fn (string $header): bool => strcasecmp($header, $targetHeader) === 0);

        if ($providedHeader === null) {
            return null;
        }

        $outcome = trim((string) ($record[$providedHeader] ?? ''));
        if ($outcome === '') {
            return null;
        }
        if (! in_array($outcome, ['Dropout', 'Graduate', 'Enrolled'], true)) {
            throw new InvalidArgumentException('A coluna target contém uma classe desconhecida.');
        }

        return $outcome;
    }

    /** @param array<string, string|null> $record
     * @param  array<string, int>  $headerPositions
     * @return array{name: string|null, external_id: string}
     */
    private function identityFromRecord(array $record, array $headerPositions, CsvImport $import, int $lineNumber): array
    {
        $profileColumns = config('permaneceuni.student_profile_columns');
        $nameColumn = $profileColumns['name'];
        $registrationColumn = $profileColumns['external_id'];

        if (! array_key_exists($nameColumn, $headerPositions) && ! array_key_exists($registrationColumn, $headerPositions)) {
            return [
                'name' => null,
                'external_id' => sprintf('BASE-%06d-%06d', $import->id, $lineNumber),
            ];
        }

        $name = trim((string) ($record[$nameColumn] ?? ''));
        $registration = trim((string) ($record[$registrationColumn] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidArgumentException("A coluna {$nameColumn} contém um nome vazio ou muito longo.");
        }
        if ($registration === '' || mb_strlen($registration) > 255) {
            throw new InvalidArgumentException("A coluna {$registrationColumn} contém uma matrícula vazia ou muito longa.");
        }

        return ['name' => $name, 'external_id' => $registration];
    }

    /** @param array<string, string|null> $record
     * @param  array<string, int|float>  $features
     * @param  array<string, mixed>  $prediction
     */
    private function persistStudentAndPrediction(
        CsvImport $import,
        array $features,
        array $prediction,
        array $identity,
        ?string $observedOutcome,
    ): void {
        $gradeValues = [
            $features['Curricular units 1st sem (grade)'],
            $features['Curricular units 2nd sem (grade)'],
        ];
        DB::transaction(function () use ($import, $features, $prediction, $identity, $observedOutcome, $gradeValues): void {
            $student = Student::create([
                'institution_id' => $import->institution_id,
                'csv_import_id' => $import->id,
                'external_id' => $identity['external_id'],
                'name' => $identity['name'],
                'course_code' => (string) $features['Course'],
                'age_at_enrollment' => (int) $features['Age at enrollment'],
                'admission_grade' => $features['Admission grade'],
                'average_grade' => array_sum($gradeValues) / count($gradeValues),
                'approved_units_first_semester' => (int) $features['Curricular units 1st sem (approved)'],
                'approved_units_second_semester' => (int) $features['Curricular units 2nd sem (approved)'],
                'observed_outcome' => $observedOutcome,
                'academic_data' => $features,
            ]);

            $student->predictions()->create([
                ...$prediction,
                'cause' => 'undetermined',
            ]);
        });
    }
}
