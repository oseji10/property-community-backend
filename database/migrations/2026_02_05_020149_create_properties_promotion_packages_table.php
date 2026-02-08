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
        Schema::create('properties_promotion_packages', function (Blueprint $table) {
            $table->id('packageId');
            $table->string('packageName')->nullable();
            $table->text('packageDescription')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('durationDays')->nullable();
            $table->enum('promotionType', ['featured', 'top_search', 'highlighted'])->default('featured');
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties_promotion_packages');
    }
};
