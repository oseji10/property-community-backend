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
        Schema::create('saved_properties', function (Blueprint $table) {
            $table->id('saveId');
            $table->unsignedBigInteger('userId')->nullable();
            $table->unsignedBigInteger('propertyId')->nullable();
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('propertyId')->references('propertyId')->on('properties')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_properties');
    }
};
