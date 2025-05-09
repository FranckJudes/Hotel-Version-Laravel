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
        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['REVENUE', 'OCCUPANCY_RATE', 'AVERAGE_STAY_DURATION', 'BOOKINGS_COUNT', 'CANCELLATION_RATE', 'CUSTOMER_SATISFACTION']);
            $table->date('date');
            $table->decimal('value', 10, 2)->nullable();
            $table->string('value_string')->nullable();
            $table->integer('value_integer')->nullable();
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics');
    }
};
