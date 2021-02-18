<?php
defined('CREATE_ABSOLUTE_URL') or define('CREATE_ABSOLUTE_URL', true);
defined('SQL_DATE_FORMAT') or define('SQL_DATE_FORMAT', "Y-m-d H:i:s");
defined('PJAX_TIMEOUT') or define('PJAX_TIMEOUT', 3000);
date_default_timezone_set('GMT');

return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [

    ],
    'modules' => [
        'gridview' =>  [
            'class' => '\kartik\grid\Module',
        ],
    ],
];
