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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("id_usuario");
            $table->foreign("id_usuario")->references("id")->on("usuarios")->onDelete("cascade");
            $table->string("nombre", 150);
            $table->decimal("precio", 5, 2);
            $table->text("descripcion");
            $table->string("categoria", 50);
            $table->string("estado", 10);
            $table->string("imagen")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
