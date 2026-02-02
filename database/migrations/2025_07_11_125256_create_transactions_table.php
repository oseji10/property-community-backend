<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Schema::create('payments', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('applicationId');
        //     $table->unsignedBigInteger('userId');
        //     $table->string('rrr')->unique();
        //     $table->decimal('amount', 10, 2);
        //     $table->string('orderId')->unique();
        //     $table->string('status')->default('pending'); // pending, success, failed
        //     $table->text('response')->nullable();
        //     $table->text('channel')->nullable();
        //     $table->text('paymentDate')->nullable();


        //     $table->timestamps();

        //     $table->foreign('applicationId')->references('applicationId')->on('applications')->onDelete('cascade');
        //     $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        // });

        Schema::create('transactions', function (Blueprint $table) {
    $table->id('transactionId');
    $table->unsignedBigInteger('userId')->nullable();
    $table->morphs('transactionable');
    $table->enum('transactionType', ['rent', 'purchase'])->nullable();
    $table->decimal('amount', 15, 2)->nullable();
    $table->enum('status', ['pending', 'paid', 'completed', 'cancelled'])->nullable();
    $table->string('reference')->unique();
    $table->timestamps();

    $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
});
    }

    public function down()
    {
        //
    }
};