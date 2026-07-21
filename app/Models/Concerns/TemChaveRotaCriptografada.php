<?php

namespace App\Models\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

trait TemChaveRotaCriptografada
{
    public function getRouteKey(): string
    {
        return $this->codificarChaveRota((string) $this->getKey());
    }

    public function resolveRouteBinding($value, $field = null): ?static
    {
        $id = $this->decodificarChaveRota((string) $value);

        if ($id === null) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $id)->first();
    }

    protected function codificarChaveRota(string $id): string
    {
        $criptografado = Crypt::encryptString($id);

        return rtrim(strtr($criptografado, '+/', '-_'), '=');
    }

    protected function decodificarChaveRota(string $valor): ?string
    {
        try {
            $preenchido = strtr($valor, '-_', '+/');
            $tamanhoPadding = (4 - strlen($preenchido) % 4) % 4;
            $preenchido .= str_repeat('=', $tamanhoPadding);

            return Crypt::decryptString($preenchido);
        } catch (DecryptException) {
            return null;
        }
    }
}
