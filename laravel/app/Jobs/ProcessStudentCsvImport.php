<?php

namespace App\Jobs;

use App\Models\CsvImport;
use App\Services\AcademicCsvImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessStudentCsvImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public function __construct(public int $csvImportId) {}

    public function handle(AcademicCsvImportService $importService): void
    {
        $import = CsvImport::findOrFail($this->csvImportId);

        try {
            $importService->process($import);
        } catch (Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        CsvImport::query()->whereKey($this->csvImportId)->update([
            'status' => 'failed',
            'error_message' => $exception?->getMessage() ?? 'Falha desconhecida ao processar a importação.',
            'finished_at' => now(),
        ]);
    }
}
