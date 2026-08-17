<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos', function (Blueprint $table) {
            $table->id('id_lancamento');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('id_lancamento_pai')->nullable();
            $table->uuid('id_grupo_parcela')->nullable();
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->enum('tipo', ['receita', 'despesa']);
            $table->enum('forma_pagamento', ['conta_bancaria', 'cartao_credito']);
            $table->foreignId('id_conta_bancaria')
                ->nullable()
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->nullOnDelete();
            $table->foreignId('id_cartao_credito')
                ->nullable()
                ->constrained('cartoes_credito', 'id_cartao_credito')
                ->nullOnDelete();
            $table->foreignId('id_fatura_cartao')
                ->nullable()
                ->constrained('faturas_cartao', 'id_fatura_cartao')
                ->nullOnDelete();
            $table->enum('situacao', ['pendente', 'pago', 'cancelado'])->default('pendente');
            $table->foreignId('id_categoria')
                ->nullable()
                ->constrained('categorias', 'id_categoria')
                ->nullOnDelete();
            $table->text('observacao')->nullable();
            $table->unsignedSmallInteger('parcela_atual')->nullable();
            $table->unsignedSmallInteger('total_parcelas')->nullable();
            $table->enum('eh_recorrencia', ['S', 'N'])->default('N');
            $table->enum('frequencia_recorrencia', ['mensal', 'semanal', 'anual'])->nullable();
            $table->date('recorrencia_ate')->nullable();
            $table->date('recorrencia_gerada_ate')->nullable();
            $table->timestamps();

            $table->foreign('id_lancamento_pai')
                ->references('id_lancamento')
                ->on('lancamentos')
                ->nullOnDelete();

            $table->index(['id_usuario', 'data_vencimento']);
            $table->index(['id_usuario', 'situacao']);
            $table->index('id_conta_bancaria');
            $table->index('id_cartao_credito');
            $table->index('id_fatura_cartao');
            $table->index('id_lancamento_pai');
            $table->index('id_grupo_parcela');
            $table->index('id_categoria');
            $table->index(['eh_recorrencia', 'recorrencia_gerada_ate']);
        });

        Schema::table('faturas_cartao', function (Blueprint $table) {
            $table->foreign('id_lancamento_pagamento')
                ->references('id_lancamento')
                ->on('lancamentos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faturas_cartao', function (Blueprint $table) {
            $table->dropForeign(['id_lancamento_pagamento']);
        });

        Schema::dropIfExists('lancamentos');
    }
};
