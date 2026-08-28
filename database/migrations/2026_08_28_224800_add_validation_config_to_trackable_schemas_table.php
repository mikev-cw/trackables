<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->json('validation_config')->nullable()->after('validation_rule');
        });
    }

    public function down(): void
    {
        Schema::table('trackable_schemas', function (Blueprint $table) {
            $table->dropColumn('validation_config');
        });
    }
};
