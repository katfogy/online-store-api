<?php

namespace App\Models;
use App\Traits\HasUuid;


use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    
    use HasUuid;
    //

    public function users()
{
    return $this->hasMany(User::class);
}

}
