<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('instructions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); 
            $table->string('url')->nullable();
            $table->unsignedTinyInteger('active')->default(1);

            // Relación con users
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->timestamps(); 
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('instructions');
    }
};