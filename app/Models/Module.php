<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['alias', 'default_name', 'guid', 'type'];

    public function localizedStrings()
    {
        return $this->hasMany(ModuleLocalizedString::class);
    }

    public function contents()
    {
        return $this->belongsToMany(Content::class, 'content_module');
    }
    public function getName($locale = 'ru')
    {
        return $this->localizedStrings
            ->where('type', 'name')
            ->where('locale', $locale)
            ->first()->value ?? $this->default_name;
    }

    public function getDescription($locale = 'ru')
    {
        return $this->localizedStrings
            ->where('type', 'description')
            ->where('locale', $locale)
            ->first()->value ?? null;
    }
}
