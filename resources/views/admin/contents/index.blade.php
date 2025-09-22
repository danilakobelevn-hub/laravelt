@extends('admin.layouts.app')

@section('title', 'Управление контентом')
@section('breadcrumb', 'Список контента')

@push('styles')
    <style>
        .content-table tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .content-table tr:hover {
            background-color: #f8f9fa;
        }
        .content-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .sortable:hover {
            text-decoration: underline;
        }
        .module-badge {
            font-size: 0.75rem;
            margin: 2px;
        }
    </style>
@endpush

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="card-title">Управление контентом</h3>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-end">
                        <!-- Поиск -->
                        <form method="GET" action="{{ route('admin.contents.index') }}" class="mr-3">
                            <div class="input-group input-group-sm" style="width: 250px;">
                                <input type="text" name="search" class="form-control"
                                       placeholder="Поиск по названию или alias..."
                                       value="{{ request('search') }}">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Кнопка добавления -->
                        <a href="{{ route('admin.contents.create') }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Добавить контент
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped content-table">
                    <thead class="thead-dark">
                    <tr>
                        <th width="80">
                            <a href="{{ route('admin.contents.index', [
                                'sort' => 'id',
                                'direction' => $sortColumn == 'id' && $sortDirection == 'asc' ? 'desc' : 'asc',
                                'search' => request('search')
                            ]) }}" class="text-white sortable">
                                ID
                                @if($sortColumn == 'id')
                                    <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th width="80">Картинка</th>
                        <th>
                            <a href="{{ route('admin.contents.index', [
                                'sort' => 'default_name',
                                'direction' => $sortColumn == 'default_name' && $sortDirection == 'asc' ? 'desc' : 'asc',
                                'search' => request('search')
                            ]) }}" class="text-white sortable">
                                Название
                                @if($sortColumn == 'default_name')
                                    <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.contents.index', [
                                'sort' => 'alias',
                                'direction' => $sortColumn == 'alias' && $sortDirection == 'asc' ? 'desc' : 'asc',
                                'search' => request('search')
                            ]) }}" class="text-white sortable">
                                Псевдоним
                                @if($sortColumn == 'alias')
                                    <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.contents.index', [
                                'sort' => 'subsection_id',
                                'direction' => $sortColumn == 'subsection_id' && $sortDirection == 'asc' ? 'desc' : 'asc',
                                'search' => request('search')
                            ]) }}" class="text-white sortable">
                                Раздел
                                @if($sortColumn == 'subsection_id')
                                    <i class="fas fa-sort-{{ $sortDirection == 'asc' ? 'up' : 'down' }}"></i>
                                @else
                                    <i class="fas fa-sort"></i>
                                @endif
                            </a>
                        </th>
                        <th>Модули</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($contents as $content)
                        <tr onclick="window.location='{{ route('admin.contents.show', $content) }}'" style="cursor: pointer;">
                            <td class="align-middle">
                                <strong>{{ $content->id }}</strong>
                            </td>
                            <td class="align-middle">
                                @if($content->imageLinks->count() > 0)
                                    <img src="{{ $content->imageLinks->first()->link }}"
                                         alt="{{ $content->default_name }}"
                                         class="content-image img-thumbnail"
                                         onerror="this.src='/storage/empty.png'">
                                @else
                                    <img src="/storage/empty.png"
                                         alt="No image"
                                         class="content-image img-thumbnail">
                                @endif
                            </td>
                            <td class="align-middle">
                                <div class="font-weight-bold">{{ $content->default_name }}</div>
                                <small class="text-muted">
                                    Версий: {{ $content->versions_count }}
                                </small>
                            </td>
                            <td class="align-middle">
                                <code>{{ $content->alias }}</code>
                            </td>
                            <td class="align-middle">
                                @if($content->subsection && $content->subsection->section)
                                    <span class="badge badge-info">
                                    {{ $content->subsection->section->default_name }}
                                </span>
                                    →
                                    <span class="badge badge-secondary">
                                    {{ $content->subsection->default_name }}
                                </span>
                                @else
                                    <span class="text-danger">Не указан</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @if($content->modules->count() > 0)
                                    @foreach($content->modules as $module)
                                        <span class="badge badge-primary module-badge">
                                        {{ $module->alias }}
                                    </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Контент не найден</p>
                                @if(request('search'))
                                    <a href="{{ route('admin.contents.index') }}" class="btn btn-default">
                                        Сбросить поиск
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($contents->hasPages())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        Показано с {{ $contents->firstItem() }} по {{ $contents->lastItem() }} из {{ $contents->total() }} записей
                    </div>
                    <div>
                        {{ $contents->appends([
                            'sort' => $sortColumn,
                            'direction' => $sortDirection,
                            'search' => request('search')
                        ])->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Подсказка для пользователя
            const rows = document.querySelectorAll('.content-table tr[onclick]');
            if (rows.length > 0) {
                rows.forEach(row => {
                    row.title = "Кликните для просмотра деталей контента";
                });
            }
        });
    </script>
@endpush
