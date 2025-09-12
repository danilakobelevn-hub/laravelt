<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentAvailableLocale extends Model
{
    use HasFactory;

    protected $table = 'content_available_locales';

    protected $fillable = ['content_id', 'locale'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
