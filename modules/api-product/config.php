<?php

return [
    '__name' => 'api-product',
    '__version' => '0.0.1',
    '__git' => 'git@github.com:getmim/api-product.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'https://iqbalfn.com/'
    ],
    '__files' => [
        'modules/api-product' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'product' => NULL
            ],
            [
                'api' => NULL
            ],
            [
                'lib-app' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'ApiProduct\\Controller' => [
                'type' => 'file',
                'base' => 'modules/api-product/controller'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'api' => [
            'apiProduct' => [
                'path' => [
                    'value' => '/product'
                ],
                'handler' => 'ApiProduct\\Controller\\Product::index',
                'method' => 'GET'
            ],
            'apiProductSingle' => [
                'path' => [
                    'value' => '/product/read/(:identity)',
                    'params' => [
                        'identity' => 'any'
                    ]
                ],
                'handler' => 'ApiProduct\\Controller\\Product::single',
                'method' => 'GET'
            ]
        ]
    ]
];