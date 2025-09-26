<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentImageLink;
use App\Models\Section;
use App\Models\Version;
use App\Models\VersionLocalization;
use App\Repositories\ContentRepository;
use App\Repositories\TreeRepository;
use App\Repositories\VersionRepository;
use App\Services\ContentService;
use App\Services\FileService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    public function __construct(
        private ContentRepository $contentRepository,
        private VersionRepository $versionRepository,
        private TreeRepository $treeRepository,
        private ContentService $contentService,
        private VersionService $versionService,
        private FileService $fileService
    ) {}

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $relations = ['subsection.section', 'versions', 'imageLinks', 'modules'];

        // Создаем базовый запрос
        $query = $search
            ? $this->contentRepository->search($search, $relations)
            : $this->contentRepository->getBaseQueryWithRelations($relations);

        // Применяем сортировку к Builder, а не к Collection
        $query = $this->applySorting($query, $request);

        // Пагинируем результат
        $contents = $query->paginate(20);

        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'desc');

        return view('admin.contents.index', compact('contents', 'sortColumn', 'sortDirection'));
    }

    public function create()
    {
        $sections = Section::with('subsections')->get();
        $locales = config('app.available_locales', ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es']);

        return view('admin.contents.create', compact('sections', 'locales'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateContentRequest($request);

        if (!$this->hasValidLocalization($request)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Необходимо заполнить хотя бы одну локализацию (язык и название)');
        }

        try {
            $contentData = [
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
                'guid' => Str::uuid(),
            ];

            $localizations = $this->prepareLocalizations($request);

            $content = $this->contentService->createContent(
                $contentData,
                $localizations,
                $request->file('images'),
                $request->file('videos')
            );

            return redirect()->route('admin.contents.show', $content->id)
                ->with('success', 'Контент успешно создан!');

        } catch (\Exception $e) {
            Log::error('Content creation failed', ['error' => $e->getMessage()]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Ошибка при создании контента: ' . $e->getMessage());
        }
    }

    public function show(Content $content)
    {
        $content->load([
            'localizedStrings',
            'imageLinks',
            'videoLinks',
            'availableLocales',
            'subsection.section',
            'modules.localizedStrings',
            'versions' => function($query) {
                $query->latestVersion();
            }
        ]);

        $allModules = \App\Models\Module::all();

        return view('admin.contents.show', compact('content', 'allModules'));
    }

    public function edit(Content $content)
    {
        $content->load([
            'localizedStrings',
            'imageLinks',
            'videoLinks',
            'availableLocales',
            'modules',
            'subsection.section'
        ]);

        $sections = Section::with('subsections')->get();

        return view('admin.contents.edit', compact('content', 'sections'));
    }

    public function update(Request $request, Content $content)
    {
        $validated = $this->validateContentRequest($request, $content->id);

        if (!$this->hasValidLocalization($request)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Необходимо заполнить хотя бы одну локализацию (язык и название)');
        }

        try {
            $contentData = [
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
            ];

            $localizations = $this->prepareLocalizations($request);

            $this->contentService->updateContent($content, $contentData, $localizations);

            return redirect()->route('admin.contents.show', $content)
                ->with('success', 'Контент успешно обновлен!');

        } catch (\Exception $e) {
            Log::error('Content update failed', ['content_id' => $content->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при обновлении: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Content $content)
    {
        $this->contentRepository->delete($content);

        return redirect()->route('admin.contents.index')
            ->with('success', 'Контент помечен на удаление!');
    }

    public function forceDestroy($id)
    {
        try {
            $content = Content::withTrashed()->findOrFail($id);

            // Удаляем файлы версий
            foreach ($content->versions as $version) {
                $this->versionService->deleteVersion($version);
            }

            // Удаляем все связанные данные
            $content->localizedStrings()->forceDelete();
            $content->imageLinks()->forceDelete();
            $content->videoLinks()->forceDelete();
            $content->availableLocales()->forceDelete();
            $content->modules()->detach();

            // Полностью удаляем контент
            $this->contentRepository->forceDelete($content);

            return redirect()->route('admin.contents.index')
                ->with('success', 'Контент полностью удален!');

        } catch (\Exception $e) {
            Log::error("Error force deleting content", ['content_id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при удалении: ' . $e->getMessage());
        }
    }

    public function trashed()
    {
        $contents = $this->contentRepository->getTrashed();
        return view('admin.contents.trashed', compact('contents'));
    }

    public function restore($id)
    {
        $result = $this->contentRepository->restore($id);

        if ($result) {
            return redirect()->route('admin.contents.show', $id)
                ->with('success', 'Контент восстановлен!');
        }

        return back()->with('error', 'Ошибка при восстановлении контента');
    }

    public function getSubsections($sectionId): JsonResponse
    {
        try {
            $subsections = $this->treeRepository->getSubsectionsBySection($sectionId);

            if ($subsections->isEmpty()) {
                return response()->json([], 200);
            }

            $result = $subsections->pluck('default_name', 'id');
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error("Error getting subsections", ['section_id' => $sectionId, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function getSectionsTree(): JsonResponse
    {
        try {
            $sections = $this->treeRepository->getSectionsTree();
            $tree = $this->treeRepository->formatSectionsForJsTree($sections);

            return response()->json($tree);
        } catch (\Exception $e) {
            Log::error('Error in getSectionsTree', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    public function downloadVersion(Version $version)
    {
        try {
            if (!Storage::disk('public')->exists($version->file_path)) {
                if (Storage::disk('private')->exists($version->file_path)) {
                    return Storage::disk('private')->download($version->file_path, $version->file_name);
                }
                return back()->with('error', 'Файл не найден на сервере');
            }

            return Storage::disk('public')->download($version->file_path, $version->file_name, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $version->file_name . '"'
            ]);

        } catch (\Exception $e) {
            Log::error("Error downloading version", ['version_id' => $version->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при скачивании файла: ' . $e->getMessage());
        }
    }

    public function uploadVersion(Request $request, Content $content)
    {
        $validated = $this->validateVersionRequest($request);

        try {
            $versionData = $this->prepareVersionData($request, $validated);
            $localizations = $this->prepareVersionLocalizations($request);

            $version = $this->versionService->uploadVersion(
                $content,
                $validated['platform'],
                $versionData,
                $request->file('file'),
                $localizations
            );

            return redirect()->route('admin.contents.platform-versions', [
                'content' => $content->id,
                'platform' => $validated['platform']
            ])->with('success', 'Версия успешно загружена!');

        } catch (\Exception $e) {
            Log::error('Version upload failed', ['content_id' => $content->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при загрузке версии: ' . $e->getMessage());
        }
    }

    public function downloadLocalization(VersionLocalization $localization)
    {
        try {
            if (!Storage::disk('public')->exists($localization->file_path)) {
                return back()->with('error', 'Файл локализации не найден на сервере');
            }

            return Storage::disk('public')->download($localization->file_path, $localization->file_name, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $localization->file_name . '"'
            ]);

        } catch (\Exception $e) {
            Log::error("Error downloading localization", ['localization_id' => $localization->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при скачивании файла локализации: ' . $e->getMessage());
        }
    }

    public function destroyVersion(Version $version)
    {
        try {
            $this->versionService->deleteVersion($version);
            return back()->with('success', 'Версия полностью удалена!');

        } catch (\Exception $e) {
            Log::error("Error deleting version", ['version_id' => $version->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ошибка при удалении версии');
        }
    }

    public function editVersion(Version $version)
    {
        $version->load('content');
        return view('admin.versions.edit', compact('version'));
    }

    public function updateVersion(Request $request, Version $version)
    {
        $validated = $request->validate([
            'release_note' => 'nullable|string|max:500',
            'tested' => 'boolean',
        ]);

        try {
            $this->versionService->updateVersion($version, $validated);

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Версия успешно обновлена!']);
            }

            return redirect()->route('admin.contents.platform-versions', [
                'content' => $version->content_id,
                'platform' => $version->platform
            ])->with('success', 'Версия успешно обновлена!');

        } catch (\Exception $e) {
            Log::error('Error updating version', ['version_id' => $version->id, 'error' => $e->getMessage()]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ошибка обновления: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Ошибка обновления: ' . $e->getMessage());
        }
    }

    public function attachModules(Request $request, Content $content)
    {
        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*' => 'exists:modules,id'
        ]);

        $content->modules()->syncWithoutDetaching($validated['modules']);

        return back()->with('success', 'Модули успешно добавлены!');
    }

    public function detachModule(Content $content, \App\Models\Module $module)
    {
        $content->modules()->detach($module->id);

        return back()->with('success', 'Модуль успешно удален из контента!');
    }

    public function platformVersions(Request $request, Content $content)
    {
        $platform = $request->get('platform', 'windows');
        $allowedPlatforms = ['windows', 'macos', 'linux', 'android', 'ios', 'web'];

        if (!in_array($platform, $allowedPlatforms)) {
            abort(404, 'Платформа не найдена');
        }

        $sort = [
            'column' => $request->get('sort', 'id'),
            'direction' => $request->get('direction', 'desc')
        ];

        $versions = $this->versionRepository->getPlatformVersions($content->id, $platform, $sort);

        return view('admin.contents.platform-versions', compact('content', 'platform', 'versions'));
    }

    public function deleteImage(ContentImageLink $imageLink): JsonResponse
    {
        try {
            $this->fileService->deleteImage($imageLink);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error deleting image', ['image_id' => $imageLink->id, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Вспомогательные методы
    private function validateContentRequest(Request $request, ?int $contentId = null): array
    {
        $rules = [
            'alias' => 'required|unique:contents,alias' . ($contentId ? ",$contentId" : '') . '|max:255',
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|mimetypes:video/mp4,video/quicktime|max:10240',
        ];

        return $request->validate($rules);
    }

    private function validateVersionRequest(Request $request): array
    {
        return $request->validate([
            'platform' => 'required|string|in:windows,macos,linux,android,ios,web',
            'major' => 'required|integer|min:0',
            'minor' => 'required|integer|min:0',
            'micro' => 'required|integer|min:0',
            'tested' => 'boolean',
            'release_note' => 'nullable|string|max:500',
            'file' => 'required|file|mimes:zip|max:102400',
            'localizations' => 'nullable|array',
            'localizations.*.locale' => 'sometimes|required|string|size:2',
            'localizations.*.file' => 'sometimes|nullable|file|mimes:zip|max:51200'
        ]);
    }

    private function hasValidLocalization(Request $request): bool
    {
        if (!$request->has('locales')) {
            return false;
        }

        foreach ($request->locales as $index => $locale) {
            if (!empty($locale) && !empty($request->names[$index])) {
                return true;
            }
        }

        return false;
    }

    private function prepareLocalizations(Request $request): array
    {
        $localizations = [];

        if ($request->has('locales')) {
            foreach ($request->locales as $index => $locale) {
                if (empty($locale) || empty(trim($request->names[$index]))) {
                    continue;
                }

                $localizations[] = [
                    'locale' => $locale,
                    'name' => trim($request->names[$index]),
                    'description' => trim($request->descriptions[$index] ?? ''),
                ];
            }
        }

        return $localizations;
    }

    private function prepareVersionData(Request $request, array $validated): array
    {
        $versionData = [
            'major' => $validated['major'],
            'minor' => $validated['minor'],
            'micro' => $validated['micro'],
            'tested' => $validated['tested'] ?? false,
            'release_note' => $validated['release_note'] ?? null,
        ];

        // Альтернативная валидация через version_number
        if ($request->has('version_number')) {
            $versionParts = explode('.', $request->version_number);
            if (count($versionParts) === 3) {
                $versionData['major'] = (int)$versionParts[0];
                $versionData['minor'] = (int)$versionParts[1];
                $versionData['micro'] = (int)$versionParts[2];
            }
        }

        return $versionData;
    }

    private function prepareVersionLocalizations(Request $request): array
    {
        $localizations = [];

        if ($request->has('localizations')) {
            foreach ($request->localizations as $localizationData) {
                if (empty($localizationData['locale']) || !isset($localizationData['file'])) {
                    continue;
                }
                $localizations[] = $localizationData;
            }
        }

        return $localizations;
    }

    private function applySorting($query, Request $request)
    {
        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSortColumns = ['id', 'default_name', 'alias', 'subsection_id'];

        if (in_array($sortColumn, $allowedSortColumns)) {
            return $query->orderBy($sortColumn, $sortDirection);
        }

        return $query->orderBy('id', 'desc');
    }
}
