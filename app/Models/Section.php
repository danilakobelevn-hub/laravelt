<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['alias', 'default_name', 'default_description'];

    public function subsections(): HasMany
    {
        return $this->hasMany(Subsection::class);
    }
}
