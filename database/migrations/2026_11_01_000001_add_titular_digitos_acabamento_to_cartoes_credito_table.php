<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartoes_credito', function (Blueprint $table) {
            $table->string('nome_titular', 100)->nullable()->after('nome');
            $table->string('ultimos_digitos', 4)->nullable()->after('bandeira');
            $table->string('acabamento', 20)->default('esmeralda')->after('ultimos_digitos');
        });

        DB::table('cartoes_credito')
            ->whereNull('nome_titular')
            ->update([
                'nome_titular' => DB::raw('(SELECT usuario.nome FROM usuario WHERE usuario.id_usuario = cartoes_credito.id_usuario)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('cartoes_credito', function (Blueprint $table) {
            $table->dropColumn(['nome_titular', 'ultimos_digitos', 'acabamento']);
        });
    }
};
