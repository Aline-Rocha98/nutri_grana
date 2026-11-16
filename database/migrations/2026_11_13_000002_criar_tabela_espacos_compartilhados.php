<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espacos_compartilhados', function (Blueprint $table) {
            $table->id('id_espaco_compartilhado');
            $table->string('nome', 80);
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->enum('status', ['ativo', 'arquivado'])->default('ativo');
            $table->timestamps();

            $table->foreign('criado_por', 'esp_comp_criado_por_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->index('status', 'esp_comp_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espacos_compartilhados');
    }
};
