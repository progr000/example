<?php

/** @var $this \yii\web\View */
/** @var $content string */
/** @var $CurrentUser \common\models\Users */

use frontend\assets\smart\IndexRequestFormAsset;
use frontend\assets\smart\guestAsset;

/** init vars */
$static_action = Yii::$app->request->get('action', null);
$CurrentUser = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
$MENU = [
    '/' => 'Главная страница',
    '/vocal-course' => 'Курс Вокала',
    '/learning-stages' => 'Этапы обучения',
    '/cost' => 'Стоимость',
    '/for-coaches' => 'Преподавателям',
    '/contacts' => 'Контакты',
];

/** Register all assets (js + css) */
$this->render('js-css-assets', ['CurrentUser' => $CurrentUser]);
IndexRequestFormAsset::register($this);
guestAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <?= $this->render('head', ['CurrentUser' => $CurrentUser]) ?>
</head>
<body class="loaded"
      lang="<?= Yii::$app->language ?>"
      data-is-debug="<?= YII_DEBUG ? 1 : 0 ?>"
      data-flash-timeout="<?= Yii::$app->params['FLASH_TIMEOUT'] ?>"
      data-default-lang="<?= Yii::$app->sourceLanguage ?>"
      data-uid="<?= $CurrentUser ? $CurrentUser->user_id : "null" ?>">
<?php $this->beginBody() ?>

<!-- begin .alert-messages-->
<?= $this->render('alert-messages', ['CurrentUser' => $CurrentUser]) ?>
<!-- end .alert-messages-->

<?php
if (!isset($this->params['additional_header_class'])) { $this->params['additional_header_class'] = ''; }
?>
<div class="page bg">


    <div class="gradient-5"> <!-- этот див закрывается в файле layouts/guest_header -->

        <?= $this->render('/layouts/guest-header', [
            'additional_header_class' => $this->params['additional_header_class'],
            'static_action' => $static_action,
            'MENU' => $MENU,
            'CurrentUser' => $CurrentUser,
        ]) ?>



        <?= $content ?>



        <?= $this->render('guest-footer', [
            'static_action' => $static_action,
            'MENU' => $MENU,
            'CurrentUser' => $CurrentUser,
        ]) ?>

</div>

<?= $this->render('../modals/common-modal', ['CurrentUser' => $CurrentUser]) ?>
<?= $this->render('../modals/guest-modal', ['CurrentUser' => $CurrentUser]) ?>

<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
