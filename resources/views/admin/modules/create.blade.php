@extends('admin.layouts.app')

@section('title', 'Создание модуля')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">Модули</a></li>
    <li class="breadcrumb-item active">Создание</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Создание нового модуля</h3>
        </div>

        <form action="{{ route('admin.modules.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alias">Alias *</label>
                            <input type="text" class="form-control @error('alias') is-invalid @enderror"
                                   id="alias" name="alias" value="{{ old('alias') }}" required>
                            <small class="form-text text-muted">Уникальный идентификатор модуля</small>
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
                    <label for="type">Тип модуля *</label>
                    <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">Выберите тип</option>
                        @foreach($types as $key => $name)
                            <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Локализации -->
                <div class="card card-secondary mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Локализации</h3>
                    </div>
                    <div class="card-body">
                        <x-admin.localizations-table
                            :editable="true"
                            modelType="module" />
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Создать модуль</button>
                <a href="{{ route('admin.modules.index') }}" class="btn btn-default">Отмена</a>
            </div>
        </form>
    </div>
@endsection
