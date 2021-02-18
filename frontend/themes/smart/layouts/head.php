<?php

/** @var $this \yii\web\View */

use yii\helpers\Html;

?>
<!-- begin meta block -->
<meta charset="<?= Yii::$app->charset ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, initial-scale=1.0">
<meta name="description" content="<?= Html::encode($this->title) ?> | Smart">
<meta name="keywords" content="">
<?= Html::csrfMetaTags() ?>
<!-- end meta block -->
<title><?= Html::encode($this->title) ?> | Smart</title>


<?php
$str_path_canonical = trim(htmlspecialchars(strip_tags(Yii::$app->request->getPathInfo())));
$str_path_canonical = $str_path_canonical == "" ? "" : "/" . $str_path_canonical;
?>


<!-- begin favicon block -->
<link rel="canonical" href="<?= Yii::getAlias('@frontendWeb') ?><?= $str_path_canonical ?>" />
<?= $this->render('favicon') ?>
<!-- end favicon block -->


<?= ''/*$this->render('critical_css')*/ ?>


<!-- begin css block -->
<?php $this->head() ?>
<!-- end css block -->
