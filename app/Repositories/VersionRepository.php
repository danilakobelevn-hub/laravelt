<?php


namespace App\Repositories;

use App\Models\Version;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VersionRepository
{
    public function __construct(private Version $model)
    {
    }

    public function getPlatformVersions(int $contentId, string $platform, array $sort = []): LengthAwarePaginator
    {
        $query = $this->model->where('content_id', $contentId)
            ->where('platform', $platform);

        if (!empty($sort)) {
            $query = $this->applySorting($query, $sort);
        }

        return $query->paginate(20);
    }

    public function findVersion(int $contentId, string $platform, int $major, int $minor, int $micro): ?Version
    {
        return $this->model->where('content_id', $contentId)
            ->where('platform', $platform)
            ->where('major', $major)
            ->where('minor', $minor)
            ->where('micro', $micro)
            ->first();
    }

    private function applySorting(Builder $query, array $sort): Builder
    {
        $allowedSortColumns = ['id', 'major', 'release_note', 'tested', 'file_size', 'created_at'];

        if (in_array($sort['column'], $allowedSortColumns)) {
            if ($sort['column'] === 'major') {
                return $query->orderBy('major', $sort['direction'])
                    ->orderBy('minor', $sort['direction'])
                    ->orderBy('micro', $sort['direction']);
            }
            return $query->orderBy($sort['column'], $sort['direction']);
        }

        return $query->orderBy('id', 'desc');
    }
}
