<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enums', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('uid')->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('enum_values', function (Blueprint $table) {
            $table->string('label', 255)->nullable()->after('value');
            $table->text('description')->nullable()->after('label');
            $table->boolean('is_active')->default(true)->after('description');
            $table->unsignedInteger('position')->default(0)->after('is_active');
        });

        DB::table('enum_values')
            ->whereNull('label')
            ->update(['label' => DB::raw('value')]);
    }

    public function down(): void
    {
        Schema::table('enum_values', function (Blueprint $table) {
            $table->dropColumn(['label', 'description', 'is_active', 'position']);
        });

        Schema::table('enums', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
