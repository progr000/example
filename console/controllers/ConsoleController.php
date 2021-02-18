<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Json;
use common\helpers\Functions;
use common\models\Users;

/**
 * Site controller
 */
class ConsoleController extends Controller
{
    public $mail_id;
    public $status;
    public $description;

    public $userId;
    public $restorePatchTTL;

    public $user_email;
    public $user_password;
    public $license_key;

    public $SignalAccessKey;

    public $server_type;
    public $server_url;
    public $server_ip;
    public $server_port;
    public $server_login;
    public $server_password;
    public $server_description;

    /**
     * При разработке консольного приложения принято использовать код возврата.
     * Принято, код 0 (ExitCode::OK) означает, что команда выполнилась удачно.
     * Если команда вернула код больше нуля, то это говорит об ошибке.
     */

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $ret = parent::options($actionID);

        if ($actionID == 'create-user'){
            return array_merge($ret, [
                'user_email',
                'user_password',
            ]);
        }

        return $ret;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        echo "\n";
        return parent::beforeAction($action);
    }

    /**
     * Создаст нового юзера по user_email и user_password
     * пример строки запуска: "./yii console/create-user --user_email=UNIQUE_EMAIL --user_password=PLAIN_PASSWORD"
     * @return int
     */
    public function actionCreateUser()
    {
        if (!$this->user_email || !$this->user_password) {
            echo "Params 'user_email' and 'user_password' are required.\n";
            echo "Usage example:\n";
            echo './yii console/create-user --user_email=UNIQUE_EMAIL --user_password=PLAIN_PASSWORD' . "\n\n";
            return ExitCode::NOINPUT;
        }

        $User = Users::findByEmail($this->user_email);
        if ($User) {
            echo Json::encode([
                'result'  => "error",
                'errcode' => "USER_EXIST",
                'info'    => "User already exist",
            ]). "\n";
            return ExitCode::DATAERR;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /* Создаем оператора системы */
        $user                     = new Users();
        $user->user_first_name    = Functions::getNameFromEmail($this->user_email);
        $user->user_last_name     = $user->user_first_name;
        $user->user_full_name     = $user->user_first_name;
        $user->user_email         = $this->user_email;
        $user->user_last_ip       = '127.0.0.1';
        $user->user_status        = Users::STATUS_ACTIVE;
        $user->user_type          = Users::TYPE_ADMIN;
        $user->user_need_set_password = Users::NO;
        $user->setPassword($this->user_password);
        $user->generateAuthKey();
        $user->generatePasswordResetToken();

        if ($user->save()) {
            $transaction->commit();
            echo "User created.\n";
            return ExitCode::OK;
        }

        $transaction->rollBack();
        echo Json::encode([
                'result'  => "error",
                'errcode' => "DB_ERROR",
                'info'    => $user->getErrors(),
            ]). "\n";
        return ExitCode::DATAERR;
    }
}
