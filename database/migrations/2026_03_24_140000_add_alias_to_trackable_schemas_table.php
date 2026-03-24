<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->string('alias', 80)->nullable()->after('name');
            $table->unique(['trackable_uid', 'alias']);
        });

        $schemasByTrackable = DB::table('trackable_schemas')
            ->orderBy('trackable_uid')
            ->orderBy('created_at')
            ->get()
            ->groupBy('trackable_uid');

        foreach ($schemasByTrackable as $schemas) {
            $usedAliases = [];

            foreach ($schemas as $schema) {
                $baseAlias = Str::snake($schema->name);
                $baseAlias = Str::limit($baseAlias !== '' ? $baseAlias : 'field', 80, '');
                $alias = $baseAlias;
                $suffix = 2;

                while (in_array($alias, $usedAliases, true)) {
                    $alias = Str::limit($baseAlias, 80 - strlen((string) $suffix) - 1, '').'_'.$suffix;
                    $suffix++;
                }

                $usedAliases[] = $alias;

                DB::table('trackable_schemas')
                    ->where('uid', $schema->uid)
                    ->update(['alias' => $alias]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->dropUnique(['trackable_uid', 'alias']);
            $table->dropColumn('alias');
        });
    }
};
