<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos_financeiros', function (Blueprint $table) {
            $table->id('id_plano_financeiro');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('nome', 120);
            $table->string('descricao', 255)->nullable();
            $table->enum('situacao', ['ativo', 'inativo'])->default('ativo');
            $table->enum('padrao', ['S', 'N'])->default('N');
            $table->timestamps();

            $table->index(['id_usuario', 'situacao']);
            $table->index(['id_usuario', 'padrao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos_financeiros');
    }
};
