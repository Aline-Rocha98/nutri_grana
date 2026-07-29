<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartoes_credito', function (Blueprint $table) {
            $table->id('id_cartao_credito');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('nome', 100);
            $table->decimal('limite_total', 15, 2)->default(0);
            $table->unsignedTinyInteger('dia_fechamento');
            $table->unsignedTinyInteger('dia_vencimento');
            $table->string('bandeira', 30);
            $table->enum('padrao', ['S', 'N'])->nullable()->default(null);
            $table->boolean('arquivada')->default(false);
            $table->timestamps();

            $table->index('padrao');
            $table->index('arquivada');
            $table->index('bandeira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartoes_credito');
    }
};
