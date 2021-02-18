<?php

/** @var $this \yii\web\View */
/** @var $content string */
/** @var $CurrentUser \common\models\Users */

use common\models\Users;
use frontend\assets\smart\memberAsset;

/** init vars */
$DASHBOARD_SCHEDULE_DATA = [];
$static_action = Yii::$app->request->get('action', null);
$CurrentUser = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;

$hide_left_menu = isset($_COOKIE['hide_left_menu']) ? intval($_COOKIE['hide_left_menu']) : 0;

/** Register all assets (js + css) */
$this->render('js-css-assets', ['CurrentUser' => $CurrentUser]);
memberAsset::register($this);

/** */
switch ($CurrentUser->user_type) {
    case Users::TYPE_STUDENT:
        $tpl = 'student';
        $controller = 'student';
        break;
    case Users::TYPE_METHODIST:
        $tpl = 'methodist';
        $controller = 'methodist';
        break;
    case Users::TYPE_TEACHER:
        $tpl = 'teacher';
        $controller = 'teacher';
        break;
    case Users::TYPE_OPERATOR:
        $tpl = 'operator';
        $controller = 'operator';
        break;
    case Users::TYPE_ADMIN:
        $tpl = 'admin';
        $controller = 'admin';
        break;
    default:
        $tpl = 'student';
        $controller = 'student';
        break;
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>
    <?= $this->render('head', ['CurrentUser' => $CurrentUser]) ?>
</head>
<body class="loaded <?= $hide_left_menu ? '' : 'has-slide-menu' ?>"
      lang="<?= Yii::$app->language ?>"
      data-is-debug="<?= YII_DEBUG ? 1 : 0 ?>"
      data-flash-timeout="<?= Yii::$app->params['FLASH_TIMEOUT'] ?>"
      data-default-lang="<?= Yii::$app->sourceLanguage ?>"
      data-uid="<?= $CurrentUser ? $CurrentUser->user_id : "null" ?>"
      data-user-status="<?= $CurrentUser ? $CurrentUser->user_status : "null" ?>"
      data-week-day="<?= intval(date('N', ($CurrentUser ? $CurrentUser->_user_local_time : time()) )) ?>"
      data-timestamp="<?= $CurrentUser ? $CurrentUser->_user_local_time : time() ?>"
      data-date="<?= date(SQL_DATE_FORMAT, ($CurrentUser ? $CurrentUser->_user_local_time : time()) ) ?>">
<?php $this->beginBody() ?>

<!-- begin .alert-messages-->
<?= $this->render('alert-messages', ['CurrentUser' => $CurrentUser]) ?>
<!-- end .alert-messages-->

<div class="page page--member bg">
    <div class="member-container">
        <!--begin User menu-->

        <?= $this->render("member-{$tpl}-menu", [
            'CurrentUser'   => $CurrentUser,
            'static_action' => $static_action,
            'tpl' => $tpl,
            'controller' => $controller,
            'hide_left_menu' => $hide_left_menu
        ]) ?>

        <!--end User menu-->
        <div class="member-area">
            <div class="member-area__main">
                <?= $this->render("member-{$tpl}-header", [
                    'CurrentUser'   => $CurrentUser,
                    'static_action' => $static_action,
                    'tpl' => $tpl,
                    'controller' => $controller,
                ]) ?>
                <div class="member-area__body">
                    <?= $content ?>
                </div>
            </div>
            <!--begin .page-footer-->

            <?= $this->render('member-footer', [
                'CurrentUser' => $CurrentUser,
                'static_action' => $static_action,
                'tpl' => $tpl,
                'controller' => $controller,
            ]) ?>

            <!--end .page-footer-->
        </div>
    </div>
</div>

<?= $this->render('../modals/common-modal', ['CurrentUser' => $CurrentUser]) ?>

<?php $this->endBody() ?>

</body>
</html>
<?php $this->endPage() ?>
