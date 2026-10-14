<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackableSchemaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->uid,
            'name' => $this->name,
            'alias' => $this->alias,
            'field_type' => $this->field_type,
            'enum_uid' => $this->enum_uid,
            'enum' => $this->when($this->field_type === 'enum' && $this->enum, function () {
                return [
                    'uid' => $this->enum->uid,
                    'name' => $this->enum->name,
                    'values' => $this->enum->values->map(fn ($value) => [
                        'uid' => $value->uid,
                        'key' => $value->value,
                        'label' => $value->display_label,
                        'description' => $value->description,
                        'is_active' => $value->is_active,
                    ])->values(),
                ];
            }),
            'validation_rule' => $this->validation_rule,
            'validation_config' => $this->validationConfigForEditor(),
        ];
    }
}
