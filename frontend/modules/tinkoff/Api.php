<?php

namespace frontend\modules\tinkoff;

use Yii;
use yii\base\Module;

class Api extends Module
{
    public $controllerNamespace = 'frontend\modules\tinkoff\controllers';
    public $defaultController = 'default';

    public function init()
    {
        parent::init();
        
        Yii::$app->user->enableSession = false;
    }
}
