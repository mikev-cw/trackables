<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackableRecord extends baseModel
{
    use HasFactory;

    protected $fillable = ['trackable_uid', 'record_date'];

    public function trackable() {
        return $this->belongsTo(Trackable::class);
    }

    public function data() {
        return $this->hasMany(TrackableData::class);
    }
}
