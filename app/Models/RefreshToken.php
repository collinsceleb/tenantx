<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    protected $table = 'refresh_token';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'refresh_token', 'expires_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }

            if (empty($model->refresh_token)) {
                $model->refresh_token = hash('sha256', Str::random(60));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
