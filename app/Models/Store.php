<?php

namespace App\Models;
use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Store extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    use HasUuid, HasFactory;

    protected $fillable = [
        'user_id', 'name', 'slug', 'description', 'logo', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
