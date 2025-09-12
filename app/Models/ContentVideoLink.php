<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentVideoLink extends Model
{
    use HasFactory;

    protected $table = 'content_video_links';

    protected $fillable = ['content_id', 'link'];

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }
}
