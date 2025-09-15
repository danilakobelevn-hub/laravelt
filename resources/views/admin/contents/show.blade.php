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
                        <tr><th>Раздел:</th><td>{{ $content->getGroupPath() }}</td></tr>
                        <tr><th>Access Type:</th><td>{{ $content->access_type }}</td></tr>
                    </table>
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
                    @if($content->hasVersions())
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Версия</th>
                                <th>Платформа</th>
                                <th>Размер</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($content->versions as $version)
                                <tr>
                                    <td>{{ $version->major }}.{{ $version->minor }}.{{ $version->micro }}</td>
                                    <td>{{ $version->platform }}</td>
                                    <td>{{ number_format($version->file_size / 1024 / 1024, 2) }} MB</td>
                                    <td>
                                <span class="badge badge-{{ $version->tested ? 'success' : 'warning' }}">
                                    {{ $version->tested ? 'Проверено' : 'На проверке' }}
                                </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.versions.download', $version) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <form action="{{ route('admin.versions.destroy', $version) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить версию?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Нет загруженных версий</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Быстрые действия -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Действия</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.contents.destroy', $content) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Удалить контент?')">
                            <i class="fas fa-trash"></i> Удалить контент
                        </button>
                    </form>
                </div>
            </div>

            <!-- Статистика -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Статистика</h3>
                </div>
                <div class="card-body">
                    <p>Версий: <strong>{{ $content->versions->count() }}</strong></p>
                    <p>Модулей: <strong>{{ $content->modules->count() }}</strong></p>
                    <p>Создан: <strong>{{ $content->created_at->format('d.m.Y H:i') }}</strong></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно загрузки версии -->
    @include('admin.contents.modals.upload-version')
@endsection
