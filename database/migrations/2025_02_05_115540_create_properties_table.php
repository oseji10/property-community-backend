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
        // First create the table without foreign keys
        Schema::create('properties', function (Blueprint $table) {
            $table->id('propertyId');
            $table->unsignedBigInteger('typeId')->nullable();
           
            $table->string('propertyTitle')->nullable();
            $table->text('propertyDescription')->nullable();
            $table->unsignedBigInteger('addedBy')->nullable();
            
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->enum('listingType', ['rent', 'sale']);
            $table->boolean('isAvailable')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('typeId')->references('typeId')->on('property_types')->onDelete('cascade');
            $table->foreign('addedBy')->references('id')->on('users')->onDelete('cascade');
        });
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
