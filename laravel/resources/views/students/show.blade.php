@extends('layouts.app')

@section('title', 'Perfil do estudante')

@section('content')
<div class="mb-6"><a href="{{ route('students.index') }}" class="text-sm font-medium text-slate-500 hover:text-blue-700">← Estudantes</a></div>
<section class="flex flex-wrap items-start justify-between gap-5 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-7">
    <div class="flex min-w-0 items-center gap-4"><span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-blue-50 text-xl font-semibold text-blue-700">{{ mb_substr($student->name ?: $student->external_id, 0, 1) }}</span><div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-[.15em] text-blue-700">Perfil do estudante</p><h1 class="mt-1 truncate text-2xl font-semibold tracking-tight text-[#142342] sm:text-3xl">{{ $student->name ?: 'Estudante '.$student->external_id }}</h1><p class="mt-1.5 text-sm text-slate-500">Registro {{ $student->external_id }} <span class="px-1.5 text-slate-300">·</span> {{ $student->course_code ?? 'Curso não informado' }} <span class="px-1.5 text-slate-300">·</span> {{ $student->age_at_enrollment ? $student->age_at_enrollment.' anos no ingresso' : 'Período não informado' }}</p></div></div>
    <button id="open-feedback-modal" type="button" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100">Solicitar feedback</button>
</section>

@php($feedbackMessage = "Olá, ".($student->name ?: 'estudante')."!\n\nA equipe da ".$student->institution->name." está acompanhando sua trajetória acadêmica e gostaria de ouvir você. Queremos saber como tem sido sua experiência e se existe algo em que possamos apoiar neste momento.\n\nSua resposta é importante para nós e será tratada com cuidado. Se preferir, responda a este e-mail para conversarmos.\n\nCom carinho,\nEquipe da ".$student->institution->name."\nPermaneceUni — apoio à permanência estudantil")
<dialog id="feedback-dialog" aria-labelledby="feedback-title" class="w-[min(92vw,560px)] rounded-2xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/40">
    <form id="feedback-form" method="POST" action="{{ route('students.feedback.send', $student) }}" class="p-6 sm:p-7">
        @csrf
        <input type="hidden" name="_feedback_form" value="1">
        <div class="flex items-start justify-between gap-4">
            <div><p class="text-xs font-semibold uppercase tracking-[.15em] text-blue-700">Contato com estudante</p><h2 id="feedback-title" class="mt-1 text-xl font-semibold text-[#142342]">Solicitar feedback</h2><p class="mt-2 text-sm leading-6 text-slate-500">Informe o e-mail para enviar uma mensagem acolhedora em nome da instituição.</p></div>
            <button id="close-feedback-modal" type="button" aria-label="Fechar" class="rounded-lg px-2 py-1 text-xl text-slate-400 hover:bg-slate-100">×</button>
        </div>
        <label for="feedback-email" class="mt-5 block text-sm font-medium text-slate-700">E-mail do estudante</label>
        <input id="feedback-email" name="email" type="email" required maxlength="254" autocomplete="email" value="{{ old('email') }}" placeholder="estudante@exemplo.com" class="mt-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-sm outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">
        <label for="feedback-message" class="mt-5 block text-sm font-medium text-slate-700">Mensagem que será enviada</label>
        <textarea id="feedback-message" rows="9" readonly class="mt-1.5 w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm leading-6 text-slate-600">{{ $feedbackMessage }}</textarea>
        <p class="mt-2 text-xs leading-5 text-slate-400">A mensagem convida o estudante a conversar, sem atribuir uma causa específica ao risco estimado.</p>
        <div class="mt-6 flex justify-end gap-3"><button id="cancel-feedback-modal" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button><button type="submit" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Enviar e-mail</button></div>
    </form>
</dialog>
<script>
    const feedbackDialog = document.getElementById('feedback-dialog');
    document.getElementById('open-feedback-modal').addEventListener('click', () => feedbackDialog.showModal());
    document.getElementById('close-feedback-modal').addEventListener('click', () => feedbackDialog.close());
    document.getElementById('cancel-feedback-modal').addEventListener('click', () => feedbackDialog.close());
    feedbackDialog.addEventListener('click', (event) => {
        if (event.target === feedbackDialog) feedbackDialog.close();
    });
    @if ($errors->any() && old('_feedback_form'))
        feedbackDialog.showModal();
    @endif
</script>

