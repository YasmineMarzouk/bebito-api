<?php

namespace Tests\Unit\Catalog;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageProductSelectionInput;
use Modules\Catalog\Exceptions\HomepageProductsMustBePublishedException;
use Modules\Catalog\Models\Product;
use Modules\Catalog\UseCases\UpdateHomepageProductSelectionUseCase;
use Tests\TestCase;

class UpdateHomepageProductSelectionUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_replaces_the_homepage_product_selection(): void
    {
        $product = $this->product(12, 'published');
        $products = Mockery::mock(ProductRepositoryInterface::class);
        $products->shouldReceive('findByIds')->once()->with([12])->andReturn(new Collection([$product]));
        $products->shouldReceive('replaceHomepageSelection')->once()->with([12])->andReturn(new Collection([$product]));

        $result = (new UpdateHomepageProductSelectionUseCase($products))->execute(
            new UpdateHomepageProductSelectionInput([12]),
        );

        self::assertSame(12, $result[0]->id);
    }

    public function test_it_rejects_unpublished_products(): void
    {
        $product = $this->product(12, 'draft');
        $products = Mockery::mock(ProductRepositoryInterface::class);
        $products->shouldReceive('findByIds')->once()->with([12])->andReturn(new Collection([$product]));
        $products->shouldNotReceive('replaceHomepageSelection');

        $this->expectException(HomepageProductsMustBePublishedException::class);

        (new UpdateHomepageProductSelectionUseCase($products))->execute(
            new UpdateHomepageProductSelectionInput([12]),
        );
    }

    private function product(int $id, string $status): Product
    {
        $product = new Product;
        $product->setRawAttributes([
            'id' => $id,
            'status' => $status,
            'is_homepage_featured' => true,
            'homepage_position' => 1,
            'price_amount' => 1000,
            'stock_quantity' => 10,
            'low_stock_threshold' => 3,
        ]);
        foreach (['translations', 'variants', 'assets', 'categories', 'collections', 'tags'] as $relation) {
            $product->setRelation($relation, new Collection);
        }

        return $product;
    }
}
