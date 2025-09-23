@extends('admin.layouts.app')

@section('title', $module->default_name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Модули</a></li>
    <li class="breadcrumb-item active">{{ $module->default_name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card card-primary module-card"
                 onclick="openEditModal({{ $module->id }})"
                 style="cursor: pointer;">
                <div class="card-header">
                    <h3 class="card-title">Информация о модуле</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.modules.edit', $module) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Редактировать
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-table">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">ID:</th>
                                <td>{{ $module->id }}</td>
                            </tr>
                            <tr>
                                <th>GUID:</th>
                                <td><code>{{ $module->guid }}</code></td>
                            </tr>
                            <tr>
                                <th>Название:</th>
                                <td><strong>{{ $module->default_name }}</strong></td>
                            </tr>
                            <tr>
                                <th>Alias:</th>
                                <td><code>{{ $module->alias }}</code></td>
                            </tr>
                            <tr>
                                <th>Тип:</th>
                                <td>
                                    <span class="badge badge-info">{{ $module->getTypeName() }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Создан:</th>
                                <td>{{ $module->created_at->format('d.m.Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Обновлен:</th>
                                <td>{{ $module->updated_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="card card-secondary mt-4">
                        <div class="card-header">
                            <h4 class="card-title">Локализации</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th>Язык</th>
                                        <th>Название</th>
                                        <th>Описание</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($locales as $locale)
                                        @php
                                            $name = $module->localizedStrings
                                                ->where('type', 'name')
                                                ->where('locale', $locale)
                                                ->first();
                                            $description = $module->localizedStrings
                                                ->where('type', 'description')
                                                ->where('locale', $locale)
                                                ->first();
                                        @endphp
                                        <tr>
                                            <td><strong>{{ strtoupper($locale) }}</strong></td>
                                            <td>{{ $name->value ?? 'Не указано' }}</td>
                                            <td>{{ $description->value ?? 'Не указано' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
