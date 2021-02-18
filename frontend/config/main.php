<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'name' => 'Smart',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'sourceLanguage' => 'ru',
    'language' => 'ru', //'en-US',
    'modules' => [
        'api' => [
            'class' => 'frontend\modules\api\Api',
        ],
        'tinkoff' => [
            'class' => 'frontend\modules\tinkoff\Api',
        ],
    ],
    'components' => [
        //************************************************************************
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        //************************************************************************
        'user' => [
            'identityClass'   => 'common\models\Users',
            'enableAutoLogin' => true,
            'enableSession'   => true,
            'identityCookie'  => ['name' => '_identity-frontend', 'httpOnly' => true],
            'on ' . \yii\web\User::EVENT_AFTER_LOGIN => ['common\models\Users', 'afterLogin'],
        ],
        //************************************************************************
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        //************************************************************************
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        //************************************************************************
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        //************************************************************************
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                ['pattern' => 'api/upload-video-lessons', 'route' => 'api/default/upload-video-lessons'],
                ['pattern' => 'api',          'route' => 'api/default/index'],
                ['pattern' => 'api/<action>', 'route' => 'api/default/index'],

                ['pattern' => 'tinkoff',          'route' => 'tinkoff/default/index'],
                ['pattern' => 'tinkoff/<action>', 'route' => 'tinkoff/default/index'],

                // Все остальное отправляем на контроллер SiteController акшен static
                [
                    'pattern' => '<action:vocal-course|learning-stages|cost|for-coaches|contacts|show-logo>/<id:\w*>',
                    //'pattern' => '<action:\w+>/<id:\w*>',
                    'route' => 'site/static', 'defaults' => ['id' => 1]
                ],
            ],
        ],
        //************************************************************************
        'view' => [
            'theme' => [
                'basePath' => '@app/themes/' . DESIGN_THEME,
                'baseUrl' => '@web/themes/' . DESIGN_THEME,
                'pathMap' => [
                    '@app/views'   => [
                        '@app/themes/holidays',        //Сначала будет искать файлы виевов тут, и если их нет то
                        '@app/themes/' . DESIGN_THEME, // тогда уже тут, таким образом можно подменять на праздники основной виев на праздничный
                    ],
                    //'@app/views/layouts' => '@app/themes/' . DESIGN_THEME . '/layouts',
                    '@app/modules'       => '@app/themes/' . DESIGN_THEME . '/modules',
                    '@app/widgets'       => '@app/themes/' . DESIGN_THEME . '/widgets',
                    '@app/page'          => '@app/themes/' . DESIGN_THEME . '/page',
                ],
            ],
        ],
        //************************************************************************
        'i18n' => [
            'translations' => [
                'models*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'ru-RU',
                ],
                'forms*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],
                'search*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],
                'user*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],
                'modules*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],
                'mail*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages/' . DESIGN_THEME,
                    'sourceLanguage' => 'ru-RU',
                ],

            ],
        ],
        //************************************************************************
        'assetManager' => [
            'appendTimestamp' => APPEND_TIMESTAMP_FOR_CSS_JS,
            'forceCopy' => APPEND_TIMESTAMP_FOR_CSS_JS,
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'basePath' => '@webroot',
                    'baseUrl' => '@web',
                    'js' => [
                        'themes/smart/js/bundle.js',
                        //'themes/smart/js/bundle-repair.js',
                    ],
//                    'js' => [
//                        //'jquery.min.js',
//                        '/themes/smart/js/bundle.js',
//                    ],
                    'jsOptions' => [
                        //'async' => true,
                    ],
                ],
                'yii\web\YiiAsset' => [
                    'jsOptions' => [
                        'defer' => true,
                    ],
                ],
                'yii\widgets\ActiveFormAsset' => [
                    'jsOptions' => [
                        'defer' => true,
                    ],
//                    'depends' => [
//                        'frontend\assets\smart\AppAsset',
//                    ],
                ],
                'yii\validators\ValidationAsset' => [
                    'jsOptions' => [
                        'defer' => true,
                    ],
//                    'depends' => [
//                        'frontend\assets\smart\AppAsset',
//                    ],
                ],
                'yii\bootstrap\BootstrapAsset' => [
                    'css' => [],
                ],
//                'yii\bootstrap\BootstrapAsset' => [
//                    'css' => (DISABLE_BOOTSTRAP_CSS
//                        ? []
//                        : ['css/bootstrap.css']),
//                ],
//                'yii\bootstrap\BootstrapPluginAsset' => [
//                    'js' => (DISABLE_BOOTSTRAP_PLUGIN_JS
//                        ? []                       // для v20190812 дизайна
//                        : ['js/bootstrap.min.js']), // для orange дизайна
//                ],
            ],
        ],
    ],
    'params' => $params,
];
