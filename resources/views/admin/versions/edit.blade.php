@extends('admin.layouts.app')

@section('title', 'Редактирование версии')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.show', $version->content_id) }}">{{ $version->content->default_name }}</a></li>
    <li class="breadcrumb-item active">Редактирование версии</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Редактирование версии: {{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}</h3>
        </div>

        <form action="{{ route('admin.versions.update', $version) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Платформа</label>
                            <input type="text" class="form-control" value="{{ $version->platform }}" disabled>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Версия</label>
                            <input type="text" class="form-control" value="{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}" disabled>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="release_note">Release Note</label>
                    <textarea class="form-control" id="release_note" name="release_note"
                              rows="4" placeholder="Что нового в этой версии?">{{ old('release_note', $version->release_note) }}</textarea>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="tested" name="tested"
                               value="1" {{ $version->tested ? 'checked' : '' }}>
                        <label class="form-check-label" for="tested">Проверено (тестирование пройдено)</label>
                    </div>
                </div>

                <div class="card card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">Информация о файле</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Имя файла:</strong> {{ $version->file_name }}</p>
                        <p><strong>Размер:</strong> {{ number_format($version->file_size / 1024 / 1024, 2) }} MB</p>
                        <p><strong>Загружен:</strong> {{ $version->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                <a href="{{ route('admin.contents.show', $version->content_id) }}" class="btn btn-default">Отмена</a>
            </div>
        </form>
    </div>
@endsection
