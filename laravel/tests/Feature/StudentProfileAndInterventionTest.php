<?php

namespace Tests\Feature;

use App\Mail\StudentFeedbackMail;
use App\Models\Institution;
use App\Models\Intervention;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentProfileAndInterventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_profile_shows_latest_risk_explanation_and_intervention_history(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create([
            'academic_data' => ['Admission grade' => 14.5],
        ]);
        $student->predictions()->create([
            'risk_score' => 78.4,
            'risk_level' => 'high',
            'predicted_class' => 'dropout',
            'cause' => 'undetermined',
            'explanation' => [
                'method' => 'tree_shap_path_dependent',
                'baseline_score' => 50.0,
                'factors' => [
                    ['feature' => 'Admission grade', 'value' => 14.5, 'contribution' => 12.7, 'direction' => 'increases_risk'],
                    ['feature' => 'Age at enrollment', 'value' => 20, 'contribution' => -3.2, 'direction' => 'decreases_risk'],
                ],
            ],
        ]);
        $intervention = $student->interventions()->create([
            'created_by_user_id' => $user->id,
            'title' => 'Agendar conversa de acompanhamento',
            'category' => 'academic',
            'status' => 'planned',
        ]);
        $intervention->events()->create([
            'user_id' => $user->id,
            'event_type' => 'created',
            'description' => 'Intervenção registrada.',
        ]);

        $this->actingAs($user)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('78,4%')
            ->assertSee('Maior contribuição para o risco')
            ->assertSee('Nota de ingresso')
            ->assertSee('+12,70')
            ->assertSee('TreeSHAP')
            ->assertSee('Agendar conversa de acompanhamento')
            ->assertSee('Intervenção registrada.');
    }

    public function test_student_from_another_institution_is_not_found(): void
    {
        $institution = Institution::factory()->create();
        $otherInstitution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($otherInstitution)->create();

        $this->actingAs($user)->get(route('students.show', $student))->assertNotFound();
    }

    public function test_feedback_modal_requires_email_and_sends_a_personalized_message(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'apoio@universidade.test',
        ]);

        $institution = Institution::factory()->create(['name' => 'Universidade Exemplo']);
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create(['name' => 'Ana Clara Costa']);

        $this->actingAs($user)->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Solicitar feedback')
            ->assertSee('feedback-email', false)
            ->assertSee('A equipe da Universidade Exemplo está acompanhando sua trajetória acadêmica');

        $this->actingAs($user)->post(route('students.feedback.send', $student), [
            'email' => 'ana@example.test',
        ])->assertRedirectToRoute('students.show', $student)
            ->assertSessionHas('status');

        Mail::assertSent(StudentFeedbackMail::class, function (StudentFeedbackMail $mail): bool {
            return $mail->hasTo('ana@example.test')
                && str_contains($mail->messageText, 'Olá, Ana Clara Costa!')
                && str_contains($mail->messageText, 'Universidade Exemplo');
        });
    }

    public function test_feedback_request_rejects_missing_or_invalid_email(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'apoio@universidade.test',
        ]);

        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create();

        $this->actingAs($user)->post(route('students.feedback.send', $student), [
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_feedback_request_does_not_claim_delivery_when_mail_is_only_logged(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create();

        $this->actingAs($user)->post(route('students.feedback.send', $student), [
            'email' => 'student@example.test',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_dashboard_shows_the_strongest_local_risk_contribution(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create([
            'name' => 'Ana Clara Costa',
            'external_id' => 'PU2026-00481',
        ]);
        $student->predictions()->create([
            'risk_score' => 78.4,
            'risk_level' => 'high',
            'predicted_class' => 'dropout',
            'cause' => 'undetermined',
            'explanation' => [
                'method' => 'tree_shap_path_dependent',
                'baseline_score' => 50.0,
                'factors' => [
                    ['feature' => 'Admission grade', 'value' => 14.5, 'contribution' => 12.7, 'direction' => 'increases_risk'],
                    ['feature' => 'Age at enrollment', 'value' => 20, 'contribution' => -3.2, 'direction' => 'decreases_risk'],
                ],
            ],
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Fator que eleva o score')
            ->assertSee('Nota de ingresso')
            ->assertDontSee('Indeterminada');
    }

    public function test_intervention_creation_and_completion_are_recorded_in_history(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create();

        $this->actingAs($user)->post(route('students.interventions.store', $student), [
            'title' => 'Encaminhamento para tutoria',
            'category' => 'academic',
            'description' => 'Acompanhar atividades do semestre.',
        ])->assertRedirectToRoute('students.show', $student);

        $intervention = Intervention::query()->firstOrFail();
        $this->assertDatabaseHas('intervention_events', [
            'intervention_id' => $intervention->id,
            'event_type' => 'created',
        ]);

        $this->actingAs($user)->patch(route('interventions.complete', $intervention))
            ->assertRedirectToRoute('students.show', $student);

        $this->assertSame('completed', $intervention->fresh()->status);
        $this->assertDatabaseHas('intervention_events', [
            'intervention_id' => $intervention->id,
            'event_type' => 'completed',
        ]);
    }

    public function test_intervention_board_groups_items_and_can_mark_one_ignored(): void
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->for($institution)->create();
        $student = Student::factory()->for($institution)->create();
        $intervention = $student->interventions()->create([
            'created_by_user_id' => $user->id,
            'title' => 'Contato para entender necessidade de apoio',
            'category' => 'other',
            'description' => 'Conversa de acompanhamento combinada pela equipe.',
            'status' => 'planned',
        ]);

        $this->actingAs($user)->get(route('interventions.index'))
            ->assertOk()
            ->assertSee('Pendentes')
            ->assertSee('Realizadas')
            ->assertSee('Ignoradas')
            ->assertSee('Contato para entender necessidade de apoio')
            ->assertSee('Conversa de acompanhamento combinada pela equipe.');

        $this->actingAs($user)->patch(route('interventions.ignore', $intervention))
            ->assertRedirectToRoute('students.show', $student);

        $this->assertSame('ignored', $intervention->fresh()->status);
        $this->assertDatabaseHas('intervention_events', [
            'intervention_id' => $intervention->id,
            'event_type' => 'ignored',
        ]);
    }
}
