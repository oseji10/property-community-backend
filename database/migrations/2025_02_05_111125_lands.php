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
        Schema::create('lands', function (Blueprint $table) {
            $table->id('landId');
            $table->string('landTitle')->nullable();
            $table->text('landDescription')->nullable();
            $table->string('location')->nullable();
            $table->decimal('size', 10, 2)->nullable(); // e.g sqm or acres
            $table->decimal('price', 15, 2)->nullable();
            $table->boolean('isAvailable')->default(true);
            $table->unsignedBigInteger('addedBy')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('addedBy')->references('id')->on('users')->onDelete('cascade');
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
