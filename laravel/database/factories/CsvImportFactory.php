<?php

namespace Database\Factories;

use App\Models\CsvImport;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvImport>
 */
class CsvImportFactory extends Factory
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
            'user_id' => User::factory(),
            'dataset_type' => CsvImport::TYPE_ACADEMIC_DATASET,
            'original_filename' => 'students.csv',
            'file_path' => 'imports/students.csv',
            'status' => 'queued',
            'metadata' => [],
        ];
    }
}
