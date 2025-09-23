<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Content;
use App\Models\ModuleLocalizedString;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index()
    {
        $modules = Module::with('localizedStrings')->paginate(20);
        return view('admin.modules.index', compact('modules'));
    }

    public function create()
    {
        $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
        $types = Module::getTypes();

        return view('admin.modules.create', compact('locales', 'types'));
    }

    public function store(Request $request)
    {
        // Дополнительная проверка - нельзя создать модуль без content_id
        if (!$request->has('content_id')) {
            return response()->json([
                'message' => 'Content ID обязателен для создания модуля'
            ], 422);
        }
        $validated = $request->validate([
            'alias' => 'required|unique:modules,alias|max:255|alpha_dash',
            'default_name' => 'required|max:255',
            'type' => 'required|integer|in:0,1,2',
            'locales' => 'required|array',
            'locales.*' => 'required|string|size:2',
            'names' => 'required|array',
            'names.*' => 'required|string|max:500',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:1000',
            'content_id' => 'required|exists:contents,id' // Добавляем обязательную проверку content_id
        ]);

        // Проверяем уникальность локалей
        if (count($validated['locales']) !== count(array_unique($validated['locales']))) {
            return response()->json([
                'message' => 'Локали должны быть уникальными'
            ], 422);
        }

        try {
            DB::beginTransaction();

            $module = Module::create([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'type' => $validated['type'],
                'guid' => Str::uuid(),
            ]);

            // Сохраняем локализации
            foreach ($validated['locales'] as $index => $locale) {
                ModuleLocalizedString::create([
                    'module_id' => $module->id,
                    'type' => 'name',
                    'locale' => $locale,
                    'value' => $validated['names'][$index],
                ]);

                if (!empty($validated['descriptions'][$index])) {
                    ModuleLocalizedString::create([
                        'module_id' => $module->id,
                        'type' => 'description',
                        'locale' => $locale,
                        'value' => $validated['descriptions'][$index],
                    ]);
                }
            }

            // Прикрепляем модуль к контенту (обязательно)
            $content = Content::findOrFail($validated['content_id']);
            $content->modules()->attach($module->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Модуль успешно создан и прикреплен к контенту'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Module creation error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Ошибка при создании модуля: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Module $module)
    {
        $module->load('localizedStrings');
        $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
        $types = Module::getTypes();

        return view('admin.modules.show', compact('module', 'locales', 'types'));
    }

    public function edit(Module $module)
    {
        $module->load('localizedStrings');

        // Находим контент, к которому привязан модуль
        $content = $module->contents()->first();
        if (!$content) {
            return redirect()->route('admin.contents.index')
                ->with('error', 'Модуль не привязан к контенту');
        }

        return view('admin.modules.edit', compact('module', 'content'));
    }

    public function editData(Module $module)
    {
        $module->load('localizedStrings');

        return response()->json([
            'id' => $module->id,
            'alias' => $module->alias,
            'default_name' => $module->default_name,
            'type' => $module->type,
            'localized_strings' => $module->localizedStrings
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'alias' => 'required|unique:modules,alias,' . $module->id . '|max:255|alpha_dash',
            'default_name' => 'required|max:255',
            'type' => 'required|integer|in:0,1,2',
            'locales' => 'required|array',
            'locales.*' => 'required|string|size:2',
            'names' => 'required|array',
            'names.*' => 'required|string|max:500',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:1000',
            'content_id' => 'required|exists:contents,id'
        ]);

        // Проверяем уникальность локалей
        if (count($validated['locales']) !== count(array_unique($validated['locales']))) {
            return back()->withErrors(['locales' => 'Локали должны быть уникальными'])->withInput();
        }

        try {
            DB::beginTransaction();

            // Обновляем основную информацию модуля
            $module->update([
                'alias' => $validated['alias'],
                'default_name' => $validated['default_name'],
                'type' => $validated['type'],
            ]);

            // Удаляем старые локализации
            $module->localizedStrings()->delete();

            // Сохраняем новые локализации
            foreach ($validated['locales'] as $index => $locale) {
                ModuleLocalizedString::create([
                    'module_id' => $module->id,
                    'type' => 'name',
                    'locale' => $locale,
                    'value' => $validated['names'][$index],
                ]);

                if (!empty($validated['descriptions'][$index])) {
                    ModuleLocalizedString::create([
                        'module_id' => $module->id,
                        'type' => 'description',
                        'locale' => $locale,
                        'value' => $validated['descriptions'][$index],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.contents.show', $validated['content_id'])
                ->with('success', 'Модуль успешно обновлен');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Module update error: ' . $e->getMessage());

            return back()->with('error', 'Ошибка при обновлении модуля: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Module $module)
    {
        try {
            $module->localizedStrings()->delete();
            $module->delete();

            return redirect()->route('admin.modules.index')
                ->with('success', 'Модуль успешно удален!');

        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при удалении модуля: ' . $e->getMessage());
        }
    }
}
