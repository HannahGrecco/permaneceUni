<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'institution_id', 'csv_import_id', 'external_id', 'name', 'course_code', 'age_at_enrollment',
        'admission_grade', 'average_grade', 'approved_units_first_semester', 'approved_units_second_semester',
        'observed_outcome', 'academic_data',
    ];

    protected function casts(): array
    {
        return ['academic_data' => 'array'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function csvImport(): BelongsTo
    {
        return $this->belongsTo(CsvImport::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function latestPrediction(): HasOne
    {
        return $this->hasOne(Prediction::class)->latestOfMany();
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
