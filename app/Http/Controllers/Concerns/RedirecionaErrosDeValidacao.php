<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

trait RedirecionaErrosDeValidacao
{
    protected function redirecionarComErrosDeValidacao(ValidationException $excecao, array $camposSemFormulario = []): RedirectResponse
    {
        $erros = $excecao->errors();
        $redirecionamento = redirect()->back()->withErrors($erros)->withInput();
        $mensagemSemFormulario = collect($erros)
            ->only($camposSemFormulario)
            ->flatten()
            ->first();

        if ($mensagemSemFormulario) {
            $redirecionamento->with('erro', $mensagemSemFormulario);
        }

        return $redirecionamento;
    }

    protected function redirecionarComPrimeiroErro(ValidationException $excecao): RedirectResponse
    {
        return redirect()
            ->back()
            ->with('erro', collect($excecao->errors())->flatten()->first());
    }
}
