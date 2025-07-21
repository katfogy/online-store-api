<?php

// ProductService.php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {

            return Product::create([
                'store_id'       => $data['store_id'],
                'category_id'    => $data['category_id'],
                'name'           => $data['name'],
                'slug'           => Str::slug($data['name']),
                'description'    => $data['description'] ?? null,
                'price'          => $data['price'],
                'created_by'     => $data['created_by'],
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'image'          => $data['image'] ?? null,
                'status'         => $data['status'] ?? 'available',
            ]);
        });
    }

    public function getProductById(int $id): ?Product
    {
        return Product::find($id);
    }

    public function updateProduct(int $id, array $data): ?Product
    {
        return DB::transaction(function () use ($id, $data): ?Product {
            $product = Product::find($id);
            if (! $product) {
                return null;
            }

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product->update($data);
            return $product;
        });    }

    public function deleteProduct(int $id): bool
    {
        $product = Product::find($id);
        return $product ? $product->delete() : false;
    }

    public function listProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::with(['category', 'store', 'creator'])->get();
    }
}
