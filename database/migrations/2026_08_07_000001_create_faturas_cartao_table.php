<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faturas_cartao', function (Blueprint $table) {
            $table->id('id_fatura_cartao');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->foreignId('id_cartao_credito')
                ->constrained('cartoes_credito', 'id_cartao_credito')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('ano');
            $table->unsignedTinyInteger('mes');
            $table->date('data_fechamento');
            $table->date('data_vencimento');
            $table->enum('situacao', ['aberta', 'fechada', 'paga'])->default('aberta');
            $table->foreignId('id_conta_bancaria_pagamento')
                ->nullable()
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->nullOnDelete();
            $table->unsignedBigInteger('id_lancamento_pagamento')->nullable();
            $table->timestamps();

            $table->unique(['id_cartao_credito', 'ano', 'mes'], 'faturas_cartao_cartao_competencia_unique');
            $table->index(['id_usuario', 'situacao']);
            $table->index(['id_cartao_credito', 'situacao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faturas_cartao');
    }
};
