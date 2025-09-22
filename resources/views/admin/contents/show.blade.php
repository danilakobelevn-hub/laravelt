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
                    <div class="row">
                        @foreach(['ru', 'en', 'ar', 'zh', 'fr', 'de', 'es'] as $locale)
                            <div class="col-md-6 mb-3">
                                <div class="card card-secondary">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ strtoupper($locale) }}</h4>
                                    </div>
                                    <div class="card-body">
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
                                        <p><strong>Название:</strong> {{ $name->value ?? 'Не указано' }}</p>
                                        <p><strong>Описание:</strong> {{ $description->value ?? 'Не указано' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Версии -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Версии ({{ $content->versions->count() }})</h3>
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#uploadVersionModal">
                        <i class="fas fa-upload"></i> Загрузить версию
                    </button>
                </div>
                <div class="card-body">
                    @if($content->versions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                <tr>
                                    <th>Platform</th>
                                    <th>Version</th>
                                    <th>Tested</th>
                                    <th>File</th>
                                    <th>Size</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($content->versions->sortByDesc('created_at') as $version)
                                    <tr>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ ucfirst($version->platform) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong>{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}</strong>
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
                                                    {{ $version->file_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">
                                                    <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                                    File not found
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($version->file_size)
                                                {{ number_format($version->file_size / 1024 / 1024, 2) }} MB
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.versions.edit', $version->id) }}"
                                                   class="btn btn-info" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <a href="{{ route('admin.versions.download', $version->id) }}"
                                                   class="btn btn-success" title="Download" download>
                                                    <i class="fas fa-download"></i>
                                                </a>

                                                <form action="{{ route('admin.versions.destroy', $version->id) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger"
                                                            onclick="return confirm('Are you sure you want to delete this version?')"
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            No versions uploaded yet. Click "Upload New Version" to add the first version.
                        </div>
                    @endif
                </div>
            </div>
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
@endpush
