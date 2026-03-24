<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackableGraph extends baseModel
{
    use HasFactory;

    protected $fillable = ['trackable_uid', 'title', 'graph_type', 'range_type', 'sampling', 'bucket_size', 'aggregate', 'schema_uids', 'filters'];

    protected function casts(): array
    {
        return [
            'schema_uids' => 'array',
            'filters' => 'array',
        ];
    }

    public function trackable()
    {
        return $this->belongsTo(Trackable::class);
    }
}
