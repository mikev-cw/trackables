<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackableEnumValue extends baseModel
{
    protected $table = 'enum_values';

    protected $fillable = ['enum_uid', 'value', 'label', 'description', 'is_active', 'position'];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function enum(): BelongsTo
    {
        return $this->belongsTo(TrackableEnum::class, 'enum_uid', 'uid');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: $this->value;
    }
}
