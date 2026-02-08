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
        Schema::create('properties_promotion', function (Blueprint $table) {
            $table->id('promotionId');
            $table->unsignedBigInteger('propertyId')->nullable();
            $table->unsignedBigInteger('packageId')->nullable();
            $table->date('startDate')->nullable();
            $table->date('endDate')->nullable();
            $table->integer('priority')->default(0);
            $table->foreign('propertyId')->references('propertyId')->on('properties')->onDelete('cascade');
            $table->foreign('packageId')->references('packageId')->on('properties_promotion_packages')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_promotion');
    }
};
