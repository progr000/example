<?php
return [
    'aliases' => [
        '@frontendWeb'    => 'https://smart.my',
        '@backendWeb'     => 'https://backend.smart.my',
        '@adminWeb'       => 'https://admin.smart.my',
        '@frontendDomain' => 'smart.my',
        '@backendDomain'  => 'backend.smart.my',
        '@adminDomain'    => 'admin.smart.my',
    ],
    'components' => [
        //************************************************************************
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'pgsql:dbname=smart',                              // мой
            'username' => '',                                         // мой
            'password' => '',                                        // мой
            'charset' => 'utf8',
            'tablePrefix' => 'sm_',
            'schemaMap' => [
                'pgsql'=> [
                    'class'=>'yii\db\pgsql\Schema',
                    'defaultSchema' => 'public',
                ],
            ],
            'on afterOpen' => function($event) {
                $event->sender->createCommand("SET TIME ZONE 'UTC';")->execute();
                //$event->sender->createCommand("SET search_path = sh, public;")->execute();
            },
        ],
        //************************************************************************
        'cache' => [
            'class' => 'yii\caching\MemCache',
            'useMemcached' => true,
            'servers' => [
                [
                    'host' => '/var/run/memcached/memcached.sock', // мой
                    'port' => 0,                                   // мой
                    'weight' => 100,
                ],
            ],

        ],
        //************************************************************************
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'redis',
            'password' => '',
            'port' => 36379,
            'database' => 1,
            'connectionTimeout' => 1,
        ],
        //************************************************************************
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',   // путь к виевам
            'useFileTransport' => true,     // для работы с почтой через фейк файлы без смтп
            'transport' => [
                'class' => 'Swift_NullTransport',
            ],
        ],
        //************************************************************************
    ],
];
