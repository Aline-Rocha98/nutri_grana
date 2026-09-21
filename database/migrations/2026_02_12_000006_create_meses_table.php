<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meses', function (Blueprint $table) {
            $table->id('id_mes');
            $table->foreignId('id_usuario')->constrained('usuario', 'id_usuario')->onDelete('cascade');
            $table->integer('mes'); 
            $table->integer('ano');
            $table->integer('salario_base')->default(0);
            $table->integer('outros_valores')->default(0);
            $table->timestamps();
            
            $table->unique(['id_usuario', 'ano', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meses');
    }
};
