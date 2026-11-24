<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('convites_compartilhamento', function (Blueprint $table) {
            $table->id('id_convite_compartilhamento');
            $table->unsignedBigInteger('id_espaco_compartilhado');
            $table->enum('tipo', ['email', 'codigo']);
            $table->string('email_destinatario', 100)->nullable();
            $table->string('codigo_hash', 64)->nullable();
            $table->unsignedSmallInteger('limite_usos')->default(1);
            $table->unsignedSmallInteger('quantidade_usos')->default(0);
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamp('expira_em');
            $table->timestamp('usado_em')->nullable();
            $table->enum('status', ['pendente', 'aceito', 'recusado', 'expirado', 'revogado'])->default('pendente');
            $table->timestamps();

            $table->foreign('id_espaco_compartilhado', 'conv_comp_espaco_fk')
                ->references('id_espaco_compartilhado')->on('espacos_compartilhados')
                ->cascadeOnDelete();

            $table->foreign('criado_por', 'conv_comp_criado_por_fk')
                ->references('id_usuario')->on('usuario')
                ->nullOnDelete();

            $table->unique('codigo_hash', 'conv_comp_codigo_hash_uq');
            $table->index(['id_espaco_compartilhado', 'status'], 'conv_comp_espaco_status_idx');
            $table->index(['email_destinatario', 'status'], 'conv_comp_email_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('convites_compartilhamento');
    }
};
