<?php

namespace App\Models;

use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    /** @use HasFactory<InstitutionFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function csvImports(): HasMany
    {
        return $this->hasMany(CsvImport::class);
    }
}
