<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendas', function (Blueprint $table) {
            $table->id('id_renda');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('descricao', 255);
            $table->decimal('valor_esperado', 15, 2);
            $table->foreignId('id_conta_bancaria')
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->restrictOnDelete();
            $table->enum('frequencia', ['mensal', 'semanal', 'anual']);
            $table->unsignedTinyInteger('dia_esperado');
            $table->date('data_inicio');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'descricao']);
            $table->index('id_conta_bancaria');
            $table->index('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendas');
    }
};
