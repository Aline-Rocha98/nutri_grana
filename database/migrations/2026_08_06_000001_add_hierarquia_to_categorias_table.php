<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->index('id_usuario', 'categorias_id_usuario_index');
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['id_usuario', 'nome']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->foreignId('id_categoria_pai')
                ->nullable()
                ->after('id_usuario')
                ->constrained('categorias', 'id_categoria')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('nivel')
                ->default(1)
                ->after('id_categoria_pai');

            $table->index(['id_usuario', 'id_categoria_pai']);
            $table->index(['id_usuario', 'nivel']);
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            $table->dropForeign(['id_categoria_pai']);
            $table->dropIndex(['id_usuario', 'id_categoria_pai']);
            $table->dropIndex(['id_usuario', 'nivel']);
            $table->dropColumn(['id_categoria_pai', 'nivel']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->unique(['id_usuario', 'nome']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropIndex('categorias_id_usuario_index');
        });
    }
};
