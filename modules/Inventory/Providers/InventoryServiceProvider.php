<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Inventory\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Repositories\InventoryMovementRepository;

final class InventoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryMovementRepositoryInterface::class, InventoryMovementRepository::class);
    }
}
