<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private FileStorageServiceInterface $files,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(CreateProductInput $input, ?int $userId = null): ProductOutput
    {
        return $this->transactions->run(function () use ($input, $userId): ProductOutput {
            $product = $this->products->create($input, $userId);

            foreach ($input->images as $index => $image) {
                $this->files->store($image, new StoreAssetInput('product', $product->id, 'products', 'public', $index, $index === 0));
            }

            return ProductOutput::fromModel($product);
        });
    }

}
