<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TrackableSchema extends baseModel
{
    use HasFactory;

    protected $fillable = ['trackable_uid', 'name', 'alias', 'field_type', 'enum_uid', 'calc_formula', 'validation_rule'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($schema) {
            if (empty($schema->alias)) {
                $schema->alias = static::generateUniqueAlias($schema->trackable_uid, $schema->name);
            }
        });
    }

    public static function generateUniqueAlias(string $trackableUid, string $name, ?string $preferredAlias = null, ?string $ignoreUid = null): string
    {
        $baseAlias = Str::snake($preferredAlias ?: $name);
        $baseAlias = Str::limit($baseAlias !== '' ? $baseAlias : 'field', 80, '');
        $alias = $baseAlias;
        $suffix = 2;

        while (static::query()
            ->where('trackable_uid', $trackableUid)
            ->when($ignoreUid, fn ($query) => $query->where('uid', '!=', $ignoreUid))
            ->where('alias', $alias)
            ->exists()) {
            $alias = Str::limit($baseAlias, 80 - strlen((string) $suffix) - 1, '').'_'.$suffix;
            $suffix++;
        }

        return $alias;
    }
}
