<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id('id_orcamento');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->foreignId('id_categoria')
                ->nullable()
                ->constrained('categorias', 'id_categoria')
                ->cascadeOnDelete();
            $table->decimal('valor_mensal', 15, 2);
            $table->enum('exibir_dashboard', ['S', 'N'])->default('N');
            $table->timestamps();

            $table->index(['id_usuario', 'tipo']);
            $table->index(['id_usuario', 'exibir_dashboard']);
            $table->unique(['id_usuario', 'tipo', 'id_categoria'], 'orcamentos_usuario_tipo_categoria_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