<div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_310px]">
    <div class="space-y-5">
        <section class="grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-6"><div class="flex items-center justify-between"><div><p class="text-sm font-medium text-slate-500">Risco estimado de evasão</p><p class="mt-1 text-xs text-slate-400">Previsão mais recente</p></div><span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">◉</span></div>
                @if ($student->latestPrediction)
                    @php($riskClass = $student->latestPrediction->risk_level)
                    <div class="mt-5 flex items-center gap-4"><div class="relative grid size-[90px] shrink-0 place-items-center rounded-full" style="background: conic-gradient({{ match ($riskClass) { 'high' => '#e11d48', 'medium' => '#f59e0b', 'low' => '#10b981', default => '#94a3b8' } }} {{ min(100, max(0, (float) $student->latestPrediction->risk_score)) }}%, #f1f5f9 0)"><div aria-label="{{ number_format((float) $student->latestPrediction->risk_score, 1, ',', '.') }}%" class="grid size-[70px] place-items-center rounded-full bg-white text-lg font-semibold tracking-tight text-[#142342]">{{ number_format((float) $student->latestPrediction->risk_score, 1, ',', '.') }}<span class="-mt-4 text-[10px] text-slate-400">%</span></div></div><div><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ match ($riskClass) { 'high' => 'bg-rose-50 text-rose-700', 'medium' => 'bg-amber-50 text-amber-700', 'low' => 'bg-emerald-50 text-emerald-700', default => 'bg-slate-100 text-slate-600' } }}">{{ match ($riskClass) { 'high' => 'Alto risco', 'medium' => 'Risco médio', 'low' => 'Baixo risco', default => 'Não classificado' } }}</span><p class="mt-2 text-xs leading-5 text-slate-500">A previsão é um sinal de acompanhamento e deve ser interpretada pela equipe.</p></div></div>
                    <p class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-400">Resultado estimado: <span class="font-medium text-slate-600">{{ $student->latestPrediction->predicted_class }}</span> <span class="px-1">·</span> {{ $student->latestPrediction->created_at->format('d/m/Y H:i') }}</p>
                @else<p class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">A previsão ainda não está disponível.</p>@endif
            </article>
            <article class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-6"><div class="flex items-center justify-between"><div><h2 class="font-semibold text-[#142342]">Maior contribuição para o risco</h2><p class="mt-1 text-xs text-slate-400">Explicação local do modelo</p></div><span class="grid size-10 place-items-center rounded-xl bg-amber-50 text-amber-700">↗</span></div><div class="mt-5 rounded-xl border border-slate-100 bg-slate-50/80 p-4">@if ($topRiskFactor)<p class="text-sm font-semibold text-slate-700">{{ $topRiskFactor['label'] }}</p><p class="mt-1.5 text-sm font-semibold text-rose-700">+{{ number_format((float) $topRiskFactor['contribution'], 2, ',', '.') }} pontos percentuais no score</p>@else<p class="text-sm font-semibold text-slate-700">Nenhum atributo elevou o score nesta previsão</p><p class="mt-1.5 text-xs leading-5 text-slate-500">Os fatores da explicação reduziram ou não alteraram a estimativa em relação ao score-base.</p>@endif</div><p class="mt-4 text-[11px] leading-5 text-slate-400">A contribuição explica o cálculo do Random Forest; não comprova uma causa real nem define uma intervenção.</p></article>
        </section>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-6"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-[#142342]">Fatores desta previsão</h2><p class="mt-1 text-xs text-slate-400">Contribuições locais calculadas com TreeSHAP</p></div><span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-medium text-blue-700">Modelo Random Forest</span></div>
            @if ($student->latestPrediction && ($student->latestPrediction->explanation['method'] ?? null) === 'tree_shap_path_dependent')
                <div class="mt-5 flex flex-wrap gap-5 rounded-xl bg-slate-50 px-4 py-3 text-xs"><span class="text-slate-500">Score-base <strong class="ml-1 text-slate-700">{{ number_format((float) $student->latestPrediction->explanation['baseline_score'], 2, ',', '.') }}%</strong></span><span class="text-slate-500">Score estimado <strong class="ml-1 text-slate-700">{{ number_format((float) $student->latestPrediction->risk_score, 2, ',', '.') }}%</strong></span></div>
                <div class="mt-5 space-y-4">@foreach ($explanationFactors as $item)<div><div class="mb-1.5 flex flex-wrap items-center justify-between gap-2 text-xs"><span class="font-medium text-slate-600">{{ $item['label'] }} <span class="ml-1 font-normal text-slate-400">(valor {{ number_format((float) $item['value'], 2, ',', '.') }})</span></span><span class="shrink-0 font-semibold {{ $item['contribution'] > 0 ? 'text-rose-700' : ($item['contribution'] < 0 ? 'text-blue-700' : 'text-slate-500') }}">{{ $item['contribution'] > 0 ? '+' : '' }}{{ number_format((float) $item['contribution'], 2, ',', '.') }} p.p.</span></div><div class="h-1.5 rounded-full bg-slate-100"><div class="h-1.5 rounded-full {{ $item['contribution'] > 0 ? 'bg-rose-400' : 'bg-blue-400' }}" style="width: {{ max(2, $item['bar_width']) }}%"></div></div></div>@endforeach</div>
                <p class="mt-5 border-t border-slate-100 pt-4 text-[11px] leading-5 text-slate-400">As contribuições dos atributos explicam como o modelo chegou ao score-base e à estimativa. Valores positivos elevam o score; negativos reduzem. Isso descreve o modelo, não uma causa ou efeito comprovado no estudante.</p>
            @else<p class="mt-5 text-sm text-slate-500">A explicação local estará disponível após uma nova previsão.</p>@endif
        </section>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-6"><div><h2 class="font-semibold text-[#142342]">Dados acadêmicos</h2><p class="mt-1 text-xs text-slate-400">Informações disponíveis na base importada</p></div><dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ([['Idade no ingresso', $student->age_at_enrollment], ['Nota de ingresso', $student->admission_grade !== null ? number_format((float) $student->admission_grade, 2, ',', '.') : null], ['Média semestral', $student->average_grade !== null ? number_format((float) $student->average_grade, 2, ',', '.') : null], ['Aprovadas — 1º semestre', $student->approved_units_first_semester], ['Aprovadas — 2º semestre', $student->approved_units_second_semester], ['Resultado observado', $student->observed_outcome]] as [$label, $value])<div class="rounded-xl bg-slate-50/70 p-3.5"><dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1.5 text-sm font-semibold text-slate-700">{{ $value ?? '—' }}</dd></div>@endforeach</dl></section>
    </div>
    <aside class="space-y-5">
        <section id="intervention-form" class="scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)]"><span class="grid size-9 place-items-center rounded-xl bg-blue-50 text-blue-700">＋</span><h2 class="mt-4 font-semibold text-[#142342]">Próximo passo</h2><p class="mt-1 text-xs leading-5 text-slate-400">Registre uma ação de apoio ou contato de acompanhamento.</p><form method="POST" action="{{ route('students.interventions.store', $student) }}" class="mt-4 space-y-3">@csrf<label class="block text-xs font-medium text-slate-600">Ação<input name="title" required maxlength="160" value="{{ old('title') }}" placeholder="Ex.: solicitar feedback" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-normal outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></label><label class="block text-xs font-medium text-slate-600">Categoria<select name="category" required class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal"><option value="academic">Acadêmica</option><option value="financial">Financeira</option><option value="other">Outra</option></select></label><label class="block text-xs font-medium text-slate-600">Data prevista<input name="scheduled_at" type="date" value="{{ old('scheduled_at') }}" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-normal"></label><label class="block text-xs font-medium text-slate-600">Plano / observações<textarea name="description" rows="3" class="mt-1.5 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-normal outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100">{{ old('description') }}</textarea></label><button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Registrar intervenção</button></form></section>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)]"><div class="flex items-center justify-between"><h2 class="font-semibold text-[#142342]">Histórico de intervenções</h2><a href="{{ route('interventions.index') }}" class="text-xs font-semibold text-blue-700">Ver quadro →</a></div>
            @if ($student->interventions->isEmpty())
                <p class="mt-4 rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-500">Nenhuma ação registrada para este estudante.</p>
            @else
                <ol class="mt-4 space-y-4">
                    @foreach ($student->interventions->sortByDesc('created_at') as $intervention)
                        <li class="relative border-l border-slate-200 pb-1 pl-4 last:border-transparent"><span class="absolute -left-[5px] top-0.5 size-2.5 rounded-full {{ match ($intervention->status) { 'completed' => 'bg-emerald-500 ring-4 ring-emerald-50', 'ignored' => 'bg-slate-400 ring-4 ring-slate-100', default => 'bg-blue-500 ring-4 ring-blue-50' } }}"></span><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-semibold text-slate-700">{{ $intervention->title }}</p><span class="text-[10px] font-semibold {{ match ($intervention->status) { 'completed' => 'text-emerald-700', 'ignored' => 'text-slate-500', 'in_progress' => 'text-blue-700', default => 'text-amber-700' } }}">{{ match ($intervention->status) { 'completed' => 'Realizada', 'ignored' => 'Ignorada', 'in_progress' => 'Em andamento', default => 'Pendente' } }}</span></div><p class="mt-1 text-[11px] text-slate-400">{{ $intervention->created_at->format('d/m/Y') }} · {{ $intervention->creator->name }}</p>
                            @if ($intervention->description)<p class="mt-2 text-xs leading-5 text-slate-500">{{ $intervention->description }}</p>@endif
                            @foreach ($intervention->events->sortBy('created_at') as $event)
                                <p class="mt-2 text-[10px] text-slate-400">{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->description }}</p>
                            @endforeach
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </aside>
</div>
@endsection
