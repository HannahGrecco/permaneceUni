<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class MlPredictionService
{
    /** @param array<string, int|float> $features
     * @return array{risk_score: float, risk_level: string, predicted_class: string, cause: string, explanation: array{method: string, baseline_score: float, factors: array<int, array{feature: string, value: float, contribution: float, direction: string}>}}
     */
    public function predict(array $features): array
    {
        $response = Http::acceptJson()
            ->connectTimeout((int) config('services.permaneceuni_ml.connect_timeout'))
            ->timeout((int) config('services.permaneceuni_ml.timeout'))
            ->post(rtrim((string) config('services.permaneceuni_ml.url'), '/').'/v1/predictions', [
                'student' => $features,
            ])
            ->throw()
            ->json();

        if (! is_array($response)
            || ! isset($response['risk_score'], $response['risk_level'], $response['predicted_class'])
            || ! is_numeric($response['risk_score'])
            || ! is_array($response['explanation'] ?? null)
            || ! is_string($response['explanation']['method'] ?? null)
            || ! is_numeric($response['explanation']['baseline_score'] ?? null)
            || ! is_array($response['explanation']['factors'] ?? null)) {
            throw new UnexpectedValueException('The ML service returned an invalid prediction response.');
        }

        foreach ($response['explanation']['factors'] as $factor) {
            if (! is_array($factor)
                || ! is_string($factor['feature'] ?? null)
                || ! is_numeric($factor['value'] ?? null)
                || ! is_numeric($factor['contribution'] ?? null)
                || ! in_array($factor['direction'] ?? null, ['increases_risk', 'decreases_risk', 'no_change'], true)) {
                throw new UnexpectedValueException('The ML service returned an invalid local explanation.');
            }
        }

        $score = (float) $response['risk_score'];
        if (! is_finite($score) || $score < 0 || $score > 100) {
            throw new UnexpectedValueException('The ML service returned a risk score outside the accepted range.');
        }

        return [
            'risk_score' => $score,
            'risk_level' => (string) $response['risk_level'],
            'predicted_class' => (string) $response['predicted_class'],
            'cause' => 'undetermined',
            'explanation' => [
                'method' => $response['explanation']['method'],
                'baseline_score' => (float) $response['explanation']['baseline_score'],
                'factors' => array_map(static fn (array $factor): array => [
                    'feature' => $factor['feature'],
                    'value' => (float) $factor['value'],
                    'contribution' => (float) $factor['contribution'],
                    'direction' => $factor['direction'],
                ], $response['explanation']['factors']),
            ],
        ];
    }
}
