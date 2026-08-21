<?php

namespace App\Support\Dashboard;

use Illuminate\Support\Facades\Cache;

final class DashboardCache
{
    public const TTL_SEGUNDOS = 45;

    public static function versaoKey(int $idUsuario): string
    {
        return "dashboard:v:{$idUsuario}";
    }

    public static function versao(int $idUsuario): int
    {
        return (int) Cache::get(self::versaoKey($idUsuario), 1);
    }

    public static function chave(int $idUsuario, int $ano, int $mes, string $periodo, array $widgets): string 
    {
        $widgetsOrdenados = $widgets;
        sort($widgetsOrdenados);

        return sprintf(
            'dashboard:%d:%d:%d:%d:%s:%s',
            $idUsuario,
            self::versao($idUsuario),
            $ano,
            $mes,
            $periodo,
            implode(',', $widgetsOrdenados),
        );
    }

    public static function invalidar(int $idUsuario): void
    {
        $chave = self::versaoKey($idUsuario);

        if (!Cache::has($chave)) {
            Cache::forever($chave, 2);

            return;
        }

        Cache::increment($chave);
    }
}
