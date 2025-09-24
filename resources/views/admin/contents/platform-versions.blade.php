@extends('admin.layouts.app')

@section('title', 'Версии для ' . ucfirst($platform) . ' - ' . $content->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.show', $content) }}">{{ $content->default_name }}</a></li>
    <li class="breadcrumb-item active">Версии {{ ucfirst($platform) }}</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Версии для {{ ucfirst($platform) }} - {{ $content->default_name }}</h3>
            <div>
                <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#addVersionModal">
                    <i class="fas fa-plus"></i> Добавить версию
                </button>
                <a href="{{ route('admin.contents.show', $content) }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> Назад к контенту
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                    <tr>
                        <th width="80">
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'id',
                                    'direction' => request('sort') == 'id' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                ID
                                @if(request('sort') == 'id')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'major',
                                    'direction' => request('sort') == 'major' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                Версия
                                @if(request('sort') == 'major')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'release_note',
                                    'direction' => request('sort') == 'release_note' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                Release Note
                                @if(request('sort') == 'release_note')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th width="100">
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'tested',
                                    'direction' => request('sort') == 'tested' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                Tested
                                @if(request('sort') == 'tested')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>Файл</th>
                        <th>Локализации</th>
                        <th width="120">
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'file_size',
                                    'direction' => request('sort') == 'file_size' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                Размер
                                @if(request('sort') == 'file_size')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th width="150">
                            <a href="{{ route('admin.contents.platform-versions', [
                                    'content' => $content,
                                    'platform' => $platform,
                                    'sort' => 'created_at',
                                    'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc'
                                ]) }}" class="text-white">
                                Дата изменения
                                @if(request('sort') == 'created_at')
                                    <i class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th width="120">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($versions as $version)
                        <tr>
                            <td><strong>{{ $version->id }}</strong></td>
                            <td>
                                    <span class="badge badge-primary">
                                        v{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}
                                    </span>
                            </td>
                            <td>
                                @if($version->release_note)
                                    <span title="{{ $version->release_note }}">
                                            {{ Str::limit($version->release_note, 50) }}
                                        </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                    <span class="badge badge-{{ $version->tested ? 'success' : 'warning' }}">
                                        {{ $version->tested ? 'Yes' : 'No' }}
                                    </span>
                            </td>
                            <td>
                                @if($version->file_path && Storage::disk('public')->exists($version->file_path))
                                    <a href="{{ Storage::disk('public')->url($version->file_path) }}" download
                                       class="text-primary" title="Download {{ $version->file_name }}">
                                        <i class="fas fa-download mr-1"></i>
                                        {{ Str::limit($version->file_name, 30) }}
                                    </a>
                                @else
                                    <span class="text-muted">
                                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                            File not found
                                        </span>
                                @endif
                            </td>
                            <td>
                                @if($version->localizations->count() > 0)
                                    <div class="localizations-badges">
                                        @foreach($version->localizations as $localization)
                                            <span class="badge badge-info mr-1" title="{{ $localization->file_name }}">
                                                {{ strtoupper($localization->locale) }}
                                             </span>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">({{ $version->localizations->count() }})</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($version->file_size)
                                    {{ number_format($version->file_size / 1024 / 1024, 1) }} MB
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $version->created_at->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info edit-version"
                                            data-version-id="{{ $version->id }}"
                                            data-release-note="{{ $version->release_note }}"
                                            data-tested="{{ $version->tested }}"
                                            title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.versions.destroy', $version->id) }}"
                                          method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger"
                                                onclick="return confirm('Удалить эту версию?')"
                                                title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Версии не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($versions->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Показано с {{ $versions->firstItem() }} по {{ $versions->lastItem() }} из {{ $versions->total() }} записей
                    </div>
                    <div>
                        {{ $versions->appends([
                            'sort' => request('sort'),
                            'direction' => request('direction')
                        ])->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Модальное окно добавления версии -->
    <!-- Модальное окно добавления версии -->
    <div class="modal fade" id="addVersionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить версию для {{ ucfirst($platform) }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.contents.upload-version', $content) }}" method="POST" enctype="multipart/form-data" id="addVersionForm">
                    @csrf
                    <input type="hidden" name="platform" value="{{ $platform }}">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="version_number">Версия *</label>
                                    <input type="text" class="form-control" id="version_number" name="version_number"
                                           placeholder="1.0.0" required>
                                    <small class="form-text text-muted">Формат: major.minor.micro (например: 1.0.0)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="tested" name="tested" value="1">
                                    <label class="form-check-label" for="tested">Отметить как протестированную</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="release_note">Release Note</label>
                            <textarea class="form-control" id="release_note" name="release_note"
                                      rows="3" placeholder="Что нового в этой версии?"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="file">ZIP-файл версии *</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="file" name="file" accept=".zip" required>
                                <label class="custom-file-label" for="file">Выберите ZIP-файл версии</label>
                            </div>
                            <small class="form-text text-muted">Максимальный размер: 100MB</small>
                        </div>

                        <!-- Таблица локализаций -->
                        <div class="card card-secondary mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">Локализации версии</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="addLocalizationRow">
                                    <i class="fas fa-plus"></i> Добавить локаль
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="localizationsTable">
                                        <thead class="thead-light">
                                        <tr>
                                            <th width="150">Язык</th>
                                            <th>ZIP-файл локализации</th>
                                            <th width="80">Действия</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Строки будут добавляться динамически -->
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted">
                                    Если поле для загрузки файла пустое, локаль не будет сохранена
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Загрузить версию</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Модальное окно редактирования версии -->
    <div class="modal fade" id="editVersionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование версии</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editVersionForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Платформа</label>
                            <input type="text" class="form-control" id="edit_platform" readonly>
                        </div>

                        <div class="form-group">
                            <label>Версия</label>
                            <input type="text" class="form-control" id="edit_version" readonly>
                        </div>

                        <div class="form-group">
                            <label for="edit_release_note">Release Note</label>
                            <textarea class="form-control" id="edit_release_note" name="release_note"
                                      rows="3" placeholder="Что нового в этой версии?"></textarea>
                        </div>

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="edit_tested" name="tested" value="1">
                            <label class="form-check-label" for="edit_tested">Протестирована</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Локализации для выбора
        const availableLocales = [
            {code: 'ru', name: 'Русский'},
            {code: 'en', name: 'English'},
            {code: 'ar', name: 'العربية'},
            {code: 'zh', name: '中文'},
            {code: 'fr', name: 'Français'},
            {code: 'de', name: 'Deutsch'},
            {code: 'es', name: 'Español'}
        ];

        let usedLocales = new Set();

        // Функция для добавления строки локализации
        function addLocalizationRow(selectedLocale = '') {
            const tbody = $('#localizationsTable tbody');
            const rowId = Date.now();

            // Создаем options для select
            let options = '<option value="">Выберите язык</option>';
            availableLocales.forEach(locale => {
                const selected = locale.code === selectedLocale ? 'selected' : '';
                const disabled = usedLocales.has(locale.code) && locale.code !== selectedLocale ? 'disabled' : '';
                options += `<option value="${locale.code}" ${selected} ${disabled}>${locale.name} (${locale.code.toUpperCase()})</option>`;
            });

            const row = `
        <tr id="localization-row-${rowId}">
            <td>
                <select class="form-control form-control-sm locale-select" name="localizations[${rowId}][locale]" required>
                    ${options}
                </select>
            </td>
            <td>
                <div class="custom-file">
                    <input type="file" class="custom-file-input localization-file"
                           name="localizations[${rowId}][file]" accept=".zip">
                    <label class="custom-file-label">Выберите ZIP-файл</label>
                </div>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-localization-row"
                        data-row-id="${rowId}">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `;

            tbody.append(row);

            // Если указана локаль, добавляем в использованные
            if (selectedLocale) {
                usedLocales.add(selectedLocale);
                $(`#localization-row-${rowId} .locale-select`).data('old-value', selectedLocale);
            }

            // Обновляем обработчики событий
            updateLocaleSelects();
            initFileInputs();
        }

        // Функция для удаления строки локализации
        function removeLocalizationRow(rowId) {
            const row = $(`#localization-row-${rowId}`);
            const selectedLocale = row.find('.locale-select').val();

            row.remove();

            if (selectedLocale) {
                usedLocales.delete(selectedLocale);
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

        // Инициализация file inputs
        function initFileInputs() {
            $('.localization-file').off('change').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName || "Выберите ZIP-файл");
            });
        }

        // Обработчик добавления строки
        $('#addLocalizationRow').click(function() {
            addLocalizationRow();
        });

        // Обработчик удаления строки
        $(document).on('click', '.remove-localization-row', function() {
            const rowId = $(this).data('row-id');
            removeLocalizationRow(rowId);
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

        // Добавляем первую строку при загрузке
        $(document).ready(function() {
            addLocalizationRow();
        });

        $(document).ready(function() {
            // Имя файла в input
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            });

            // Обработчик кнопки редактирования
            $('.edit-version').click(function() {
                const versionId = $(this).data('version-id');
                const releaseNote = $(this).data('release-note') || '';
                const tested = $(this).data('tested');

                // Находим строку таблицы для получения данных о версии
                const row = $(this).closest('tr');
                const platform = '{{ $platform }}';
                const version = row.find('td:nth-child(2) .badge').text().replace('v', '');

                // Заполняем форму редактирования
                $('#edit_platform').val(platform);
                $('#edit_version').val(version);
                $('#edit_release_note').val(releaseNote);
                $('#edit_tested').prop('checked', tested);

                // Устанавливаем action формы
                $('#editVersionForm').attr('action', '/admin/versions/' + versionId);

                // Открываем модальное окно
                $('#editVersionModal').modal('show');
            });

            // Обработчик отправки формы добавления версии
            $('#addVersionForm').on('submit', function(e) {
                e.preventDefault();

                // Парсим версию
                const versionText = $('#version_number').val();
                const versionParts = versionText.split('.');

                if (versionParts.length !== 3) {
                    alert('Неверный формат версии. Используйте формат: major.minor.micro');
                    return;
                }

                // Добавляем hidden поля для версии
                $('<input>').attr({
                    type: 'hidden',
                    name: 'major',
                    value: parseInt(versionParts[0])
                }).appendTo(this);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'minor',
                    value: parseInt(versionParts[1])
                }).appendTo(this);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'micro',
                    value: parseInt(versionParts[2])
                }).appendTo(this);

                // Отправляем форму
                this.submit();
            });
        });
    </script>
@endpush
