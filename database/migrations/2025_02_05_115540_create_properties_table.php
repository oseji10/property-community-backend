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
            $table->unsignedBigInteger('propertyTypeId')->nullable();
           
            $table->string('propertyTitle')->nullable();
            $table->text('propertyDescription')->nullable();
            $table->unsignedBigInteger('addedBy')->nullable();
            
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->string('garage')->nullable();
            $table->string('size')->nullable();
            $table->enum('listingType', ['rent', 'sale']);

            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('otherFeatures')->nullable();
            $table->string('amenities')->nullable();


            $table->enum('status', ['active', 'pending', 'sold', 'rented'])->default('active');
            $table->boolean('isAvailable')->default(true);
            $table->string('slug')->unique()->nullable();
            $table->unsignedBigInteger('currency')->nullable();
           
           

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('propertyTypeId')->references('typeId')->on('property_types')->onDelete('cascade');
            $table->foreign('addedBy')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency')->references('currencyId')->on('currencies')->onDelete('cascade');
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
