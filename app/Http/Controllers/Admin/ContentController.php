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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    // Скачивание версии
    public function downloadVersion(Version $version)
    {
        if (!Storage::disk('private')->exists($version->file_path)) {
            return back()->with('error', 'Файл не найден на сервере');
        }

        return Storage::disk('private')->download($version->file_path, $version->file_name);
    }

    // Сохранение нового контента
    public function store(StoreContentRequest $request)
    {
        $validated = $request->validated();

        // Создаем контент
        $content = Content::create([
            'alias' => $validated['alias'],
            'default_name' => $validated['default_name'],
            'guid' => Str::uuid(),
            'subsection_id' => $validated['subsection_id'],
            'access_type' => $validated['access_type']
        ]);

        // Сохраняем локализации названий
        foreach ($validated['names'] as $locale => $value) {
            ContentLocalizedString::create([
                'content_id' => $content->id,
                'type' => 'name',
                'locale' => $locale,
                'value' => $value
            ]);
        }

        // Сохраняем локализации описаний
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

        // Сохраняем доступные локали
        foreach ($validated['available_locales'] as $locale) {
            ContentAvailableLocale::create([
                'content_id' => $content->id,
                'locale' => $locale
            ]);
        }

        // Привязываем модули
        if (!empty($validated['modules'])) {
            $content->modules()->attach($validated['modules']);
        }

        return redirect()->route('admin.contents.show', $content)
            ->with('success', 'Контент успешно создан!');
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

    // Обновление контента
    public function update(UpdateContentRequest $request, Content $content)
    {
        $validated = $request->validated();

        // Обновляем основную информацию
        $content->update([
            'alias' => $validated['alias'],
            'default_name' => $validated['default_name'],
            'subsection_id' => $validated['subsection_id'],
            'access_type' => $validated['access_type']
        ]);

        // Удаляем старые локализации и создаем новые
        $content->localizedStrings()->delete();

        foreach ($validated['names'] as $locale => $value) {
            ContentLocalizedString::create([
                'content_id' => $content->id,
                'type' => 'name',
                'locale' => $locale,
                'value' => $value
            ]);
        }

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

        // Обновляем доступные локали
        $content->availableLocales()->delete();
        foreach ($validated['available_locales'] as $locale) {
            ContentAvailableLocale::create([
                'content_id' => $content->id,
                'locale' => $locale
            ]);
        }

        // Обновляем медиа-ссылки
        $content->imageLinks()->delete();
        if (!empty($validated['image_links'])) {
            $links = array_filter(explode("\n", $validated['image_links']));
            foreach ($links as $link) {
                if (!empty(trim($link))) {
                    ContentImageLink::create([
                        'content_id' => $content->id,
                        'link' => trim($link)
                    ]);
                }
            }
        }

        $content->videoLinks()->delete();
        if (!empty($validated['video_links'])) {
            $links = array_filter(explode("\n", $validated['video_links']));
            foreach ($links as $link) {
                if (!empty(trim($link))) {
                    ContentVideoLink::create([
                        'content_id' => $content->id,
                        'link' => trim($link)
                    ]);
                }
            }
        }

        // Обновляем модули
        $content->modules()->sync($validated['modules'] ?? []);

        return redirect()->route('admin.contents.show', $content)
            ->with('success', 'Контент успешно обновлен!');
    }


    // Полное удаление контента
    public function forceDestroy($id)
    {
        $content = Content::withTrashed()->findOrFail($id);

        // Удаляем все связанные файлы
        foreach ($content->versions as $version) {
            Storage::disk('private')->delete($version->file_path);
            $version->forceDelete();
        }

        // Удаляем все связанные данные
        $content->localizedStrings()->delete();
        $content->imageLinks()->delete();
        $content->videoLinks()->delete();
        $content->availableLocales()->delete();
        $content->modules()->detach();

        // Полностью удаляем контент
        $content->forceDelete();

        return redirect()->route('admin.contents.index')
            ->with('success', 'Контент полностью удален!');
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
            'platform' => 'required|string|in:android,ios,windows,web',
            'major' => 'boolean',
            'minor' => 'boolean',
            'micro' => 'boolean',
            'release_note' => 'nullable|string',
            'file' => 'required|file|mimes:zip|max:102400' // 100MB max
        ]);

        // Определяем номер версии
        $latestVersion = Version::where('content_id', $content->id)
            ->where('platform', $validated['platform'])
            ->orderBy('major', 'desc')
            ->orderBy('minor', 'desc')
            ->orderBy('micro', 'desc')
            ->first();

        $major = $latestVersion ? $latestVersion->major : 0;
        $minor = $latestVersion ? $latestVersion->minor : 0;
        $micro = $latestVersion ? $latestVersion->micro : 0;

        if ($validated['major']) $major++;
        if ($validated['minor']) $minor++;
        if ($validated['micro']) $micro++;

        // Сохраняем файл
        $file = $request->file('file');
        $fileName = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('versions', $fileName, 'private');

        // Создаем версию
        $version = Version::create([
            'content_id' => $content->id,
            'platform' => $validated['platform'],
            'major' => $major,
            'minor' => $minor,
            'micro' => $micro,
            'tested' => false,
            'release_note' => $validated['release_note'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Версия успешно загружена!');
    }

    // Удаление версии
    public function destroyVersion(Version $version)
    {
        // Удаляем файл
        Storage::disk('private')->delete($version->file_path);

        // Удаляем запись
        $version->delete();

        return back()->with('success', 'Версия удалена!');
    }
}
