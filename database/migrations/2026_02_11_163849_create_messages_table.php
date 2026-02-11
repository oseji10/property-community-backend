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
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('senderId')->nullable();
        $table->unsignedBigInteger('receiverId')->nullable();
        $table->unsignedBigInteger('propertyId')->nullable();
        
        $table->text('content');
        $table->boolean('isRead')->default(false);
        $table->timestamps();

        $table->foreign('senderId')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('receiverId')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('propertyId')->references('propertyId')->on('properties')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
