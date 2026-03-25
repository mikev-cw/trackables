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
        Schema::table('trackables', function (Blueprint $table) {
            $table->string('alias', 255)->nullable()->after('name')->unique();
        });

        $trackables = DB::table('trackables')
            ->orderBy('created_at')
            ->get();

        $usedAliases = [];

        foreach ($trackables as $trackable) {
            $baseAlias = Str::snake($trackable->name);
            $baseAlias = Str::limit($baseAlias !== '' ? $baseAlias : 'trackable', 255, '');
            $alias = $baseAlias;
            $suffix = 2;

            while (in_array($alias, $usedAliases, true)) {
                $alias = Str::limit($baseAlias, 255 - strlen((string) $suffix) - 1, '').'_'.$suffix;
                $suffix++;
            }

            $usedAliases[] = $alias;

            DB::table('trackables')
                ->where('uid', $trackable->uid)
                ->update(['alias' => $alias]);
        }
    }

    public function down(): void
    {
        Schema::table('trackables', function (Blueprint $table) {
            $table->dropUnique(['alias']);
            $table->dropColumn('alias');
        });
    }
};
