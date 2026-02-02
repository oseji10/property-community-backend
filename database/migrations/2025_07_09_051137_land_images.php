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
        Schema::create('land_images', function (Blueprint $table) {
            $table->id('imageId');
            $table->unsignedBigInteger('landId')->nullable();
            $table->string('imageUrl')->nullable();
            
            
            $table->timestamps();
            $table->softDeletes();

        $table->foreign('landId')->references('landId')->on('lands')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
