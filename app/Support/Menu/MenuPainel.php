<?php

namespace App\Support\Menu;

use App\Models\Usuario\Usuario;
use Illuminate\Support\Facades\Route;

class MenuPainel
{
    private const ICONES_MATERIAL = [
        'home' => 'grid_view',
        'financeiro' => 'account_balance_wallet',
        'cadastros' => 'folder',
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
                'tipo' => 'grupo',
                'id' => 'financeiro',
                'rotulo' => 'Financeiro',
                'icone' => 'financeiro',
                'filhos' => [
                    ['rotulo' => 'Contas bancárias', 'rota' => 'contas-bancarias.index'],
                    ['rotulo' => 'Cartões de crédito', 'rota' => 'cartoes-credito.index'],
                    ['rotulo' => 'Categorias', 'rota' => 'categorias.index'],
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

    public static function resolverUrl(?string $nomeRota): string
    {
        if (! $nomeRota || ! Route::has($nomeRota)) {
            return '#';
        }

        return route($nomeRota);
    }

    public static function linkAtivo(?string $nomeRota): bool
    {
        return $nomeRota && request()->routeIs($nomeRota);
    }

    public static function grupoAtivo(array $filhos): bool
    {
        foreach ($filhos as $filho) {
            if (isset($filho['rota']) && request()->routeIs($filho['rota'])) {
                return true;
            }
        }

        return false;
    }

    private static function prepararLink(array $item): array
    {
        return array_merge($item, [
            'url' => self::resolverUrl($item['rota'] ?? null),
            'ativo' => self::linkAtivo($item['rota'] ?? null),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]);
    }

    private static function prepararGrupo(array $item): array
    {
        $filhos = array_map(
            fn (array $filho) => array_merge($filho, [
                'url' => self::resolverUrl($filho['rota'] ?? null),
                'ativo' => self::linkAtivo($filho['rota'] ?? null),
            ]),
            $item['filhos']
        );

        return array_merge($item, [
            'filhos' => $filhos,
            'ativo' => self::grupoAtivo($item['filhos']),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]);
    }

    private static function iconeMaterial(string $chave): string
    {
        return self::ICONES_MATERIAL[$chave] ?? 'circle';
    }
}
