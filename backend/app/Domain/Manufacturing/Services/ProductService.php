<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CreateProductDTO;
use App\Domain\Manufacturing\DTOs\UpdateProductDTO;
use App\Domain\Manufacturing\Models\Product;

class ProductService
{
    public function getProducts(string $organizationId)
    {
        return Product::where('organization_id', $organizationId)->paginate();
    }

    public function getProductById(string $organizationId, string $productId): Product
    {
        return Product::where('organization_id', $organizationId)
            ->findOrFail($productId);
    }

    public function create(CreateProductDTO $dto): Product
    {
        return Product::create([
            'organization_id' => $dto->organizationId,
            'code' => $dto->code,
            'name' => $dto->name,
            'description' => $dto->description,
            'unit_id' => $dto->unitId,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Product $product, UpdateProductDTO $dto): Product
    {
        $product->update(array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
            'description' => $dto->description,
            'unit_id' => $dto->unitId,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function findOrFail(string $productId): Product
    {
        return Product::findOrFail($productId);
    }
}
