<?php

return [
    'base_url'   => APPLICATION_HOST.'/social/callback',
    'providers'  => [
        // openid providers
        'OpenID'     => [
            'enabled' => true
        ],
        'Google'     => [
            'enabled' => true,
            'keys'    => [
                'id'     => '',
                'secret' => ''
            ],
            'scope'   => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/plus.circles.read https://www.googleapis.com/auth/plus.stream.read https://www.googleapis.com/auth/plus.me'
        ],
        'Facebook'   => [
            'enabled'        => true,
            'keys'           => ['id' => '', 'secret' => ''],
            'trustForwarded' => false
        ],
        'Twitter'    => [
            'enabled' => true,
            'keys'    => [
                'key'    => '',
                'secret' => ''
            ]
        ],
        'LinkedIn'   => [
            'enabled' => true,
            'keys'    => ['key' => '', 'secret' => '']
        ],
        'Foursquare' => [
            'enabled' => true,
            'keys'    => [
                'id'     => '',
                'secret' => ''
            ]
        ],
        'Tumblr'     => [
            'enabled' => true,
            'keys'    => [
                'key'    => '',
                'secret' => ''
            ]
        ],
        'Flickr'     => [
            'enabled' => false
        ],
        'Youtube'    => [
            'enabled' => true,
            'keys'    => [
                'id'     => '',
                'secret' => ''
            ],
            'scope'   => 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/youtubepartner https://www.googleapis.com/auth/youtube https://www.googleapis.com/auth/youtube.readonly https://www.googleapis.com/auth/youtube.force-ssl https://www.googleapis.com/auth/youtube.upload'
        ],
        'Instagram'  => [
            'enabled' => true,
            'keys'    => [
                'id'     => '',
                'secret' => ''
            ]
        ]
    ],
    // If you want to enable logging, set 'debug_mode' to true.
    // You can also set it to
    // - 'error' To log only error messages. Useful in production
    // - 'info' To log info and error messages (ignore debug messages]
    'debug_mode' => 'error',
    // Path to file writable by the web server. Required if 'debug_mode' is not false
    'debug_file' => APPLICATION_LOG_DIR.'/hybrid.log'
];