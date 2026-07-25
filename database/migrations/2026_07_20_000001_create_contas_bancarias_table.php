<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_bancarias', function (Blueprint $table) {
            $table->id('id_conta_bancaria');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('nome', 100);
            $table->decimal('saldo_inicial', 15, 2)->default(0);
            $table->string('tipo', 20);
            $table->boolean('arquivada')->default(false);
            $table->timestamps();

            $table->index('arquivada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_bancarias');
    }
};
