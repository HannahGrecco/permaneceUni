<?php

namespace App\Models;

use Database\Factories\InterventionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Intervention extends Model
{
    /** @use HasFactory<InterventionFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id', 'created_by_user_id', 'title', 'category', 'description', 'status', 'scheduled_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InterventionEvent::class);
    }
}
