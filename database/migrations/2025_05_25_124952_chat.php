<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(){
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->unsignedBigInteger('usuario_1_id');
            $table->unsignedBigInteger('usuario_2_id');
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('usuario_1_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('usuario_2_id')->references('id')->on('usuarios')->onDelete('cascade');

            $table->unique(['producto_id', 'usuario_1_id', 'usuario_2_id']);
        });
    }


    public function down(): void {
        Schema::dropIfExists('chats');
    }
};
