<?php

namespace Tests\Unit\Core;

use Illuminate\Http\UploadedFile;
use Mockery;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Modules\Core\Contracts\Services\FilesystemStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;
use Modules\Core\Models\Asset;
use Modules\Core\Services\FileStorageService;
use Tests\TestCase;

class FileStorageServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_stores_a_file_and_creates_its_asset_record(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getClientOriginalName')->once()->andReturn('onesie.jpg');
        $file->shouldReceive('getMimeType')->once()->andReturn('image/jpeg');
        $file->shouldReceive('getSize')->once()->andReturn(1024);

        $filesystem = Mockery::mock(FilesystemStorageServiceInterface::class);
        $filesystem->shouldReceive('store')->once()->with($file, 'products', 'public')->andReturn('products/uuid.jpg');

        $asset = new Asset(['id' => 1, 'path' => 'products/uuid.jpg']);
        $assets = Mockery::mock(AssetsRepositoryInterface::class);
        $assets->shouldReceive('create')->once()->with([
            'resource_type' => 'product',
            'resource_id' => 9,
            'disk' => 'public',
            'path' => 'products/uuid.jpg',
            'original_name' => 'onesie.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'sort_order' => 2,
            'is_primary' => true,
        ])->andReturn($asset);

        $result = (new FileStorageService($filesystem, $assets))->store(
            $file,
            new StoreAssetInput('product', 9, 'products', 'public', 2, true),
        );

        self::assertSame($asset, $result);
    }

    public function test_it_deletes_the_file_before_removing_its_asset_record(): void
    {
        $asset = new Asset(['disk' => 'public', 'path' => 'products/uuid.jpg']);
        $fileWasDeleted = false;

        $filesystem = Mockery::mock(FilesystemStorageServiceInterface::class);
        $filesystem->shouldReceive('delete')->once()->with('public', 'products/uuid.jpg')->andReturnUsing(
            function () use (&$fileWasDeleted): void {
                $fileWasDeleted = true;
            },
        );

        $assets = Mockery::mock(AssetsRepositoryInterface::class);
        $assets->shouldReceive('delete')->once()->with($asset)->andReturnUsing(
            function () use (&$fileWasDeleted): void {
                self::assertTrue($fileWasDeleted);
            },
        );

        (new FileStorageService($filesystem, $assets))->delete($asset);
    }

    public function test_it_resolves_an_asset_url_from_its_disk_and_path(): void
    {
        $asset = new Asset(['disk' => 'public', 'path' => 'products/uuid.jpg']);

        $filesystem = Mockery::mock(FilesystemStorageServiceInterface::class);
        $filesystem->shouldReceive('url')->once()->with('public', 'products/uuid.jpg')->andReturn('http://localhost:8000/storage/products/uuid.jpg');

        $assets = Mockery::mock(AssetsRepositoryInterface::class);

        $url = (new FileStorageService($filesystem, $assets))->url($asset);

        self::assertSame('http://localhost:8000/storage/products/uuid.jpg', $url);
    }
}
