@extends('admin.layouts.app')

@section('title', 'Управление модулями')
@section('breadcrumb')
    <li class="breadcrumb-item active">Модули</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">Управление модулями</h3>
                <a href="{{ route('admin.modules.create') }}" class="btn btn-success">
                    <i class="fas fa-plus"></i> Добавить модуль
                </a>
            </div>
        </div>

        <div class="card-body">
            @if($modules->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Alias</th>
                            <th>Тип</th>
                            <th>GUID</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($modules as $module)
                            <tr>
                                <td>{{ $module->id }}</td>
                                <td>{{ $module->default_name }}</td>
                                <td><code>{{ $module->alias }}</code></td>
                                <td>
                                    <span class="badge badge-info">{{ $module->getTypeName() }}</span>
                                </td>
                                <td><small>{{ $module->guid }}</small></td>
                                <td>
                                    <a href="{{ route('admin.modules.show', $module) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.modules.edit', $module) }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.modules.destroy', $module) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Удалить модуль?')">
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
                    <i class="fas fa-cubes fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Модули не найдены</p>
                    <a href="{{ route('admin.modules.create') }}" class="btn btn-primary">
                        Создать первый модуль
                    </a>
                </div>
            @endif
        </div>

        @if($modules->hasPages())
            <div class="card-footer">
                {{ $modules->links() }}
            </div>
        @endif
    </div>
@endsection
