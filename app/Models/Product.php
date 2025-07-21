<?php

namespace App\Models;
use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuid;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'store_id', 'category_id', 'name', 'slug', 'description',
        'price', 'created_by', 'stock_quantity', 'image', 'status'
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
