<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espaco_compartilhado_usuario', function (Blueprint $table) {
            $table->id('id_membro_espaco');
            $table->unsignedBigInteger('id_espaco_compartilhado');
            $table->unsignedBigInteger('id_usuario');
            $table->enum('papel', ['administrador', 'membro'])->default('membro');
            $table->enum('status', ['ativo', 'removido', 'saiu'])->default('ativo');
            $table->timestamp('entrou_em')->nullable();
            $table->timestamps();

            $table->foreign('id_espaco_compartilhado', 'esp_usu_espaco_fk')
                ->references('id_espaco_compartilhado')->on('espacos_compartilhados')
                ->cascadeOnDelete();

            $table->foreign('id_usuario', 'esp_usu_usuario_fk')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();

            $table->unique(['id_espaco_compartilhado', 'id_usuario'], 'esp_usu_espaco_usuario_uq');
            $table->index(['id_usuario', 'status'], 'esp_usu_usuario_status_idx');
            $table->index(['id_espaco_compartilhado', 'status'], 'esp_usu_espaco_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espaco_compartilhado_usuario');
    }
};
