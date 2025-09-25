@extends('admin.layouts.app')

@section('title', 'Редактирование: ' . $content->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.show', $content) }}">{{ $content->default_name }}</a></li>
    <li class="breadcrumb-item active">Редактирование</li>
@endsection

@push('styles')
    <style>
        #sections-tree {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 200px;
        }
        .jstree-default .jstree-clicked {
            background: #007bff;
            color: white;
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Редактирование контента: {{ $content->default_name }}</h3>
        </div>

        <form action="{{ route('admin.contents.update', $content) }}" method="POST" id="contentForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="subsection_id" value="{{ $content->subsection_id }}">

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alias">Alias *</label>
                            <input type="text" class="form-control @error('alias') is-invalid @enderror"
                                   id="alias" name="alias" value="{{ old('alias', $content->alias) }}" required>
                            @error('alias')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_name">Название по умолчанию *</label>
                            <input type="text" class="form-control @error('default_name') is-invalid @enderror"
                                   id="default_name" name="default_name"
                                   value="{{ old('default_name', $content->default_name) }}" required>
                            @error('default_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Выберите раздел и подраздел *</label>
                    <div id="sections-tree"></div>
                    <small class="form-text text-muted">Текущий раздел: {{ $content->subsection->section->default_name }} → {{ $content->subsection->default_name }}</small>
                    @error('subsection_id')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="access_type">Access Type</label>
                    <input type="number" class="form-control @error('access_type') is-invalid @enderror"
                           id="access_type" name="access_type"
                           value="{{ old('access_type', $content->access_type) }}" min="0" max="255">
                    @error('access_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Локализации -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Локализации *</h3>
                    </div>
                    <div class="card-body">
                        <x-admin.localizations-table
                            :localizations="$content->localizedStrings"
                            :editable="true"
                            :model="$content"
                            modelType="content" />
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            * Необходимо заполнить хотя бы одну локаль. Пустые строки будут проигнорированы.
                        </small>
                    </div>
                </div>

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Медиа-файлы</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Изображения</label>
                            <input type="file" class="form-control @error('images') is-invalid @enderror"
                                   name="images[]" multiple accept="image/*">
                            <small class="form-text text-muted">Максимальный размер: 2MB. Форматы: JPEG, PNG, JPG, GIF, WebP</small>
                            @error('images')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('images.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <!-- Показываем существующие изображения -->
                            @if($content->imageLinks->count() > 0)
                                <div class="mt-3">
                                    <h6>Текущие изображения:</h6>
                                    @foreach($content->imageLinks as $imageLink)
                                        <div class="d-inline-block mr-2 mb-2 position-relative">
                                            <img src="{{ $imageLink->link }}" alt="Image" style="height: 60px; width: auto;" class="img-thumbnail">
                                            <button type="button" class="btn btn-danger btn-sm position-absolute"
                                                    style="top: -5px; right: -5px;"
                                                    onclick="deleteImage({{ $imageLink->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <br>
                                            <small>{{ basename($imageLink->link) }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>Видео</label>
                            <input type="file" class="form-control @error('videos') is-invalid @enderror"
                                   name="videos[]" multiple accept="video/*">
                            <small class="form-text text-muted">Максимальный размер: 10MB. Форматы: MP4, MOV</small>
                            @error('videos')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @error('videos.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <!-- Показываем существующие видео -->
                            @if($content->videoLinks->count() > 0)
                                <div class="mt-3">
                                    <h6>Текущие видео:</h6>
                                    @foreach($content->videoLinks as $videoLink)
                                        <div class="d-inline-block mr-2 mb-2">
                                            <i class="fas fa-video fa-2x"></i>
                                            <br>
                                            <small>{{ basename($videoLink->link) }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="{{ route('admin.contents.show', $content) }}" class="btn btn-default">Отмена</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Инициализация jsTree
            $('#sections-tree').jstree({
                'core': {
                    'data': {
                        'url': '{{ route("admin.contents.sections-tree") }}',
                        'dataType': 'json'
                    },
                    'themes': {
                        'variant': 'large'
                    }
                }
            }).on('loaded.jstree', function() {
                // Выделяем текущий подраздел при загрузке
                let currentSubsectionId = 'sub_{{ $content->subsection_id }}';
                $(this).jstree('select_node', currentSubsectionId);
            }).on('changed.jstree', function (e, data) {
                if (data.selected.length) {
                    let selectedNode = data.instance.get_node(data.selected[0]);
                    let nodeId = selectedNode.id;

                    if (nodeId.startsWith('sub_')) {
                        nodeId = nodeId.replace('sub_', '');
                        $('input[name="subsection_id"]').val(nodeId);
                    } else {
                        $('input[name="subsection_id"]').val('');
                    }
                }
            });

            // Валидация формы
            $('#contentForm').on('submit', function(e) {
                const subsectionId = $('input[name="subsection_id"]').val();
                if (!subsectionId) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите подраздел');
                    return;
                }

                // Проверяем, что заполнена хотя бы одна локализация
                let hasValidLocalization = false;
                $('select[name="locales[]"]').each(function() {
                    const locale = $(this).val();
                    const name = $(this).closest('tr').find('input[name="names[]"]').val().trim();
                    if (locale && name) {
                        hasValidLocalization = true;
                        return false;
                    }
                });

                if (!hasValidLocalization) {
                    e.preventDefault();
                    alert('Пожалуйста, заполните хотя бы одну локализацию (язык и название)');
                }
            });
        });

        // Функция удаления изображения
        function deleteImage(imageId) {
            if (confirm('Удалить это изображение?')) {
                // Правильное формирование URL с параметром
                fetch('{{ route("admin.content.images.delete", ":imageId") }}'.replace(':imageId', imageId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Ошибка при удалении изображения: ' + (data.error || 'Неизвестная ошибка'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ошибка при удалении изображения');
                    });
            }
        }
    </script>
@endpush
