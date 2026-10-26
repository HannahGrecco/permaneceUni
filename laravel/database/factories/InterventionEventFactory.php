<?php

namespace Database\Factories;

use App\Models\Intervention;
use App\Models\InterventionEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterventionEvent>
 */
class InterventionEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intervention_id' => Intervention::factory(),
            'user_id' => User::factory(),
            'event_type' => 'created',
            'description' => fake()->sentence(),
        ];
    }
}
