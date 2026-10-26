<?php

namespace App\Models;

use Database\Factories\PredictionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    /** @use HasFactory<PredictionFactory> */
    use HasFactory;

    protected $fillable = ['student_id', 'risk_score', 'risk_level', 'predicted_class', 'cause', 'explanation'];

    protected function casts(): array
    {
        return ['explanation' => 'array', 'risk_score' => 'decimal:2'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
