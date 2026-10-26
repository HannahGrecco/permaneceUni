@extends('layouts.app')

@section('title', 'Importar dados')

@section('content')
<div class="mb-6"><a href="{{ route('imports.index') }}" class="text-sm font-medium text-slate-500 hover:text-blue-700">← Importações</a></div>
<div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.16em] text-blue-700">Base institucional</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-[#142342]">Importar dados</h1><p class="mt-2 text-sm text-slate-500">Envie um arquivo para organizar os registros e gerar as previsões de risco.</p></div><span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500">CSV · até 20 MB</span></div>
<div class="mt-7 grid gap-5 xl:grid-cols-[minmax(0,1fr)_330px]">
    <form method="POST" action="{{ route('imports.store') }}" enctype="multipart/form-data" class="space-y-5">@csrf
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-7">
            <label class="block text-sm font-semibold text-slate-700">Tipo de arquivo</label>
            <select name="dataset_type" required class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-700 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"><option value="academic_dataset" @selected(old('dataset_type', 'academic_dataset') === 'academic_dataset')>Base acadêmica — análise e previsão</option><option value="student_registry" @selected(old('dataset_type') === 'student_registry')>Cadastro institucional — aguardando esquema</option></select>
            <label for="csv-file" class="group mt-5 flex min-h-[220px] cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 px-5 py-8 text-center transition hover:border-blue-300 hover:bg-blue-50/40">
                <span class="grid size-12 place-items-center rounded-2xl bg-white text-xl text-blue-600 shadow-sm ring-1 ring-slate-200/80">⇧</span><span class="mt-4 text-sm font-semibold text-[#142342]">Solte o arquivo aqui ou <span class="text-blue-700">selecione no computador</span></span><span class="mt-1.5 text-xs text-slate-400">CSV separado por vírgula · tamanho máximo 20 MB</span><span id="selected-file" class="mt-3 hidden rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"></span>
                <input id="csv-file" name="file" type="file" accept=".csv,text/csv" required class="sr-only">
            </label>
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><p class="text-xs text-slate-400">O processamento ocorre em segundo plano. Acompanhe o resultado em Importações.</p><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-blue-900/10 transition hover:bg-blue-700">Validar e enviar arquivo</button></div>
        </section>
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_2px_12px_rgba(15,23,42,.025)] sm:p-6"><div class="flex items-start gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500">≡</span><div><h2 class="font-semibold text-[#142342]">Colunas esperadas — base acadêmica</h2><p class="mt-1 text-xs text-slate-400">Os atributos acadêmicos abaixo são obrigatórios para gerar a previsão.</p></div></div><div class="mt-4 flex flex-wrap gap-2">@foreach ($expectedHeaders as $header)<span class="rounded-md border border-slate-100 bg-slate-50 px-2.5 py-1.5 font-mono text-[11px] text-slate-600">{{ $header }}</span>@endforeach</div><div class="mt-5 border-t border-slate-100 pt-4"><p class="text-xs font-semibold text-slate-600">Resultado observado (opcional)</p><p class="mt-1 text-xs text-slate-400">A coluna {{ $optionalTargetHeader }} só é necessária quando o resultado real já é conhecido; ela não participa da previsão.</p></div><div class="mt-5 border-t border-slate-100 pt-4"><p class="text-xs font-semibold text-slate-600">Para mostrar nomes fictícios no perfil (opcional)</p><p class="mt-1 text-xs text-slate-400">Inclua as duas colunas abaixo juntas. Os valores serão usados como nome e matrícula exibidos no sistema.</p><div class="mt-2 flex flex-wrap gap-2">@foreach ($optionalProfileHeaders as $header)<span class="rounded-md border border-blue-100 bg-blue-50/70 px-2.5 py-1.5 font-mono text-[11px] text-blue-700">{{ $header }}</span>@endforeach</div></div><p class="mt-4 text-xs leading-5 text-slate-400">O CSV de demonstração usa atributos acadêmicos sintéticos e nomes/matrículas fictícios. As faixas de risco foram balanceadas para a apresentação.</p></section>
    </form>
    <aside class="space-y-4">
        <section class="rounded-2xl border border-blue-100 bg-blue-50/70 p-5"><span class="grid size-9 place-items-center rounded-xl bg-white text-blue-700 shadow-sm">✓</span><h2 class="mt-4 font-semibold text-[#142342]">Validação antes de importar</h2><p class="mt-2 text-sm leading-6 text-slate-600">O sistema confere o cabeçalho, valida cada registro e solicita uma previsão ao serviço de inferência.</p><ul class="mt-4 space-y-2 text-xs text-slate-600"><li class="flex gap-2"><span class="text-blue-600">✓</span>Colunas acadêmicas presentes</li><li class="flex gap-2"><span class="text-blue-600">✓</span>Campos numéricos verificados</li><li class="flex gap-2"><span class="text-blue-600">✓</span>Linhas inválidas registradas no relatório</li></ul></section>
        <section class="rounded-2xl border border-emerald-100 bg-white p-5"><div class="flex items-center gap-2"><span class="grid size-8 place-items-center rounded-lg bg-emerald-50 text-emerald-700">♧</span><h2 class="text-sm font-semibold text-[#142342]">Privacidade e cuidado</h2></div><p class="mt-3 text-xs leading-5 text-slate-500">Envie apenas dados autorizados pela instituição. As previsões apoiam o acompanhamento e não substituem a avaliação humana. Causas individuais permanecem indeterminadas.</p><p class="mt-3 text-[11px] font-medium text-emerald-700">Tratamento alinhado aos princípios de minimização da LGPD.</p></section>
        <section class="rounded-2xl border border-amber-200/80 bg-amber-50/70 p-5"><p class="text-xs font-semibold uppercase tracking-wider text-amber-800">Segundo CSV</p><h2 class="mt-2 font-semibold text-amber-950">Cadastro institucional</h2><p class="mt-2 text-xs leading-5 text-amber-900/80">Ainda não recebemos esse arquivo nem seu esquema. O upload fica armazenado como pendente, sem interpretar ou presumir colunas.</p></section>
    </aside>
</div>
<script>
    document.getElementById('csv-file')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        const label = document.getElementById('selected-file');
        if (!label) return;
        label.textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : '';
        label.classList.toggle('hidden', !file);
    });
</script>
@endsection
