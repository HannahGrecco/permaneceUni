<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);
        $query = Student::query()
            ->where('institution_id', $request->user()->institution_id)
            ->with('latestPrediction');

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($studentQuery) use ($search): void {
                $studentQuery->where('external_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $course = $request->string('course')->toString();
        if ($course !== '') {
            $query->where('course_code', $course);
        }

        if ($risk = $request->string('risk')->toString()) {
            $query->whereHas('latestPrediction', fn ($predictionQuery) => $predictionQuery->where('risk_level', $risk));
        }

        return view('students.index', [
            'students' => $query->orderBy('external_id')->paginate(25)->withQueryString(),
            'courses' => Student::query()
                ->where('institution_id', $request->user()->institution_id)
                ->whereNotNull('course_code')
                ->distinct()
                ->orderBy('course_code')
                ->pluck('course_code'),
        ]);
    }

    public function show(Request $request, int $student): View
    {
        $student = Student::query()
            ->where('institution_id', $request->user()->institution_id)
            ->with(['institution', 'latestPrediction', 'interventions.creator', 'interventions.events.user'])
            ->findOrFail($student);
        $this->authorize('view', $student);

        $explanation = $student->latestPrediction?->explanation ?? [];
        $topRiskFactor = collect($explanation['factors'] ?? [])
            ->filter(static fn (array $factor): bool => (float) ($factor['contribution'] ?? 0) > 0)
            ->sortByDesc('contribution')
            ->first();
        if ($topRiskFactor !== null) {
            $topRiskFactor['label'] = config('permaneceuni.feature_labels.'.$topRiskFactor['feature'], $topRiskFactor['feature']);
        }
        $explanationFactors = array_slice($explanation['factors'] ?? [], 0, 5);
        $maxContribution = max([0.01, ...array_map(
            static fn (array $factor): float => abs((float) ($factor['contribution'] ?? 0)),
            $explanationFactors,
        )]);
        $explanationFactors = array_map(static function (array $factor) use ($maxContribution): array {
            return [
                ...$factor,
                'label' => config('permaneceuni.feature_labels.'.$factor['feature'], $factor['feature']),
                'bar_width' => min(100, abs((float) $factor['contribution']) / $maxContribution * 100),
            ];
        }, $explanationFactors);

        return view('students.show', [
            'student' => $student,
            'topRiskFactor' => $topRiskFactor,
            'explanationFactors' => $explanationFactors,
        ]);
    }
}
