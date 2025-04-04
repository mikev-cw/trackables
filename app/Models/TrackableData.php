<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackableData extends baseModel
{
    use HasFactory;
    protected $fillable = ['trackable_record_uid', 'trackable_schema_uid', 'value'];

    public function schema() {
        return $this->belongsTo(TrackableSchema::class, 'trackable_schema_uid');
    }

}
