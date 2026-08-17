<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('id_renda')
                ->nullable()
                ->after('id_categoria')
                ->constrained('rendas', 'id_renda')
                ->nullOnDelete();
            $table->decimal('valor_previsto', 15, 2)
                ->nullable()
                ->after('valor');
            $table->index(['id_renda', 'data_vencimento']);
        });

        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN situacao ENUM('pendente', 'pago', 'cancelado', 'previsto', 'recebido') NOT NULL DEFAULT 'pendente'");
    }

    public function down(): void
    {
        DB::statement("UPDATE lancamentos SET situacao = 'pendente' WHERE situacao = 'previsto'");
        DB::statement("UPDATE lancamentos SET situacao = 'pago' WHERE situacao = 'recebido'");
        DB::statement("ALTER TABLE lancamentos MODIFY COLUMN situacao ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente'");

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropForeign(['id_renda']);
            $table->dropIndex(['id_renda', 'data_vencimento']);
            $table->dropColumn(['id_renda', 'valor_previsto']);
        });
    }
};
