<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_espaco_compartilhado', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->unsignedBigInteger('id_espaco_compartilhado');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('acao', 60);
            $table->string('entidade', 60);
            $table->unsignedBigInteger('id_entidade')->nullable();
            $table->json('dados_anteriores')->nullable();
            $table->json('dados_novos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_espaco_compartilhado', 'aud_esp_espaco_fk')
                ->references('id_espaco_compartilhado')->on('espacos_compartilhados')
                ->cascadeOnDelete();

            $table->foreign('id_usuario', 'aud_esp_usuario_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->index(['id_espaco_compartilhado', 'created_at'], 'aud_esp_espaco_criado_idx');
            $table->index(['entidade', 'id_entidade'], 'aud_esp_entidade_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_espaco_compartilhado');
    }
};
