<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->id('id_movimentacao_saldo_guardado');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->foreignId('id_conta_bancaria')
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->restrictOnDelete();
            $table->foreignId('id_conta_bancaria_origem')
                ->nullable()
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->restrictOnDelete();
            $table->foreignId('id_destinacao_renda')
                ->nullable()
                ->constrained('destinacoes_renda', 'id_destinacao_renda')
                ->nullOnDelete();
            $table->foreignId('id_lancamento_origem')
                ->nullable()
                ->constrained('lancamentos', 'id_lancamento')
                ->nullOnDelete();
            $table->foreignId('id_aporte_objetivo')
                ->nullable()
                ->constrained('aportes_objetivo', 'id_aporte_objetivo')
                ->nullOnDelete();
            $table->enum('tipo', ['separacao', 'resgate']);
            $table->decimal('valor', 15, 2);
            $table->date('data_movimentacao');
            $table->string('observacao', 255)->nullable();
            $table->timestamps();

            $table->unique(['id_lancamento_origem', 'id_destinacao_renda'], 'msg_lancamento_destinacao_unique');
            $table->index(['id_conta_bancaria', 'tipo']);
            $table->index(['id_conta_bancaria_origem', 'tipo']);
            $table->index('id_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_saldo_guardado');
    }
};
