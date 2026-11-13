<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alocacoes', function (Blueprint $table) {
            $table->id('id_alocacao');
            $table->unsignedBigInteger('id_usuario');
            $table->string('nome', 120);
            $table->decimal('saldo_consolidado', 15, 2)->default(0);
            $table->timestamp('saldo_consolidado_em')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario', 'aloc_usuario_fk')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();

            $table->unique(['id_usuario', 'nome'], 'aloc_usuario_nome_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alocacoes');
    }
};
