<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Version extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];
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

    public function scopeLatestVersion($query)
    {
        return $query->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('micro', 'desc');
    }

    public function scopeTested($query, bool $tested = true)
    {
        return $query->where('tested', $tested);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
