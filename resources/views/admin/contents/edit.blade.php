@extends('admin.layouts.app')

@section('title', 'Редактирование: ' . $content->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.show', $content) }}">{{ $content->default_name }}</a></li>
    <li class="breadcrumb-item active">Редактирование</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Редактирование контента: {{ $content->default_name }}</h3>
        </div>

        <form action="{{ route('admin.contents.update', $content) }}" method="POST">
            @csrf @method('PUT')

            <div class="card-body">
                <!-- Основная информация -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alias">Alias *</label>
                            <input type="text" class="form-control" id="alias" name="alias"
                                   value="{{ old('alias', $content->alias) }}" required>
                            <small class="form-text text-muted">Уникальный идентификатор</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="default_name">Название по умолчанию *</label>
                            <input type="text" class="form-control" id="default_name" name="default_name"
                                   value="{{ old('default_name', $content->default_name) }}" required>
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
                                    <option value="{{ $section->id }}"
                                        {{ $section->id == $content->subsection->section_id ? 'selected' : '' }}>
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
                                <option value="{{ $content->subsection_id }}" selected>
                                    {{ $content->subsection->default_name }}
                                </option>
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Название ({{ strtoupper($locale) }}) *</label>
                                        <input type="text" class="form-control"
                                               name="names[{{ $locale }}]"
                                               value="{{ old("names.{$locale}", $name->value ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Описание ({{ strtoupper($locale) }})</label>
                                        <textarea class="form-control"
                                                  name="descriptions[{{ $locale }}]">{{ old("descriptions.{$locale}", $description->value ?? '') }}</textarea>
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
                                   value="{{ old('access_type', $content->access_type) }}" min="0" max="255">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Доступные языки *</label>
                            <select class="form-control select2" name="available_locales[]" multiple required>
                                @foreach($locales as $locale)
                                    <option value="{{ $locale }}"
                                        {{ $content->availableLocales->contains('locale', $locale) ? 'selected' : '' }}>
                                        {{ strtoupper($locale) }}
                                    </option>
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
                            <option value="{{ $module->id }}"
                                {{ $content->modules->contains($module->id) ? 'selected' : '' }}>
                                {{ $module->default_name }} ({{ $module->alias }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Ссылки на медиа -->
                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Медиа-ссылки</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Ссылки на изображения</label>
                            <textarea class="form-control" name="image_links" placeholder="По одной ссылке на строку">@foreach($content->imageLinks as $link){{ $link->link }}{{ !$loop->last ? "\n" : '' }}@endforeach</textarea>
                        </div>
                        <div class="form-group">
                            <label>Ссылки на видео</label>
                            <textarea class="form-control" name="video_links" placeholder="По одной ссылке на строку">@foreach($content->videoLinks as $link){{ $link->link }}{{ !$loop->last ? "\n" : '' }}@endforeach</textarea>
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
            // Инициализация Select2
            $('.select2').select2();

            // Динамическая загрузка подразделов
            $('#section_id').change(function() {
                var sectionId = $(this).val();
                $('#subsection_id').html('<option value="">Загрузка...</option>');

                if (sectionId) {
                    $.get('{{ url("/admin/subsections-by-section") }}/' + sectionId, function(data) {
                        $('#subsection_id').html('<option value="">Выберите подраздел</option>');
                        $.each(data, function(key, value) {
                            $('#subsection_id').append('<option value="'+ key +'">'+ value +'</option>');
                        });
                    }).fail(function() {
                        $('#subsection_id').html('<option value="">Ошибка загрузки</option>');
                    });
                } else {
                    $('#subsection_id').html('<option value="">Сначала выберите раздел</option>');
                }
            });
        });
    </script>
@endpush
