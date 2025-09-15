@extends('admin.layouts.app')

@section('title', 'Корзина - Удаленные контенты')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.contents.index') }}">Контент</a></li>
    <li class="breadcrumb-item active">Корзина</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Удаленные контенты</h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('admin.contents.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> Назад к списку
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            @if($contents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Alias</th>
                            <th>Раздел</th>
                            <th>Удален</th>
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
                                <td>{{ $content->deleted_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <form action="{{ route('admin.contents.restore', $content->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" title="Восстановить">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.contents.force-destroy', $content->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('ВНИМАНИЕ! Это полностью удалит контент и все файлы. Продолжить?')"
                                                title="Удалить навсегда">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Корзина пуста</p>
                </div>
            @endif
        </div>

        @if($contents->count() > 0)
            <div class="card-footer">
                {{ $contents->links() }}
            </div>
        @endif
    </div>
@endsection
