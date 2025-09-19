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
    public function index()
    {
        $contents = Content::with(['subsection.section', 'versions'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.contents.index', compact('contents'));
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
            'guid' => 'nullable|uuid',
            'available_locales' => 'required|array',
            'available_locales.*' => 'string|size:2',
        ]);

        \Log::info('Create content request received', $request->all());

        try {
            DB::beginTransaction();


            $content = Content::create([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type'],
                'guid' => $validated['guid'] ?? Str::uuid(),
            ]);


            foreach ($validated['available_locales'] as $locale) {
                ContentAvailableLocale::create([
                    'content_id' => $content->id,
                    'locale' => $locale,
                ]);
            }


            if ($request->has('localized_names')) {
                foreach ($request->localized_names as $locale => $name) {
                    if (!empty($name)) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'name',
                            'locale' => $locale,
                            'value' => $name,
                        ]);
                    }
                }
            }

            if ($request->has('localized_descriptions')) {
                foreach ($request->localized_descriptions as $locale => $description) {
                    if (!empty($description)) {
                        ContentLocalizedString::create([
                            'content_id' => $content->id,
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => $description,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.contents.show', $content->id)
                ->with('success', 'Content created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Content creation failed: ' . $e->getMessage());

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

    // Форма редактирования
    public function edit(Content $content)
    {
        $content->load(['localizedStrings', 'imageLinks', 'videoLinks', 'availableLocales', 'modules']);
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
    public function updateVersion(UpdateVersionRequest $request, Version $version)
    {
        try {
            $validated = $request->validated();

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
    public function update(Request $request, Content $content)
    {
        $validated = $request->validate([
            'alias' => 'required|unique:contents,alias,' . $content->id,
            'default_name' => 'required|max:255',
            'subsection_id' => 'required|exists:subsections,id',
            'access_type' => 'required|integer|min:0|max:255',
            'available_locales' => 'required|array',
            'available_locales.*' => 'string|size:2',
            'names' => 'required|array',
            'names.*' => 'required|string|max:255',
            'descriptions' => 'array',
            'descriptions.*' => 'nullable|string',
            'modules' => 'array',
            'modules.*' => 'exists:modules,id',
            'image_links' => 'nullable|string',
            'video_links' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Обновляем основную информацию
            $content->update([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'subsection_id' => $validated['subsection_id'],
                'access_type' => $validated['access_type']
            ]);

            // Обновляем локализации
            $content->localizedStrings()->delete();
            foreach ($validated['names'] as $locale => $value) {
                $content->localizedStrings()->create([
                    'type' => 'name',
                    'locale' => $locale,
                    'value' => $value
                ]);
            }

            if (isset($validated['descriptions'])) {
                foreach ($validated['descriptions'] as $locale => $value) {
                    if (!empty($value)) {
                        $content->localizedStrings()->create([
                            'type' => 'description',
                            'locale' => $locale,
                            'value' => $value
                        ]);
                    }
                }
            }

            // Обновляем доступные локали
            $content->availableLocales()->delete();
            foreach ($validated['available_locales'] as $locale) {
                $content->availableLocales()->create(['locale' => $locale]);
            }

            // Обновляем связи с модулями
            $content->modules()->sync($validated['modules'] ?? []);

            // Обновляем медиа-ссылки
            $content->imageLinks()->delete();
            $content->videoLinks()->delete();

            if (!empty($validated['image_links'])) {
                $links = array_filter(explode("\n", $validated['image_links']));
                foreach ($links as $link) {
                    if (!empty(trim($link))) {
                        $content->imageLinks()->create(['link' => trim($link)]);
                    }
                }
            }

            if (!empty($validated['video_links'])) {
                $links = array_filter(explode("\n", $validated['video_links']));
                foreach ($links as $link) {
                    if (!empty(trim($link))) {
                        $content->videoLinks()->create(['link' => trim($link)]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.contents.show', $content)
                ->with('success', 'Контент успешно обновлен!');

        } catch (\Exception $e) {
            DB::rollBack();
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
