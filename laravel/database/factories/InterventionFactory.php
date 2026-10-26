<?php

namespace Database\Factories;

use App\Models\Intervention;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'category' => fake()->randomElement(['academic', 'financial', 'other']),
            'description' => fake()->sentence(),
            'status' => 'planned',
        ];
    }
}
