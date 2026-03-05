<?php
return [
    'name' => 'HARD AI Try-On',
    'description' => 'AI-виртуальная примерка на карточке товара Shop-Script.',
    'img' => 'img/hardtryon.png',
    'version' => '1.1.0',
    'vendor' => 'hard',
    'frontend' => true,
    'custom_settings' => true,
    'handlers' => [
        'frontend_head' => 'frontendHead',
        'frontend_product' => 'frontendProduct',
        'routing' => 'routing',
    ],
];
