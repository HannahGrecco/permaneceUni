<?php

namespace Tests\Feature;

use App\Jobs\ProcessStudentCsvImport;
use App\Models\CsvImport;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicCsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_fictional_demo_csv_imports_student_names_and_registrations(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        Storage::fake('local');
        Queue::fake([ProcessStudentCsvImport::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8000/v1/predictions' => Http::response([
                'risk_score' => 54.25,
                'risk_level' => 'medium',
                'predicted_class' => 'not_dropout',
                'cause' => 'undetermined',
                'explanation' => ['method' => 'tree_shap_path_dependent', 'baseline_score' => 40.0, 'factors' => []],
            ]),
        ]);

        $samplePath = dirname(base_path()).DIRECTORY_SEPARATOR.'datasets'.DIRECTORY_SEPARATOR.'permaneceuni_demo_students.csv';
        $this->assertFileExists($samplePath);
        $this->actingAs($user)->post(route('imports.store'), [
            'dataset_type' => CsvImport::TYPE_ACADEMIC_DATASET,
            'file' => UploadedFile::fake()->createWithContent('permaneceuni_demo_students.csv', file_get_contents($samplePath)),
        ])->assertRedirect();

        $import = CsvImport::query()->firstOrFail();
        app(AcademicCsvImportService::class)->process($import);

        $this->assertSame(18, $import->fresh()->imported_rows);
        $this->assertSame(18, Student::query()->where('institution_id', $institution->id)->count());
        $this->assertDatabaseHas('students', [
            'institution_id' => $institution->id,
            'external_id' => '2026.1.00481',
            'name' => 'Ana Clara Costa',
        ]);
        $this->assertSame(0, Student::query()->where('institution_id', $institution->id)->where('external_id', 'like', 'BASE-%')->count());
        Http::assertSentCount(18);
    }

    public function test_academic_csv_creates_student_and_persists_ml_prediction(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        Storage::fake('local');
        Queue::fake([ProcessStudentCsvImport::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8000/v1/predictions' => Http::response([
                'risk_score' => 78.4,
                'risk_level' => 'high',
                'predicted_class' => 'dropout',
                'cause' => 'academic',
                'explanation' => [
                    'method' => 'tree_shap_path_dependent',
                    'baseline_score' => 40.0,
                    'factors' => [['feature' => 'Admission grade', 'value' => 14.5, 'contribution' => 5.2, 'direction' => 'increases_risk']],
                ],
            ]),
        ]);
        $csv = $this->academicCsv();

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'dataset_type' => CsvImport::TYPE_ACADEMIC_DATASET,
            'file' => UploadedFile::fake()->createWithContent('academic.csv', $csv),
        ]);

        $response->assertRedirect();
        $import = CsvImport::query()->firstOrFail();
        $this->assertSame('queued', $import->status);
        Queue::assertPushed(ProcessStudentCsvImport::class, fn (ProcessStudentCsvImport $job): bool => $job->csvImportId === $import->id);

        app(AcademicCsvImportService::class)->process($import);

        $student = Student::query()->firstOrFail();
        $this->assertSame($institution->id, $student->institution_id);
        $this->assertSame('Ana Clara Costa', $student->name);
        $this->assertSame('PU2026-0001', $student->external_id);
        $this->assertSame('Graduate', $student->observed_outcome);
        $this->assertDatabaseHas('predictions', [
            'student_id' => $student->id,
            'risk_score' => 78.4,
            'risk_level' => 'high',
            'cause' => 'undetermined',
        ]);
        $this->assertSame('completed', $import->fresh()->status);
        $this->assertSame(1, $import->fresh()->imported_rows);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://127.0.0.1:8000/v1/predictions'
            && $request['student']['Admission grade'] === 14.5);
    }

    public function test_academic_csv_can_be_predicted_without_an_observed_target(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        Storage::fake('local');
        Queue::fake([ProcessStudentCsvImport::class]);
        Http::preventStrayRequests();
        Http::fake([
            'http://127.0.0.1:8000/v1/predictions' => Http::response([
                'risk_score' => 54.25,
                'risk_level' => 'medium',
                'predicted_class' => 'not_dropout',
                'cause' => 'undetermined',
                'explanation' => ['method' => 'tree_shap_path_dependent', 'baseline_score' => 40.0, 'factors' => []],
            ]),
        ]);

        $this->actingAs($user)->post(route('imports.store'), [
            'dataset_type' => CsvImport::TYPE_ACADEMIC_DATASET,
            'file' => UploadedFile::fake()->createWithContent('current-students.csv', $this->academicCsv(false)),
        ])->assertRedirect();

        $import = CsvImport::query()->firstOrFail();
        app(AcademicCsvImportService::class)->process($import);

        $this->assertSame('completed', $import->fresh()->status);
        $this->assertSame(1, $import->fresh()->imported_rows);
        $this->assertNull(Student::query()->firstOrFail()->observed_outcome);
        Http::assertSentCount(1);
    }

    public function test_unavailable_second_csv_is_stored_without_assuming_a_schema(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        Storage::fake('local');
        Queue::fake([ProcessStudentCsvImport::class]);

        $this->actingAs($user)->post(route('imports.store'), [
            'dataset_type' => CsvImport::TYPE_STUDENT_REGISTRY,
            'file' => UploadedFile::fake()->createWithContent('cadastro.csv', "coluna_a,coluna_b\nvalor,valor\n"),
        ])->assertRedirect();

        $import = CsvImport::query()->firstOrFail();
        $this->assertSame('awaiting_schema', $import->status);
        $this->assertSame(0, $import->students()->count());
        Queue::assertNothingPushed();
    }

    private function academicCsv(bool $includeTarget = true): string
    {
        $features = config('permaneceuni.academic_features');
        $values = array_fill_keys($features, '1');
        $values['Course'] = '33';
        $values['Admission grade'] = '14.5';
        $values['Age at enrollment'] = '20';
        $values['Curricular units 1st sem (grade)'] = '12';
        $values['Curricular units 2nd sem (grade)'] = '13';
        $values['Curricular units 1st sem (approved)'] = '5';
        $values['Curricular units 2nd sem (approved)'] = '6';
        $headers = [...$features, ...array_values(config('permaneceuni.student_profile_columns'))];
        $row = array_map(fn (string $header): string => $values[$header] ?? 'Graduate', $features);
        array_splice($row, count($features), 0, ['Ana Clara Costa', 'PU2026-0001']);
        if ($includeTarget) {
            $headers[] = config('permaneceuni.academic_target');
            $row[] = 'Graduate';
        }

        return implode(',', $headers)."\n".implode(',', $row)."\n";
    }
}
