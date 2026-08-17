<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lancamentos')) {
            DB::table('lancamentos')
                ->where('frequencia_recorrencia', 'a_cada_x_dias')
                ->update(['frequencia_recorrencia' => 'mensal']);

            DB::statement("ALTER TABLE lancamentos MODIFY COLUMN frequencia_recorrencia ENUM('mensal', 'semanal', 'anual') NULL");

            if (Schema::hasColumn('lancamentos', 'intervalo_dias')) {
                Schema::table('lancamentos', function (Blueprint $table) {
                    $table->dropColumn('intervalo_dias');
                });
            }
        }

        if (Schema::hasTable('rendas')) {
            DB::table('rendas')
                ->where('frequencia', 'a_cada_x_dias')
                ->update(['frequencia' => 'mensal']);

            DB::statement("ALTER TABLE rendas MODIFY COLUMN frequencia ENUM('mensal', 'semanal', 'anual') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lancamentos')) {
            DB::statement("ALTER TABLE lancamentos MODIFY COLUMN frequencia_recorrencia ENUM('mensal', 'semanal', 'anual', 'a_cada_x_dias') NULL");

            if (! Schema::hasColumn('lancamentos', 'intervalo_dias')) {
                Schema::table('lancamentos', function (Blueprint $table) {
                    $table->unsignedSmallInteger('intervalo_dias')->nullable()->after('frequencia_recorrencia');
                });
            }
        }

        if (Schema::hasTable('rendas')) {
            DB::statement("ALTER TABLE rendas MODIFY COLUMN frequencia ENUM('mensal', 'semanal', 'anual', 'a_cada_x_dias') NOT NULL");
        }
    }
};
