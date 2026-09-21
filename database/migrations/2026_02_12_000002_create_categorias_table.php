<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('id_categoria');
            $table->foreignId('id_usuario')->nullable()->constrained('usuario', 'id_usuario')->cascadeOnDelete();
            $table->enum('padrao', ['S', 'N'])->default('S');
            $table->string('nome', 100);
            $table->timestamps();
            
            $table->unique(['id_usuario', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
