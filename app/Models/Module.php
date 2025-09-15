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
}
