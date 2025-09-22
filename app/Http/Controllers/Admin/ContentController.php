<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        \Log::info('Store request data:', $request->all());

        $validated = $request->validate([
            'alias' => 'required|unique:contents,alias|max:255',
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'guid' => 'nullable|uuid',
            'available_locales' => 'required|array',
            'available_locales.*' => 'string|size:2',
            'names' => 'required|array',
            'names.*' => 'required|string|max:500',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:1000',
            'modules' => 'nullable|array',
            'modules.*' => 'exists:modules,id',
            // УПРОЩЕННАЯ ВАЛИДАЦИЯ ФАЙЛОВ:
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        \Log::info('Create content request received', $request->all());

        try {
            DB::beginTransaction();

            // Создаем контент
            $content = Content::create([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
                'guid' => $validated['guid'] ?? Str::uuid(),
                'available_locales' => $validated['available_locales']
            ]);

            // Сохраняем локализации названий
            foreach ($validated['names'] as $locale => $value) {
                ContentLocalizedString::create([
                    'content_id' => $content->id,
                    'type' => 'name',
                    'locale' => $locale,
                    'value' => $value,
                ]);
            }

            // Сохраняем локализации описаний (если есть)
            if (isset($validated['descriptions'])) {
                foreach ($validated['descriptions'] as $locale => $value) {
                    if (!empty($value)) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => $value,
                        ]);
                    }
                }
            }

            // Сохраняем модули (если есть)
            if (isset($validated['modules'])) {
                $content->modules()->sync($validated['modules']);
            }

            // ОБРАБОТКА ФАЙЛОВ С ПРОВЕРКОЙ
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                \Log::info('Images files:', ['count' => count($images), 'files' => array_map(function($file) {
                    return $file ? $file->getClientOriginalName() : 'null';
                }, $images)]);

                foreach ($images as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('content/images', 'public');
                        $content->imageLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                        \Log::info('Image saved:', ['path' => $path]);
                    }
                }
            }

            // Обрабатываем загруженные видео
            if ($request->hasFile('videos')) {
                $videos = $request->file('videos');
                \Log::info('Videos files:', ['count' => count($videos)]);

                foreach ($videos as $video) {
                    if ($video && $video->isValid()) {
                        $path = $video->store('content/videos', 'public');
                        $content->videoLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                        \Log::info('Video saved:', ['path' => $path]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.contents.show', $content->id)
                ->with('success', 'Content created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Content creation failed: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create content: ' . $e->getMessage());
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

        return view('admin.contents.show', compact('content'));
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
        $modules = Module::all();
        $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];

        return view('admin.contents.edit', compact('content', 'sections', 'modules', 'locales'));
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

            return redirect()->route('admin.contents.show', $version->content_id)
                ->with('success', 'Версия успешно обновлена!');

        } catch (\Exception $e) {
            \Log::error('Error updating version: ' . $e->getMessage());
            return back()->with('error', 'Ошибка обновления: ' . $e->getMessage());
        }
    }

    // Обновление контента
    // app/Http/Controllers/Admin/ContentController.php

    public function update(Request $request, Content $content)
    {
        \Log::info('Update request data:', $request->all());

        $validated = $request->validate([
            'alias' => 'required|unique:contents,alias,' . $content->id,
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'available_locales' => 'required|array',
            'available_locales.*' => 'string|size:2',
            'names' => 'required|array',
            'names.*' => 'required|string|max:500',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:1000',
            'modules' => 'nullable|array',
            'modules.*' => 'exists:modules,id',
            // УПРОЩЕННАЯ ВАЛИДАЦИЯ ФАЙЛОВ:
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'videos' => 'nullable|array',
            'videos.*' => 'nullable|mimetypes:video/mp4,video/quicktime|max:10240',
        ]);

        \Log::info('Validated data:', $validated);

        try {
            DB::beginTransaction();

            // Обновляем основную информацию
            $content->update([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
                'available_locales' => $validated['available_locales']
            ]);

            // Удаляем старые локализации и создаем новые
            $content->localizedStrings()->delete();

            // Сохраняем названия
            foreach ($validated['names'] as $locale => $value) {
                ContentLocalizedString::create([
                    'content_id' => $content->id,
                    'type' => 'name',
                    'locale' => $locale,
                    'value' => $value
                ]);
            }

            // Сохраняем описания
            if (isset($validated['descriptions'])) {
                foreach ($validated['descriptions'] as $locale => $value) {
                    if (!empty($value)) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => $value
                        ]);
                    }
                }
            }

            // Обновляем модули
            $content->modules()->sync($validated['modules'] ?? []);

            // ОБРАБОТКА ФАЙЛОВ С ПРОВЕРКОЙ И ЛОГИРОВАНИЕМ
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                \Log::info('Update - Images files:', ['count' => count($images)]);

                // Удаляем старые изображения только если загружаем новые
                foreach ($content->imageLinks as $oldImage) {
                    $path = parse_url($oldImage->link, PHP_URL_PATH);
                    $filePath = str_replace('/storage/', '', $path);

                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
                $content->imageLinks()->delete();

                foreach ($images as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('content/images', 'public');
                        $content->imageLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                        \Log::info('Image updated:', ['path' => $path]);
                    }
                }
            }

            // Обрабатываем загруженные видео
            if ($request->hasFile('videos')) {
                $videos = $request->file('videos');
                \Log::info('Update - Videos files:', ['count' => count($videos)]);

                // Удаляем старые видео только если загружаем новые
                foreach ($content->videoLinks as $oldVideo) {
                    $path = parse_url($oldVideo->link, PHP_URL_PATH);
                    $filePath = str_replace('/storage/', '', $path);

                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
                $content->videoLinks()->delete();

                foreach ($videos as $video) {
                    if ($video && $video->isValid()) {
                        $path = $video->store('content/videos', 'public');
                        $content->videoLinks()->create([
                            'link' => Storage::disk('public')->url($path)
                        ]);
                        \Log::info('Video updated:', ['path' => $path]);
                    }
                }
            }

            DB::commit();

            \Log::info('Content updated successfully: ' . $content->id);

            return redirect()->route('admin.contents.show', $content)
                ->with('success', 'Контент успешно обновлен!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating content: ' . $e->getMessage());
            \Log::error('Trace: ' . $e->getTraceAsString());

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
            'platform' => 'required|string|max:255',
            'major' => 'required|integer|min:0',
            'minor' => 'required|integer|min:0',
            'micro' => 'required|integer|min:0',
            'tested' => 'boolean',
            'release_note' => 'nullable|string',
            'file' => 'required|file|mimes:zip|max:102400' // 100MB max, zip only
        ]);

        \Log::info('Upload version request received', [
            'content_id' => $content->id,
            'platform' => $validated['platform'],
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'no file'
        ]);

        try {
            DB::beginTransaction();

            // Сначала создаем версию с пустыми значениями файла
            $version = Version::create([
                'content_id' => $content->id,
                'platform' => $validated['platform'],
                'major' => $validated['major'],
                'minor' => $validated['minor'],
                'micro' => $validated['micro'],
                'tested' => $validated['tested'] ?? false,
                'release_note' => $validated['release_note'] ?? null,
                'file_name' => '', // временно пустое значение
                'file_path' => '', // временно пустое значение
                'file_size' => 0, // временно 0
            ]);

            // Handle file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('versions', $fileName, 'public');

                // Обновляем версию с информацией о файле
                $version->update([
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                ]);

            }

            DB::commit();

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Version uploaded successfully!',
                    'version' => $version
                ]);
            }

            return redirect()->back()
                ->with('success', 'Version uploaded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Version upload failed: ' . $e->getMessage());

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload version: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to upload version: ' . $e->getMessage());
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


}
