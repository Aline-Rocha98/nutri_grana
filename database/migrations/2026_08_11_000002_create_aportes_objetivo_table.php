<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aportes_objetivo', function (Blueprint $table) {
            $table->id('id_aporte_objetivo');
            $table->foreignId('id_objetivo')
                ->constrained('objetivos', 'id_objetivo')
                ->cascadeOnDelete();
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->enum('tipo', ['manual', 'conta_bancaria']);
            $table->decimal('valor', 15, 2);
            $table->date('data_aporte');
            $table->foreignId('id_conta_bancaria')
                ->nullable()
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->nullOnDelete();
            $table->foreignId('id_lancamento')
                ->nullable()
                ->constrained('lancamentos', 'id_lancamento')
                ->nullOnDelete();
            $table->string('observacao', 255)->nullable();
            $table->timestamps();

            $table->index(['id_objetivo', 'data_aporte']);
            $table->index('id_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aportes_objetivo');
    }
};
