<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginUserRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'As credenciais informadas não conferem.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function showRegistration(): View
    {
        return view('auth.register');
    }

    public function register(RegisterUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($data): User {
            $institution = Institution::create([
                'name' => $data['institution_name'],
                'slug' => Str::slug($data['institution_name']).'-'.Str::lower(Str::random(8)),
            ]);

            return $institution->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
