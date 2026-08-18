<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos_servico', function (Blueprint $table) {
            $table->id('id_orcamento_servico');
            $table->foreignId('id_usuario')
                ->constrained('usuario', 'id_usuario')
                ->cascadeOnDelete();
            $table->string('descricao', 255);
            $table->decimal('valor', 15, 2);
            $table->date('data_orcamento');
            $table->date('data_validade');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['id_usuario', 'data_validade']);
            $table->index(['id_usuario', 'data_orcamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos_servico');
    }
};
