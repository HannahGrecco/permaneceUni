<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCsvImportRequest;
use App\Jobs\ProcessStudentCsvImport;
use App\Models\CsvImport;
use App\Services\AcademicCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CsvImportController extends Controller
{
    public function index(Request $request): View
    {
        return view('imports.index', [
            'imports' => CsvImport::query()
                ->where('institution_id', $request->user()->institution_id)
                ->with('user')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(AcademicCsvImportService $importService): View
    {
        return view('imports.create', [
            'expectedHeaders' => $importService->expectedHeaders(),
            'optionalProfileHeaders' => array_values(config('permaneceuni.student_profile_columns')),
            'optionalTargetHeader' => config('permaneceuni.academic_target'),
        ]);
    }

    public function store(StoreCsvImportRequest $request, AcademicCsvImportService $importService): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $type = $data['dataset_type'];

        if ($type === CsvImport::TYPE_ACADEMIC_DATASET) {
            $importService->validateUploadedFile($file);
        }

        $path = $file->storeAs('imports', Str::uuid().'.csv', 'local');
        $import = CsvImport::create([
            'institution_id' => $request->user()->institution_id,
            'user_id' => $request->user()->id,
            'dataset_type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => $type === CsvImport::TYPE_ACADEMIC_DATASET ? 'queued' : 'awaiting_schema',
            'metadata' => $type === CsvImport::TYPE_STUDENT_REGISTRY
                ? ['note' => 'Aguardando o segundo CSV e sua especificação de colunas.']
                : [],
        ]);

        if ($type === CsvImport::TYPE_ACADEMIC_DATASET) {
            ProcessStudentCsvImport::dispatch($import->id);
            $message = 'Arquivo recebido. O processamento e as previsões foram colocados na fila.';
        } else {
            $message = 'Arquivo armazenado como pendente. A estrutura de colunas será definida quando o segundo CSV for fornecido.';
        }

        return redirect()->route('imports.show', $import)->with('status', $message);
    }

    public function show(Request $request, int $csvImport): View
    {
        $import = CsvImport::query()
            ->where('institution_id', $request->user()->institution_id)
            ->with('user')
            ->findOrFail($csvImport);

        return view('imports.show', ['import' => $import]);
    }
}
