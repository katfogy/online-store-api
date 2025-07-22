<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Http\Requests\ProductRequest;
use Illuminate\Http\JsonResponse;
use App\Traits\HasJsonResponse;
use App\Support\HttpConstants as HTTP;

class ProductController extends Controller
{
    use HasJsonResponse;

    public function __construct(protected ProductService $productService)
    {
    }

    public function index(): JsonResponse
    {
        $products = $this->productService->listProducts();
        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Product list fetched successfully', $products);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = $this->productService->createProduct($data);
        return $this->jsonResponse(HTTP::HTTP_CREATED, 'Product created successfully', $product);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (! $product) {
            return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Product not found');
        }

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Product fetched successfully', $product);
    }

    public function update(ProductRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        $product = $this->productService->updateProduct($id, $data);

        if (! $product) {
            return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Product not found');
        }

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Product updated successfully', $product);
    }

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->productService->deleteProduct($id);

        if (! $deleted) {
            return $this->jsonResponse(HTTP::HTTP_NOT_FOUND, 'Product not found or already deleted');
        }

        return $this->jsonResponse(HTTP::HTTP_SUCCESS, 'Product deleted successfully');
    }
}
