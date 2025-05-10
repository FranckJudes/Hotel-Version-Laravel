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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->string('status')->default('pending');
            $table->integer('total_price');
            $table->string('payment_method');
            $table->string('payment_status')->default('pending');
            $table->integer('guests');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
