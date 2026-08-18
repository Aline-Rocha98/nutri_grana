<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos_servico', function (Blueprint $table) {
            $table->string('fornecedor', 255)->nullable()->after('descricao');
            $table->string('status', 20)->default('em_analise')->after('observacao');
            $table->foreignId('id_categoria')
                ->nullable()
                ->after('status')
                ->constrained('categorias', 'id_categoria')
                ->nullOnDelete();
            $table->foreignId('id_subcategoria')
                ->nullable()
                ->after('id_categoria')
                ->constrained('categorias', 'id_categoria')
                ->nullOnDelete();
            $table->timestamp('data_aprovacao')->nullable()->after('id_cartao_credito');
            $table->timestamp('data_recusa')->nullable()->after('data_aprovacao');
            $table->timestamp('data_conclusao')->nullable()->after('data_recusa');

            $table->index(['id_usuario', 'status']);
        });

        Schema::table('orcamentos_servico', function (Blueprint $table) {
            $table->string('modalidade_pagamento', 20)->nullable()->default(null)->change();
            $table->unsignedSmallInteger('total_parcelas')->nullable()->default(null)->change();
            $table->string('forma_pagamento', 30)->nullable()->default(null)->change();
        });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('id_orcamento_servico')
                ->nullable()
                ->after('id_renda')
                ->constrained('orcamentos_servico', 'id_orcamento_servico')
                ->nullOnDelete();

            $table->index('id_orcamento_servico');
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_orcamento_servico');
        });

        Schema::table('orcamentos_servico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_subcategoria');
            $table->dropConstrainedForeignId('id_categoria');
            $table->dropColumn([
                'fornecedor',
                'status',
                'data_aprovacao',
                'data_recusa',
                'data_conclusao',
            ]);
        });
    }
};
