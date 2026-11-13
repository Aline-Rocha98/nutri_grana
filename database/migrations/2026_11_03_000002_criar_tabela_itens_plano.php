<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_plano', function (Blueprint $table) {
            $table->id('id_item_plano');
            $table->unsignedBigInteger('id_plano_financeiro');
            $table->unsignedBigInteger('id_usuario');
            $table->string('nome', 120);
            $table->string('descricao', 255)->nullable();
            $table->string('cor', 20);
            $table->enum('tipo_calculo', ['percentual', 'valor_fixo']);
            $table->decimal('valor', 15, 2);
            $table->enum('separar_do_saldo', ['S', 'N'])->default('N');
            $table->unsignedBigInteger('id_conta_bancaria_destino')->nullable();
            $table->unsignedBigInteger('id_objetivo')->nullable();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();

            $table->foreign('id_plano_financeiro', 'ip_plano_fk')
                ->references('id_plano_financeiro')->on('planos_financeiros')
                ->cascadeOnDelete();
            $table->foreign('id_usuario', 'ip_usuario_fk')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();
            $table->foreign('id_conta_bancaria_destino', 'ip_conta_fk')
                ->references('id_conta_bancaria')->on('contas_bancarias')
                ->restrictOnDelete();
            $table->foreign('id_objetivo', 'ip_objetivo_fk')
                ->references('id_objetivo')->on('objetivos')
                ->nullOnDelete();

            $table->index(['id_plano_financeiro', 'ordem'], 'ip_plano_ordem_idx');
            $table->index(['id_plano_financeiro', 'separar_do_saldo'], 'ip_plano_separar_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_plano');
    }
};
