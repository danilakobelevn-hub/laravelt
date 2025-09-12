<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subsection extends Model
{
    use HasFactory;

    protected $fillable = ['section_id', 'alias', 'default_name', 'default_description'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
