<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'external_id' => fake()->unique()->bothify('STU-######'),
            'name' => fake()->name(),
            'course_code' => (string) fake()->numberBetween(1, 99),
            'age_at_enrollment' => fake()->numberBetween(18, 55),
            'admission_grade' => fake()->randomFloat(2, 0, 20),
            'average_grade' => fake()->randomFloat(2, 0, 20),
            'approved_units_first_semester' => fake()->numberBetween(0, 10),
            'approved_units_second_semester' => fake()->numberBetween(0, 10),
            'academic_data' => [],
        ];
    }
}
