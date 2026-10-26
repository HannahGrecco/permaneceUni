<?php

namespace App\Models;

use Database\Factories\CsvImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CsvImport extends Model
{
    /** @use HasFactory<CsvImportFactory> */
    use HasFactory;

    public const TYPE_ACADEMIC_DATASET = 'academic_dataset';

    public const TYPE_STUDENT_REGISTRY = 'student_registry';

    protected $fillable = [
        'institution_id', 'user_id', 'dataset_type', 'original_filename', 'file_path', 'status',
        'processed_rows', 'imported_rows', 'failed_rows', 'error_message', 'metadata', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
