<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Categories\Services\CategoryService;
use App\Domain\Categories\DTOs\CreateCategoryDTO;
use App\Domain\Categories\DTOs\UpdateCategoryDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $service;

    public function __construct(CategoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        return ApiResponse::send(
            new CategoryCollection($this->service->getCategories($organizationId)),
            "Categories retrieved"
        );
    }

    public function store(StoreCategoryRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateCategoryDTO(
            organizationId: $organizationId,
            code: $request->code,
            name: $request->name,
            type: $request->type,
            parentId: $request->parent_id,
            metadata: $request->metadata ?? null
        );

        $category = $this->service->create($dto);
        return ApiResponse::send(new CategoryResource($category), "Category created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $category = $this->service->getCategoryById($organizationId, $id);
        return ApiResponse::send(new CategoryResource($category), "Category retrieved");
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $category = $this->service->getCategoryById($organizationId, $id);

        $dto = new UpdateCategoryDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            type: $request->type ?? null,
            parentId: $request->parent_id,
            metadata: $request->metadata ?? null
        );

        $category = $this->service->update($category, $dto);
        return ApiResponse::send(new CategoryResource($category), "Category updated");
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $category = $this->service->getCategoryById($organizationId, $id);
        $this->service->delete($category);
        return ApiResponse::send(null, "Category deleted");
    }
}
