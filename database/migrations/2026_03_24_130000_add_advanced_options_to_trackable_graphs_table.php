<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackable_graphs', function (Blueprint $table) {
            $table->string('bucket_size', 20)->default('raw')->after('range_type');
            $table->string('aggregate', 20)->default('latest')->after('bucket_size');
            $table->json('filters')->nullable()->after('schema_uids');
        });
    }

    public function down(): void
    {
        Schema::table('trackable_graphs', function (Blueprint $table) {
            $table->dropColumn(['bucket_size', 'aggregate', 'filters']);
        });
    }
};
