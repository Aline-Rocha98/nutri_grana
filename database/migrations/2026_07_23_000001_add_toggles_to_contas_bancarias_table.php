<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->enum('padrao_desconto', ['S', 'N'])->nullable()->default(null)->after('arquivada');
            $table->enum('exibir_resumo', ['S', 'N'])->nullable()->default(null)->after('padrao_desconto');

            $table->index('padrao_desconto');
            $table->index('exibir_resumo');
        });
    }

    public function down(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->dropIndex(['padrao_desconto']);
            $table->dropIndex(['exibir_resumo']);
            $table->dropColumn(['padrao_desconto', 'exibir_resumo']);
        });
    }
};
