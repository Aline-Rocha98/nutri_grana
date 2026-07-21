<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_conta_bancaria')->nullable()->after('id_forma_pagamento');
            $table->foreign('id_conta_bancaria')
                ->references('id_conta_bancaria')
                ->on('contas_bancarias')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropForeign(['id_conta_bancaria']);
            $table->dropColumn('id_conta_bancaria');
        });
    }
};
