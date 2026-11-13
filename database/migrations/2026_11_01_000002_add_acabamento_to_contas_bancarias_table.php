<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->string('acabamento', 20)->default('esmeralda')->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('contas_bancarias', function (Blueprint $table) {
            $table->dropColumn('acabamento');
        });
    }
};
