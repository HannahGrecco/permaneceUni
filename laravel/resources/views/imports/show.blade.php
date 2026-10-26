@extends('layouts.app')

@section('title', 'Detalhes da importação')

@section('content')
<a href="{{ route('imports.index') }}" class="text-sm font-semibold text-indigo-700 hover:underline">← Voltar às importações</a>
<div class="mt-4 flex flex-wrap items-start justify-between gap-4">
    <div><p class="text-sm font-semibold uppercase tracking-wider text-indigo-700">Importação CSV</p><h1 class="mt-1 break-all text-3xl font-bold tracking-tight">{{ $import->original_filename }}</h1><p class="mt-2 text-slate-600">{{ $import->dataset_type === 'academic_dataset' ? 'Base acadêmica' : 'Cadastro institucional' }} · enviada por {{ $import->user->name }}</p></div>
    <span class="rounded-full px-3 py-1.5 text-sm font-semibold {{ $import->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($import->status === 'failed' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ match ($import->status) { 'queued' => 'Na fila', 'processing' => 'Processando', 'completed' => 'Concluída', 'failed' => 'Falhou', 'awaiting_schema' => 'Aguardando esquema', default => $import->status } }}</span>
</div>
<section class="mt-7 grid gap-4 sm:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Linhas processadas</p><p class="mt-2 text-2xl font-bold">{{ number_format($import->processed_rows, 0, ',', '.') }}</p></article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-sm text-emerald-800">Importadas com previsão</p><p class="mt-2 text-2xl font-bold text-emerald-950">{{ number_format($import->imported_rows, 0, ',', '.') }}</p></article>
    <article class="rounded-2xl border border-rose-200 bg-rose-50 p-5"><p class="text-sm text-rose-800">Linhas com erro</p><p class="mt-2 text-2xl font-bold text-rose-950">{{ number_format($import->failed_rows, 0, ',', '.') }}</p></article>
</section>
@if ($import->status === 'awaiting_schema')
    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">O arquivo foi guardado. A interpretação está aguardando o segundo CSV e a definição de seus cabeçalhos.</div>
@endif
@if ($import->error_message)
    <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">{{ $import->error_message }}</div>
@endif
@if ($import->metadata['row_errors'] ?? [])
    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5"><h2 class="font-semibold">Avisos das linhas</h2><ul class="mt-3 space-y-2 text-sm text-slate-600">@foreach ($import->metadata['row_errors'] as $rowError)<li>Linha {{ $rowError['row'] }}: {{ $rowError['message'] }}</li>@endforeach</ul></section>
@endif
<p class="mt-5 text-sm text-slate-500">Criada em {{ $import->created_at->format('d/m/Y H:i') }}@if ($import->finished_at) · finalizada em {{ $import->finished_at->format('d/m/Y H:i') }}@endif</p>
@endsection
