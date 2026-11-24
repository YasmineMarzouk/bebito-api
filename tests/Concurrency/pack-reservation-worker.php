<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CheckoutInventoryRepositoryInterface;
use Modules\Orders\Enums\OrderItemTypeEnum;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $barrierPath, $resultPath, $packVariantId, $quantity] = $argv;
$deadline = microtime(true) + 10;
while (! file_exists($barrierPath) && microtime(true) < $deadline) {
    usleep(10_000);
}

try {
    DB::transaction(function () use ($packVariantId, $quantity): void {
        app(CheckoutInventoryRepositoryInterface::class)->reserve([[
            'catalogItemType' => OrderItemTypeEnum::Pack->value,
            'variantId' => null,
            'packVariantId' => (int) $packVariantId,
            'quantity' => (int) $quantity,
        ]]);
    }, 3);

    file_put_contents($resultPath, json_encode(['success' => true], JSON_THROW_ON_ERROR));
} catch (Throwable $exception) {
    file_put_contents($resultPath, json_encode([
        'success' => false,
        'exception' => $exception::class,
    ], JSON_THROW_ON_ERROR));
}
