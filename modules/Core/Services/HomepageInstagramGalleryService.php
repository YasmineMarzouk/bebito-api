<?php

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;
use Modules\Core\Models\Asset;

final readonly class HomepageInstagramGalleryService
{
    private const string ResourceType = 'homepage_instagram_gallery';

    private const int ResourceId = 1;

    public function __construct(
        private FileStorageServiceInterface $files,
        private SettingsRepositoryInterface $settings,
    ) {}

    /** @return array{instagramUrl: ?string, images: array<int, array{id: int, position: int, url: string}>} */
    public function list(): array
    {
        $settings = $this->settings->values();

        return [
            'instagramUrl' => $settings['social.instagram'] ?? null,
            'images' => $this->assets()
                ->map(fn (Asset $asset): array => [
                    'id' => $asset->id,
                    'position' => (int) $asset->sort_order,
                    'url' => $this->files->url($asset),
                ])
                ->values()
                ->all(),
        ];
    }

    /** @param array<int, UploadedFile> $images @return array{instagramUrl: ?string, images: array<int, array{id: int, position: int, url: string}>} */
    public function replace(array $images): array
    {
        $replacedAssets = DB::transaction(function () use ($images): Collection {
            $existingAssets = Asset::query()
                ->where('resource_type', self::ResourceType)
                ->where('resource_id', self::ResourceId)
                ->lockForUpdate()
                ->get()
                ->groupBy('sort_order');
            $replacedAssets = collect($existingAssets->all())
                ->only(array_map('intval', array_keys($images)))
                ->flatten()
                ->values();

            $replacedAssets->each(fn (Asset $asset): bool => $asset->delete());

            foreach ($images as $position => $image) {
                $position = (int) $position;

                $this->files->store($image, new StoreAssetInput(
                    self::ResourceType,
                    self::ResourceId,
                    'homepage/instagram',
                    'public',
                    $position,
                    $position === 0,
                ));
            }

            return $replacedAssets;
        });

        $replacedAssets->each(fn (Asset $asset): mixed => $this->files->delete($asset));

        return $this->list();
    }

    /** @return Collection<int, Asset> */
    private function assets(): Collection
    {
        return Asset::query()
            ->where('resource_type', self::ResourceType)
            ->where('resource_id', self::ResourceId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->unique('sort_order')
            ->values();
    }
}
