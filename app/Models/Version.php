<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Version extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'platform',
        'major',
        'minor',
        'micro',
        'tested',
        'release_note',
        'file_name',
        'file_path',
        'file_size'
    ];

    protected $casts = [
        'tested' => 'boolean'
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function localizations(): HasMany
    {
        return $this->hasMany(VersionLocalization::class);
    }
}
