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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('paymentId');
            $table->unsignedBigInteger('userId')->nullable();
            $table->unsignedBigInteger('propertyId')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('paymentMethod')->nullable();
            $table->string('transactionId')->nullable();
            $table->string('transactionReference')->nullable();
            $table->string('metaData')->nullable();
            $table->string('paymentGateway')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
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
        Schema::dropIfExists('payments');
    }
};
