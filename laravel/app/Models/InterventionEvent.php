<?php

namespace App\Models;

use Database\Factories\InterventionEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionEvent extends Model
{
    /** @use HasFactory<InterventionEventFactory> */
    use HasFactory;

    protected $fillable = ['intervention_id', 'user_id', 'event_type', 'description', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
