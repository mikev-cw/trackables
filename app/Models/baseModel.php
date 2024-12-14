<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class baseModel extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = self::generateUniqueUid($model);
            }
        });
    }

    private static function generateUniqueUid($model)
    {
        do {
            $uid = bin2hex(random_bytes(12)); // 24-character hex string
        } while ($model::where('uid', $uid)->exists());

        return $uid;
    }

    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
}
