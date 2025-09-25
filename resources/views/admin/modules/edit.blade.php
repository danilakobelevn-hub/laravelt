@extends('admin.layouts.app')

@section('title', 'Редактирование модуля: ' . $module->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.show', $content) }}">{{ $content->default_name }}</a></li>
    <li class="breadcrumb-item active">Редактирование модуля</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Редактирование модуля: {{ $module->default_name }}</h3>
        </div>

        <form action="{{ route('admin.modules.update', $module) }}" method="POST" id="editModuleForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="content_id" value="{{ $content->id }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_alias">Alias *</label>
                            <input type="text" class="form-control" id="module_alias" name="alias"
                                   value="{{ old('alias', $module->alias) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="module_default_name">Название по умолчанию *</label>
                            <input type="text" class="form-control" id="module_default_name" name="default_name"
                                   value="{{ old('default_name', $module->default_name) }}" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="module_type">Тип модуля *</label>
                    <select class="form-control" id="module_type" name="type" required>
                        <option value="">Выберите тип</option>
                        <option value="0" {{ old('type', $module->type) == 0 ? 'selected' : '' }}>Демонстрация</option>
                        <option value="1" {{ old('type', $module->type) == 1 ? 'selected' : '' }}>Атлас</option>
                        <option value="2" {{ old('type', $module->type) == 2 ? 'selected' : '' }}>Квиз</option>
                    </select>
                </div>

                <!-- Локализации -->
                <div class="card card-secondary mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Локализации</h3>
                    </div>
                    <div class="card-body">
                        <x-admin.localizations-table
                            :localizations="$module->localizedStrings"
                            :editable="true"
                            :model="$module"
                            modelType="module" />
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="{{ route('admin.contents.show', $content) }}" class="btn btn-default">Отмена</a>
                <button type="button" class="btn btn-danger float-right"
                        onclick="if(confirm('Удалить модуль полностью?')) { document.getElementById('deleteForm').submit(); }">
                    <i class="fas fa-trash"></i> Удалить модуль
                </button>
            </div>
        </form>

        <!-- Форма для удаления -->
        <form id="deleteForm" action="{{ route('admin.modules.destroy', $module) }}" method="POST" class="d-none">
            @csrf @method('DELETE')
        </form>
    </div>
@endsection

@push('styles')
    <style>
        #localizationsTable th {
            font-size: 12px;
            padding: 8px;
        }
        #localizationsTable td {
            padding: 8px;
            vertical-align: middle;
        }
        .remove-row {
            padding: 0.25rem 0.5rem;
            font-size: 12px;
        }
        .locale-select {
            min-width: 120px;
        }
        .module-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
            const localeNames = {
                'ru': 'Русский', 'en': 'English', 'ar': 'العربية',
                'zh': '中文', 'fr': 'Français', 'de': 'Deutsch', 'es': 'Español'
            };
            let usedLocales = new Set();

            // Данные модуля из PHP
            const moduleData = @json([
        'localized_strings' => $module->localizedStrings->map(function($item) {
            return [
                'type' => $item->type,
                'locale' => $item->locale,
                'value' => $item->value
            ];
        })
    ]);

            // Функция для добавления строки локализации
            function addLocalizationRow(locale = '', name = '', description = '') {
                const tbody = $('#localizationsTable tbody');
                const rowId = Date.now();

                // Создаем options для select
                let options = '<option value="">Выберите локаль</option>';
                locales.forEach(loc => {
                    const selected = loc === locale ? 'selected' : '';
                    const disabled = usedLocales.has(loc) && loc !== locale ? 'disabled' : '';
                    options += `<option value="${loc}" ${selected} ${disabled}>${localeNames[loc]} (${loc})</option>`;
                });

                const row = `
            <tr id="row-${rowId}">
                <td>
                    <select class="form-control form-control-sm locale-select" name="locales[]" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm"
                           name="names[]" value="${name}" placeholder="Название" required>
                </td>
                <td>
                    <textarea class="form-control form-control-sm"
                              name="descriptions[]" placeholder="Описание" rows="1">${description}</textarea>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-edit-row"
        data-row-id="${rowId}" data-locale="${locale}">
    <i class="fas fa-times"></i>
</button>
                </td>
            </tr>
        `;

                tbody.append(row);

                // Если указана локаль, добавляем в использованные
                if (locale) {
                    usedLocales.add(locale);
                }

                updateLocaleSelects();
            }

            // Функция для удаления строки
            function removeLocalizationRow(rowId, locale) {
                $(`#row-${rowId}`).remove();
                if (locale) {
                    usedLocales.delete(locale);
                }
                updateLocaleSelects();
            }

            // Функция для обновления доступных локалей в select
            function updateLocaleSelects() {
                $('.locale-select').each(function() {
                    const currentValue = $(this).val();
                    $(this).find('option').each(function() {
                        const optionValue = $(this).val();
                        if (optionValue && optionValue !== currentValue && usedLocales.has(optionValue)) {
                            $(this).prop('disabled', true);
                        } else {
                            $(this).prop('disabled', false);
                        }
                    });
                });
            }

            // Заполняем таблицу данными модуля
            function populateLocalizations() {
                const nameLocalizations = moduleData.localized_strings.filter(item => item.type === 'name');
                const descriptionLocalizations = moduleData.localized_strings.filter(item => item.type === 'description');

                nameLocalizations.forEach(nameLoc => {
                    const description = descriptionLocalizations.find(descLoc => descLoc.locale === nameLoc.locale)?.value || '';
                    addLocalizationRow(nameLoc.locale, nameLoc.value, description);
                });

                // Добавляем недостающие локали
                const existingLocales = nameLocalizations.map(item => item.locale);
                locales.forEach(locale => {
                    if (!existingLocales.includes(locale)) {
                        addLocalizationRow(locale, '', '');
                    }
                });
            }

            // Инициализируем таблицу
            populateLocalizations();

            // Обработчик кнопки добавления строки
            $('#addLocalizationRow').click(function() {
                addLocalizationRow('', '', '');
            });

            // Обработчик изменения выбора локали
            $(document).on('change', '.locale-select', function() {
                const oldValue = $(this).data('old-value');
                const newValue = $(this).val();

                if (oldValue) {
                    usedLocales.delete(oldValue);
                }
                if (newValue) {
                    usedLocales.add(newValue);
                }

                $(this).data('old-value', newValue);
                updateLocaleSelects();
            });

            // Обработчик отправки формы
            $('#editModuleForm').on('submit', function(e) {
                e.preventDefault();

                // Проверяем, что все локали уникальны
                const selectedLocales = [];
                let hasErrors = false;

                $('.locale-select').each(function() {
                    const value = $(this).val();
                    if (!value) {
                        alert('Все локали должны быть выбраны!');
                        $(this).focus();
                        hasErrors = true;
                        return false;
                    }
                    if (selectedLocales.includes(value)) {
                        alert('Локали должны быть уникальными! Дубликат: ' + value);
                        $(this).focus();
                        hasErrors = true;
                        return false;
                    }
                    selectedLocales.push(value);
                });

                if (hasErrors) return;

                // Проверяем обязательные поля названий
                $('input[name="names[]"]').each(function() {
                    if (!$(this).val().trim()) {
                        alert('Все названия должны быть заполнены!');
                        $(this).focus();
                        hasErrors = true;
                        return false;
                    }
                });

                if (hasErrors) return;

                // Отправляем форму
                this.submit();
            });
        });

        // Глобальная функция для удаления строк
        function removeLocalizationRow(rowId, locale) {
            $(`#row-${rowId}`).remove();
            if (locale) {
                const usedLocales = new Set();
                $('.locale-select').each(function() {
                    const value = $(this).val();
                    if (value) usedLocales.add(value);
                });
                usedLocales.delete(locale);
            }

            // Обновляем select'ы
            $('.locale-select').each(function() {
                const currentValue = $(this).val();
                $(this).find('option').each(function() {
                    const optionValue = $(this).val();
                    if (optionValue && optionValue !== currentValue && usedLocales.has(optionValue)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
            });
        }
    </script>
@endpush
