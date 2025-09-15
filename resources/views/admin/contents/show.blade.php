@extends('admin.layouts.app')

@section('title', $content->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item active">{{ $content->default_name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <!-- Основная информация -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Основная информация</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Редактировать
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>ID:</th><td>{{ $content->id }}</td></tr>
                        <tr><th>Alias:</th><td>{{ $content->alias }}</td></tr>
                        <tr><th>GUID:</th><td>{{ $content->guid }}</td></tr>
                        <tr><th>Раздел:</th><td>{{ $content->subsection->section->default_name }} → {{ $content->subsection->default_name }}</td></tr>
                        <tr><th>Access Type:</th><td>{{ $content->access_type }}</td></tr>
                        <tr><th>Создан:</th><td>{{ $content->created_at->format('d.m.Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>

            <!-- Локализации -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Локализации</h3>
                </div>
                <div class="card-body">
                    @foreach(['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'] as $locale)
                        @php
                            $name = $content->localizedStrings
                                ->where('type', 'name')
                                ->where('locale', $locale)
                                ->first();
                            $description = $content->localizedStrings
                                ->where('type', 'description')
                                ->where('locale', $locale)
                                ->first();
                        @endphp
                        <div class="card card-secondary mb-3">
                            <div class="card-header">
                                <h4 class="card-title">{{ strtoupper($locale) }}</h4>
                            </div>
                            <div class="card-body">
                                <p><strong>Название:</strong> {{ $name->value ?? 'Не указано' }}</p>
                                <p><strong>Описание:</strong> {{ $description->value ?? 'Не указано' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Версии -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Версии</h3>
                    <button class="btn btn-success btn-sm float-right" data-toggle="modal" data-target="#uploadVersionModal">
                        <i class="fas fa-upload"></i> Загрузить версию
                    </button>
                </div>
                <div class="card-body">
                    @if($content->versions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>Версия</th>
                                    <th>Платформа</th>
                                    <th>Размер</th>
                                    <th>Статус</th>
                                    <th>Release Note</th>
                                    <th>Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($content->versions as $version)
                                    <tr>
                                        <td>{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $version->platform }}</span>
                                        </td>
                                        <td>{{ number_format($version->file_size / 1024 / 1024, 2) }} MB</td>
                                        <td>
                                    <span class="badge badge-{{ $version->tested ? 'success' : 'warning' }}">
                                        {{ $version->tested ? 'Проверено' : 'На проверке' }}
                                    </span>
                                        </td>
                                        <td>{{ $version->release_note ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('admin.versions.download', $version) }}" class="btn btn-info btn-sm" title="Скачать">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="{{ route('admin.versions.edit', $version) }}" class="btn btn-warning btn-sm" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.versions.destroy', $version) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                        title="Удалить навсегда"
                                                        onclick="return confirm('Удалить эту версию и файл?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center">Нет загруженных версий</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Статистика -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Статистика</h3>
                </div>
                <div class="card-body">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-info"><i class="fas fa-code-branch"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Версий</span>
                            <span class="info-box-number">{{ $content->versions->count() }}</span>
                        </div>
                    </div>

                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-success"><i class="fas fa-puzzle-piece"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Модулей</span>
                            <span class="info-box-number">{{ $content->modules->count() }}</span>
                        </div>
                    </div>

                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning"><i class="fas fa-language"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Языков</span>
                            <span class="info-box-number">{{ $content->availableLocales->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Действия</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary btn-block mb-2">
                        <i class="fas fa-edit"></i> Редактировать контент
                    </a>

                    <form action="{{ route('admin.contents.destroy', $content) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('Удалить этот контент? Это действие нельзя отменить.')">
                            <i class="fas fa-trash"></i> Удалить контент
                        </button>
                    </form>
                </div>
            </div>

            <!-- Модули -->
            @if($content->modules->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Модули</h3>
                    </div>
                    <div class="card-body">
                        @foreach($content->modules as $module)
                            <div class="mb-2">
                                <strong>{{ $module->default_name }}</strong>
                                <br>
                                <small class="text-muted">Type: {{ $module->type }}, Alias: {{ $module->alias }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Модальное окно загрузки версии -->
    <div class="modal fade" id="uploadVersionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Загрузка новой версии</h5>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <form action="{{ route('admin.contents.upload-version', $content) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Платформа *</label>
                            <select class="form-control" name="platform" required>
                                <option value="android">Android</option>
                                <option value="ios">iOS</option>
                                <option value="windows">Windows</option>
                                <option value="web">Web</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Тип обновления *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="major" id="major">
                                <label class="form-check-label" for="major">Major (крупное обновление)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="minor" id="minor">
                                <label class="form-check-label" for="minor">Minor (новые функции)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="micro" id="micro">
                                <label class="form-check-label" for="micro">Micro (исправления багов)</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Release Note</label>
                            <textarea class="form-control" name="release_note" placeholder="Что нового в этой версии?"></textarea>
                        </div>

                        <div class="form-group">
                            <label>ZIP-файл *</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="file" name="file" accept=".zip" required>
                                <label class="custom-file-label" for="file">Выберите ZIP-файл</label>
                            </div>
                            <small class="form-text text-muted">Максимальный размер: 100MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Показываем имя файла в input
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    </script>
@endpush
