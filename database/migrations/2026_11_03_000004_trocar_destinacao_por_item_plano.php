<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->index('id_lancamento_origem', 'msg_lancamento_origem_index');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->dropUnique('msg_lancamento_destinacao_unique');
            $table->dropForeign(['id_destinacao_renda']);
            $table->dropColumn('id_destinacao_renda');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->unsignedBigInteger('id_item_plano')
                ->nullable()
                ->after('id_conta_bancaria_origem');

            $table->foreign('id_item_plano', 'msg_item_plano_fk')
                ->references('id_item_plano')->on('itens_plano')
                ->nullOnDelete();

            $table->unique(['id_lancamento_origem', 'id_item_plano'], 'msg_lanc_item_uq');
        });

        Schema::dropIfExists('destinacoes_renda');
    }

    public function down(): void
    {
        Schema::create('destinacoes_renda', function (Blueprint $table) {
            $table->id('id_destinacao_renda');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->foreignId('id_renda')
                ->constrained('rendas', 'id_renda')
                ->cascadeOnDelete();
            $table->string('descricao', 255);
            $table->enum('tipo_calculo', ['percentual', 'valor_fixo']);
            $table->decimal('valor', 15, 2);
            $table->foreignId('id_conta_bancaria_destino')
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->restrictOnDelete();
            $table->foreignId('id_objetivo')
                ->nullable()
                ->constrained('objetivos', 'id_objetivo')
                ->nullOnDelete();
            $table->enum('ativa', ['S', 'N'])->default('S');
            $table->timestamps();

            $table->index(['id_renda', 'ativa']);
            $table->index('id_usuario');
            $table->index('id_conta_bancaria_destino');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->dropUnique('msg_lanc_item_uq');
            $table->dropForeign('msg_item_plano_fk');
            $table->dropColumn('id_item_plano');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->foreignId('id_destinacao_renda')
                ->nullable()
                ->after('id_conta_bancaria_origem')
                ->constrained('destinacoes_renda', 'id_destinacao_renda')
                ->nullOnDelete();

            $table->unique(['id_lancamento_origem', 'id_destinacao_renda'], 'msg_lancamento_destinacao_unique');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->dropIndex('msg_lancamento_origem_index');
        });
    }
};
