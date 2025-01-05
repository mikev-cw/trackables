<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackableSchema extends baseModel
{
    use HasFactory;

    protected $fillable = ['trackable_uid', 'name', 'field_type', 'enum_uid', 'calc_formula', 'validation_rule'];
}
