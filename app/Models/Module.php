<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'alias',
        'default_name',
        'guid',
        'type'
    ];

    protected $casts = [
        'type' => 'integer'
    ];

    // Типы модулей
    const TYPE_DEMONSTRATION = 0;
    const TYPE_ATLAS = 1;
    const TYPE_QUIZ = 2;

    public static function getTypes()
    {
        return [
            self::TYPE_DEMONSTRATION => 'Демонстрация',
            self::TYPE_ATLAS => 'Атлас',
            self::TYPE_QUIZ => 'Квиз'
        ];
    }

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

    public function getTypeName()
    {
        return self::getTypes()[$this->type] ?? 'Неизвестно';
    }
}
