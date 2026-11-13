<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE aportes_objetivo MODIFY COLUMN tipo ENUM('manual', 'conta_bancaria', 'destinacao_renda') NOT NULL");

            return;
        }

        Schema::table('aportes_objetivo', function (Blueprint $table) {
            $table->enum('tipo', ['manual', 'conta_bancaria', 'destinacao_renda'])->change();
        });
    }

    public function down(): void
    {
        DB::table('aportes_objetivo')->where('tipo', 'destinacao_renda')->delete();

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE aportes_objetivo MODIFY COLUMN tipo ENUM('manual', 'conta_bancaria') NOT NULL");

            return;
        }

        Schema::table('aportes_objetivo', function (Blueprint $table) {
            $table->enum('tipo', ['manual', 'conta_bancaria'])->change();
        });
    }
};
