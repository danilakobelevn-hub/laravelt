@extends('admin.layouts.app')

@section('title', $content->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item active">{{ $content->default_name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Основная информация</h3>
                    <div class="card-tools">
                        <div class="btn-group">
                            <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Левая колонка - изображение -->
                        <div class="col-md-4">
                            <div class="text-center">
                                @if($content->imageLinks->count() > 0)
                                    <img src="{{ $content->imageLinks->first()->link }}"
                                         alt="{{ $content->default_name }}"
                                         class="img-fluid rounded"
                                         style="max-height: 300px; width: auto;"
                                         onerror="this.src='/storage/empty.png'">
                                @else
                                    <img src="/storage/empty.png"
                                         alt="No image"
                                         class="img-fluid rounded"
                                         style="max-height: 300px; width: auto;">
                                    <div class="text-muted mt-2">Изображение отсутствует</div>
                                @endif

                                <!-- Дополнительные изображения -->
                                @if($content->imageLinks->count() > 1)
                                    <div class="mt-3">
                                        <h6>Дополнительные изображения:</h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($content->imageLinks->slice(1) as $imageLink)
                                                <img src="{{ $imageLink->link }}"
                                                     alt="Additional image"
                                                     class="img-thumbnail"
                                                     style="width: 60px; height: 60px; object-fit: cover;"
                                                     onerror="this.src='/storage/empty.png'">
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Правая колонка - информация -->
                        <div class="col-md-8">
                            <div class="info-table">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">ID:</th>
                                        <td>
                                            <span class="badge badge-secondary">{{ $content->id }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>GUID:</th>
                                        <td>
                                            <code>{{ $content->guid }}</code>
                                            <button class="btn btn-sm btn-outline-secondary ml-2"
                                                    onclick="navigator.clipboard.writeText('{{ $content->guid }}')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Название:</th>
                                        <td>
                                            <h5 class="mb-0">{{ $content->default_name }}</h5>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Alias:</th>
                                        <td>
                                            <code>{{ $content->alias }}</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Раздел:</th>
                                        <td>
                                            @if($content->subsection && $content->subsection->section)
                                                <span class="badge badge-info">
                                                {{ $content->subsection->section->default_name }}
                                            </span>
                                                →
                                                <span class="badge badge-secondary">
                                                {{ $content->subsection->default_name }}
                                            </span>
                                            @else
                                                <span class="text-danger">Не указан</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Access Type:</th>
                                        <td>
                                        <span class="badge badge-{{ $content->access_type == 0 ? 'success' : 'warning' }}">
                                            {{ $content->access_type }}
                                        </span>
                                            <small class="text-muted ml-2">
                                                @if($content->access_type == 0)
                                                    Публичный доступ
                                                @else
                                                    Ограниченный доступ
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Доступные языки:</th>
                                        <td>
                                            @if($content->available_locales && count($content->available_locales) > 0)
                                                @foreach($content->available_locales as $locale)
                                                    <span class="badge badge-primary mr-1">
                                                    {{ strtoupper($locale) }}
                                                </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Не указаны</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Создан:</th>
                                        <td>
                                            {{ $content->created_at->format('d.m.Y H:i') }}
                                            <small class="text-muted">
                                                ({{ $content->created_at->diffForHumans() }})
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Обновлен:</th>
                                        <td>
                                            {{ $content->updated_at->format('d.m.Y H:i') }}
                                            <small class="text-muted">
                                                ({{ $content->updated_at->diffForHumans() }})
                                            </small>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Версий:</th>
                                        <td>
                                        <span class="badge badge-info">
                                            {{ $content->versions->count() }}
                                        </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Модули:</th>
                                        <td>
                                            @if($content->modules->count() > 0)
                                                @foreach($content->modules as $module)
                                                    <span class="badge badge-success mr-1">
                                                    {{ $module->default_name }} ({{ $module->alias }})
                                                </span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">Модули не назначены</span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Описание -->
                    @if($content->getDescription())
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Описание:</h5>
                                <div class="card card-body bg-light">
                                    {{ $content->getDescription() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Локализации -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Локализации</h3>
                </div>
                <div class="card-body">
                    <x-admin.localizations-table
                        :localizations="$content->localizedStrings"
                        :editable="false" />
                </div>
            </div>

            <!-- Модули -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Модули ({{ $content->modules->count() }})</h3>
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createModuleModal">
                        <i class="fas fa-plus"></i> Создать модуль
                    </button>
                </div>
                <div class="card-body">
                    @if($content->modules->count() > 0)
                        <div class="row">
                            @foreach($content->modules as $module)
                                <div class="col-md-6 mb-3">
                                    <div class="card card-primary module-card"
                                         onclick="openEditModal({{ $module->id }})"
                                         style="cursor: pointer;">
                                        <div class="card-header">
                                            <h4 class="card-title">{{ $module->default_name }}</h4>
                                            <div class="card-tools">
                                                <span class="badge badge-light">{{ $module->alias }}</span>
                                                <span class="badge badge-info ml-1">{{ $module->getTypeName() }}</span>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-2">
                                                <strong>Описание:</strong>
                                                {{ $module->getDescription() ?? 'Не указано' }}
                                            </p>
                                            <div class="mt-3">
                                                <small class="text-muted">GUID: {{ $module->guid }}</small>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <form action="{{ route('admin.contents.modules.detach', [$content, $module]) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('Удалить модуль из контента?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="event.stopPropagation()">
                                                    <i class="fas fa-times"></i> Удалить из контента
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            Модули не добавлены. Нажмите "Создать модуль" чтобы создать новый модуль.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Модальное окно создания модуля -->
            <div class="modal fade" id="createModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Создать новый модуль</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="createModuleForm" action="{{ route('admin.modules.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="content_id" value="{{ $content->id }}">

                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="module_alias">Alias *</label>
                                            <input type="text" class="form-control" id="module_alias" name="alias" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="module_default_name">Название по умолчанию *</label>
                                            <input type="text" class="form-control" id="module_default_name" name="default_name" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="module_type">Тип модуля *</label>
                                    <select class="form-control" id="module_type" name="type" required>
                                        <option value="">Выберите тип</option>
                                        <option value="0">Демонстрация</option>
                                        <option value="1">Атлас</option>
                                        <option value="2">Квиз</option>
                                    </select>
                                </div>

                                <div class="card card-secondary">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="card-title mb-0">Локализации</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="addLocalizationRow">
                                            <i class="fas fa-plus"></i> Добавить локаль
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="localizationsTable">
                                                <thead>
                                                <tr>
                                                    <th width="150">Локаль</th>
                                                    <th>Название *</th>
                                                    <th>Описание</th>
                                                    <th width="50">Действия</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <!-- Строки будут добавляться динамически -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                                <button type="submit" class="btn btn-primary">Создать модуль</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Платформы и версии -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Платформы и версии</h3>
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#uploadVersionModal">
                        <i class="fas fa-upload"></i> Загрузить версию
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-dark">
                            <tr>
                                <th>Платформа</th>
                                <th>Версия релиза</th>
                                <th>Версия на тест</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $platforms = ['windows', 'macos', 'linux', 'android', 'ios', 'web'];
                            @endphp
                            @foreach($platforms as $platform)
                                @php
                                    $releaseVersion = $content->versions()
                                        ->where('platform', $platform)
                                        ->where('tested', true)
                                        ->orderBy('major', 'desc')
                                        ->orderBy('minor', 'desc')
                                        ->orderBy('micro', 'desc')
                                        ->first();

                                    $testVersion = $content->versions()
                                        ->where('platform', $platform)
                                        ->where('tested', false)
                                        ->orderBy('major', 'desc')
                                        ->orderBy('minor', 'desc')
                                        ->orderBy('micro', 'desc')
                                        ->first();
                                @endphp
                                <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.contents.platform-versions', [$content, 'platform' => $platform]) }}'">
                                    <td>
                                        <strong>{{ ucfirst($platform) }}</strong>
                                        <span class="badge badge-info ml-2">
                                    {{ $content->versions()->where('platform', $platform)->count() }} версий
                                </span>
                                    </td>
                                    <td>
                                        @if($releaseVersion)
                                            <span class="badge badge-success">
                                        v{{ $releaseVersion->major }}.{{ $releaseVersion->minor }}.{{ $releaseVersion->micro }}
                                    </span>
                                            <small class="text-muted ml-2">
                                                {{ $releaseVersion->created_at->format('d.m.Y') }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($testVersion)
                                            <span class="badge badge-warning">
                                        v{{ $testVersion->major }}.{{ $testVersion->minor }}.{{ $testVersion->micro }}
                                    </span>
                                            <small class="text-muted ml-2">
                                                {{ $testVersion->created_at->format('d.m.Y') }}
                                            </small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Версии -->
{{--            <div class="card mt-4">--}}
{{--                <div class="card-header d-flex justify-content-between align-items-center">--}}
{{--                    <h3 class="card-title mb-0">Версии ({{ $content->versions->count() }})</h3>--}}
{{--                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#uploadVersionModal">--}}
{{--                        <i class="fas fa-upload"></i> Загрузить версию--}}
{{--                    </button>--}}
{{--                </div>--}}
{{--                <div class="card-body">--}}
{{--                    @if($content->versions->count() > 0)--}}
{{--                        <div class="table-responsive">--}}
{{--                            <table class="table table-striped table-hover">--}}
{{--                                <thead class="thead-dark">--}}
{{--                                <tr>--}}
{{--                                    <th>Platform</th>--}}
{{--                                    <th>Version</th>--}}
{{--                                    <th>Tested</th>--}}
{{--                                    <th>File</th>--}}
{{--                                    <th>Size</th>--}}
{{--                                    <th>Uploaded</th>--}}
{{--                                    <th>Actions</th>--}}
{{--                                </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody>--}}
{{--                                @foreach($content->versions->sortByDesc('created_at') as $version)--}}
{{--                                    <tr>--}}
{{--                                        <td>--}}
{{--                                            <span class="badge badge-info">--}}
{{--                                                {{ ucfirst($version->platform) }}--}}
{{--                                            </span>--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            <strong>{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}</strong>--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            <span class="badge badge-{{ $version->tested ? 'success' : 'warning' }}">--}}
{{--                                                {{ $version->tested ? 'Yes' : 'No' }}--}}
{{--                                            </span>--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            @if($version->file_path && Storage::disk('public')->exists($version->file_path))--}}
{{--                                                <a href="{{ Storage::disk('public')->url($version->file_path) }}" download--}}
{{--                                                   class="text-primary" title="Download {{ $version->file_name }}">--}}
{{--                                                    <i class="fas fa-download mr-1"></i>--}}
{{--                                                    {{ $version->file_name }}--}}
{{--                                                </a>--}}
{{--                                            @else--}}
{{--                                                <span class="text-muted">--}}
{{--                                                    <i class="fas fa-exclamation-triangle text-warning mr-1"></i>--}}
{{--                                                    File not found--}}
{{--                                                </span>--}}
{{--                                            @endif--}}
{{--                                        </td>--}}
{{--                                        <td>--}}
{{--                                            @if($version->file_size)--}}
{{--                                                {{ number_format($version->file_size / 1024 / 1024, 2) }} MB--}}
{{--                                            @else--}}
{{--                                                ---}}
{{--                                            @endif--}}
{{--                                        </td>--}}
{{--                                        <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>--}}
{{--                                        <td>--}}
{{--                                            <div class="btn-group btn-group-sm">--}}
{{--                                                <a href="{{ route('admin.versions.edit', $version->id) }}"--}}
{{--                                                   class="btn btn-info" title="Edit">--}}
{{--                                                    <i class="fas fa-edit"></i>--}}
{{--                                                </a>--}}

{{--                                                <a href="{{ route('admin.versions.download', $version->id) }}"--}}
{{--                                                   class="btn btn-success" title="Download" download>--}}
{{--                                                    <i class="fas fa-download"></i>--}}
{{--                                                </a>--}}

{{--                                                <form action="{{ route('admin.versions.destroy', $version->id) }}"--}}
{{--                                                      method="POST" class="d-inline">--}}
{{--                                                    @csrf--}}
{{--                                                    @method('DELETE')--}}
{{--                                                    <button type="submit" class="btn btn-danger"--}}
{{--                                                            onclick="return confirm('Are you sure you want to delete this version?')"--}}
{{--                                                            title="Delete">--}}
{{--                                                        <i class="fas fa-trash"></i>--}}
{{--                                                    </button>--}}
{{--                                                </form>--}}
{{--                                            </div>--}}
{{--                                        </td>--}}
{{--                                    </tr>--}}
{{--                                @endforeach--}}
{{--                                </tbody>--}}
{{--                            </table>--}}
{{--                        </div>--}}
{{--                    @else--}}
{{--                        <div class="alert alert-info mb-0">--}}
{{--                            <i class="fas fa-info-circle mr-2"></i>--}}
{{--                            No versions uploaded yet. Click "Upload New Version" to add the first version.--}}
{{--                        </div>--}}
{{--                    @endif--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
    </div>

    <!-- Модальное окно удаления -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <p>Вы уверены, что хотите удалить контент <strong>"{{ $content->default_name }}"</strong>?</p>
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Это действие нельзя будет отменить. Все связанные версии и файлы будут удалены.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <form action="{{ route('admin.contents.destroy', $content) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Удалить контент</button>
                    </form>
                </div>
            </div>
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
                <form action="{{ route('admin.contents.upload-version', $content) }}" method="POST" enctype="multipart/form-data" id="uploadVersionForm">
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

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="major">Major Version *</label>
                                    <input type="number" name="major" id="major" class="form-control" required min="0" value="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="minor">Minor Version *</label>
                                    <input type="number" name="minor" id="minor" class="form-control" required min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="micro">Micro Version *</label>
                                    <input type="number" name="micro" id="micro" class="form-control" required min="0" value="0">
                                </div>
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

                        <div class="form-group form-check">
                            <input type="checkbox" name="tested" id="tested" class="form-check-input" value="1">
                            <label for="tested" class="form-check-label">Mark as tested</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary" id="uploadSubmitBtn">Загрузить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования модуля -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактирование модуля</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editModuleForm" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="content_id" value="{{ $content->id }}">
                    <input type="hidden" name="module_id" id="edit_module_id">

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_module_alias">Alias *</label>
                                    <input type="text" class="form-control" id="edit_module_alias" name="alias" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_module_default_name">Название по умолчанию *</label>
                                    <input type="text" class="form-control" id="edit_module_default_name" name="default_name" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_module_type">Тип модуля *</label>
                            <select class="form-control" id="edit_module_type" name="type" required>
                                <option value="">Выберите тип</option>
                                <option value="0">Демонстрация</option>
                                <option value="1">Атлас</option>
                                <option value="2">Квиз</option>
                            </select>
                        </div>

                        <div class="card card-secondary">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0">Локализации</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="edit_addLocalizationRow">
                                    <i class="fas fa-plus"></i> Добавить локаль
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="edit_localizationsTable">
                                        <thead>
                                        <tr>
                                            <th width="150">Локаль</th>
                                            <th>Название *</th>
                                            <th>Описание</th>
                                            <th width="50">Действия</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Строки будут добавляться динамически -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        <button type="button" class="btn btn-danger" id="deleteModuleBtn">
                            <i class="fas fa-trash"></i> Удалить модуль
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Форма для удаления модуля -->
    <form id="deleteModuleForm" method="POST" class="d-none">
        @csrf @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        // Имя файла в input
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Обработка отправки формы с прогрессом
        document.addEventListener('DOMContentLoaded', function() {
            const uploadForm = document.getElementById('uploadVersionForm');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const submitBtn = document.getElementById('uploadSubmitBtn');
                    const originalText = submitBtn.innerHTML;

                    // Индикатор загрузки
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

                    const formData = new FormData(this);

                    // CSRF токен
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || document.querySelector('input[name="_token"]')?.value;

                    if (!csrfToken) {
                        alert('CSRF token not found');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        return;
                    }

                    // Заголовок для JSON response
                    formData.append('ajax', '1');

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                        .then(response => {
                            // Проверяем Content-Type заголовок
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return response.json();
                            } else {
                                throw new Error('Server returned HTML instead of JSON');
                            }
                        })
                        .then(data => {
                            if (data.success) {
                                $('#uploadVersionModal').modal('hide');
                                alert(data.message || 'Version uploaded successfully!');
                                setTimeout(() => {
                                    location.reload();
                                }, 1000);
                            } else {
                                alert(data.message || 'Upload failed');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred during upload. Please check console for details.');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }
        });
    </script>
    <script>
        // Глобальные переменные
        const locales = ['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'];
        const localeNames = {
            'ru': 'Русский', 'en': 'English', 'ar': 'العربية',
            'zh': '中文', 'fr': 'Français', 'de': 'Deutsch', 'es': 'Español'
        };

        let usedLocales = new Set();
        let editUsedLocales = new Set();

        $(document).ready(function() {
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
                    <select class="form-control form-control-sm locale-select" name="locales[]" required
                            data-row-id="${rowId}">
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
                    <button type="button" class="btn btn-danger btn-sm remove-create-row"
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
                    $(`#row-${rowId} .locale-select`).data('old-value', locale);
                }

                // Обновляем обработчики событий
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
                const currentUsedLocales = new Set();

                // Сначала собираем все используемые локали
                $('.locale-select').each(function() {
                    const value = $(this).val();
                    if (value) {
                        currentUsedLocales.add(value);
                    }
                });

                // Обновляем disabled состояния
                $('.locale-select').each(function() {
                    const currentValue = $(this).val();
                    $(this).find('option').each(function() {
                        const optionValue = $(this).val();
                        if (optionValue && optionValue !== currentValue && currentUsedLocales.has(optionValue)) {
                            $(this).prop('disabled', true);
                        } else {
                            $(this).prop('disabled', false);
                        }
                    });
                });

                usedLocales = currentUsedLocales;
            }

            // Добавляем строки для всех локалей по умолчанию
            locales.forEach(locale => {
                addLocalizationRow(locale, '', '');
            });

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

            // Обработчик удаления строки в создании
            $(document).on('click', '.remove-create-row', function() {
                const rowId = $(this).data('row-id');
                const locale = $(this).data('locale');
                removeLocalizationRow(rowId, locale);
            });

            // Обработчик отправки формы
            $('#createModuleForm').on('submit', function(e) {
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
                const formData = $(this).serialize();

                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#createModuleModal').modal('hide');
                            alert(response.message);
                            location.reload();
                        } else {
                            alert('Ошибка: ' + (response.message || 'Неизвестная ошибка'));
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Ошибка сервера';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert('Ошибка: ' + errorMessage);
                    }
                });
            });

            // Очистка формы при закрытии модального окна
            $('#createModuleModal').on('hidden.bs.modal', function() {
                $('#createModuleForm')[0].reset();
                $('#localizationsTable tbody').empty();
                usedLocales.clear();
                locales.forEach(locale => {
                    addLocalizationRow(locale, '', '');
                });
            });
        });

        // Функция для добавления строки локализации в редактирование
        function addEditLocalizationRow(locale = '', name = '', description = '') {
            const tbody = $('#edit_localizationsTable tbody');
            const rowId = Date.now();

            let options = '<option value="">Выберите локаль</option>';
            locales.forEach(loc => {
                const selected = loc === locale ? 'selected' : '';
                const disabled = editUsedLocales.has(loc) && loc !== locale ? 'disabled' : '';
                options += `<option value="${loc}" ${selected} ${disabled}>${localeNames[loc]} (${loc})</option>`;
            });

            const row = `
        <tr id="edit_row-${rowId}">
            <td>
                <select class="form-control form-control-sm edit-locale-select" name="locales[]" required
                        data-row-id="${rowId}">
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

            if (locale) {
                editUsedLocales.add(locale);
                $(`#edit_row-${rowId} .edit-locale-select`).data('old-value', locale);
            }

            updateEditLocaleSelects();
        }

        // Функция для удаления строки в редактировании
        function removeEditLocalizationRow(rowId, locale) {
            $(`#edit_row-${rowId}`).remove();
            if (locale) {
                editUsedLocales.delete(locale);
            }
            updateEditLocaleSelects();
        }

        // Функция для обновления select'ов в редактировании
        function updateEditLocaleSelects() {
            const currentUsedLocales = new Set();

            $('.edit-locale-select').each(function() {
                const value = $(this).val();
                if (value) {
                    currentUsedLocales.add(value);
                }
            });

            $('.edit-locale-select').each(function() {
                const currentValue = $(this).val();
                $(this).find('option').each(function() {
                    const optionValue = $(this).val();
                    if (optionValue && optionValue !== currentValue && currentUsedLocales.has(optionValue)) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }
                });
            });

            editUsedLocales = currentUsedLocales;
        }

        // Функция для открытия модального окна редактирования
        function openEditModal(moduleId) {
            // Загружаем данные модуля
            $.ajax({
                url: `/admin/modules/${moduleId}/edit-data`,
                type: 'GET',
                success: function(response) {
                    // Заполняем форму
                    $('#edit_module_id').val(response.id);
                    $('#edit_module_alias').val(response.alias);
                    $('#edit_module_default_name').val(response.default_name);
                    $('#edit_module_type').val(response.type);

                    // Очищаем таблицу локализаций
                    $('#edit_localizationsTable tbody').empty();
                    editUsedLocales.clear();

                    // Заполняем локализации
                    response.localized_strings.forEach(loc => {
                        if (loc.type === 'name') {
                            const description = response.localized_strings.find(d =>
                                d.type === 'description' && d.locale === loc.locale
                            )?.value || '';

                            addEditLocalizationRow(loc.locale, loc.value, description);
                        }
                    });

                    // Открываем модальное окно
                    $('#editModuleModal').modal('show');
                },
                error: function() {
                    alert('Ошибка загрузки данных модуля');
                }
            });
        }

        // Обработчики для редактирования
        $(document).on('change', '.edit-locale-select', function() {
            const oldValue = $(this).data('old-value');
            const newValue = $(this).val();

            if (oldValue) {
                editUsedLocales.delete(oldValue);
            }
            if (newValue) {
                editUsedLocales.add(newValue);
            }

            $(this).data('old-value', newValue);
            updateEditLocaleSelects();
        });

        $(document).on('click', '.remove-edit-row', function() {
            const rowId = $(this).data('row-id');
            const locale = $(this).data('locale');
            removeEditLocalizationRow(rowId, locale);
        });

        $('#edit_addLocalizationRow').click(function() {
            addEditLocalizationRow('', '', '');
        });

        // Обработчик отправки формы редактирования
        $('#editModuleForm').on('submit', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const moduleId = $('#edit_module_id').val();

            $.ajax({
                url: `/admin/modules/${moduleId}`,
                type: 'POST',
                data: formData + '&_method=PUT',
                success: function(response) {
                    $('#editModuleModal').modal('hide');
                    alert('Модуль успешно обновлен!');
                    location.reload();
                },
                error: function(xhr) {
                    alert('Ошибка: ' + (xhr.responseJSON?.message || 'Неизвестная ошибка'));
                }
            });
        });

        // Обработчик удаления модуля
        $('#deleteModuleBtn').click(function() {
            if (confirm('Удалить модуль полностью?')) {
                const moduleId = $('#edit_module_id').val();
                $('#deleteModuleForm').attr('action', `/admin/modules/${moduleId}`);
                $('#deleteModuleForm').submit();
            }
        });

        // Очистка формы редактирования при закрытии
        $('#editModuleModal').on('hidden.bs.modal', function() {
            $('#editModuleForm')[0].reset();
            $('#edit_localizationsTable tbody').empty();
            editUsedLocales.clear();
        });
    </script>
@endpush
