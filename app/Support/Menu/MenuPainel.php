<?php

namespace App\Support\Menu;

use App\Models\Usuario\Usuario;
use Illuminate\Support\Facades\Route;

class MenuPainel
{
    private const ICONES_MATERIAL = [
        'home' => 'grid_view',
        'dashboard' => 'dashboard',
        'financeiro' => 'account_balance_wallet',
        'objetivos' => 'flag',
        'orcamentos' => 'donut_large',
        'perfil' => 'person',
    ];

    public static function obterItens(): array
    {
        return [
            [
                'tipo' => 'link',
                'id' => 'home',
                'rotulo' => 'Home',
                'icone' => 'home',
                'rota' => 'home',
            ],
            [
                'tipo' => 'link',
                'id' => 'dashboard',
                'rotulo' => 'Dashboard',
                'icone' => 'dashboard',
                'rota' => 'dashboard',
            ],
            [
                'tipo' => 'grupo',
                'id' => 'financeiro',
                'rotulo' => 'Financeiro',
                'icone' => 'financeiro',
                'filhos' => [
                    ['rotulo' => 'Cartões de crédito', 'rota' => 'cartoes-credito.index'],
                    ['rotulo' => 'Categorias', 'rota' => 'categorias.index'],
                    ['rotulo' => 'Contas bancárias', 'rota' => 'contas-bancarias.index'],
                    ['rotulo' => 'Lançamentos', 'rota' => 'lancamentos.index'],
                    ['rotulo' => 'Rendas', 'rota' => 'rendas.index'],
                ],
            ],
            [
                'tipo' => 'link',
                'id' => 'objetivos',
                'rotulo' => 'Objetivos',
                'icone' => 'objetivos',
                'rota' => 'objetivos.index',
            ],
            [
                'tipo' => 'grupo',
                'id' => 'orcamentos',
                'rotulo' => 'Orçamentos',
                'icone' => 'orcamentos',
                'filhos' => [
                    [
                        'rotulo' => 'Por categoria',
                        'rota' => 'orcamentos.index',
                        'parametros' => ['tipo' => 'por_categoria'],
                    ],
                    [
                        'rotulo' => 'Por serviço',
                        'rota' => 'orcamentos.index',
                        'parametros' => ['tipo' => 'por_servico'],
                    ],
                ],
            ],
            [
                'tipo' => 'link',
                'id' => 'perfil',
                'rotulo' => 'Perfil',
                'icone' => 'perfil',
                'rota' => 'usuario.perfil',
            ],
        ];
    }

    public static function prepararItens(): array
    {
        return array_map(
            fn (array $item) => $item['tipo'] === 'grupo'
                ? self::prepararGrupo($item)
                : self::prepararLink($item),
            self::obterItens()
        );
    }

    public static function obterPerfilUsuario(?Usuario $usuario): array
    {
        if (! $usuario) {
            return [
                'nome' => '',
                'iniciais' => 'NG',
                'foto_url' => null,
            ];
        }

        $iniciais = collect(explode(' ', $usuario->nome ?? ''))
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        return [
            'nome' => $usuario->nome,
            'iniciais' => $iniciais ?: 'NG',
            'foto_url' => $usuario->foto_url,
        ];
    }

    public static function gruposInicialmenteAbertos(): array
    {
        $estado = [];

        foreach (self::obterItens() as $item) {
            if ($item['tipo'] !== 'grupo') {
                continue;
            }

            $estado[$item['id']] = self::grupoAtivo($item['filhos']);
        }

        return $estado;
    }

    public static function resolverUrl(?string $nomeRota, array $parametros = []): string
    {
        if (! $nomeRota || ! Route::has($nomeRota)) {
            return '#';
        }

        return route($nomeRota, $parametros);
    }

    public static function linkAtivo(?string $nomeRota, array $parametros = []): bool
    {
        if (! $nomeRota || ! request()->routeIs($nomeRota)) {
            return false;
        }

        foreach ($parametros as $chave => $valor) {
            $atual = request()->query($chave);

            if ($chave === 'tipo' && ($valor === 'por_categoria' || $valor === null || $valor === '')) {
                return $atual === null || $atual === '' || $atual === 'por_categoria';
            }

            if ((string) $atual !== (string) $valor) {
                return false;
            }
        }

        return true;
    }

    public static function grupoAtivo(array $filhos): bool
    {
        foreach ($filhos as $filho) {
            if (self::linkAtivo($filho['rota'] ?? null, $filho['parametros'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    private static function prepararLink(array $item): array
    {
        $parametros = $item['parametros'] ?? [];

        return array_merge($item, [
            'url' => self::resolverUrl($item['rota'] ?? null, $parametros),
            'ativo' => self::linkAtivo($item['rota'] ?? null, $parametros),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]);
    }

    private static function prepararGrupo(array $item): array
    {
        $filhosOrdenados = collect($item['filhos'])
            ->sortBy(fn (array $filho) => mb_strtolower($filho['rotulo'] ?? ''), SORT_NATURAL)
            ->values()
            ->all();

        $filhos = array_map(
            function (array $filho) {
                $parametros = $filho['parametros'] ?? [];

                return array_merge($filho, [
                    'url' => self::resolverUrl($filho['rota'] ?? null, $parametros),
                    'ativo' => self::linkAtivo($filho['rota'] ?? null, $parametros),
                ]);
            },
            $filhosOrdenados
        );

        return array_merge($item, [
            'filhos' => $filhos,
            'ativo' => self::grupoAtivo($filhosOrdenados),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]);
    }

    private static function iconeMaterial(string $chave): string
    {
        return self::ICONES_MATERIAL[$chave] ?? 'circle';
    }
}
