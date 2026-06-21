<?php

return [
    'accepted' => 'O campo :attribute deve ser aceito.',
    'before' => 'O campo :attribute deve ser uma data anterior a :date.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'date' => 'O campo :attribute deve ser uma data válida.',
    'email' => 'O campo :attribute deve ser um e-mail válido.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'required' => 'O campo :attribute é obrigatório.',
    'unique' => 'Já existe um registro com este :attribute.',

    'attributes' => [
        'nome' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'data_nascimento' => 'data de nascimento',
        'motivo_controle_financeiro' => 'motivo do controle financeiro',
    ],

    'usuario' => [
        'email' => [
            'unique' => 'Já existe uma conta cadastrada com este e-mail.',
        ],
    ],

    'password.min' => 'A senha deve ter no mínimo :min caracteres.',
];
