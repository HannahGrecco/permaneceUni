@extends('layouts.app')

@section('title', 'Criar conta')

@section('content')
<div class="mx-auto max-w-lg">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight">Comece a acompanhar</h1>
        <p class="mt-2 text-sm text-slate-600">Crie o acesso da sua instituição ao PermaneceUni.</p>
    </div>
    <form method="POST" action="{{ route('register.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <label class="block text-sm font-medium text-slate-700">Nome
            <input name="name" value="{{ old('name') }}" required autocomplete="name" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </label>
        <label class="block text-sm font-medium text-slate-700">Instituição
            <input name="institution_name" value="{{ old('institution_name') }}" required autocomplete="organization" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </label>
        <label class="block text-sm font-medium text-slate-700">E-mail
            <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm font-medium text-slate-700">Senha
                <input name="password" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="block text-sm font-medium text-slate-700">Confirme a senha
                <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
        </div>
        <p class="text-xs text-slate-500">Use ao menos 10 caracteres, incluindo letras e números.</p>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">Criar conta</button>
    </form>
</div>
@endsection
