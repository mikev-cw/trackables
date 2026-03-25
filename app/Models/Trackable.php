<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Trackable extends baseModel
{
    use HasFactory;

    protected $fillable = ['uid', 'user_id', 'name', 'alias', 'deleted'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($trackable) {
            if (empty($trackable->alias)) {
                $trackable->alias = static::generateUniqueAlias($trackable->name);
            }
        });
    }

    public static function generateUniqueAlias(string $name, ?string $preferredAlias = null, ?string $ignoreUid = null): string
    {
        $baseAlias = Str::snake($preferredAlias ?: $name);
        $baseAlias = Str::limit($baseAlias !== '' ? $baseAlias : 'trackable', 255, '');
        $alias = $baseAlias;
        $suffix = 2;

        while (static::query()
            ->when($ignoreUid, fn ($query) => $query->where('uid', '!=', $ignoreUid))
            ->where('alias', $alias)
            ->exists()) {
            $alias = Str::limit($baseAlias, 255 - strlen((string) $suffix) - 1, '').'_'.$suffix;
            $suffix++;
        }

        return $alias;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function schema() {
        return $this->hasMany(TrackableSchema::class);
    }

    public function records() {
        return $this->hasMany(TrackableRecord::class);
    }

    public function graphs() {
        return $this->hasMany(TrackableGraph::class);
    }

}
