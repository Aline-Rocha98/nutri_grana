<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_plano_financeiro')
                ->nullable()
                ->after('id_conta_bancaria');

            $table->foreign('id_plano_financeiro', 'rendas_plano_fk')
                ->references('id_plano_financeiro')->on('planos_financeiros')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rendas', function (Blueprint $table) {
            $table->dropForeign('rendas_plano_fk');
            $table->dropColumn('id_plano_financeiro');
        });
    }
};
