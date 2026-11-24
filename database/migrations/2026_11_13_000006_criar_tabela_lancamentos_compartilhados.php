<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos_compartilhados', function (Blueprint $table) {
            $table->id('id_lancamento_compartilhado');
            $table->unsignedBigInteger('id_espaco_compartilhado');
            $table->unsignedBigInteger('id_recorrencia_compartilhada')->nullable();
            $table->unsignedBigInteger('id_usuario_responsavel')->nullable();
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->date('data_vencimento');
            $table->enum('status', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->date('data_pagamento')->nullable();
            $table->unsignedBigInteger('id_usuario_baixa')->nullable();
            $table->unsignedBigInteger('id_lancamento_pessoal')->nullable();
            $table->unsignedInteger('versao')->default(1);
            $table->text('observacao')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_espaco_compartilhado', 'lanc_comp_espaco_fk')
                ->references('id_espaco_compartilhado')->on('espacos_compartilhados')
                ->cascadeOnDelete();

            $table->foreign('id_recorrencia_compartilhada', 'lanc_comp_recorrencia_fk')
                ->references('id_recorrencia_compartilhada')->on('recorrencias_compartilhadas')
                ->nullOnDelete();

            $table->foreign('id_usuario_responsavel', 'lanc_comp_responsavel_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->foreign('id_usuario_baixa', 'lanc_comp_usuario_baixa_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->foreign('id_lancamento_pessoal', 'lanc_comp_lanc_pessoal_fk')
                ->references('id_lancamento')->on('lancamentos')
                ->nullOnDelete();

            $table->foreign('criado_por', 'lanc_comp_criado_por_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->index(['id_espaco_compartilhado', 'data_vencimento'], 'lanc_comp_espaco_venc_idx');
            $table->index(['id_espaco_compartilhado', 'status'], 'lanc_comp_espaco_status_idx');
            $table->index(['id_usuario_responsavel', 'status'], 'lanc_comp_responsavel_status_idx');
            $table->unique(['id_recorrencia_compartilhada', 'data_vencimento'], 'lanc_comp_recorrencia_venc_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_compartilhados');
    }
};
