<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentDataResource;
use App\Http\Resources\VersionDataResource;
use App\Models\Content;
use App\Models\Version;
use App\Models\VersionLocalization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Получение всех контентов
     * GET /api/v1/contents/all
     */
    public function all()
    {
        try {
            $contents = Content::with([
                'localizedStrings',
                'imageLinks',
                'videoLinks',
                'availableLocales',
                'subsection.section',
                'modules.localizedStrings',
                'versions' => function($query) {
                    $query->orderBy('major', 'desc')
                        ->orderBy('minor', 'desc')
                        ->orderBy('micro', 'desc');
                },
                'versions.localizations'
            ])->get();

            return ContentDataResource::collection($contents);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch contents',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение контента по псевдониму
     * GET /api/v1/contents/by_alias?alias=test
     */
    public function byAlias(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $content = Content::with([
                'localizedStrings',
                'imageLinks',
                'videoLinks',
                'availableLocales',
                'subsection.section',
                'modules.localizedStrings',
                'versions' => function($query) {
                    $query->orderBy('major', 'desc')
                        ->orderBy('minor', 'desc')
                        ->orderBy('micro', 'desc');
                },
                'versions.localizations'
            ])->where('alias', $request->alias)->first();

            if (!$content) {
                return response()->json(['error' => 'Content not found'], 404);
            }

            return new ContentDataResource($content);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch content',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузка новой версии
     * POST /api/v1/contents/uploadVersion
     */
    public function uploadVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
            'major' => 'required|string|in:true,false,1,0', // Разрешаем строки и числа
            'minor' => 'required|string|in:true,false,1,0',
            'micro' => 'required|string|in:true,false,1,0',
            'releaseNote' => 'nullable|string|max:500',
            'file' => 'required|file|mimes:zip|max:102400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            \DB::beginTransaction();

            $content = Content::where('alias', $request->alias)->first();

            if (!$content) {
                return response()->json([
                    'error' => 'Content not found',
                    'message' => 'Content with alias "' . $request->alias . '" not found'
                ], 404);
            }

            $major = filter_var($request->major, FILTER_VALIDATE_BOOLEAN);
            $minor = filter_var($request->minor, FILTER_VALIDATE_BOOLEAN);
            $micro = filter_var($request->micro, FILTER_VALIDATE_BOOLEAN);

            \Log::info('Upload version request', [
                'alias' => $request->alias,
                'platform' => $request->platform,
                'major' => $major,
                'minor' => $minor,
                'micro' => $micro,
                'releaseNote' => $request->releaseNote
            ]);

            // Получаем последнюю версию для этой платформы
            $latestVersion = $content->versions()
                ->where('platform', $request->platform)
                ->orderBy('major', 'desc')
                ->orderBy('minor', 'desc')
                ->orderBy('micro', 'desc')
                ->first();

            \Log::info('Latest version found', [
                'latest_version' => $latestVersion ? "{$latestVersion->major}.{$latestVersion->minor}.{$latestVersion->micro}" : 'none'
            ]);

            // Если нет существующих версий, начинаем с 1.0.0
            if (!$latestVersion) {
                $majorVersion = 1;
                $minorVersion = 0;
                $microVersion = 0;

                \Log::info('No existing versions, starting with 1.0.0');
            } else {
                // Берем версию из последней существующей версии
                $majorVersion = $latestVersion->major;
                $minorVersion = $latestVersion->minor;
                $microVersion = $latestVersion->micro;

                \Log::info('Current version', [
                    'current' => "{$majorVersion}.{$minorVersion}.{$microVersion}"
                ]);

                // Применяем повышение версии согласно флагам
                if ($major) {
                    $majorVersion++;
                    $minorVersion = 0;
                    $microVersion = 0;
                    \Log::info('Major increment applied');
                } elseif ($minor) {
                    $minorVersion++;
                    $microVersion = 0;
                    \Log::info('Minor increment applied');
                } elseif ($micro) {
                    $microVersion++;
                    \Log::info('Micro increment applied');
                } else {
                    // Если ни один флаг не установлен, увеличиваем micro по умолчанию
                    $microVersion++;
                    \Log::info('Default micro increment applied');
                }
            }

            // Проверяем, существует ли уже такая версия
            $existingVersion = Version::where('content_id', $content->id)
                ->where('platform', $request->platform)
                ->where('major', $majorVersion)
                ->where('minor', $minorVersion)
                ->where('micro', $microVersion)
                ->first();

            if ($existingVersion) {
                \Log::warning('Version already exists', [
                    'version' => "{$majorVersion}.{$minorVersion}.{$microVersion}"
                ]);

                return response()->json([
                    'error' => 'Version already exists',
                    'message' => "Version {$majorVersion}.{$minorVersion}.{$microVersion} already exists for this platform"
                ], 409);
            }

            // Валидация файла
            if (!$request->hasFile('file')) {
                return response()->json([
                    'error' => 'File required',
                    'message' => 'ZIP file is required'
                ], 422);
            }

            $file = $request->file('file');
            if (!$file->isValid()) {
                return response()->json([
                    'error' => 'Invalid file',
                    'message' => 'Uploaded file is not valid'
                ], 422);
            }

            // Сохраняем файл
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('versions', $fileName, 'public');

            \Log::info('File uploaded', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $filePath,
                'size' => $file->getSize()
            ]);

            // Создаем версию
            $version = Version::create([
                'content_id' => $content->id,
                'platform' => $request->platform,
                'major' => $majorVersion,
                'minor' => $minorVersion,
                'micro' => $microVersion,
                'tested' => false, // По умолчанию не протестирована
                'release_note' => $request->releaseNote ?: null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]);

            \Log::info('Version created successfully', [
                'version_id' => $version->id,
                'version' => "{$version->major}.{$version->minor}.{$version->micro}",
                'platform' => $version->platform
            ]);

            \DB::commit();

            return new VersionDataResource($version->load('localizations'));

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('Version upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to upload version',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузка локализации
     * POST /api/v1/contents/uploadLocalizationVersion
     */
    public function uploadLocalizationVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
            'lang' => 'required|string|size:2',
            'versionData' => 'required|string',
            'file' => 'required|file|mimes:zip|max:51200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            \DB::beginTransaction();

            $content = Content::where('alias', $request->alias)->firstOrFail();

            // Парсим версию
            $versionParts = explode('.', $request->versionData);
            if (count($versionParts) !== 3) {
                return response()->json([
                    'error' => 'Invalid version format',
                    'message' => 'Version must be in format major.minor.micro'
                ], 422);
            }

            [$major, $minor, $micro] = array_map('intval', $versionParts);

            $version = $content->versions()
                ->where('platform', $request->platform)
                ->where('major', $major)
                ->where('minor', $minor)
                ->where('micro', $micro)
                ->first();

            if (!$version) {
                return response()->json([
                    'error' => 'Version not found',
                    'message' => "Version {$major}.{$minor}.{$micro} not found for platform {$request->platform}"
                ], 404);
            }

            // Проверяем, существует ли уже локализация для этого языка
            $existingLocalization = $version->localizations()
                ->where('locale', $request->lang)
                ->first();

            if ($existingLocalization) {
                return response()->json([
                    'error' => 'Localization already exists',
                    'message' => "Localization for language {$request->lang} already exists for this version"
                ], 409);
            }

            // Сохраняем файл
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('version_localizations', $fileName, 'public');

            $localization = $version->localizations()->create([
                'locale' => $request->lang,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]);

            \DB::commit();

            return new VersionDataResource($version->load('localizations'));

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'error' => 'Failed to upload localization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Скачивание последней версии контента
     * GET /api/v1/contents/download?alias=test&platform=android
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $content = Content::where('alias', $request->alias)->first();

            if (!$content) {
                return response()->json(['error' => 'Content not found'], 404);
            }

            // Ищем последнюю протестированную версию
            $version = $content->versions()
                ->where('platform', $request->platform)
                ->where('tested', true)
                ->orderBy('major', 'desc')
                ->orderBy('minor', 'desc')
                ->orderBy('micro', 'desc')
                ->first();

            // Если нет протестированной версии, ищем любую последнюю
            if (!$version) {
                $version = $content->versions()
                    ->where('platform', $request->platform)
                    ->orderBy('major', 'desc')
                    ->orderBy('minor', 'desc')
                    ->orderBy('micro', 'desc')
                    ->first();
            }

            if (!$version) {
                return response()->json([
                    'error' => 'Version not found',
                    'message' => 'No versions found for this platform'
                ], 404);
            }

            if (!Storage::disk('public')->exists($version->file_path)) {
                return response()->json([
                    'error' => 'File not found',
                    'message' => 'Version file not found on server'
                ], 404);
            }
            
            \Log::info("API download version", [
                'content_alias' => $request->alias,
                'platform' => $request->platform,
                'version' => "{$version->major}.{$version->minor}.{$version->micro}",
                'file_name' => $version->file_name
            ]);

            return Storage::disk('public')->download(
                $version->file_path,
                $version->file_name,
                ['Content-Type' => 'application/zip']
            );

        } catch (\Exception $e) {
            \Log::error("API download error: " . $e->getMessage());
            return response()->json([
                'error' => 'Download failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Скачивание последней версии локализации контента
     * GET /api/v1/contents/downloadLocalization?alias=test&platform=android&lang=en
     */
    public function downloadLocalization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
            'lang' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $content = Content::where('alias', $request->alias)->first();

            if (!$content) {
                return response()->json(['error' => 'Content not found'], 404);
            }

            // Ищем последнюю протестированную версию
            $version = $content->versions()
                ->where('platform', $request->platform)
                ->where('tested', true)
                ->orderBy('major', 'desc')
                ->orderBy('minor', 'desc')
                ->orderBy('micro', 'desc')
                ->first();

            // Если нет протестированной версии, ищем любую последнюю
            if (!$version) {
                $version = $content->versions()
                    ->where('platform', $request->platform)
                    ->orderBy('major', 'desc')
                    ->orderBy('minor', 'desc')
                    ->orderBy('micro', 'desc')
                    ->first();
            }

            if (!$version) {
                return response()->json([
                    'error' => 'Version not found',
                    'message' => 'No versions found for this platform'
                ], 404);
            }

            // Ищем локализацию для указанного языка
            $localization = $version->localizations()
                ->where('locale', $request->lang)
                ->first();

            if (!$localization) {
                return response()->json([
                    'error' => 'Localization not found',
                    'message' => "Localization for language {$request->lang} not found"
                ], 404);
            }

            if (!Storage::disk('public')->exists($localization->file_path)) {
                return response()->json([
                    'error' => 'File not found',
                    'message' => 'Localization file not found on server'
                ], 404);
            }

            // Логируем скачивание
            \Log::info("API download localization", [
                'content_alias' => $request->alias,
                'platform' => $request->platform,
                'lang' => $request->lang,
                'version' => "{$version->major}.{$version->minor}.{$version->micro}",
                'file_name' => $localization->file_name
            ]);

            return Storage::disk('public')->download(
                $localization->file_path,
                $localization->file_name,
                ['Content-Type' => 'application/zip']
            );

        } catch (\Exception $e) {
            \Log::error("API download localization error: " . $e->getMessage());
            return response()->json([
                'error' => 'Download failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение непротестированных контентов
     * GET /api/v1/contents/qa
     */
    public function qaIndex()
    {
        try {
            $contents = Content::with([
                'localizedStrings',
                'imageLinks',
                'videoLinks',
                'availableLocales',
                'subsection.section',
                'modules.localizedStrings',
                'versions' => function($query) {
                    $query->where('tested', false)
                        ->orderBy('major', 'desc')
                        ->orderBy('minor', 'desc')
                        ->orderBy('micro', 'desc');
                },
                'versions.localizations'
            ])->get();

            return ContentDataResource::collection($contents);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch QA contents',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Пометить версию как протестированную
     * PATCH /api/v1/contents/versions/{version}/test
     */
    public function markAsTested(Version $version)
    {
        try {
            $version->update(['tested' => true]);

            \Log::info("Version marked as tested", [
                'version_id' => $version->id,
                'content_id' => $version->content_id,
                'platform' => $version->platform,
                'version' => "{$version->major}.{$version->minor}.{$version->micro}"
            ]);

            return new VersionDataResource($version->load('localizations'));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to mark version as tested',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить непротестированные версии
     * GET /api/v1/contents/untested
     */
    public function untestedVersions()
    {
        try {
            $versions = Version::with(['content', 'localizations'])
                ->where('tested', false)
                ->orderBy('major', 'desc')
                ->orderBy('minor', 'desc')
                ->orderBy('micro', 'desc')
                ->get();

            return VersionDataResource::collection($versions);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch untested versions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
