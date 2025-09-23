<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleLocalizedString extends Model
{
    use HasFactory;

    protected $table = 'module_localized_strings';

    protected $fillable = [
        'module_id',
        'type',
        'locale',
        'value'
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
