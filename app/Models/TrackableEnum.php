<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackableEnum extends baseModel
{
    protected $table = 'enums';

    protected $fillable = ['user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(TrackableEnumValue::class, 'enum_uid', 'uid')
            ->orderBy('position')
            ->orderBy('created_at');
    }

    public function schemaFields(): HasMany
    {
        return $this->hasMany(TrackableSchema::class, 'enum_uid', 'uid');
    }
}
