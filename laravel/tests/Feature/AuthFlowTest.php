<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_institution_and_authenticated_user(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ana Souza',
            'email' => 'ana@example.test',
            'institution_name' => 'Universidade Exemplo',
            'password' => 'permanence123',
            'password_confirmation' => 'permanence123',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('institutions', ['name' => 'Universidade Exemplo']);
        $this->assertDatabaseHas('users', ['email' => 'ana@example.test']);
        $this->assertSame(1, Institution::count());
        $this->get(route('dashboard'))->assertOk()->assertSee('Painel de permanência');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_login_authenticates_a_registered_user(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create([
            'email' => 'staff@example.test',
            'password' => 'permanence123',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'permanence123',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }
}
