@extends('admin.layouts.app')

@section('title', 'Управление контентом')
@section('breadcrumb', 'Список контента')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Весь контент</h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('admin.contents.create') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Добавить контент
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Alias</th>
                    <th>Раздел</th>
                    <th>Версии</th>
                    <th>Создан</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                @foreach($contents as $content)
                    <tr>
                        <td>{{ $content->id }}</td>
                        <td>{{ $content->default_name }}</td>
                        <td>{{ $content->alias }}</td>
                        <td>{{ $content->subsection->section->default_name }} → {{ $content->subsection->default_name }}</td>
                        <td>{{ $content->versions_count }}</td>
                        <td>{{ $content->created_at->format('d.m.Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.contents.show', $content) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.contents.edit', $content) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.contents.destroy', $content) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $contents->links() }}
        </div>
    </div>
@endsection
