<?php
namespace frontend\components;

use Yii;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use common\models\Users;

/**
 * Site controller
 *
 * @property \common\models\Users $CurrentUser
 *
 */
class SController extends Controller
{
    protected $CurrentUser;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        /* CurrentUser */
        if (!Yii::$app->user->isGuest) {
            $this->CurrentUser = $this->findUserModel(Yii::$app->user->identity->getId());
            //$this->CurrentUser = Yii::$app->user->identity;
        }
    }

    /**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return \common\models\Users $User
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findUserModel($id)
    {
        if (($User = Users::findIdentity($id)) !== null) {
            if (in_array($User->user_status, [
                Users::STATUS_ACTIVE,
                Users::STATUS_AFTER_INTRODUCE,
                Users::STATUS_BEFORE_INTRODUCE,
                Users::STATUS_AFTER_PAYMENT,
            ])) {
                return $User;
            } else {
                Yii::$app->user->logout();
            }
        }

        //throw new ForbiddenHttpException('Forbidden');
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

