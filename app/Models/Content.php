<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Content extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'default_name',
        'guid',
        'subsection_id',
        'access_type'
    ];

    public function subsection(): BelongsTo
    {
        return $this->belongsTo(Subsection::class);
    }

    public function localizedStrings(): HasMany
    {
        return $this->hasMany(ContentLocalizedString::class);
    }

    public function imageLinks(): HasMany
    {
        return $this->hasMany(ContentImageLink::class);
    }

    public function videoLinks(): HasMany
    {
        return $this->hasMany(ContentVideoLink::class);
    }

    public function availableLocales(): HasMany
    {
        return $this->hasMany(ContentAvailableLocale::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'content_module');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }
}
