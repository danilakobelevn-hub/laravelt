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

                <div class="form-group">
                    <label for="access_type">Access Type</label>
                    <input type="number" class="form-control @error('access_type') is-invalid @enderror"
                           id="access_type" name="access_type"
                           value="{{ old('access_type', 0) }}" min="0" max="255">
                    @error('access_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Локализации -->
                <div class="card card-secondary mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Локализации *</h3>
                    </div>
                    <div class="card-body">
                        <x-admin.localizations-table
                            :editable="true"
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
                        return false; // break loop
                    }
                });

                if (!hasValidLocalization) {
                    e.preventDefault();
                    alert('Пожалуйста, заполните хотя бы одну локализацию (язык и название)');
                }
            });
        });
    </script>
@endpush
