@extends('layouts.app')

@section('title', 'Intervenções')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-700">Plano de permanência</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-[#142342]">Intervenções</h1><p class="mt-2 text-sm text-slate-500">Organize os contatos e acompanhe o histórico de apoio aos estudantes.</p></div><a href="{{ route('students.index') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">＋ Registrar intervenção</a></div>
@php($columns = [['title' => 'Pendentes', 'items' => $pendingInterventions, 'color' => 'bg-amber-400', 'number' => 'text-amber-800', 'empty' => 'Nenhuma ação aguardando acompanhamento.'], ['title' => 'Realizadas', 'items' => $completedInterventions, 'color' => 'bg-emerald-500', 'number' => 'text-emerald-800', 'empty' => 'As ações concluídas aparecerão aqui.'], ['title' => 'Ignoradas', 'items' => $ignoredInterventions, 'color' => 'bg-slate-400', 'number' => 'text-slate-600', 'empty' => 'Nenhuma ação foi ignorada.']])
<div class="mt-7 grid items-start gap-5 xl:grid-cols-3">
    @foreach ($columns as $column)
        <section class="min-w-0 rounded-2xl border border-slate-200/80 bg-slate-100/65 p-3.5 sm:p-4"><div class="flex items-center justify-between px-1 pb-3"><div class="flex items-center gap-2.5"><span class="size-2 rounded-full {{ $column['color'] }}"></span><h2 class="text-sm font-semibold text-[#142342]">{{ $column['title'] }}</h2></div><span class="grid min-w-7 place-items-center rounded-full bg-white px-2 py-1 text-xs font-semibold {{ $column['number'] }} shadow-sm">{{ $column['items']->count() }}</span></div>
            <div class="space-y-3">
                @forelse ($column['items'] as $intervention)
                    @php($level = $intervention->student->latestPrediction?->risk_level)
                    <article class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-[0_2px_8px_rgba(15,23,42,.035)]"><div class="flex items-start justify-between gap-2"><span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-blue-700">{{ match ($intervention->category) { 'academic' => 'Acadêmica', 'financial' => 'Financeira', default => 'Acompanhamento' } }}</span>@if ($level)<span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ match ($level) { 'high' => 'bg-rose-50 text-rose-700', 'medium' => 'bg-amber-50 text-amber-700', default => 'bg-emerald-50 text-emerald-700' } }}">{{ match ($level) { 'high' => 'Alto risco', 'medium' => 'Risco médio', default => 'Baixo risco' } }}</span>@endif</div>
                        <a href="{{ route('students.show', $intervention->student) }}" class="mt-3 block font-semibold text-[#142342] hover:text-blue-700">{{ $intervention->student->name ?: $intervention->student->external_id }}</a><p class="mt-0.5 text-xs text-slate-400">{{ $intervention->student->external_id }} · {{ $intervention->student->course_code ?? 'Curso não informado' }}</p><h3 class="mt-4 text-sm font-medium text-slate-700">{{ $intervention->title }}</h3><p class="mt-1 line-clamp-3 text-xs leading-5 text-slate-500">{{ $intervention->description ?: 'Sem recomendação ou observação adicional registrada.' }}</p>
                        <div class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs text-slate-500"><p class="flex items-center justify-between"><span>Data</span><span class="font-medium text-slate-700">{{ $intervention->scheduled_at?->format('d/m/Y') ?? 'Não agendada' }}</span></p><p class="flex items-center justify-between"><span>Responsável</span><span class="max-w-[60%] truncate font-medium text-slate-700">{{ $intervention->creator->name }}</span></p></div>
                        @if ($column['title'] === 'Pendentes')<div class="mt-4 flex gap-2"><form method="POST" action="{{ route('interventions.complete', $intervention) }}" class="flex-1">@csrf @method('PATCH')<button class="w-full rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Marcar realizada</button></form><form method="POST" action="{{ route('interventions.ignore', $intervention) }}">@csrf @method('PATCH')<button aria-label="Ignorar intervenção" title="Ignorar" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-50">Ignorar</button></form></div>@endif
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 bg-white/60 px-4 py-8 text-center"><span class="mx-auto grid size-9 place-items-center rounded-xl bg-white text-slate-400 shadow-sm">—</span><p class="mt-3 text-xs leading-5 text-slate-400">{{ $column['empty'] }}</p></div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
<p class="mt-5 text-xs text-slate-400">As contribuições no perfil explicam o score do modelo; não comprovam causas reais. As ações são definidas pela equipe.</p>
@endsection
