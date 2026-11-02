<?php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\FilesystemStorageServiceInterface;
use Modules\Core\Contracts\Services\TextTranslationServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\Repositories\AssetsRepository;
use Modules\Core\Services\DatabaseTransactionManager;
use Modules\Core\Services\DeepLTranslationService;
use Modules\Core\Services\FileStorageService;
use Modules\Core\Services\FilesystemStorageService;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssetsRepositoryInterface::class, AssetsRepository::class);
        $this->app->bind(FilesystemStorageServiceInterface::class, FilesystemStorageService::class);
        $this->app->bind(FileStorageServiceInterface::class, FileStorageService::class);
        $this->app->bind(TextTranslationServiceInterface::class, DeepLTranslationService::class);
        $this->app->bind(TransactionManagerInterface::class, DatabaseTransactionManager::class);
    }
}
