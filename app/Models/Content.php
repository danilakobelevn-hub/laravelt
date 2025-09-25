<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'alias',
        'default_name',
        'guid',
        'subsection_id',
        'access_type'
    ];

    // Получить название на определенном языке
    public function getName($locale = 'ru')
    {
        return $this->localizedStrings
            ->where('type', 'name')
            ->where('locale', $locale)
            ->first()->value ?? $this->default_name;
    }

    // Получить описание на определенном языке
    public function getDescription($locale = 'ru')
    {
        return $this->localizedStrings
            ->where('type', 'description')
            ->where('locale', $locale)
            ->first()->value ?? null;
    }

    // Получить раздел и подраздел
    public function getGroupPath()
    {
        return $this->subsection->section->default_name . ' → ' . $this->subsection->default_name;
    }

    // Проверить, есть ли версии
    public function hasVersions()
    {
        return $this->versions->count() > 0;
    }

    // Получить последнюю версию
    public function getLatestVersion()
    {
        return $this->versions()
            ->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('micro', 'desc')
            ->first();
    }

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
