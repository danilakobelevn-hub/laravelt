<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentLocalizedString extends Model
{
    use HasFactory;

    protected $table = 'content_localized_strings';

    protected $fillable = ['content_id', 'type', 'locale', 'value'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
