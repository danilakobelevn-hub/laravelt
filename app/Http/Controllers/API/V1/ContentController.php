<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentDataResource;
use App\Http\Resources\VersionDataResource;
use App\Models\Content;
use App\Models\Version;
use App\Models\VersionLocalization;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateContentRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    /**
     * Получение всех контентов
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
                'versions'
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
     */
    public function byAlias(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web'
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
                'versions' => function($query) use ($request) {
                    $query->where('platform', $request->platform)
                        ->orderBy('major', 'desc')
                        ->orderBy('minor', 'desc')
                        ->orderBy('micro', 'desc');
                }
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
     */
    public function uploadVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alias' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
            'major' => 'required|boolean',
            'minor' => 'required|boolean',
            'micro' => 'required|boolean',
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

            $content = Content::where('alias', $request->alias)->firstOrFail();

            // Получаем последнюю версию
            $latestVersion = $content->versions()
                ->where('platform', $request->platform)
                ->orderBy('major', 'desc')
                ->orderBy('minor', 'desc')
                ->orderBy('micro', 'desc')
                ->first();

            // Вычисляем новые номера версии
            $major = $latestVersion ? $latestVersion->major : 1;
            $minor = $latestVersion ? $latestVersion->minor : 0;
            $micro = $latestVersion ? $latestVersion->micro : 0;

            if ($request->boolean('major')) {
                $major++;
                $minor = 0;
                $micro = 0;
            } elseif ($request->boolean('minor')) {
                $minor++;
                $micro = 0;
            } elseif ($request->boolean('micro')) {
                $micro++;
            }

            // Сохраняем файл
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('versions', $fileName, 'public');

            // Создаем версию
            $version = Version::create([
                'content_id' => $content->id,
                'platform' => $request->platform,
                'major' => $major,
                'minor' => $minor,
                'micro' => $micro,
                'tested' => false,
                'release_note' => $request->releaseNote,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]);

            \DB::commit();

            return new VersionDataResource($version);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'error' => 'Failed to upload version',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Загрузка локализации
     */
    public function uploadLocalization(Request $request)
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
            [$major, $minor, $micro] = array_map('intval', explode('.', $request->versionData));

            $version = $content->versions()
                ->where('platform', $request->platform)
                ->where('major', $major)
                ->where('minor', $minor)
                ->where('micro', $micro)
                ->firstOrFail();

            // Сохраняем файл
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('localizations', $fileName, 'public');

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
     * Скачивание контента
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $version = Version::where('file_name', $request->fileName)
                ->where('platform', $request->platform)
                ->first();

            if (!$version || !Storage::disk('public')->exists($version->file_path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return Storage::disk('public')->download($version->file_path);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Download failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Скачивание локализации
     */
    public function downloadLocalization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fileName' => 'required|string',
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $localization = VersionLocalization::whereHas('version', function($query) use ($request) {
                $query->where('platform', $request->platform);
            })->where('file_name', $request->fileName)->first();

            if (!$localization || !Storage::disk('public')->exists($localization->file_path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return Storage::disk('public')->download($localization->file_path);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Download failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение непротестированных контентов
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
                    $query->where('tested', false);
                }
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
     */
    public function markAsTested(Version $version)
    {
        try {
            $version->update(['tested' => true]);
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
     */
    public function untestedVersions()
    {
        try {
            $versions = Version::with(['content', 'localizations'])
                ->where('tested', false)
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
