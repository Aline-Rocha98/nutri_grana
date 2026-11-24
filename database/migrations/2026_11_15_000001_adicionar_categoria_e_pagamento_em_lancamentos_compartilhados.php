<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $idsLancamentosPessoais = DB::table('lancamentos_compartilhados')
            ->whereNotNull('id_lancamento_pessoal')
            ->pluck('id_lancamento_pessoal');

        Schema::table('lancamentos_compartilhados', function (Blueprint $table) {
            $table->dropForeign(['id_lancamento_pessoal']);
            $table->dropColumn('id_lancamento_pessoal');
        });

        if ($idsLancamentosPessoais->isNotEmpty()) {
            DB::table('lancamentos')
                ->whereIn('id_lancamento', $idsLancamentosPessoais)
                ->delete();
        }

        Schema::table('lancamentos_compartilhados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categoria')->nullable()->after('id_usuario_responsavel');
            $table->string('forma_pagamento', 30)->nullable()->after('data_pagamento');
            $table->unsignedBigInteger('id_conta_bancaria')->nullable()->after('forma_pagamento');
            $table->unsignedBigInteger('id_cartao_credito')->nullable()->after('id_conta_bancaria');

            $table->foreign('id_categoria', 'lanc_comp_categoria_fk')
                ->references('id_categoria')->on('categorias')
                ->nullOnDelete();

            $table->foreign('id_conta_bancaria', 'lanc_comp_conta_fk')
                ->references('id_conta_bancaria')->on('contas_bancarias')
                ->nullOnDelete();

            $table->foreign('id_cartao_credito', 'lanc_comp_cartao_fk')
                ->references('id_cartao_credito')->on('cartoes_credito')
                ->nullOnDelete();

            $table->index(['id_usuario_responsavel', 'data_vencimento'], 'lanc_comp_responsavel_venc_idx');
            $table->index(['id_categoria', 'data_vencimento'], 'lanc_comp_categoria_venc_idx');
        });

        Schema::table('recorrencias_compartilhadas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_categoria')->nullable()->after('id_usuario_responsavel');

            $table->foreign('id_categoria', 'rec_comp_categoria_fk')
                ->references('id_categoria')->on('categorias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recorrencias_compartilhadas', function (Blueprint $table) {
            $table->dropForeign(['id_categoria']);
            $table->dropColumn('id_categoria');
        });

        Schema::table('lancamentos_compartilhados', function (Blueprint $table) {
            $table->dropForeign(['id_categoria']);
            $table->dropForeign(['id_conta_bancaria']);
            $table->dropForeign(['id_cartao_credito']);
            $table->dropIndex('lanc_comp_responsavel_venc_idx');
            $table->dropIndex('lanc_comp_categoria_venc_idx');
            $table->dropColumn(['id_categoria', 'forma_pagamento', 'id_conta_bancaria', 'id_cartao_credito']);
        });

        Schema::table('lancamentos_compartilhados', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lancamento_pessoal')->nullable()->after('id_usuario_baixa');

            $table->foreign('id_lancamento_pessoal', 'lanc_comp_lanc_pessoal_fk')
                ->references('id_lancamento')->on('lancamentos')
                ->nullOnDelete();
        });
    }
};
