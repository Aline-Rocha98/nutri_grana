<?php

namespace App\Support\Menu;

use App\Models\Usuario\Usuario;
use App\Services\EspacoCompartilhado\EspacoCompartilhadoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class MenuPainel
{
    private const ICONES_MATERIAL = [
        'home' => 'grid_view',
        'dashboard' => 'dashboard',
        'financeiro' => 'account_balance_wallet',
        'rendas' => 'payments',
        'objetivos' => 'flag',
        'alocacoes' => 'savings',
        'orcamentos' => 'donut_large',
        'compartilhados' => 'group',
        'perfil' => 'person',
    ];

    public static function obterItens(?Usuario $usuario = null): array
    {
        $itens = [
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
                ],
            ],
            [
                'tipo' => 'grupo',
                'id' => 'rendas',
                'rotulo' => 'Rendas',
                'icone' => 'rendas',
                'filhos' => [
                    ['rotulo' => 'Minhas rendas', 'rota' => 'rendas.index'],
                    ['rotulo' => 'Plano financeiro', 'rota' => 'planos-financeiros.index'],
                ],
            ],
            [
                'tipo' => 'link',
                'id' => 'alocacoes',
                'rotulo' => 'Alocações',
                'icone' => 'alocacoes',
                'rota' => 'alocacoes.index',
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
        ];

        if ($usuario?->possuiCompartilhamentoHabilitado()) {
            $itens[] = self::montarGrupoCompartilhados($usuario);
        }

        $itens[] = [
            'tipo' => 'link',
            'id' => 'perfil',
            'rotulo' => 'Perfil',
            'icone' => 'perfil',
            'rota' => 'usuario.perfil',
        ];

        return $itens;
    }

    public static function prepararItens(?Usuario $usuario = null): array
    {
        return array_map(
            fn (array $item) => $item['tipo'] === 'grupo'
                ? self::prepararGrupo($item)
                : self::prepararLink($item),
            self::obterItens($usuario)
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

    public static function gruposInicialmenteAbertos(?Usuario $usuario = null): array
    {
        $estado = [];

        foreach (self::obterItens($usuario) as $item) {
            if ($item['tipo'] !== 'grupo') {
                continue;
            }

            $estado[$item['id']] = self::grupoAtivo($item['filhos']);
        }

        return $estado;
    }

    public static function extrairGruposAbertosDosItensPreparados(array $itensPreparados): array
    {
        return collect($itensPreparados)
            ->where('tipo', 'grupo')
            ->mapWithKeys(fn (array $grupo) => [$grupo['id'] => (bool) $grupo['ativo']])
            ->all();
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
            if ($valor instanceof Model) {
                if (! self::parametroDeRotaCorrespondeAoModelo($chave, $valor)) {
                    return false;
                }

                continue;
            }

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

    private static function montarGrupoCompartilhados(Usuario $usuario): array
    {
        $espacos = app(EspacoCompartilhadoService::class)
            ->listarOpcoesDeEspacosDoUsuario((int) $usuario->id_usuario);

        $filhosDosEspacos = $espacos
            ->map(fn ($espaco) => [
                'rotulo' => $espaco->nome,
                'rota' => 'compartilhados.show',
                'parametros' => ['espacoCompartilhado' => $espaco],
            ])
            ->all();

        return [
            'tipo' => 'grupo',
            'id' => 'compartilhados',
            'rotulo' => 'Compartilhados',
            'icone' => 'compartilhados',
            'manterOrdemDosFilhos' => true,
            'filhos' => [
                ...$filhosDosEspacos,
                ['rotulo' => 'Gerenciar espaços', 'rota' => 'compartilhados.index'],
            ],
        ];
    }

    private static function parametroDeRotaCorrespondeAoModelo(string $chave, Model $modelo): bool
    {
        $parametroAtual = request()->route($chave);

        return $parametroAtual instanceof Model
            && $parametroAtual::class === $modelo::class
            && (string) $parametroAtual->getKey() === (string) $modelo->getKey();
    }

    private static function prepararLink(array $item): array
    {
        $parametros = $item['parametros'] ?? [];

        return self::removerParametrosDoItem(array_merge($item, [
            'url' => self::resolverUrl($item['rota'] ?? null, $parametros),
            'ativo' => self::linkAtivo($item['rota'] ?? null, $parametros),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]));
    }

    private static function prepararGrupo(array $item): array
    {
        $filhosOrdenados = ($item['manterOrdemDosFilhos'] ?? false)
            ? $item['filhos']
            : collect($item['filhos'])
                ->sortBy(fn (array $filho) => mb_strtolower($filho['rotulo'] ?? ''), SORT_NATURAL)
                ->values()
                ->all();

        $filhos = array_map(
            function (array $filho) {
                $parametros = $filho['parametros'] ?? [];

                return self::removerParametrosDoItem(array_merge($filho, [
                    'url' => self::resolverUrl($filho['rota'] ?? null, $parametros),
                    'ativo' => self::linkAtivo($filho['rota'] ?? null, $parametros),
                ]));
            },
            $filhosOrdenados
        );

        $grupo = array_merge($item, [
            'filhos' => $filhos,
            'ativo' => self::grupoAtivo($filhosOrdenados),
            'iconeMaterial' => self::iconeMaterial($item['icone']),
        ]);

        unset($grupo['manterOrdemDosFilhos']);

        return $grupo;
    }

    private static function removerParametrosDoItem(array $item): array
    {
        unset($item['parametros']);

        return $item;
    }

    private static function iconeMaterial(string $chave): string
    {
        return self::ICONES_MATERIAL[$chave] ?? 'circle';
    }
}
