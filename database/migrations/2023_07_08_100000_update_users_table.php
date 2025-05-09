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
        Schema::table('users', function (Blueprint $table) {
            // Renommer la colonne name en username si elle existe
            if (Schema::hasColumn('users', 'name')) {
                $table->renameColumn('name', 'username');
            }

            // Ajouter les colonnes manquantes
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('last_name');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['ADMIN', 'MANAGER', 'CLIENT'])->default('CLIENT')->after('phone_number');
            }

            if (!Schema::hasColumn('users', 'enabled')) {
                $table->boolean('enabled')->default(true)->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Renommer la colonne username en name si on revient en arrière
            if (Schema::hasColumn('users', 'username')) {
                $table->renameColumn('username', 'name');
            }

            // Supprimer les colonnes ajoutées
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone_number',
                'role',
                'enabled'
            ]);
        });
    }
};
