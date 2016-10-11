<?php

return new \Phalcon\Config(
    [
        'database'               => [
            'production'  => [
                'adapter'  => 'Mysql',
                'host'     => '',
                'username' => 'brandme',
                'password' => '',
                'name'     => 'production'
            ],
            'development' =>
                [
                    'adapter'  => 'Mysql',
                    'host'     => 'localhost',
                    'username' => 'brandme',
                    'password' => '',
                    'name'     => ''
                ]
        ],
        'elastic'                => [
            'development' => '127.0.0.1:9200',
            'production'  => '*:9200'
        ],
        'email'                  => [
            'username' => 'brandme',
            'password' => '',
            'sender'   => 'no-reply@brandme.la'
        ],
        'token'                  => '',
        'persistent_cookie_life' => 604800, //7 days
        'bitly'                  => [
            'client_id'     => '',
            'client_secret' => '',
            'access_token'  => ''
        ],
        'paypal'                 => [
            'logEnabled'  => true,
            'logFileName' => defined('APPLICATION_LOG_DIR') ? APPLICATION_LOG_DIR . '/payment-paypal.log'
                : '../var/logs',
            'logLevel'    => 'FINE',
            'sandbox'     => [
                'user'      => '',
                'password'  => '',
                'signature' => ''
            ],
            'live'        => [
                'user'      => '',
                'password'  => '',
                'signature' => ''
            ]
        ],
        'security'               => [
            'legacy_hash' => '' //the fixed old-gen hash for password algorithms
        ]
    ]
);