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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->enum('type', ['SINGLE', 'DOUBLE', 'TWIN', 'DELUXE', 'SUITE', 'PRESIDENTIAL']);
            $table->integer('capacity');
            $table->decimal('price_per_night', 10, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'CLEANING']);
            $table->boolean('has_air_conditioning')->default(false);
            $table->boolean('has_tv')->default(false);
            $table->boolean('has_minibar')->default(false);
            $table->boolean('has_safe')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->timestamps();
        });

        // Table pour les URLs d'images de la chambre
        Schema::create('room_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('image_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_images');
        Schema::dropIfExists('rooms');
    }
};
