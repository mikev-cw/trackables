<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackableRecord extends baseModel
{
    use HasFactory;

    public function data() {
        return $this->hasMany(TrackableData::class);
    }
}
