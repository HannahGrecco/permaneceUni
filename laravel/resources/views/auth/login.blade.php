@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
<div class="mx-auto max-w-md">
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold tracking-tight">Acesse sua conta</h1>
        <p class="mt-2 text-sm text-slate-600">Entre para acompanhar os estudantes da sua instituição.</p>
    </div>
    <form method="POST" action="{{ route('login.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <label class="block text-sm font-medium text-slate-700">E-mail
            <input name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </label>
        <label class="block text-sm font-medium text-slate-700">Senha
            <input name="password" type="password" required autocomplete="current-password" class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 font-normal outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input name="remember" type="checkbox" value="1" class="rounded border-slate-300 text-indigo-600"> Manter conectado</label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white hover:bg-indigo-700">Entrar</button>
        <p class="text-center text-sm text-slate-600">Primeiro acesso? <a href="{{ route('register') }}" class="font-semibold text-indigo-700 hover:underline">Crie sua conta</a></p>
    </form>
</div>
@endsection
