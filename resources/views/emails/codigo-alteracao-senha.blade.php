<x-mail::message>
# Alteração de senha

Olá, {{ $nome }}!

Recebemos uma solicitação para alterar a senha da sua conta no NutriGrana.

Seu código de confirmação é:

<x-mail::panel>
**{{ $codigo }}**
</x-mail::panel>

Este código expira em **{{ $minutosValidade }} minutos**.

Se você não solicitou esta alteração, ignore este e-mail. Sua senha permanecerá a mesma.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
