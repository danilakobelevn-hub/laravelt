<?php

namespace App\Repositories;

use App\Models\Content;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContentRepository
{
    public function __construct(private Content $model) {}

    public function getPaginatedWithRelations(array $relations = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage);
    }

    public function search(string $search, array $relations = []): Builder
    {
        return $this->model->with($relations)
            ->where(function($query) use ($search) {
                $query->where('default_name', 'like', "%{$search}%")
                    ->orWhere('alias', 'like', "%{$search}%");
            });
    }

    public function getBaseQueryWithRelations(array $relations = []): Builder
    {
        return $this->model->with($relations);
    }

    public function findWithRelations(int $id, array $relations = []): ?Content
    {
        return $this->model->with($relations)->find($id);
    }

    public function create(array $data): Content
    {
        return $this->model->create($data);
    }

    public function update(Content $content, array $data): bool
    {
        return $content->update($data);
    }

    public function delete(Content $content): bool
    {
        return $content->delete();
    }

    public function forceDelete(Content $content): bool
    {
        return $content->forceDelete();
    }

    public function getTrashed(): LengthAwarePaginator
    {
        return $this->model->onlyTrashed()
            ->with(['subsection.section'])
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);
    }

    public function restore(int $id): bool
    {
        $content = $this->model->withTrashed()->find($id);
        return $content ? $content->restore() : false;
    }
}
