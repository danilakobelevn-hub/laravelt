@props([
    'localizations' => [],
    'editable' => false,
    'model' => null,
    'modelType' => 'content'
])

@php
    // Определяем locales внутри компонента, чтобы не зависеть от внешних переменных
    $locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
    $localeNames = [
        'ru' => 'Русский', 'en' => 'English', 'ar' => 'العربية',
        'zh' => '中文', 'fr' => 'Français', 'de' => 'Deutsch', 'es' => 'Español'
    ];
@endphp

<div class="card card-secondary">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Локализации</h6>
        @if($editable)
            <button type="button" class="btn btn-sm btn-primary" id="addLocalizationRow">
                <i class="fas fa-plus"></i> Добавить локаль
            </button>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="{{ $editable ? 'localizationsTable' : 'localizationsViewTable' }}">
                <thead>
                <tr>
                    <th width="150">Локаль</th>
                    <th>Название</th>
                    <th>Описание</th>
                    @if($editable)
                        <th width="50">Действия</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @if($editable)
                    <!-- Строки будут добавляться динамически через JavaScript -->
                @else
                    <!-- Статическое отображение для просмотра -->
                    @foreach($locales as $locale)
                        @php
                            $name = $localizations->where('type', 'name')->where('locale', $locale)->first();
                            $description = $localizations->where('type', 'description')->where('locale', $locale)->first();
                        @endphp
                        @if($name || $description)
                            <tr>
                                <td>
                                    <strong>{{ $localeNames[$locale] }} ({{ strtoupper($locale) }})</strong>
                                </td>
                                <td>{{ $name->value ?? 'Не указано' }}</td>
                                <td>{{ $description->value ?? 'Не указано' }}</td>
                            </tr>
                        @endif
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($editable)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
                const localeNames = {
                    'ru': 'Русский', 'en': 'English', 'ar': 'العربية',
                    'zh': '中文', 'fr': 'Français', 'de': 'Deutsch', 'es': 'Español'
                };
                let usedLocales = new Set();

                // Функция для добавления строки локализации
                function addLocalizationRow(locale = '', name = '', description = '') {
                    const tbody = document.querySelector('#localizationsTable tbody');
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
                                <select class="form-control form-control-sm locale-select" name="locales[]"
                                        data-row-id="${rowId}">
                                    ${options}
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm"
                                       name="names[]" value="${name}" placeholder="Название">
                            </td>
                            <td>
                                <textarea class="form-control form-control-sm"
                                          name="descriptions[]" placeholder="Описание" rows="1">${description}</textarea>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-row"
                                        data-row-id="${rowId}" data-locale="${locale}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    `;

                    tbody.insertAdjacentHTML('beforeend', row);

                    // Если указана локаль, добавляем в использованные
                    if (locale) {
                        usedLocales.add(locale);
                        const select = document.querySelector(`#row-${rowId} .locale-select`);
                        if (select) {
                            select.setAttribute('data-old-value', locale);
                        }
                    }

                    updateLocaleSelects();
                }

                // Функция для удаления строки
                function removeLocalizationRow(rowId, locale) {
                    const row = document.getElementById(`row-${rowId}`);
                    if (row) {
                        row.remove();
                    }
                    if (locale) {
                        usedLocales.delete(locale);
                    }
                    updateLocaleSelects();
                }

                // Функция для обновления доступных локалей в select
                function updateLocaleSelects() {
                    const currentUsedLocales = new Set();

                    document.querySelectorAll('.locale-select').forEach(select => {
                        const value = select.value;
                        if (value) {
                            currentUsedLocales.add(value);
                        }
                    });

                    document.querySelectorAll('.locale-select').forEach(select => {
                        const currentValue = select.value;
                        select.querySelectorAll('option').forEach(option => {
                            const optionValue = option.value;
                            if (optionValue && optionValue !== currentValue && currentUsedLocales.has(optionValue)) {
                                option.disabled = true;
                            } else {
                                option.disabled = false;
                            }
                        });
                    });

                    usedLocales = currentUsedLocales;
                }

                // Инициализация таблицы при загрузке
                @if(isset($model) && $model->localizedStrings)
                // Заполняем существующими данными если редактируем
                @foreach($model->localizedStrings->where('type', 'name') as $nameLoc)
                @php
                    $description = $model->localizedStrings
                        ->where('type', 'description')
                        ->where('locale', $nameLoc->locale)
                        ->first();
                @endphp
                addLocalizationRow(
                    '{{ $nameLoc->locale }}',
                    '{{ addslashes($nameLoc->value) }}',
                    '{{ $description ? addslashes($description->value) : '' }}'
                );
                @endforeach

                // Добавляем пустые строки для остальных локалей
                @php
                    $existingLocales = $model->localizedStrings->where('type', 'name')->pluck('locale')->toArray();
                    $missingLocales = array_diff($locales, $existingLocales);
                @endphp
                @foreach($missingLocales as $locale)
                addLocalizationRow('{{ $locale }}', '', '');
                @endforeach
                @else
                // Добавляем строки для всех локалей по умолчанию при создании
                locales.forEach(locale => {
                    addLocalizationRow(locale, '', '');
                });
                @endif

                // Обработчик кнопки добавления строки
                document.getElementById('addLocalizationRow')?.addEventListener('click', function() {
                    addLocalizationRow('', '', '');
                });

                // Обработчик изменения выбора локали
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('locale-select')) {
                        const oldValue = e.target.getAttribute('data-old-value');
                        const newValue = e.target.value;

                        if (oldValue) {
                            usedLocales.delete(oldValue);
                        }
                        if (newValue) {
                            usedLocales.add(newValue);
                        }

                        e.target.setAttribute('data-old-value', newValue);
                        updateLocaleSelects();
                    }
                });

                // Обработчик удаления строки
                document.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-row')) {
                        const button = e.target.closest('.remove-row');
                        const rowId = button.getAttribute('data-row-id');
                        const locale = button.getAttribute('data-locale');
                        removeLocalizationRow(rowId, locale);
                    }
                });
            });
        </script>
    @endpush
@endif
