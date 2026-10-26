<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterventionRequest;
use App\Models\Intervention;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterventionController extends Controller
{
    public function index(Request $request): View
    {
        $interventions = Intervention::query()
            ->whereHas('student', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
            ->with(['student.latestPrediction', 'creator'])
            ->latest('updated_at')
            ->get();

        return view('interventions.index', [
            'pendingInterventions' => $interventions->whereIn('status', ['planned', 'in_progress']),
            'completedInterventions' => $interventions->where('status', 'completed'),
            'ignoredInterventions' => $interventions->where('status', 'ignored'),
        ]);
    }

    public function store(StoreInterventionRequest $request, int $student): RedirectResponse
    {
        $student = Student::query()
            ->where('institution_id', $request->user()->institution_id)
            ->findOrFail($student);
        $this->authorize('view', $student);

        $intervention = $student->interventions()->create([
            ...$request->validated(),
            'created_by_user_id' => $request->user()->id,
            'status' => 'planned',
        ]);
        $intervention->events()->create([
            'user_id' => $request->user()->id,
            'event_type' => 'created',
            'description' => 'Intervenção registrada.',
        ]);

        return redirect()->route('students.show', $student)->with('status', 'Intervenção registrada.');
    }

    public function complete(Request $request, int $intervention): RedirectResponse
    {
        $intervention = Intervention::query()
            ->whereHas('student', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
            ->findOrFail($intervention);
        $this->authorize('update', $intervention);

        if ($intervention->status !== 'completed') {
            $intervention->update(['status' => 'completed', 'completed_at' => now()]);
            $intervention->events()->create([
                'user_id' => $request->user()->id,
                'event_type' => 'completed',
                'description' => 'Intervenção concluída.',
            ]);
        }

        return $this->returnToBoardOrProfile($request, $intervention)->with('status', 'Intervenção concluída.');
    }

    public function ignore(Request $request, int $intervention): RedirectResponse
    {
        $intervention = Intervention::query()
            ->whereHas('student', fn ($query) => $query->where('institution_id', $request->user()->institution_id))
            ->findOrFail($intervention);
        $this->authorize('update', $intervention);

        if (in_array($intervention->status, ['planned', 'in_progress'], true)) {
            $intervention->update(['status' => 'ignored']);
            $intervention->events()->create([
                'user_id' => $request->user()->id,
                'event_type' => 'ignored',
                'description' => 'Intervenção marcada como ignorada.',
            ]);
        }

        return $this->returnToBoardOrProfile($request, $intervention)->with('status', 'Intervenção movida para ignoradas.');
    }

    private function returnToBoardOrProfile(Request $request, Intervention $intervention): RedirectResponse
    {
        if (str_starts_with((string) $request->headers->get('referer'), route('interventions.index'))) {
            return redirect()->route('interventions.index');
        }

        return redirect()->route('students.show', $intervention->student_id);
    }
}
