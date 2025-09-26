<?php

namespace App\Services;

use App\Models\Content;
use App\Models\ContentImageLink;
use App\Models\ContentVideoLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Exception;

class FileService
{
    public function storeContentImage(Content $content, UploadedFile $image): ContentImageLink
    {
        $path = $image->store('content/images', 'public');

        return $content->imageLinks()->create([
            'link' => Storage::disk('public')->url($path)
        ]);
    }

    public function storeContentVideo(Content $content, UploadedFile $video): ContentVideoLink
    {
        $path = $video->store('content/videos', 'public');

        return $content->videoLinks()->create([
            'link' => Storage::disk('public')->url($path)
        ]);
    }

    public function deleteImage(ContentImageLink $imageLink): bool
    {
        try {
            $path = parse_url($imageLink->link, PHP_URL_PATH);
            $filePath = str_replace('/storage/', '', $path);

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return $imageLink->delete();

        } catch (Exception $e) {
            Log::error('Error deleting image', ['image_id' => $imageLink->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
