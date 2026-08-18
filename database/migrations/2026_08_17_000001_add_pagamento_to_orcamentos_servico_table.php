<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orcamentos_servico', function (Blueprint $table) {
            $table->string('modalidade_pagamento', 20)->default('a_vista')->after('observacao');
            $table->unsignedSmallInteger('total_parcelas')->default(1)->after('modalidade_pagamento');
            $table->string('forma_pagamento', 30)->default('conta_bancaria')->after('total_parcelas');
            $table->foreignId('id_conta_bancaria')
                ->nullable()
                ->after('forma_pagamento')
                ->constrained('contas_bancarias', 'id_conta_bancaria')
                ->nullOnDelete();
            $table->foreignId('id_cartao_credito')
                ->nullable()
                ->after('id_conta_bancaria')
                ->constrained('cartoes_credito', 'id_cartao_credito')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orcamentos_servico', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_cartao_credito');
            $table->dropConstrainedForeignId('id_conta_bancaria');
            $table->dropColumn([
                'modalidade_pagamento',
                'total_parcelas',
                'forma_pagamento',
            ]);
        });
    }
};
