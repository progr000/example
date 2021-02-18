<?php

/** @var $this \yii\web\View */
/** @var $content string */
/** @var $CurrentUser \common\models\Users */

use yii\web\View;
use common\models\Users;
use frontend\assets\smart\AppAsset;

/* assets */
AppAsset::register($this);

$str_js = "\n";
$str_js = "window.onload=function(){hideSiteLoader();};";

if (!$CurrentUser) {
    /* Assets для гостей */
    $str_js .= "$(document).ready(function(){";
    $str_js .= "IS_DEBUG=" . ((YII_DEBUG) ? 'true' : 'false') . ";";
    $str_js .= "STORE_JS_LOGS=true;";
    $str_js .= "IS_GUEST=true;";
    $str_js .= "});";
} else {
    /* Assets для зарегистрированных */
    $str_js .= "$(document).ready(function(){";
    $str_js .= "IS_DEBUG=" . ((YII_DEBUG) ? 'true' : 'false') . ";";
    $str_js .= "STORE_JS_LOGS=true;";
    $str_js .= "IS_GUEST=false;";
    $str_js .= "USER_TYPE={$CurrentUser->user_type};";
    $str_js .= "USER_TYPES={" .
        "'TYPE_ADMIN':" . Users::TYPE_ADMIN . "," .
        "'TYPE_OPERATOR':" . Users::TYPE_OPERATOR . "," .
        "'TYPE_METHODIST':" . Users::TYPE_METHODIST . "," .
        "'TYPE_TEACHER':" . Users::TYPE_TEACHER . "," .
        "'TYPE_STUDENT':" . Users::TYPE_STUDENT .
    "};";
    $str_js .= "});";
}

/* регистрируем яваскрипт */
$this->registerJs($str_js, View::POS_END);

