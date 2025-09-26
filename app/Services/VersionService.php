<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Version;
use App\Repositories\VersionRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class VersionService
{
    public function __construct(
        private VersionRepository $versionRepository,
        private FileService $fileService
    ) {}

    public function uploadVersion(
        Content $content,
        string $platform,
        array $versionData,
        UploadedFile $file,
        array $localizations = []
    ): Version {
        try {
            DB::beginTransaction();

            // Проверяем существование версии
            if ($this->versionRepository->findVersion(
                $content->id,
                $platform,
                $versionData['major'],
                $versionData['minor'],
                $versionData['micro']
            )) {
                throw new Exception('Версия уже существует для этой платформы');
            }

            // Сохраняем файл
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('versions', $fileName, 'public');

            // Создаем версию
            $version = Version::create([
                'content_id' => $content->id,
                'platform' => $platform,
                'major' => $versionData['major'],
                'minor' => $versionData['minor'],
                'micro' => $versionData['micro'],
                'tested' => $versionData['tested'] ?? false,
                'release_note' => $versionData['release_note'] ?? null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]);

            // Обрабатываем локализации
            $this->processVersionLocalizations($version, $localizations);

            DB::commit();
            Log::info('Version uploaded successfully', ['version_id' => $version->id]);

            return $version;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Version upload failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateVersion(Version $version, array $data): bool
    {
        return $version->update([
            'release_note' => $data['release_note'] ?? null,
            'tested' => $data['tested'] ?? false,
        ]);
    }

    public function deleteVersion(Version $version): bool
    {
        try {
            // Удаляем файл
            if (Storage::disk('private')->exists($version->file_path)) {
                Storage::disk('private')->delete($version->file_path);
            }

            // Удаляем запись
            return $version->forceDelete();

        } catch (Exception $e) {
            Log::error('Error deleting version', ['version_id' => $version->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function processVersionLocalizations(Version $version, array $localizations): void
    {
        foreach ($localizations as $localizationData) {
            if (empty($localizationData['locale']) || !isset($localizationData['file'])) {
                continue;
            }

            $localizationFile = $localizationData['file'];

            if (!$localizationFile || !$localizationFile->isValid()) {
                continue;
            }

            $localizationFileName = time() . '_' . $localizationFile->getClientOriginalName();
            $localizationFilePath = $localizationFile->storeAs('version_localizations', $localizationFileName, 'public');

            $version->localizations()->create([
                'locale' => $localizationData['locale'],
                'file_name' => $localizationFile->getClientOriginalName(),
                'file_path' => $localizationFilePath,
                'file_size' => $localizationFile->getSize(),
            ]);
        }
    }
}
