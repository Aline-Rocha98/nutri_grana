<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tabelas = [
        'contas_bancarias',
        'cartoes_credito',
        'categorias',
    ];

    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (! Schema::hasTable($tabela)) {
                continue;
            }

            $temArquivada = Schema::hasColumn($tabela, 'arquivada');
            $temArquivadaSn = Schema::hasColumn($tabela, 'arquivada_sn');

            if ($temArquivada && $this->colunaJaEhSimNao($tabela, 'arquivada')) {
                if ($temArquivadaSn) {
                    DB::statement("ALTER TABLE `{$tabela}` DROP COLUMN `arquivada_sn`");
                }

                continue;
            }

            // Estado parcial: só arquivada_sn existe (rename anterior falhou)
            if (! $temArquivada && $temArquivadaSn) {
                $this->promoverArquivadaSn($tabela);

                continue;
            }

            if (! $temArquivada) {
                continue;
            }

            if (! $temArquivadaSn) {
                DB::statement(
                    "ALTER TABLE `{$tabela}` ADD COLUMN `arquivada_sn` ENUM('S','N') NOT NULL DEFAULT 'N' AFTER `arquivada`"
                );
            }

            DB::statement(
                "UPDATE `{$tabela}` SET `arquivada_sn` = CASE WHEN `arquivada` = 1 THEN 'S' ELSE 'N' END"
            );

            $this->droparIndiceArquivadaSeExistir($tabela);

            DB::statement("ALTER TABLE `{$tabela}` DROP COLUMN `arquivada`");

            $this->promoverArquivadaSn($tabela);
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (! Schema::hasTable($tabela) || ! Schema::hasColumn($tabela, 'arquivada')) {
                continue;
            }

            if (! $this->colunaJaEhSimNao($tabela, 'arquivada')) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$tabela}` ADD COLUMN `arquivada_bool` TINYINT(1) NOT NULL DEFAULT 0 AFTER `arquivada`"
            );

            DB::statement(
                "UPDATE `{$tabela}` SET `arquivada_bool` = CASE WHEN `arquivada` = 'S' THEN 1 ELSE 0 END"
            );

            $this->droparIndiceArquivadaSeExistir($tabela);

            DB::statement("ALTER TABLE `{$tabela}` DROP COLUMN `arquivada`");
            DB::statement(
                "ALTER TABLE `{$tabela}` CHANGE `arquivada_bool` `arquivada` TINYINT(1) NOT NULL DEFAULT 0"
            );
            DB::statement("ALTER TABLE `{$tabela}` ADD INDEX `{$tabela}_arquivada_index` (`arquivada`)");
        }
    }

    private function promoverArquivadaSn(string $tabela): void
    {
        DB::statement(
            "ALTER TABLE `{$tabela}` CHANGE `arquivada_sn` `arquivada` ENUM('S','N') NOT NULL DEFAULT 'N'"
        );

        if (! $this->indiceArquivadaExiste($tabela)) {
            DB::statement("ALTER TABLE `{$tabela}` ADD INDEX `{$tabela}_arquivada_index` (`arquivada`)");
        }
    }

    private function colunaJaEhSimNao(string $tabela, string $coluna): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return true;
        }

        $resultado = DB::selectOne(
            'SHOW COLUMNS FROM `'.$tabela.'` WHERE Field = ?',
            [$coluna]
        );

        if ($resultado === null) {
            return false;
        }

        $tipo = strtolower((string) $resultado->Type);

        return str_starts_with($tipo, 'enum')
            && str_contains($tipo, "'s'")
            && str_contains($tipo, "'n'");
    }

    private function indiceArquivadaExiste(string $tabela): bool
    {
        $indices = DB::select('SHOW INDEX FROM `'.$tabela.'` WHERE Column_name = ?', ['arquivada']);

        return collect($indices)
            ->pluck('Key_name')
            ->reject(fn (string $nome) => $nome === 'PRIMARY')
            ->isNotEmpty();
    }

    private function droparIndiceArquivadaSeExistir(string $tabela): void
    {
        $indices = DB::select('SHOW INDEX FROM `'.$tabela.'` WHERE Column_name = ?', ['arquivada']);

        $nomes = collect($indices)
            ->pluck('Key_name')
            ->unique()
            ->reject(fn (string $nome) => $nome === 'PRIMARY');

        foreach ($nomes as $nome) {
            DB::statement("ALTER TABLE `{$tabela}` DROP INDEX `{$nome}`");
        }
    }
};
