<?php

namespace App\Services;

use App\Models\Content;
use App\Repositories\ContentRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ContentService
{
    public function __construct(
        private ContentRepository $contentRepository,
        private FileService $fileService
    ) {}

    public function createContent(array $data, array $localizations, ?array $images = null, ?array $videos = null): Content
    {
        try {
            DB::beginTransaction();

            $content = $this->contentRepository->create($data);
            $this->processLocalizations($content, $localizations);
            $this->processMediaFiles($content, $images, $videos);

            DB::commit();
            Log::info('Content created successfully', ['content_id' => $content->id]);

            return $content;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Content creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateContent(Content $content, array $data, array $localizations): bool
    {
        try {
            DB::beginTransaction();

            $this->contentRepository->update($content, $data);
            $this->updateLocalizations($content, $localizations);

            DB::commit();
            Log::info('Content updated successfully', ['content_id' => $content->id]);

            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Content update failed', ['content_id' => $content->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function processLocalizations(Content $content, array $localizations): void
    {
        $content->localizedStrings()->delete();
        $content->availableLocales()->delete();

        $usedLocales = [];

        foreach ($localizations as $localization) {
            if (empty($localization['locale']) || empty(trim($localization['name']))) {
                continue;
            }

            // Сохраняем название
            $content->localizedStrings()->create([
                'type' => 'name',
                'locale' => $localization['locale'],
                'value' => trim($localization['name']),
            ]);

            // Сохраняем описание если есть
            if (!empty(trim($localization['description'] ?? ''))) {
                $content->localizedStrings()->create([
                    'type' => 'description',
                    'locale' => $localization['locale'],
                    'value' => trim($localization['description']),
                ]);
            }

            $usedLocales[] = $localization['locale'];
        }

        // Сохраняем доступные языки
        foreach (array_unique($usedLocales) as $locale) {
            $content->availableLocales()->create(['locale' => $locale]);
        }
    }

    private function updateLocalizations(Content $content, array $localizations): void
    {
        $this->processLocalizations($content, $localizations);
    }

    private function processMediaFiles(Content $content, ?array $images, ?array $videos): void
    {
        if ($images) {
            foreach ($images as $image) {
                $this->fileService->storeContentImage($content, $image);
            }
        }

        if ($videos) {
            foreach ($videos as $video) {
                $this->fileService->storeContentVideo($content, $video);
            }
        }
    }
}
