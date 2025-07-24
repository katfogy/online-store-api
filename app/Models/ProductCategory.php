<?php

namespace App\Models;
use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasUuid;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['name', 'slug'];
}
