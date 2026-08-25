<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trackable_records', function (Blueprint $table) {
            $table->index(['trackable_uid', 'record_date'], 'trackable_records_trackable_date_index');
        });

        Schema::table('trackable_data', function (Blueprint $table) {
            $table->index(['trackable_record_uid', 'trackable_schema_uid'], 'trackable_data_record_schema_index');
            $table->index(['trackable_schema_uid', 'trackable_record_uid'], 'trackable_data_schema_record_index');
        });
    }

    public function down(): void
    {
        Schema::table('trackable_data', function (Blueprint $table) {
            $table->dropIndex('trackable_data_schema_record_index');
            $table->dropIndex('trackable_data_record_schema_index');
        });

        Schema::table('trackable_records', function (Blueprint $table) {
            $table->dropIndex('trackable_records_trackable_date_index');
        });
    }
};
