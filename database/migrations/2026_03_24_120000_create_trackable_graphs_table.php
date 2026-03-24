<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackable_graphs', function (Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('trackable_uid', 24);
            $table->string('title', 255);
            $table->enum('graph_type', ['line', 'bar'])->default('line');
            $table->string('range_type', 40)->default('all_time');
            $table->enum('sampling', ['all', 'daily_latest'])->default('all');
            $table->json('schema_uids');
            $table->timestamps();

            $table->foreign('trackable_uid')->references('uid')->on('trackables')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trackable_graphs');
    }
};
