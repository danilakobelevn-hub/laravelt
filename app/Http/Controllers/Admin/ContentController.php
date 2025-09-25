<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Models\Content;
use App\Models\Section;
use App\Models\Subsection;
use App\Models\Module;
use App\Models\Version;
use App\Models\ContentLocalizedString;
use App\Models\ContentImageLink;
use App\Models\ContentVideoLink;
use App\Models\ContentAvailableLocale;
use App\Models\ContentVersionFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    // Список всех контентов
    public function index(Request $request)
    {
        $query = Content::with(['subsection.section', 'versions', 'imageLinks', 'modules'])
            ->withCount('versions');

        // Поиск
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('default_name', 'like', "%{$search}%")
                    ->orWhere('alias', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortColumns = ['id', 'default_name', 'alias', 'subsection_id'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }

        $contents = $query->paginate(20);

        return view('admin.contents.index', compact('contents', 'sortColumn', 'sortDirection'));
    }

    // Форма создания контента
    public function create()
    {
        $sections = Section::with('subsections')->get();
        $modules = Module::all();
        $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];

        return view('admin.contents.create', compact('sections', 'modules', 'locales'));
    }

    public function getSubsections($sectionId)
    {
        \Log::info("=== SUBSECTIONS REQUEST ===");
        \Log::info("Section ID: " . $sectionId);

        try {
            // Проверяем существование раздела
            $section = Section::find($sectionId);
            if (!$section) {
                \Log::warning("Section not found: " . $sectionId);
                return response()->json(['error' => 'Section not found'], 404);
            }

            \Log::info("Section found: " . $section->default_name);

            // Получаем подразделы
            $subsections = Subsection::where('section_id', $sectionId)
                ->get(['id', 'default_name']);

            \Log::info("Subsections count: " . $subsections->count());

            if ($subsections->isEmpty()) {
                \Log::warning("No subsections for section: " . $sectionId);
                return response()->json([], 200);
            }

            $result = $subsections->pluck('default_name', 'id');
            \Log::info("Result: " . json_encode($result));

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error("EXCEPTION: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function getSectionsTree(): JsonResponse
    {
        try {
            $sections = Section::with('subsections')
                ->where('is_active', true)
                ->orderBy('order')
                ->get();

            $tree = $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'text' => $section->default_name,
                    'children' => $section->subsections->map(function ($subsection) {
                        return [
                            'id' => 'sub_' . $subsection->id,
                            'text' => $subsection->default_name
                        ];
                    })->toArray()
                ];
            });

            return response()->json($tree);
        } catch (\Exception $e) {
            \Log::error('Error in getSectionsTree: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    // Скачивание версии
    public function downloadVersion(Version $version)
    {
        try {
            \Log::info("Attempting to download version: {$version->id}, file: {$version->file_path}");

            // Проверяем существование файла в public storage
            if (!Storage::disk('public')->exists($version->file_path)) {
                \Log::error("File not found in public storage: {$version->file_path}");

                // Проверяем в private storage (если файл был загружен туда ранее)
                if (Storage::disk('private')->exists($version->file_path)) {
                    \Log::info("File found in private storage, downloading...");
                    return Storage::disk('private')->download($version->file_path, $version->file_name);
                }

                return back()->with('error', 'Файл не найден на сервере');
            }

            \Log::info("Downloading version from public storage: {$version->file_name}");

            // Скачиваем файл из public storage
            return Storage::disk('public')->download($version->file_path, $version->file_name, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $version->file_name . '"'
            ]);

        } catch (\Exception $e) {
            \Log::error("Error downloading version {$version->id}: " . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return back()->with('error', 'Ошибка при скачивании файла: ' . $e->getMessage());
        }
    }

    // Сохранение нового контента
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alias' => 'required|unique:contents,alias|max:255',
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        // Дополнительная проверка: должна быть хотя бы одна локализация
        $hasValidLocalization = false;
        if ($request->has('locales')) {
            foreach ($request->locales as $index => $locale) {
                if (!empty($locale) && !empty($request->names[$index])) {
                    $hasValidLocalization = true;
                    break;
                }
            }
        }

        if (!$hasValidLocalization) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Необходимо заполнить хотя бы одну локализацию (язык и название)');
        }

        \Log::info('Create content request received', $request->all());

        try {
            DB::beginTransaction();

            // Создаем контент
            $content = Content::create([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
                'guid' => Str::uuid(),
            ]);

            // Сохраняем локализации (только непустые)
            if ($request->has('locales')) {
                foreach ($request->locales as $index => $locale) {
                    // Пропускаем пустые строки
                    if (empty($locale) || empty(trim($request->names[$index]))) {
                        continue;
                    }

                    // Сохраняем название
                    ContentLocalizedString::create([
                        'content_id' => $content->id,
                        'type' => 'name',
                        'locale' => $locale,
                        'value' => trim($request->names[$index]),
                    ]);

                    // Сохраняем описание если есть
                    if (!empty(trim($request->descriptions[$index]))) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => trim($request->descriptions[$index]),
                        ]);
                    }
                }
            }

            // Автоматически определяем доступные языки из заполненных локализаций
            $usedLocales = $content->localizedStrings()->pluck('locale')->unique()->toArray();
            foreach ($usedLocales as $locale) {
                ContentAvailableLocale::create([
                    'content_id' => $content->id,
                    'locale' => $locale,
                ]);
            }

            // Обработка файлов
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('content/images', 'public');
                        $content->imageLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                    }
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    if ($video && $video->isValid()) {
                        $path = $video->store('content/videos', 'public');
                        $content->videoLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.contents.show', $content->id)
                ->with('success', 'Контент успешно создан!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Content creation failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ошибка при создании контента: ' . $e->getMessage());
        }
    }

    // Просмотр контента
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
                $query->orderBy('major', 'desc')
                    ->orderBy('minor', 'desc')
                    ->orderBy('micro', 'desc');
            }
        ]);

        $allModules = Module::all(); // Для модального окна

        return view('admin.contents.show', compact('content', 'allModules'));
    }

    public function deleteImage(ContentImageLink $imageLink)
    {
        try {
            // Удаляем файл с диска
            $path = parse_url($imageLink->link, PHP_URL_PATH);
            $filePath = str_replace('/storage/', '', $path);

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            // Удаляем запись из БД
            $imageLink->delete();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Форма редактирования
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

    // Форма редактирования версии
    public function editVersion(Version $version)
    {
        $version->load('content');
        return view('admin.versions.edit', compact('version'));
    }

    // Обновление версии
    public function updateVersion(Request $request, Version $version)
    {
        $validated = $request->validate([
            'release_note' => 'nullable|string|max:500',
            'tested' => 'boolean',
        ]);

        try {
            $version->update([
                'release_note' => $validated['release_note'],
                'tested' => $validated['tested'] ?? false,
            ]);

            \Log::info("Version {$version->id} updated");

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Версия успешно обновлена!']);
            }

            return redirect()->route('admin.contents.platform-versions', [
                'content' => $version->content_id,
                'platform' => $version->platform
            ])->with('success', 'Версия успешно обновлена!');

        } catch (\Exception $e) {
            \Log::error('Error updating version: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ошибка обновления: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Ошибка обновления: ' . $e->getMessage());
        }
    }

    // Обновление контента
    public function update(Request $request, Content $content)
    {
        $validated = $request->validate([
            'alias' => 'required|unique:contents,alias,' . $content->id,
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        // Дополнительная проверка: должна быть хотя бы одна локализация
        $hasValidLocalization = false;
        if ($request->has('locales')) {
            foreach ($request->locales as $index => $locale) {
                if (!empty($locale) && !empty($request->names[$index])) {
                    $hasValidLocalization = true;
                    break;
                }
            }
        }

        if (!$hasValidLocalization) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Необходимо заполнить хотя бы одну локализацию (язык и название)');
        }

        \Log::info('Update content request received', $request->all());

        try {
            DB::beginTransaction();

            // Обновляем основную информацию
            $content->update([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
            ]);

            // Удаляем старые локализации
            $content->localizedStrings()->delete();

            // Сохраняем новые локализации (только непустые)
            if ($request->has('locales')) {
                foreach ($request->locales as $index => $locale) {
                    // Пропускаем пустые строки
                    if (empty($locale) || empty(trim($request->names[$index]))) {
                        continue;
                    }

                    // Сохраняем название
                    ContentLocalizedString::create([
                        'content_id' => $content->id,
                        'type' => 'name',
                        'locale' => $locale,
                        'value' => trim($request->names[$index]),
                    ]);

                    // Сохраняем описание если есть
                    if (!empty(trim($request->descriptions[$index]))) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => trim($request->descriptions[$index]),
                        ]);
                    }
                }
            }

            // Обновляем доступные языки из заполненных локализаций
            $content->availableLocales()->delete();
            $usedLocales = $content->localizedStrings()->pluck('locale')->unique()->toArray();
            foreach ($usedLocales as $locale) {
                ContentAvailableLocale::create([
                    'content_id' => $content->id,
                    'locale' => $locale,
                ]);
            }

            DB::commit();

            \Log::info('Content updated successfully: ' . $content->id);

            return redirect()->route('admin.contents.show', $content)
                ->with('success', 'Контент успешно обновлен!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating content: ' . $e->getMessage());

            return back()->with('error', 'Ошибка при обновлении: ' . $e->getMessage())
                ->withInput();
        }
    }

    // Полное удаление контента со всеми файлами
    public function forceDestroy($id)
    {
        $content = Content::withTrashed()->findOrFail($id);

        \Log::info("Starting force delete for content ID: {$content->id}");

        try {
            // Удаляем файлы версий
            foreach ($content->versions as $version) {
                if (Storage::disk('private')->exists($version->file_path)) {
                    Storage::disk('private')->delete($version->file_path);
                    \Log::info("Deleted version file: {$version->file_path}");
                }
                $version->forceDelete();
            }

            // Удаляем все связанные данные
            $content->localizedStrings()->forceDelete();
            $content->imageLinks()->forceDelete();
            $content->videoLinks()->forceDelete();
            $content->availableLocales()->forceDelete();
            $content->modules()->detach();

            // Полностью удаляем контент
            $content->forceDelete();

            \Log::info("Content {$content->id} completely deleted");

            return redirect()->route('admin.contents.index')
                ->with('success', 'Контент полностью удален!');

        } catch (\Exception $e) {
            \Log::error("Error force deleting content: " . $e->getMessage());
            return back()->with('error', 'Ошибка при удалении: ' . $e->getMessage());
        }
    }

    // Список удаленных контентов
    public function trashed()
    {
        $contents = Content::onlyTrashed()
            ->with(['subsection.section'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);

        return view('admin.contents.trashed', compact('contents'));
    }

    // Восстановление контента
    public function restore($id)
    {
        $content = Content::withTrashed()->findOrFail($id);
        $content->restore();

        \Log::info("Content {$content->id} restored from trash");

        return redirect()->route('admin.contents.show', $content)
            ->with('success', 'Контент восстановлен!');
    }

    // Удаление контента (soft delete)
    public function destroy(Content $content)
    {
        $content->delete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Контент помечен на удаление!');
    }

    // Загрузка новой версии
    public function uploadVersion(Request $request, Content $content)
    {
        $validated = $request->validate([
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

        // Альтернативная валидация через version_number
        if ($request->has('version_number')) {
            $versionParts = explode('.', $request->version_number);
            if (count($versionParts) === 3) {
                $validated['major'] = (int)$versionParts[0];
                $validated['minor'] = (int)$versionParts[1];
                $validated['micro'] = (int)$versionParts[2];
            }
        }

        \Log::info('Upload version request received', [
            'content_id' => $content->id,
            'platform' => $validated['platform'],
            'version' => $validated['major'] . '.' . $validated['minor'] . '.' . $validated['micro'],
            'localizations_count' => $request->localizations ? count($request->localizations) : 0
        ]);

        try {
            DB::beginTransaction();

            // Проверяем, существует ли уже такая версия
            $existingVersion = Version::where('content_id', $content->id)
                ->where('platform', $validated['platform'])
                ->where('major', $validated['major'])
                ->where('minor', $validated['minor'])
                ->where('micro', $validated['micro'])
                ->first();

            if ($existingVersion) {
                return back()->with('error', 'Версия ' . $validated['major'] . '.' . $validated['minor'] . '.' . $validated['micro'] . ' уже существует для этой платформы');
            }

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('versions', $fileName, 'public');

            // Создаем версию
            $version = Version::create([
                'content_id' => $content->id,
                'platform' => $validated['platform'],
                'major' => $validated['major'],
                'minor' => $validated['minor'],
                'micro' => $validated['micro'],
                'tested' => $validated['tested'] ?? false,
                'release_note' => $validated['release_note'] ?? null,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]);

            // Обрабатываем локализации
            if ($request->has('localizations')) {
                foreach ($request->localizations as $localizationData) {
                    // Пропускаем если нет locale или файла
                    if (empty($localizationData['locale']) || !isset($localizationData['file'])) {
                        continue;
                    }

                    $localizationFile = $localizationData['file'];

                    // Пропускаем если файл не загружен
                    if (!$localizationFile || !$localizationFile->isValid()) {
                        continue;
                    }

                    $localizationFileName = time() . '_' . $localizationFile->getClientOriginalName();
                    $localizationFilePath = $localizationFile->storeAs('version_localizations', $localizationFileName, 'public');

                    // Создаем запись локализации
                    \App\Models\VersionLocalization::create([
                        'version_id' => $version->id,
                        'locale' => $localizationData['locale'],
                        'file_name' => $localizationFile->getClientOriginalName(),
                        'file_path' => $localizationFilePath,
                        'file_size' => $localizationFile->getSize(),
                    ]);

                    \Log::info('Localization uploaded', [
                        'version_id' => $version->id,
                        'locale' => $localizationData['locale'],
                        'file_name' => $localizationFile->getClientOriginalName()
                    ]);
                }
            }

            DB::commit();

            \Log::info('Version uploaded successfully with localizations', [
                'version_id' => $version->id,
                'localizations_count' => $version->localizations()->count()
            ]);

            return redirect()->route('admin.contents.platform-versions', [
                'content' => $content->id,
                'platform' => $validated['platform']
            ])->with('success', 'Версия успешно загружена!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Version upload failed: ' . $e->getMessage());

            return back()->with('error', 'Ошибка при загрузке версии: ' . $e->getMessage());
        }
    }

    // Скачивание локализации версии
    public function downloadLocalization(VersionLocalization $localization)
    {
        try {
            \Log::info("Attempting to download localization: {$localization->id}, file: {$localization->file_path}");

            if (!Storage::disk('public')->exists($localization->file_path)) {
                \Log::error("Localization file not found: {$localization->file_path}");
                return back()->with('error', 'Файл локализации не найден на сервере');
            }

            \Log::info("Downloading localization: {$localization->file_name}");

            return Storage::disk('public')->download($localization->file_path, $localization->file_name, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $localization->file_name . '"'
            ]);

        } catch (\Exception $e) {
            \Log::error("Error downloading localization {$localization->id}: " . $e->getMessage());
            return back()->with('error', 'Ошибка при скачивании файла локализации: ' . $e->getMessage());
        }
    }

    // Удаление версии
    public function destroyVersion(Version $version)
    {
        try {
            // Удаляем файл
            if (Storage::disk('private')->exists($version->file_path)) {
                Storage::disk('private')->delete($version->file_path);
                \Log::info("Deleted version file: {$version->file_path}");
            }

            // Удаляем запись
            $version->forceDelete();

            \Log::info("Version {$version->id} completely deleted");

            return back()->with('success', 'Версия полностью удалена!');

        } catch (\Exception $e) {
            \Log::error("Error deleting version: " . $e->getMessage());
            return back()->with('error', 'Ошибка при удалении версии');
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

    public function detachModule(Content $content, Module $module)
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

        $query = $content->versions()->where('platform', $platform);

        // Сортировка
        $sortColumn = $request->get('sort', 'id');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSortColumns = ['id', 'major', 'release_note', 'tested', 'file_size', 'created_at'];
        if (in_array($sortColumn, $allowedSortColumns)) {
            if ($sortColumn === 'major') {
                $query->orderBy('major', $sortDirection)
                    ->orderBy('minor', $sortDirection)
                    ->orderBy('micro', $sortDirection);
            } else {
                $query->orderBy($sortColumn, $sortDirection);
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $versions = $query->paginate(20);

        return view('admin.contents.platform-versions', compact('content', 'platform', 'versions'));
    }
}
