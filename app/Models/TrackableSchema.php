<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class TrackableSchema extends baseModel
{
    use HasFactory;

    protected $fillable = ['trackable_uid', 'name', 'alias', 'field_type', 'enum_uid', 'calc_formula', 'validation_rule', 'validation_config'];

    protected $casts = [
        'validation_config' => 'array',
    ];

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

    public static function normalizeValidationConfig(?string $fieldType, array $config = []): array
    {
        $fieldType = $fieldType ?: 'string';

        $normalized = [
            'required' => filter_var($config['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'min' => static::nullableNumeric($config['min'] ?? null),
            'max' => static::nullableNumeric($config['max'] ?? null),
            'max_length' => static::nullableInteger($config['max_length'] ?? null),
            'format' => in_array($config['format'] ?? null, static::allowedFormats(), true) ? $config['format'] : null,
        ];

        if (!in_array($fieldType, ['int', 'float'], true)) {
            $normalized['min'] = null;
            $normalized['max'] = null;
        }

        if (!in_array($fieldType, ['string', 'url', 'img', 'json'], true)) {
            $normalized['max_length'] = null;
        }

        if ($fieldType !== 'string') {
            $normalized['format'] = null;
        }

        return $normalized;
    }

    public static function validationRuleFromConfig(?string $fieldType, array $config = []): string
    {
        $fieldType = $fieldType ?: 'string';
        $config = static::normalizeValidationConfig($fieldType, $config);
        $rules = [$config['required'] ? 'required' : 'nullable'];

        $typeRule = match ($fieldType) {
            'int' => 'integer',
            'float' => 'numeric',
            'json' => 'json',
            'bool' => 'boolean',
            'date', 'datetime' => 'date',
            'url' => 'url',
            default => 'string',
        };

        $rules[] = $typeRule;

        if (!is_null($config['min'])) {
            $rules[] = 'min:'.$config['min'];
        }

        if (!is_null($config['max'])) {
            $rules[] = 'max:'.$config['max'];
        }

        if (!is_null($config['max_length'])) {
            $rules[] = 'max:'.$config['max_length'];
        }

        if ($config['format']) {
            $rules[] = $config['format'];
        }

        return implode('|', array_unique($rules));
    }

    public function getValidationRuleAttribute(): string
    {
        return static::validationRuleFromConfig($this->field_type, $this->validationConfigForEditor());
    }

    public function setValidationRuleAttribute(?string $value): void
    {
        $this->attributes['validation_config'] = json_encode(
            static::validationConfigFromRule($this->attributes['field_type'] ?? null, $value)
        );
    }

    public function validationConfigForEditor(): array
    {
        if (is_array($this->validation_config)) {
            return static::normalizeValidationConfig($this->field_type, $this->validation_config);
        }

        return static::normalizeValidationConfig($this->field_type);
    }

    public static function validationConfigFromRule(?string $fieldType, ?string $rule): array
    {
        $config = static::normalizeValidationConfig($fieldType);
        $rules = collect(explode('|', (string) $rule))
            ->map(fn ($item) => trim($item))
            ->filter();

        $config['required'] = $rules->contains('required');

        foreach ($rules as $item) {
            if (str_starts_with($item, 'min:')) {
                $config['min'] = static::nullableNumeric(Str::after($item, 'min:'));
            }

            if (str_starts_with($item, 'max:')) {
                if (in_array($fieldType, ['int', 'float'], true)) {
                    $config['max'] = static::nullableNumeric(Str::after($item, 'max:'));
                } else {
                    $config['max_length'] = static::nullableInteger(Str::after($item, 'max:'));
                }
            }

            if (in_array($item, static::allowedFormats(), true)) {
                $config['format'] = $item;
            }
        }

        return static::normalizeValidationConfig($fieldType, $config);
    }

    private static function allowedFormats(): array
    {
        return ['email', 'url', 'uuid'];
    }

    private static function nullableNumeric(mixed $value): int|float|null
    {
        if ($value === '' || is_null($value) || !is_numeric($value)) {
            return null;
        }

        return str_contains((string) $value, '.') ? (float) $value : (int) $value;
    }

    private static function nullableInteger(mixed $value): ?int
    {
        if ($value === '' || is_null($value) || !is_numeric($value)) {
            return null;
        }

        return max(1, (int) $value);
    }
}
