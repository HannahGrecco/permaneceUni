<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $institutionId = $request->user()->institution_id;
        $students = Student::query()->where('institution_id', $institutionId);
        $riskCount = fn (string $level): int => Student::query()
            ->where('institution_id', $institutionId)
            ->whereHas('latestPrediction', fn ($query) => $query->where('risk_level', $level))
            ->count();

        $riskStudents = Student::query()->where('institution_id', $institutionId)->with('latestPrediction');
        if ($search = trim($request->string('search')->toString())) {
            $riskStudents->where(fn ($query) => $query->where('external_id', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }
        if ($risk = $request->string('risk')->toString()) {
            $riskStudents->whereHas('latestPrediction', fn ($query) => $query->where('risk_level', $risk));
        }

        $riskStudents = $riskStudents->orderByRaw("CASE (select risk_level from predictions where predictions.student_id = students.id order by predictions.id desc limit 1) WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByDesc('id')->paginate(12)->withQueryString();
        $riskStudents->getCollection()->transform(static function (Student $student): Student {
            $explanation = $student->latestPrediction?->explanation ?? [];
            $factor = collect($explanation['factors'] ?? [])
                ->filter(static fn (array $item): bool => (float) ($item['contribution'] ?? 0) > 0)
                ->sortByDesc('contribution')
                ->first();
            $student->setAttribute('top_risk_factor', $factor
                ? config('permaneceuni.feature_labels.'.$factor['feature'], $factor['feature'])
                : 'Sem contribuição positiva');

            return $student;
        });

        return view('dashboard', [
            'studentCount' => $students->count(),
            'highRiskCount' => $riskCount('high'),
            'mediumRiskCount' => $riskCount('medium'),
            'lowRiskCount' => $riskCount('low'),
            'openInterventionCount' => Intervention::query()
                ->whereHas('student', fn ($query) => $query->where('institution_id', $institutionId))
                ->whereIn('status', ['planned', 'in_progress'])
                ->count(),
            'riskStudents' => $riskStudents,
        ]);
    }
}
