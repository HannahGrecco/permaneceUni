<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Painel') · PermaneceUni</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f7fb] text-slate-800 antialiased">
    @auth
        <div class="min-h-screen lg:flex">
            <aside class="flex w-full flex-col bg-[#101c35] text-slate-300 lg:fixed lg:inset-y-0 lg:w-[252px]">
                <div class="flex items-center justify-between px-6 py-6 lg:block">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 text-white">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-500 text-lg font-bold shadow-lg shadow-blue-950/30">P</span>
                        <span><span class="block text-base font-semibold tracking-tight">PermaneceUni</span><span class="mt-0.5 block text-[10px] font-medium uppercase tracking-[.17em] text-slate-400">Previsão e retenção</span></span>
                    </a>
                    <span class="rounded-full border border-slate-700 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400 lg:hidden">{{ auth()->user()->institution?->name ?? 'Instituição' }}</span>
                </div>
                <div class="hidden px-6 pb-2 text-[10px] font-semibold uppercase tracking-[.18em] text-slate-500 lg:block">Menu principal</div>
                <nav class="flex gap-1 overflow-x-auto px-4 pb-4 text-sm lg:flex-col lg:px-3">
                    @php($navigation = [
                        ['route' => 'dashboard', 'label' => 'Visão geral', 'icon' => '◫'],
                        ['route' => 'students.index', 'label' => 'Estudantes', 'icon' => '♙'],
                        ['route' => 'interventions.index', 'label' => 'Intervenções', 'icon' => '✓'],
                        ['route' => 'imports.index', 'label' => 'Importar dados', 'icon' => '⇧'],
                    ])
                    @foreach ($navigation as $item)
                        <a href="{{ route($item['route']) }}" class="flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-3 font-medium transition {{ request()->routeIs($item['route']) || ($item['route'] === 'interventions.index' && request()->routeIs('interventions.*')) ? 'bg-blue-500/15 text-white ring-1 ring-inset ring-blue-400/20' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                            <span class="grid size-6 place-items-center text-base {{ request()->routeIs($item['route']) ? 'text-blue-300' : 'text-slate-500' }}">{{ $item['icon'] }}</span>{{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="mt-auto hidden p-4 lg:block">
                    <div class="rounded-2xl border border-white/10 bg-white/[.04] p-4">
                        <div class="flex items-center gap-2 text-xs font-semibold text-white"><span class="size-2 rounded-full bg-emerald-400"></span> Acompanhamento ativo</div>
                        <p class="mt-2 text-xs leading-5 text-slate-400">Previsões apoiam o cuidado. Cada estudante merece uma conversa individual.</p>
                    </div>
                    <div class="mt-4 flex items-center gap-3 border-t border-white/10 px-1 pt-4">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-700 text-sm font-semibold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()->institution?->name ?? 'Equipe acadêmica' }}</p></div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button aria-label="Sair" title="Sair" class="rounded-lg p-2 text-slate-400 hover:bg-white/10 hover:text-white">↗</button></form>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-white/10 px-5 py-3 text-xs text-slate-500 lg:hidden">
                    <span>{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="font-semibold text-slate-300">Sair</button></form>
                </div>
            </aside>
            <div class="min-w-0 flex-1 lg:ml-[252px]">
                <header class="sticky top-0 z-10 flex h-[68px] items-center justify-between border-b border-slate-200/80 bg-white/90 px-5 backdrop-blur sm:px-8 lg:px-10">
                    <div><p class="text-xs font-medium text-slate-400">PermaneceUni <span class="px-1">/</span> <span class="text-slate-600">@yield('title', 'Painel')</span></p><p class="mt-1 text-sm font-semibold text-slate-700">{{ auth()->user()->institution?->name ?? 'Sua instituição' }}</p></div>
                    <div class="hidden items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-500 sm:flex"><span class="size-1.5 rounded-full bg-emerald-500"></span> Ambiente institucional</div>
                </header>
                <main class="mx-auto max-w-[1450px] px-5 py-7 sm:px-8 sm:py-9 lg:px-10">
                    @if (session('status'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>@endif
                    @if ($errors->any())<div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert"><p class="font-semibold">Confira os dados informados.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    @yield('content')
                </main>
                <footer class="px-5 pb-7 text-center text-xs text-slate-400 sm:px-8 lg:px-10">PermaneceUni · Previsão para apoiar decisões de permanência estudantil</footer>
            </div>
        </div>
    @else
        <main class="min-h-screen bg-[#f5f7fb] px-4 py-12 sm:py-20">
            <a href="{{ route('login') }}" class="mx-auto mb-10 flex w-fit items-center gap-3 text-[#142342]"><span class="grid size-10 place-items-center rounded-xl bg-blue-600 text-lg font-bold text-white">P</span><span><span class="block text-base font-semibold">PermaneceUni</span><span class="block text-[10px] uppercase tracking-[.16em] text-slate-500">Previsão e retenção</span></span></a>
            @if (session('status'))<div class="mx-auto mb-6 max-w-md rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="mx-auto mb-6 max-w-md rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
            @yield('content')
        </main>
    @endauth
</body>
</html>
