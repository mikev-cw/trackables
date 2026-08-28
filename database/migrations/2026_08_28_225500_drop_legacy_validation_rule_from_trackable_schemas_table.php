<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('trackable_schemas', 'validation_rule')) {
            return;
        }

        if (!Schema::hasColumn('trackable_schemas', 'validation_config')) {
            Schema::table('trackable_schemas', function (Blueprint $table) {
                $table->json('validation_config')->nullable()->after('validation_rule');
            });
        }

        DB::table('trackable_schemas')
            ->select(['uid', 'field_type', 'validation_rule', 'validation_config'])
            ->orderBy('uid')
            ->each(function ($schema) {
                if (!empty($schema->validation_config)) {
                    return;
                }

                DB::table('trackable_schemas')
                    ->where('uid', $schema->uid)
                    ->update([
                        'validation_config' => json_encode($this->configFromRule($schema->field_type, $schema->validation_rule)),
                    ]);
            });

        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->dropColumn('validation_rule');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('trackable_schemas', 'validation_rule')) {
            return;
        }

        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->text('validation_rule')->nullable()->after('validation_config');
        });

        DB::table('trackable_schemas')
            ->select(['uid', 'field_type', 'validation_config'])
            ->orderBy('uid')
            ->each(function ($schema) {
                $config = json_decode($schema->validation_config ?: '[]', true);

                DB::table('trackable_schemas')
                    ->where('uid', $schema->uid)
                    ->update([
                        'validation_rule' => $this->ruleFromConfig($schema->field_type, is_array($config) ? $config : []),
                    ]);
            });
    }

    private function configFromRule(?string $fieldType, ?string $rule): array
    {
        $config = $this->normalizeConfig($fieldType);
        $rules = array_filter(array_map('trim', explode('|', (string) $rule)));

        $config['required'] = in_array('required', $rules, true);

        foreach ($rules as $item) {
            if (str_starts_with($item, 'min:')) {
                $config['min'] = $this->nullableNumeric(substr($item, 4));
            }

            if (str_starts_with($item, 'max:')) {
                if (in_array($fieldType, ['int', 'float'], true)) {
                    $config['max'] = $this->nullableNumeric(substr($item, 4));
                } else {
                    $config['max_length'] = $this->nullableInteger(substr($item, 4));
                }
            }

            if (in_array($item, ['email', 'url', 'uuid'], true)) {
                $config['format'] = $item;
            }
        }

        return $this->normalizeConfig($fieldType, $config);
    }

    private function ruleFromConfig(?string $fieldType, array $config): string
    {
        $fieldType = $fieldType ?: 'string';
        $config = $this->normalizeConfig($fieldType, $config);
        $rules = [$config['required'] ? 'required' : 'nullable'];

        $rules[] = match ($fieldType) {
            'int' => 'integer',
            'float' => 'numeric',
            'json' => 'json',
            'bool' => 'boolean',
            'date', 'datetime' => 'date',
            'url' => 'url',
            default => 'string',
        };

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

    private function normalizeConfig(?string $fieldType, array $config = []): array
    {
        $fieldType = $fieldType ?: 'string';
        $normalized = [
            'required' => filter_var($config['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'min' => $this->nullableNumeric($config['min'] ?? null),
            'max' => $this->nullableNumeric($config['max'] ?? null),
            'max_length' => $this->nullableInteger($config['max_length'] ?? null),
            'format' => in_array($config['format'] ?? null, ['email', 'url', 'uuid'], true) ? $config['format'] : null,
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

    private function nullableNumeric(mixed $value): int|float|null
    {
        if ($value === '' || is_null($value) || !is_numeric($value)) {
            return null;
        }

        return str_contains((string) $value, '.') ? (float) $value : (int) $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === '' || is_null($value) || !is_numeric($value)) {
            return null;
        }

        return max(1, (int) $value);
    }
};
