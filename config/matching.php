<?php

return [
    'weights' => [
        'mandatory_skills' => 45,
        'optional_skills' => 20,
        'seniority' => 15,
        'salary' => 10,
        'location' => 10,
    ],

    'labels' => [
        'mandatory_skills' => 'Requisitos obligatorios',
        'optional_skills' => 'Requisitos deseables',
        'seniority' => 'Seniority',
        'salary' => 'Expectativa salarial',
        'location' => 'Ubicación / modalidad',
    ],

    'levels' => [
        'beginner' => 1,
        'intermediate' => 2,
        'advanced' => 3,
        'expert' => 4,
    ],

    'seniority_bands' => [
        'jr' => [0, 2],
        'junior' => [0, 2],
        'ssr' => [2, 4],
        'semi_senior' => [2, 4],
        'sr' => [4, 7],
        'senior' => [4, 7],
        'lead' => [7, 100],
    ],
];
