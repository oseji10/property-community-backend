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
        Schema::create('property_metrics', function (Blueprint $table) {
            $table->id('metricsId');
            $table->unsignedBigInteger('propertyId')->nullable();
            $table->integer('viewsCount')->default(0);
            $table->integer('savesCount')->default(0);
            $table->integer('inquiriesCount')->default(0);
            $table->integer('searchScore')->default(0);
            $table->date('lastUpdated')->nullable();
            $table->date('lastViewed')->nullable();
            $table->foreign('propertyId')->references('propertyId')->on('properties')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_metrics');
    }
};
