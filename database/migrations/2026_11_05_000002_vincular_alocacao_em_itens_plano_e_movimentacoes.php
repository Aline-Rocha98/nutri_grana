<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itens_plano', function (Blueprint $table) {
            $table->unsignedBigInteger('id_alocacao')->nullable()->after('id_usuario');

            $table->foreign('id_alocacao', 'ip_alocacao_fk')
                ->references('id_alocacao')->on('alocacoes')
                ->nullOnDelete();
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->unsignedBigInteger('id_alocacao')->nullable()->after('id_item_plano');
            $table->unsignedBigInteger('id_alocacao_origem')->nullable()->after('id_alocacao');
            $table->unsignedBigInteger('id_conta_bancaria_resgate')->nullable()->after('id_conta_bancaria_origem');

            $table->foreign('id_alocacao', 'msg_alocacao_fk')
                ->references('id_alocacao')->on('alocacoes')
                ->nullOnDelete();
            $table->foreign('id_alocacao_origem', 'msg_alocacao_origem_fk')
                ->references('id_alocacao')->on('alocacoes')
                ->nullOnDelete();
            $table->foreign('id_conta_bancaria_resgate', 'msg_conta_resgate_fk')
                ->references('id_conta_bancaria')->on('contas_bancarias')
                ->restrictOnDelete();

            $table->index(['id_alocacao', 'tipo'], 'msg_alocacao_tipo_idx');
            $table->index(['id_alocacao_origem', 'tipo'], 'msg_alocacao_origem_tipo_idx');
            $table->index(['id_conta_bancaria_resgate', 'tipo'], 'msg_conta_resgate_tipo_idx');
        });

        $this->alterarTiposMovimentacao(['separacao', 'resgate', 'transferencia']);

        $this->criarAlocacoesAPartirDosItensQueSeparam();
        $this->vincularMovimentacoesAsAlocacoes();
        $this->consolidarSaldosDasAlocacoes();
    }

    public function down(): void
    {
        DB::table('movimentacoes_saldo_guardado')->where('tipo', 'transferencia')->delete();

        $this->alterarTiposMovimentacao(['separacao', 'resgate']);

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->dropForeign('msg_alocacao_fk');
            $table->dropForeign('msg_alocacao_origem_fk');
            $table->dropForeign('msg_conta_resgate_fk');
            $table->dropIndex('msg_alocacao_tipo_idx');
            $table->dropIndex('msg_alocacao_origem_tipo_idx');
            $table->dropIndex('msg_conta_resgate_tipo_idx');
        });

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) {
            $table->dropColumn(['id_alocacao', 'id_alocacao_origem', 'id_conta_bancaria_resgate']);
        });

        Schema::table('itens_plano', function (Blueprint $table) {
            $table->dropForeign('ip_alocacao_fk');
        });

        Schema::table('itens_plano', function (Blueprint $table) {
            $table->dropColumn('id_alocacao');
        });

        DB::table('alocacoes')->delete();
    }

    private function alterarTiposMovimentacao(array $tipos): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $valores = implode(', ', array_map(fn (string $tipo) => "'{$tipo}'", $tipos));
            DB::statement("ALTER TABLE movimentacoes_saldo_guardado MODIFY COLUMN tipo ENUM({$valores}) NOT NULL");

            return;
        }

        Schema::table('movimentacoes_saldo_guardado', function (Blueprint $table) use ($tipos) {
            $table->enum('tipo', $tipos)->change();
        });
    }

    private function criarAlocacoesAPartirDosItensQueSeparam(): void
    {
        $agora = Carbon::now();
        $idsAlocacoesPorChave = [];

        $itensQueSeparam = DB::table('itens_plano')
            ->where('separar_do_saldo', 'S')
            ->orderBy('id_item_plano')
            ->get(['id_item_plano', 'id_usuario', 'nome']);

        foreach ($itensQueSeparam as $item) {
            $nomeNormalizado = mb_substr(trim((string) $item->nome), 0, 120);
            $chave = $item->id_usuario.'|'.mb_strtolower($nomeNormalizado);

            if (! isset($idsAlocacoesPorChave[$chave])) {
                $idsAlocacoesPorChave[$chave] = DB::table('alocacoes')->insertGetId([
                    'id_usuario' => $item->id_usuario,
                    'nome' => $nomeNormalizado,
                    'saldo_consolidado' => 0,
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ], 'id_alocacao');
            }

            DB::table('itens_plano')
                ->where('id_item_plano', $item->id_item_plano)
                ->update(['id_alocacao' => $idsAlocacoesPorChave[$chave]]);
        }
    }

    private function vincularMovimentacoesAsAlocacoes(): void
    {
        $alocacoesPorItem = DB::table('itens_plano')
            ->whereNotNull('id_alocacao')
            ->pluck('id_alocacao', 'id_item_plano');

        foreach ($alocacoesPorItem as $idItemPlano => $idAlocacao) {
            DB::table('movimentacoes_saldo_guardado')
                ->where('id_item_plano', $idItemPlano)
                ->update(['id_alocacao' => $idAlocacao]);
        }
    }

    private function consolidarSaldosDasAlocacoes(): void
    {
        $agora = Carbon::now();

        $saldosPorAlocacao = DB::table('movimentacoes_saldo_guardado')
            ->whereNotNull('id_alocacao')
            ->where('tipo', 'separacao')
            ->groupBy('id_alocacao')
            ->selectRaw('id_alocacao, SUM(valor) as total')
            ->pluck('total', 'id_alocacao');

        foreach ($saldosPorAlocacao as $idAlocacao => $total) {
            DB::table('alocacoes')
                ->where('id_alocacao', $idAlocacao)
                ->update([
                    'saldo_consolidado' => round((float) $total, 2),
                    'saldo_consolidado_em' => $agora,
                ]);
        }
    }
};
