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
        Schema::create('favorites', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('userId')->nullable();
        $table->unsignedBigInteger('propertyId')->nullable();
        $table->timestamps();

        $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('propertyId')->references('propertyId')->on('properties')->onDelete('cascade');
        // unique combination: one user can favorite a property only once
        $table->unique(['userId', 'propertyId']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
