@extends('admin.layouts.app')

@section('title', 'Создание контента')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item active">Создание</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Создание нового контента</h3>
        </div>

        <form action="{{ route('admin.contents.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <!-- Основная информация -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alias">Alias *</label>
                            <input type="text" class="form-control" id="alias" name="alias"
                                   value="{{ old('alias') }}" required>
                            <small class="form-text text-muted">Уникальный идентификатор (только латиница, цифры, дефисы)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_name">Название по умолчанию *</label>
                            <input type="text" class="form-control" id="default_name" name="default_name"
                                   value="{{ old('default_name') }}" required>
                        </div>
                    </div>
                </div>

                <!-- Выбор раздела и подраздела -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="section_id">Раздел *</label>
                            <select class="form-control select2" id="section_id" name="section_id" required>
                                <option value="">Выберите раздел</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                        {{ $section->default_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subsection_id">Подраздел *</label>
                            <select class="form-control select2" id="subsection_id" name="subsection_id" required>
                                <option value="">Сначала выберите раздел</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Локализации -->
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
                                        <input type="text" class="form-control"
                                               name="names[{{ $locale }}]"
                                               value="{{ old("names.{$locale}") }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Описание ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control"
                                                  name="descriptions[{{ $locale }}]">{{ old("descriptions.{$locale}") }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Дополнительные поля -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="access_type">Access Type</label>
                            <input type="number" class="form-control" id="access_type" name="access_type"
                                   value="{{ old('access_type', 0) }}" min="0" max="255">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Доступные языки *</label>
                            <select class="form-control select2" name="available_locales[]" multiple required>
                                @foreach($locales as $locale)
                                    <option value="{{ $locale }}" selected>{{ strtoupper($locale) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Модули -->
                <div class="form-group">
                    <label>Модули</label>
                    <select class="form-control select2" name="modules[]" multiple>
                        @foreach($modules as $module)
                            <option value="{{ $module->id }}">{{ $module->default_name }} ({{ $module->alias }})</option>
                        @endforeach
                    </select>
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
        $('#section_id').change(function() {
            var sectionId = $(this).val();
            $('#subsection_id').html('<option value="">Загрузка...</option>');

            if (sectionId) {
                // ИСПРАВЛЕННЫЙ URL - без префикса /admin в пути
                $.get('/admin/subsections-by-section/' + sectionId, function(data) {
                    $('#subsection_id').html('<option value="">Выберите подраздел</option>');
                    $.each(data, function(key, value) {
                        $('#subsection_id').append('<option value="'+ key +'">'+ value +'</option>');
                    });
                }).fail(function(xhr, status, error) {
                    console.error('Error loading subsections:', error);
                    $('#subsection_id').html('<option value="">Ошибка загрузки: ' + xhr.status + '</option>');
                });
            } else {
                $('#subsection_id').html('<option value="">Сначала выберите раздел</option>');
            }
        });
    </script>
@endpush
