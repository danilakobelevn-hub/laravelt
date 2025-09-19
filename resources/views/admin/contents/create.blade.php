@extends('admin.layouts.app')

@section('title', 'Создание контента')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item active">Создание</li>
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
            <h3 class="card-title">Создание нового контента</h3>
        </div>

        <form action="{{ route('admin.contents.store') }}" method="POST" id="contentForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="subsection_id" value="">

            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alias">Alias *</label>
                            <input type="text" class="form-control @error('alias') is-invalid @enderror"
                                   id="alias" name="alias" value="{{ old('alias') }}" required>
                            <small class="form-text text-muted">Уникальный идентификатор (только латиница, цифры, дефисы)</small>
                            @error('alias')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_name">Название по умолчанию *</label>
                            <input type="text" class="form-control @error('default_name') is-invalid @enderror"
                                   id="default_name" name="default_name" value="{{ old('default_name') }}" required>
                            @error('default_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="form-group">
                    <label>Выберите раздел и подраздел *</label>
                    <div id="sections-tree"></div>
                    <small class="form-text text-muted">Выберите подраздел для контента</small>
                    @error('subsection_id')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Локализации</h3>
                    </div>
                    <div class="card-body">
                        @foreach($locales as $locale)
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Название ({{ strtoupper($locale) }}) *</label>
                                        <input type="text" class="form-control @error('names.'.$locale) is-invalid @enderror"
                                               name="names[{{ $locale }}]"
                                               value="{{ old('names.'.$locale) }}" required>
                                        @error('names.'.$locale)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Описание ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control @error('descriptions.'.$locale) is-invalid @enderror"
                                                  name="descriptions[{{ $locale }}]">{{ old('descriptions.'.$locale) }}</textarea>
                                        @error('descriptions.'.$locale)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>


                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="access_type">Access Type</label>
                            <input type="number" class="form-control @error('access_type') is-invalid @enderror"
                                   id="access_type" name="access_type"
                                   value="{{ old('access_type', 0) }}" min="0" max="255">
                            @error('access_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Доступные языки *</label>
                            <select class="form-control select2 @error('available_locales') is-invalid @enderror"
                                    name="available_locales[]" multiple required>
                                @foreach($locales as $locale)
                                    <option value="{{ $locale }}"
                                        {{ in_array($locale, old('available_locales', [])) ? 'selected' : '' }}>
                                        {{ strtoupper($locale) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('available_locales')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="form-group">
                    <label>Модули</label>
                    <select class="form-control select2 @error('modules') is-invalid @enderror"
                            name="modules[]" multiple>
                        @foreach($modules as $module)
                            <option value="{{ $module->id }}"
                                {{ in_array($module->id, old('modules', [])) ? 'selected' : '' }}>
                                {{ $module->default_name }} ({{ $module->alias }})
                            </option>
                        @endforeach
                    </select>
                    @error('modules')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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

                            <!-- Показываем существующие изображения при редактировании -->
                            @if(isset($content) && $content->imageLinks->count() > 0)
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

                            <!-- Показываем существующие видео при редактировании -->
                            @if(isset($content) && $content->videoLinks->count() > 0)
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
                <button type="submit" class="btn btn-primary">Создать контент</button>
                <a href="{{ route('admin.contents.index') }}" class="btn btn-default">Отмена</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Инициализация Select2
            $('.select2').select2({
                theme: 'bootstrap4'
            });

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
                if (!$('input[name="subsection_id"]').val()) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите подраздел');
                }
            });
        });
    </script>
@endpush
@push('scripts')
    <script>
        // Предпросмотр изображений перед загрузкой
        document.addEventListener('DOMContentLoaded', function() {
            // Для изображений
            const imageInput = document.querySelector('input[name="images[]"]');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    previewFiles(e.target.files, 'images');
                });
            }

            // Для видео
            const videoInput = document.querySelector('input[name="videos[]"]');
            if (videoInput) {
                videoInput.addEventListener('change', function(e) {
                    previewFiles(e.target.files, 'videos');
                });
            }

            function previewFiles(files, type) {
                const previewContainer = document.getElementById(type + '-preview') || createPreviewContainer(type);

                // Очищаем предыдущий предпросмотр
                previewContainer.innerHTML = '';

                if (files.length > 0) {
                    const title = document.createElement('h6');
                    title.textContent = 'Новые файлы:';
                    title.className = 'mt-3';
                    previewContainer.appendChild(title);
                }

                Array.from(files).forEach(file => {
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'd-inline-block mr-2 mb-2 text-center';

                    if (type === 'images' && file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.style.height = '60px';
                        img.style.width = 'auto';
                        img.className = 'img-thumbnail';
                        fileDiv.appendChild(img);
                    } else {
                        const icon = document.createElement('i');
                        icon.className = type === 'images' ? 'fas fa-image fa-2x' : 'fas fa-video fa-2x';
                        fileDiv.appendChild(icon);
                    }

                    const name = document.createElement('div');
                    name.textContent = file.name;
                    name.style.fontSize = '12px';
                    name.className = 'text-truncate';
                    name.style.maxWidth = '80px';
                    fileDiv.appendChild(name);

                    previewContainer.appendChild(fileDiv);
                });
            }

            function createPreviewContainer(type) {
                const container = document.createElement('div');
                container.id = type + '-preview';
                const input = document.querySelector(`input[name="${type}[]"]`);
                input.parentNode.appendChild(container);
                return container;
            }
        });
    </script>
@endpush
