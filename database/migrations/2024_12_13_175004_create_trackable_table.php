<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trackables', function (Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('name', 255);
            $table->unsignedBigInteger('user_id')->index();
            $table->boolean('deleted')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('trackable_records', function(Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('trackable_uid', 24);
            $table->timestamp('record_date');
            $table->timestamps();

            $table->foreign('trackable_uid')->references('uid')->on('trackables');
        });

        Schema::create('trackable_schemas', function(Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('trackable_uid', 24);
            $table->string('name', 80);
            $table->enum('field_type',['int', 'float', 'json', 'string', 'bool', 'date', 'datetime', 'img', 'url', 'enum', 'calc'])->nullable();
            $table->boolean('required')->nullable(0)->default(0);
            $table->string('enum_uid', 24)->nullable();
            $table->json('calc_formula')->nullable();
            $table->json('validation_rule');
            $table->timestamps();

            $table->foreign('trackable_uid')->references('uid')->on('trackables');
        });

        Schema::create('trackable_data', function(Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('trackable_schema_uid', 24);
            $table->string('trackable_record_uid', 24);
            $table->mediumText('value');
            $table->timestamps();

            $table->foreign('trackable_schema_uid')->references('uid')->on('trackable_schemas');
            $table->foreign('trackable_record_uid')->references('uid')->on('trackable_records');
        });

        Schema::create('enums', function(Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('name', 255);
            $table->timestamps();
        });

        Schema::create('enum_values', function(Blueprint $table) {
            $table->string('uid', 24)->primary();
            $table->string('enum_uid', 24)->index();
            $table->string('value', 255);
            $table->timestamps();

            $table->foreign('enum_uid')->references('uid')->on('enums');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trackables');
        Schema::dropIfExists('trackable_schemas');
        Schema::dropIfExists('trackable_records');
        Schema::dropIfExists('trackable_data');
        Schema::dropIfExists('enums');
        Schema::dropIfExists('enum_values');
    }
};
