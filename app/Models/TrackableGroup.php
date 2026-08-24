<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackableGroup extends baseModel
{
    use HasFactory;

    protected $fillable = ['uid', 'user_id', 'name', 'description', 'deleted'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trackables()
    {
        return $this->hasMany(Trackable::class, 'group_uid', 'uid');
    }
}
