<?php

namespace Tests\Concurrency;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\InventoryItem;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreatesPackFixtures;
use Tests\TestCase;

class PackCheckoutContentionTest extends TestCase
{
    use CreatesPackFixtures;
    use DatabaseMigrations;

    public function test_two_independent_customers_competing_for_the_last_pack_cannot_oversell(): void
    {
        if (getenv('PACK_CONCURRENCY_ENABLED') !== '1') {
            $this->markTestSkipped('Set PACK_CONCURRENCY_ENABLED=1 for a dedicated MySQL test database.');
        }

        $connectionName = config('database.default');
        if (DB::connection($connectionName)->getDriverName() !== 'mysql') {
            $this->markTestSkipped('This test requires MySQL row-locking semantics; SQLite is not a substitute.');
        }

        $first = $this->createSellableVariant('CON-FIRST', 1, 10000);
        $second = $this->createSellableVariant('CON-SECOND', 1, 5000);
        $fixture = $this->createSellablePack('BPK-CON', 20000, [
            ['variant' => $first, 'quantity' => 1],
            ['variant' => $second, 'quantity' => 1],
        ]);
        $barrierPath = tempnam(sys_get_temp_dir(), 'pack-barrier-');
        $resultPaths = [
            tempnam(sys_get_temp_dir(), 'pack-result-'),
            tempnam(sys_get_temp_dir(), 'pack-result-'),
        ];
        if ($barrierPath === false || in_array(false, $resultPaths, true)) {
            throw new \RuntimeException('Unable to create Pack concurrency coordination files.');
        }
        unlink($barrierPath);

        try {
            $workers = array_map(
                fn (string $resultPath): Process => new Process([
                    PHP_BINARY,
                    base_path('tests/Concurrency/pack-reservation-worker.php'),
                    $barrierPath,
                    $resultPath,
                    (string) $fixture['variant']->id,
                    '1',
                ], base_path(), $this->workerEnvironment($connectionName)),
                $resultPaths,
            );
            foreach ($workers as $worker) {
                $worker->start();
            }
            file_put_contents($barrierPath, 'go');
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput());
            }

            $results = array_map(
                fn (string $resultPath): array => json_decode((string) file_get_contents($resultPath), true, 512, JSON_THROW_ON_ERROR),
                $resultPaths,
            );
            $this->assertSame(1, collect($results)->where('success', true)->count());
            $this->assertSame(0, InventoryItem::query()->where('product_variant_id', $first->id)->value('quantity'));
            $this->assertSame(0, InventoryItem::query()->where('product_variant_id', $second->id)->value('quantity'));
            $this->assertSame(0, InventoryItem::query()->where('quantity', '<', 0)->count());
        } finally {
            foreach ([$barrierPath, ...$resultPaths] as $path) {
                if ($path !== false && file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }

    /** @return array<string, string> */
    private function workerEnvironment(string $connectionName): array
    {
        /** @var array<string, mixed> $connection */
        $connection = config("database.connections.{$connectionName}");

        return [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => $connectionName,
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'],
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
        ];
    }
}
