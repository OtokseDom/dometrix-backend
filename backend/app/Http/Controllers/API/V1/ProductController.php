<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Manufacturing\Services\ProductService;
use App\Domain\Manufacturing\DTOs\CreateProductDTO;
use App\Domain\Manufacturing\DTOs\UpdateProductDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        return ApiResponse::send(
            new ProductCollection($this->service->getProducts($organizationId)),
            "Products retrieved"
        );
    }

    public function store(StoreProductRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateProductDTO(
            organizationId: $organizationId,
            code: $request->code,
            name: $request->name,
            description: $request->description ?? null,
            unitId: $request->unit_id,
            metadata: $request->metadata ?? null
        );

        $product = $this->service->create($dto);
        return ApiResponse::send(new ProductResource($product), "Product created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $product = $this->service->getProductById($organizationId, $id);
        return ApiResponse::send(new ProductResource($product), "Product retrieved");
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $product = $this->service->getProductById($organizationId, $id);

        $dto = new UpdateProductDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            description: $request->description,
            unitId: $request->unit_id ?? null,
            metadata: $request->metadata ?? null
        );

        $product = $this->service->update($product, $dto);
        return ApiResponse::send(new ProductResource($product), "Product updated");
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $product = $this->service->getProductById($organizationId, $id);
        $this->service->delete($product);
        return ApiResponse::send(null, "Product deleted");
    }
}
