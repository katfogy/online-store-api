<?php

// ProductService.php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
class ProductService
{
    public function createProduct(array $data): Product
{
    return Product::create([
        'store_id'       => $data['store_id'],
        'category_id'    => $data['category_id'],
        'name'           => $data['name'],
        'slug'           => Str::slug($data['name']),
        'description'    => $data['description'] ?? null,
        'price'          => $data['price'],
        'created_by'     => auth()->id(),
        'stock_quantity' => $data['stock_quantity'] ?? 0,
        'image'          => $data['image'] ?? null,
        'status'         => $data['status'] ?? 'available',
    ]);
}

    public function getProductById(string $id): ?Product
    {
        return Product::find($id);
    }

    public function updateProduct(string $id, array $data): ?Product
{
    $product = Product::find($id);
    if (! $product) {
        return null;
    }

    if (isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }

    $product->update($data);
    return $product;
}


    public function deleteProduct(string $id): bool
    {
        $product = Product::find($id);
        return $product ? $product->delete() : false;
    }

    public function listProducts(): LengthAwarePaginator
{
    return Product::with(['category', 'store', 'creator'])
        ->where('created_by', auth()->id())
        ->paginate(10);
}
}
