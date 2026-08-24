<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackable_groups', function (Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('deleted')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::table('trackables', function (Blueprint $table) {
            $table->string('group_uid', 24)->nullable()->after('user_id')->index();
            $table->foreign('group_uid')->references('uid')->on('trackable_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trackables', function (Blueprint $table) {
            $table->dropForeign(['group_uid']);
            $table->dropColumn('group_uid');
        });

        Schema::dropIfExists('trackable_groups');
    }
};
