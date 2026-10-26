<?php

namespace Database\Factories;

use App\Models\Prediction;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prediction>
 */
class PredictionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'risk_score' => fake()->randomFloat(2, 0, 100),
            'risk_level' => fake()->randomElement(['low', 'medium', 'high']),
            'predicted_class' => 'not_dropout',
            'cause' => 'undetermined',
            'explanation' => [],
        ];
    }
}
