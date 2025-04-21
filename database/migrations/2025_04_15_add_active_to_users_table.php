<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Añadir la columna 'active' a la tabla 'users'
        Schema::table('users', function (Blueprint $table) {
            // Usamos 'integer' y por defecto le damos el valor de 1
            $table->integer('active')->default(1); // 1 por defecto para 'activo'
        });
    }

    public function down(): void
    {
        // Eliminar la columna 'active' si es necesario revertir la migración
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};