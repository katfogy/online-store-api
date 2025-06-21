<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class Otp extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'type', 'otp', 'expired_at'];



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
