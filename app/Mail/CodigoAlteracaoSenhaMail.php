<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoAlteracaoSenhaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nome,
        public readonly string $codigo,
        public readonly int $minutosValidade,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código para alteração de senha — NutriGrana',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.codigo-alteracao-senha',
            with: [
                'nome' => $this->nome,
                'codigo' => $this->codigo,
                'minutosValidade' => $this->minutosValidade,
            ],
        );
    }
}
