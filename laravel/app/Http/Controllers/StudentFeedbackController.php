<?php

namespace App\Http\Controllers;

use App\Mail\StudentFeedbackMail;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class StudentFeedbackController extends Controller
{
    public function __invoke(Request $request, int $student): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
        ]);

        $student = Student::query()
            ->where('institution_id', $request->user()->institution_id)
            ->with('institution')
            ->findOrFail($student);
        $this->authorize('view', $student);

        if (in_array(config('mail.default'), ['log', 'array', 'failover'], true)
            || config('mail.from.address') === 'hello@example.com') {
            return back()->withErrors([
                'email' => 'O envio real de e-mails ainda não está configurado. Configure o servidor de e-mail da aplicação e tente novamente.',
            ])->withInput(['_feedback_form' => '1', 'email' => $validated['email']]);
        }

        $message = sprintf(
            "Olá, %s!\n\nA equipe da %s está acompanhando sua trajetória acadêmica e gostaria de ouvir você. Queremos saber como tem sido sua experiência e se existe algo em que possamos apoiar neste momento.\n\nSua resposta é importante para nós e será tratada com cuidado. Se preferir, responda a este e-mail para conversarmos.\n\nCom carinho,\nEquipe da %s\nPermaneceUni — apoio à permanência estudantil",
            $student->name ?: 'estudante',
            $student->institution->name,
            $student->institution->name,
        );

        try {
            Mail::to($validated['email'])->send(new StudentFeedbackMail(
                studentName: $student->name ?: 'estudante',
                institutionName: $student->institution->name,
                messageText: $message,
            ));
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar solicitação de feedback.', [
                'student_id' => $student->id,
                'exception' => $exception::class,
            ]);

            return back()->withErrors([
                'email' => 'Não foi possível enviar o e-mail. Confira a configuração do servidor de e-mail e tente novamente.',
            ])->withInput(['_feedback_form' => '1', 'email' => $validated['email']]);
        }

        return redirect()->route('students.show', $student)
            ->with('status', 'Solicitação de feedback enviada para '.$validated['email'].'.');
    }
}
