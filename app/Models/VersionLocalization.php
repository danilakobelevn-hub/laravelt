<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionLocalization extends Model
{
    use HasFactory;

    protected $table = 'version_localizations';

    protected $fillable = [
        'version_id',
        'locale',
        'file_name',
        'file_path',
        'file_size'
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
