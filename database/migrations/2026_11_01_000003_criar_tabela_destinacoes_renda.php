<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::dropIfExists('destinacoes_renda');
    }
};
