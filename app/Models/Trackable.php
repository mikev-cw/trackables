<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trackable extends baseModel
{
    use HasFactory;

    protected $fillable = ['uid', 'name', 'deleted'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function schema() {
        return $this->hasMany(TrackableSchema::class);
    }

    public function records() {
        return $this->hasMany(TrackableRecord::class);
    }

}
