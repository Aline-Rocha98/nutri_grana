<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormaPagamentoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('forma_pagamentos')->insert([
            [
                'nome' => 'Pix',
                'tipo_forma_pagamento' => 'P',
                'ativo' => 'S'
            ],
            [
                'nome' => 'Cartão de Crédito',
                'tipo_forma_pagamento' => 'C',
                'ativo' => 'S'
            ],
            [
                'nome' => 'Boleto',
                'tipo_forma_pagamento' => 'B',
                'ativo' => 'S'
            ]
        ]);

    }
}
