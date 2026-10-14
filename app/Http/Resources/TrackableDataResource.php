<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackableDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $enumValue = $this->schema?->field_type === 'enum'
            ? $this->schema->enum?->values->firstWhere('uid', $this->value)
            : null;

        return [
            'uid' => $this->uid,
            'name' => $this->schema->name,
            'alias' => $this->schema->alias,
            'trackable_schema_uid' => $this->trackable_schema_uid,
            'value' => $this->value,
            'display_value' => $enumValue?->display_label ?? $this->value,
            'enum_value' => $this->when($enumValue, fn () => [
                'uid' => $enumValue->uid,
                'key' => $enumValue->value,
                'label' => $enumValue->display_label,
                'description' => $enumValue->description,
                'is_active' => $enumValue->is_active,
            ]),
        ];
    }
}
