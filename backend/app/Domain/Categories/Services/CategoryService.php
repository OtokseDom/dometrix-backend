<?php

namespace App\Domain\Categories\Services;

use App\Domain\Categories\DTOs\CreateCategoryDTO;
use App\Domain\Categories\DTOs\UpdateCategoryDTO;
use App\Domain\Categories\Models\Category;

class CategoryService
{
    public function getCategories(string $organizationId)
    {
        return Category::where('organization_id', $organizationId)->paginate();
    }

    public function getCategoryById(string $organizationId, string $categoryId): Category
    {
        return Category::where('organization_id', $organizationId)
            ->findOrFail($categoryId);
    }

    public function create(CreateCategoryDTO $dto): Category
    {
        return Category::create([
            'organization_id' => $dto->organizationId,
            'code' => $dto->code,
            'name' => $dto->name,
            'type' => $dto->type,
            'parent_id' => $dto->parentId,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Category $category, UpdateCategoryDTO $dto): Category
    {
        $category->update(array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
            'type' => $dto->type,
            'parent_id' => $dto->parentId,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function findOrFail(string $categoryId): Category
    {
        return Category::findOrFail($categoryId);
    }
}
