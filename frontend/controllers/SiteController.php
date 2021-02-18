<?php
namespace frontend\controllers;

use common\helpers\FileSys;
use common\helpers\Functions;
use frontend\models\forms\IndexRequestForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use frontend\components\SController;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\Response;
use common\models\Users;
use frontend\models\forms\LoginForm;
use frontend\models\forms\PasswordResetRequestForm;
use frontend\models\forms\ResetPasswordForm;
use frontend\models\forms\ResendVerificationEmailForm;
use frontend\models\forms\VerifyEmailForm;

/**
 * Site controller
 *
 * @property \frontend\models\forms\LoginForm $model_login
 *
 */
class SiteController extends SController
{
    public $model_login;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                //'only' => ['logout', 'signup', 'login'],
                'rules' => [
                    [
                        'actions' => [
                            'error',
                            'maintenance',
                            'index',
                            'static',
                            'login-by-token',
                            'store-js-console-log',
                            'save-index-request-form',
                        ],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['signup', 'login'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
                /* функция которая обработает запрет на доступ к акшену (если не указать будет использована стандартная) */
                'denyCallback' => function($rule, $action) {
                    if ($this->CurrentUser) {
                        return $this->redirect(['user/']);
                    } else {
                        return $this->redirect(['/']);
                        //throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
                    }
                },
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post', 'get'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->layout = 'guest-main';
        $this->model_login = new LoginForm();
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (!$this->CurrentUser) {
            Yii::$app->session->remove('user_count_transitions');
        }
        return parent::beforeAction($action);
    }


    //----------------------------------------------------------//


    /**
     * Displays homepage.
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('static/index', [
            //'LoginFormModel' => $this->model_login,
            'CurrentUser' => $this->CurrentUser,
            'RequestStudentForm' => new IndexRequestForm(['request_name']),
            'RequestTeacherForm' => new IndexRequestForm(['request_fio']),
        ]);

    }

    /**
     * Этот акшен обработает страницы которые описаны в правилах
     * реврайта конфига frontend/config/main.php  urlManager/rules
     * в случае если имеется для них виевка
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionStatic()
    {
        $action = Yii::$app->request->get('action');
        $no_header = (bool) Yii::$app->request->get('header-free', 0);
        $empty_layout = (bool) Yii::$app->request->get('empty-layout', 0);
        //var_dump($action);exit;

        if (file_exists(Yii::getAlias('@frontend').'/themes/' . DESIGN_THEME . '/site/static/' . $action . '.php')) {
            if ($no_header && file_exists(Yii::getAlias('@frontend').'/themes/' . DESIGN_THEME . '/layouts/main-no-header-no-footer.php')) {
                $this->layout = 'main_no_header_no_footer';
            }
            if ($empty_layout) {
                $this->layout = 'main-empty';
            }
            return $this->render('static/' . $action, [
                'CurrentUser' => $this->CurrentUser,
                'RequestStudentForm' => new IndexRequestForm(['request_name']),
                'RequestTeacherForm' => new IndexRequestForm(['request_fio']),
            ]);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @param string $token
     * @return \yii\web\Response
     */
    public function actionLoginByToken($token)
    {
        if (!Yii::$app->user->isGuest) {
            Yii::$app->user->logout();
        }

        $User = Users::findByToken($token);
        //if ($User && Yii::$app->user->login($User, 0)) {
        if ($User) {

            /* для админа разрешен логин только через специальный домен */
            if ($User->user_type == Users::TYPE_ADMIN) {
                if (!Yii::getAlias('@adminWeb', false) || !Yii::getAlias('@adminDomain')) {
                    Yii::$app->session->setFlash('error', 'There was an error on login by token. (ErrorCode::SecurityErrorConfig)');
                    return $this->goHome();
                }
                if (Yii::$app->request->hostName != Yii::getAlias('@adminDomain')) {
                    Yii::$app->session->setFlash('error', 'There was an error on login by token. (ErrorCode::SecurityErrorDomain)');
                    return $this->goHome();
                }
            }

            if (Yii::$app->user->login($User, Users::LOGIN_COOKIE_TTL)) {
                return $this->redirect(['user/index']);
            } else {
                Yii::$app->session->setFlash('error', 'There was an error on login by token.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'There was an error on login by token.');
        }

        return $this->goHome();
    }

    /**
     * @return string
     */
    public function actionStoreJsConsoleLog()
    {
        //$this->enableCsrfValidation = false;
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (isset(Yii::$app->params['jsConsoleLogDir']) && isset($_POST['logs'])) {

            /**/
            $storeDir = Yii::$app->params['jsConsoleLogDir'];
            if (!file_exists($storeDir)) {
                FileSys::mkdir($storeDir, 0777);
                chmod($storeDir, 0777);
            }

            /**/
            if ($this->CurrentUser) {
                $storeDir .= DIRECTORY_SEPARATOR . $this->CurrentUser->user_email;
            } else {
                $storeDir .= DIRECTORY_SEPARATOR . '__GUEST__';
            }
            if (!file_exists($storeDir)) {
                FileSys::mkdir($storeDir, 0777);
                chmod($storeDir, 0777);
            }

            /**/
            $storeDir .= DIRECTORY_SEPARATOR . date('Y-m-d');
            if (!file_exists($storeDir)) {
                FileSys::mkdir($storeDir, 0777);
                chmod($storeDir, 0777);
            }

            /**/
            $browser = Functions::getBrowserByUserAgent(Yii::$app->request->userAgent);
            $os = Functions::getOsTypeByUserAgent(Yii::$app->request->userAgent);
            if (isset(Yii::$app->request) && method_exists(Yii::$app->request, 'getUserIP')) {
                $user_ip = Yii::$app->request->getUserIP();
            }
            $storeFile = $storeDir . DIRECTORY_SEPARATOR . $user_ip . "-{$os}-{$browser}.log";
            FileSys::fwrite($storeFile, $_POST['logs'], 0666, 'a');
        }

        return [
            'status' => true,
        ];
    }

    /**
     * @return string
     */
    public function actionSaveIndexRequestForm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new IndexRequestForm();
        $model->_validate_pattern = false;
        if (!$model->load(Yii::$app->request->post())) {
            return [
                'status' => false,
                'error'  => $model->getErrors(),
            ];
        }

        if (!$model->validate()) {
            return [
                'status' => false,
                'error'  => $model->getErrors(),
            ];
        }

        $model->saveRequest();

        return [
            'status' => true,
        ];
    }


    //----------------------------------------------------------//


    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        return $this->goHome();

        /*
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
        */
    }

    /**
     * Logs in a user.
     * @return string|Response
     * @throws HttpException
     */
    public function actionLogin()
    {

        if ($this->model_login->load(Yii::$app->request->post()) && $this->model_login->validate()) {

            if ($this->model_login->login()) {

                //return $this->goBack();
                return $this->redirect(['user/']);

            } else {

                throw new HttpException(400, 'Failed save data.');

            }

        } else {

            return $this->render('static/index', [
                //'LoginFormModel' => $this->model_login,
                'CurrentUser' => $this->CurrentUser,
                'RequestStudentForm' => new IndexRequestForm(['request_name']),
            ]);

        }
    }


    //----------------------------------------------------------//


    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }


    //----------------------------------------------------------//



}