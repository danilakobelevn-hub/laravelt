<?php

namespace App\Providers;

use App\Repositories\ContentRepository;
use App\Repositories\TreeRepository;
use App\Repositories\VersionRepository;
use App\Services\ContentService;
use App\Services\FileService;
use App\Services\VersionService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Репозитории
        $this->app->bind(ContentRepository::class, function ($app) {
            return new ContentRepository($app->make(\App\Models\Content::class));
        });

        $this->app->bind(VersionRepository::class, function ($app) {
            return new VersionRepository($app->make(\App\Models\Version::class));
        });

        $this->app->bind(TreeRepository::class, function ($app) {
            return new TreeRepository($app->make(\App\Models\Section::class));
        });

        // Сервисы
        $this->app->bind(ContentService::class, function ($app) {
            return new ContentService(
                $app->make(ContentRepository::class),
                $app->make(FileService::class)
            );
        });

        $this->app->bind(VersionService::class, function ($app) {
            return new VersionService(
                $app->make(VersionRepository::class),
                $app->make(FileService::class)
            );
        });

        $this->app->bind(FileService::class, function ($app) {
            return new FileService();
        });
    }

    public function boot(): void
    {
        //
    }
}
