<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('convites_compartilhamento')
            ->where('status', 'pendente')
            ->update(['status' => 'expirado', 'updated_at' => now()]);

        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->dropUnique('conv_comp_codigo_hash_uq');
        });

        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'limite_usos', 'quantidade_usos']);
        });

        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->unsignedBigInteger('id_usuario_convidado')->nullable()->after('id_espaco_compartilhado');
            $table->unsignedTinyInteger('tentativas_invalidas')->default(0)->after('codigo_hash');

            $table->foreign('id_usuario_convidado', 'conv_comp_convidado_fk')
                ->references('id_usuario')->on('usuario')
                ->cascadeOnDelete();

            $table->index(['id_usuario_convidado', 'status'], 'conv_comp_convidado_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->dropForeign('conv_comp_convidado_fk');
            $table->dropIndex('conv_comp_convidado_status_idx');
        });

        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->dropColumn(['id_usuario_convidado', 'tentativas_invalidas']);
        });

        Schema::table('convites_compartilhamento', function (Blueprint $table) {
            $table->enum('tipo', ['email', 'codigo'])->default('email')->after('id_espaco_compartilhado');
            $table->unsignedSmallInteger('limite_usos')->default(1)->after('codigo_hash');
            $table->unsignedSmallInteger('quantidade_usos')->default(0)->after('limite_usos');
            $table->unique('codigo_hash', 'conv_comp_codigo_hash_uq');
        });
    }
};
