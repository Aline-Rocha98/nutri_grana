<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recorrencias_compartilhadas', function (Blueprint $table) {
            $table->id('id_recorrencia_compartilhada');
            $table->unsignedBigInteger('id_espaco_compartilhado');
            $table->unsignedBigInteger('id_usuario_responsavel')->nullable();
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->enum('frequencia', ['mensal', 'semanal', 'anual']);
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->date('gerada_ate')->nullable();
            $table->enum('status', ['ativa', 'encerrada'])->default('ativa');
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_espaco_compartilhado', 'rec_comp_espaco_fk')
                ->references('id_espaco_compartilhado')->on('espacos_compartilhados')
                ->cascadeOnDelete();

            $table->foreign('id_usuario_responsavel', 'rec_comp_responsavel_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->foreign('criado_por', 'rec_comp_criado_por_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->index(['id_espaco_compartilhado', 'status'], 'rec_comp_espaco_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recorrencias_compartilhadas');
    }
};
