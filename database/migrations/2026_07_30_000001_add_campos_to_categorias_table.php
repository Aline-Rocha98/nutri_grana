<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->string('tipo', 20)->default('saida')->after('nome');
            $table->string('icone', 50)->default('category')->after('tipo');
            $table->string('cor', 7)->default('#6b7280')->after('icone');
            $table->enum('arquivada', ['S', 'N'])->default('N')->after('cor');

            $table->index('tipo');
            $table->index('arquivada');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropIndex(['arquivada']);
            $table->dropColumn(['tipo', 'icone', 'cor', 'arquivada']);
        });
    }
};
