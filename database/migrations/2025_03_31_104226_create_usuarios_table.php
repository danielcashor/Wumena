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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id(); // ya incluye primary key y autoIncrement
            $table->string("nombre", 100);
            $table->string("apellidos", 100)->nullable();
            $table->string("email", 100);
            $table->string("clave", 100);
            $table->decimal("valoracion", 2, 1); // Total 2 cifras, una de ellas decimal
            $table->string("rol", 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
