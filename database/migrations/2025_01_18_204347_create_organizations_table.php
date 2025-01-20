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
        // Creates a table 'organizations' in the database with unique names
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('org_name')->unique();
            $table->timestamps();
        });

        // Creates a table 'relationships' in the database that contains
        // information about relationships between organizations
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations');

            $table->unsignedBigInteger('daughter_id');
            $table->foreign('daughter_id')->references('id')->on('organizations');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('organizations');
    }
};
