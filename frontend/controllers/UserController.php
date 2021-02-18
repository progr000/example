<?php
namespace frontend\controllers;

use Yii;
use yii\web\Response;
use yii\filters\AccessControl;
use frontend\components\SController;
use common\helpers\Functions;
use common\models\Users;
use common\models\HomeWorks;
use common\models\Presets;
use common\models\MethodistTimeline;
use frontend\models\search\NextLessons;
use frontend\models\forms\ProfileForm;
use frontend\models\schedule\CommonScheduleForm;
use frontend\models\schedule\TeachersScheduleForm;
use frontend\models\schedule\MethodistScheduleForm;
use frontend\models\schedule\StudentsScheduleForm;
use frontend\models\search\HomeWorksSearch;

/**
 * User controller
 */
class UserController extends SController
{
    private $max_user_count_transitions = 4;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [

                    /* default police for non authorized */
                    [
                        'allow' => false,
                        'roles' => ['?'],
                    ],

                    /* for all actions */
                    [
                        'actions' => [
                            'index',
                            'profile',
                            'upload-profile-photo',
                            'delete-profile-photo',
                            'settings',
                            'device-test',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],

                    /* schedule actions */
                    [
                        'actions' => [
                            'get-schedule',
                            'change-schedule',
                        ],
                        'allow' => ($this->checkIsMethodist() || $this->checkIsTeacher() || $this->checkIsStudent()),
                        'roles' => ['@'],
                    ],

                    /* class-room actions */
                    [
                        'actions' => [
                            'educational-class-room',
                            'introductory-class-room',
                            'get-slide',
                        ],
                        'allow' => ($this->checkIsMethodist() || $this->checkIsTeacher() || $this->checkIsStudent()),
                        'roles' => ['@'],
                    ],

                    /* preset actions */
                    [
                        'actions' => [
                            'delete-preset',
                            'view-preset',
                        ],
                        'allow' => ($this->checkIsMethodist() || $this->checkIsAdmin()),
                        'roles' => ['@'],
                    ],

                    /* home-works actions */
                    [
                        'actions' => [
                            'home-works',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    /**/
                    [
                        'actions' => [
                            'view-home-work',
                        ],
                        'allow' => (!$this->checkIsStudent()),
                        'roles' => ['@'],
                        'denyCallback' => function ($rule, $action) {
                            return $this->redirect(['home-works']);
                        },
                    ],
                    /* admin actions */
                    [
                        'actions' => [
                            'get-user-info',
                        ],
                        'allow' => ($this->checkIsTeacher() || $this->checkIsMethodist() || $this->checkIsOperator() || $this->checkIsAdmin()),
                        'roles' => ['@'],
                    ],
                    /**/
                    [
                        'actions' => [
                            'delete-home-work',
                        ],
                        'allow' => (!$this->checkIsStudent() && !$this->checkIsTeacher()),
                        'roles' => ['@'],
                        'denyCallback' => function ($rule, $action) {
                            return $this->redirect(['home-works']);
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * @return bool
     */
    public function checkIsTeacher()
    {
        return ($this->CurrentUser && $this->CurrentUser->user_type == Users::TYPE_TEACHER);
    }

    /**
     * @return bool
     */
    public function checkIsStudent()
    {
        return ($this->CurrentUser && $this->CurrentUser->user_type == Users::TYPE_STUDENT);
    }

    /**
     * @return bool
     */
    public function checkIsMethodist()
    {
        return ($this->CurrentUser && $this->CurrentUser->user_type == Users::TYPE_METHODIST);
    }

    /**
     * @return bool
     */
    public function checkIsOperator()
    {
        return ($this->CurrentUser && $this->CurrentUser->user_type == Users::TYPE_OPERATOR);
    }

    /**
     * @return bool
     */
    public function checkIsAdmin()
    {
        return ($this->CurrentUser && $this->CurrentUser->user_type == Users::TYPE_ADMIN);
    }

    /**
     * @return Response
     */
    public function denyCallbackFunct()
    {
        Yii::$app->session->setFlash('access-control-alert-error', [
            'message'   => 'Ошибка доступа.',
            'ttl'       => Yii::$app->params['FLASH_TIMEOUT'],
            'showClose' => true,
            'alert_id' => 'access-control-alert',
            'type' => 'error',
            //'class' => 'alert-error',
        ]);
        return $this->redirect(['user/']);
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
        ];
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->layout = 'member-main';
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
.....
    }



    /** ================================ COMMON ACTIONS ================================= **/

    //-------------------------- вообще все авторизованные могут вызывать эти методы --------------------------------//

    /**
     * Displays homepage.
     * @return mixed
     */
    public function actionIndex()
    {
        switch ($this->CurrentUser->user_type) {
            case Users::TYPE_ADMIN:
                return $this->redirect(['/admin']);
                break;
            case Users::TYPE_OPERATOR:
                return $this->redirect(['/operator']);
                break;
            case Users::TYPE_METHODIST:
                return $this->redirect(['/methodist']);
                break;
            case Users::TYPE_TEACHER:
                return $this->redirect(['/teacher']);
                break;
            case Users::TYPE_STUDENT:
                return $this->redirect(['/student']);
                break;
            default:
                return $this->redirect(['/student']);
        }
    }

    /**
     * @return string
     */
    public function actionProfile()
    {
        $model = ProfileForm::findIdentity($this->CurrentUser->user_id);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveProfile()) {
                Yii::$app->session->setFlash('success', 'Сохранено');
            } else {
                Yii::$app->session->setFlash('danger', 'Ошибка при сохранении.');
            }
            return $this->redirect(['profile']);
        }

        return $this->render('/common/profile', [
            'model' => $model,
        ]);
    }

    /**
     * @return string
     */
    public function actionUploadProfilePhoto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (isset($_FILES['user_profile_photo']) && is_uploaded_file($_FILES['user_profile_photo']['tmp_name'])) {
            return $this->CurrentUser->addProfilePhoto();
        } else {
            return [
                'type' => 'error',
                'msg' => 'wrong data',
            ];
        }
    }

    /**
     * @return array
     */
    public function actionDeleteProfilePhoto()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($this->CurrentUser->deleteProfilePhoto()) {
            return [
                'type' => 'success',
                'msg' => 'success msg',
                'imgSrc' => $this->CurrentUser->getProfilePhotoForWeb('/assets/smart-min/images/upload_your_photo.png'),
            ];
        }
    }

    /**
     * @return string
     */
    public function actionSettings()
    {
        //var_dump($_POST);exit;
        if ($this->CurrentUser->load($_POST) && $this->CurrentUser->validate()) {
            if (!isset($_POST['Users']['receive_system_notif'])) { $this->CurrentUser->receive_system_notif = Users::NO; }
            if (!isset($_POST['Users']['receive_lesson_notif'])) { $this->CurrentUser->receive_lesson_notif = Users::NO; }
            if ($this->CurrentUser->save()) {
                Yii::$app->session->setFlash('success', 'Сохранено');
            } else {
                Yii::$app->session->setFlash('danger', 'Ошибка при сохранении.');
            }
            return $this->redirect(['settings']);
        }

        return $this->render('/common/settings', [
            'model' => $this->CurrentUser,
        ]);
    }

    /**
     * @return string
     */
    public function actionDeviceTest()
    {
        return $this->render('/common/device-test', [
            'CurrentUser' => $this->CurrentUser,
        ]);
    }



    //------------------ все авторизованные (кроме опреатора и админа) могут вызывать эти методы --------------------//

    /**
     * @return array
     */
    public function actionGetSchedule()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* выбор модели в зависимости от типа юзера */
        switch ($this->CurrentUser->user_type) {
            case Users::TYPE_METHODIST:
                $model = new MethodistScheduleForm();
                break;
            case Users::TYPE_TEACHER:
                $model = new TeachersScheduleForm();
                break;
            case Users::TYPE_STUDENT:
                $model = new StudentsScheduleForm();
                break;
            default:
                $model = new CommonScheduleForm();
        }

        /**/
        if ($model->load([$model->formName() => [
                'user_id'       => $this->CurrentUser->user_id,
                'user_type'     => $this->CurrentUser->user_type,
                'user_timezone' => $this->CurrentUser->user_timezone,
            ]]) && $model->validate()) {

            return [
                'status' => true,
                'data'   => $model->getSchedule(),
            ];

        } else {
            return [
                'status' => false,
                'data'   => $model->getErrors(),
            ];
        }
    }

    /**
     * @param integer $week_day
     * @param integer $work_hour
     * @param integer $hour_status
     * @return array
     */
    public function actionChangeSchedule($week_day, $work_hour, $hour_status)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /* выбор модели в зависимости от типа юзера */
        switch ($this->CurrentUser->user_type) {
            case Users::TYPE_METHODIST:
                $model = new MethodistScheduleForm();
                break;
            case Users::TYPE_TEACHER:
                $model = new TeachersScheduleForm();
                break;
            case Users::TYPE_STUDENT:
                $model = new StudentsScheduleForm();
                break;
            default:
                $model = new StudentsScheduleForm();
        }

        /* учет таймзоны юзера */
        $tmp = CommonScheduleForm::dayAndHourFromTzToGmt($week_day ,$work_hour, $this->CurrentUser->user_timezone);
        $week_day  = $tmp['week_day'];
        $work_hour = $tmp['work_hour'];
        //var_dump($work_hour); var_dump($week_day); exit;

        /* подготовка массива данных */
        $data = [
            'user_id'       => $this->CurrentUser->user_id,
            'user_type'     => $this->CurrentUser->user_type,
            'user_timezone' => $this->CurrentUser->user_timezone,
            'week_day'      => $week_day,
            'work_hour'     => $work_hour,
            'hour_status'   => $hour_status,
        ];
        if (isset($_GET['date_start'])) {
            $data['date_start'] = $_GET['date_start'];
        }
        if ($this->CurrentUser->user_type == Users::TYPE_STUDENT) {
            $data['teacher_user_id'] = $this->CurrentUser->teacher_user_id;
        }

        /* запуск модели с массивом данных */
        if ($model->load([$model->formName() => $data]) && $model->validate()) {

            return [
                'status' => true,
                'data'   => $model->changeSchedule(),
            ];

        } else {
            return [
                'status' => false,
                'data'   => $model->getErrors(),
            ];
        }
    }



    //------------------------- методист, учитель, студент могут вызывать эти методами ----------------------------//

    /**
     * @param string $room
     * @return string
     */
    public function actionEducationalClassRoom($room)
    {
        if (!NextLessons::checkEducationalLessonRoomHash($room, $this->CurrentUser)) {
            return $this->redirect(['/']);
        }

        $this->layout = 'member-class-room';

        return $this->render('/common/educational-class-room', [
            'room' => $room,
            'CurrentUser' => $this->CurrentUser,
        ]);
    }

    /**
     * @param string $room
     * @return string
     */
    public function actionIntroductoryClassRoom($room)
    {
        $NextLesson = NextLessons::checkIntroductoryLessonRoomHash($room, $this->CurrentUser);
        if (!$NextLesson) {
            $left_minutes = $this->CurrentUser->user_type == Users::TYPE_STUDENT
                ? intval(Users::ENTER_TO_CLASS_ROOM_NOT_EARLIER_STUDENT/60)
                : intval(Users::ENTER_TO_CLASS_ROOM_NOT_EARLIER_METHODIST/60);
            Yii::$app->session->setFlash('access-control-alert-error', [
                'message'   => 'Войти в клас можно будет не раньше чем за ' . $left_minutes .' ' . Functions::left_minutes_ru_text($left_minutes)[0] . ' до занятия.',
                'ttl'       => Yii::$app->params['FLASH_TIMEOUT'],
                'showClose' => true,
                'alert_id' => 'access-control-alert',
                'type' => 'error',
                //'class' => 'alert-error',
            ]);
            return $this->redirect(['user/']);
        }

        $this->layout = 'member-class-room';

        return $this->render('/common/introductory-class-room', [
            'room' => $room,
            'CurrentUser' => $this->CurrentUser,
            'NextLesson' => $NextLesson,
            'is_test_student' => isset($_GET['is_test_student']),
        ]);
    }

    /**
     * @param $num
     * @return string
     */
    public function actionGetSlide($num)
    {
        $num = intval($num);

        /* 12 слайд только для препода*/
        if (($this->CurrentUser->user_type == Users::TYPE_STUDENT || (isset($_GET['is_test_student']) && $_GET['is_test_student'] == 1)) && $num == 12) {
            $num = 0;
        }

        /**/
        if (file_exists(Yii::getAlias('@frontend').'/themes/' . DESIGN_THEME . "/common/slides/slide_{$num}.php")) {

            $data = [
                'CurrentUser' => $this->CurrentUser,
                'is_slave' => (isset($_GET['is_slave']) && $_GET['is_slave'] == 1),
                'is_test_student' => (isset($_GET['is_test_student']) && $_GET['is_test_student'] == 1)
            ];
            if ($num == 2 && $this->CurrentUser->user_type != Users::TYPE_STUDENT) {
                $data['Presets'] = Presets::findAll(['preset_status' => Presets::STATUS_APPROVED]);
            }
            return $this->renderPartial("/common/slides/slide_{$num}", $data);

        } else {

            return $this->renderPartial("/common/slides/slide_0");

        }
    }



    //------------------------ только методист или админ могут вызывать эти методами ------------------------------//

    /**
     * @param $id
     * @return Response
     */
    public function actionDeletePreset($id)
    {
        $redirect = $this->checkIsMethodist() ? 'methodist/presets' : 'admin/presets';

        /* если такого пресета не существует */
        $id = intval($id);
        $Preset = Presets::findOne(['preset_id' => $id]);
        if (!$Preset) {
            return $this->redirect([$redirect, 'status' => 'not-exist']);
        }

        /* методист не может удалить не свой пресет */
        if ($this->checkIsMethodist() && $Preset->methodist_user_id != $this->CurrentUser->user_id) {
            return $this->redirect([$redirect, 'status' => 'error-access-denied']);
        }

        if ($Preset->delete()) {
            return $this->redirect([$redirect, 'status' => 'success']);
        } else {
            return $this->redirect([$redirect, 'status' => 'db-error']);
        }
    }

    /**
     * @param $id
     * @return string|Response
     */
    public function actionViewPreset($id)
    {
        $redirect = $this->checkIsMethodist() ? 'methodist/presets' : 'admin/presets';

        /* если такого пресета не существует */
        $id = intval($id);
        $Preset = Presets::findOne(['preset_id' => $id]);
        if (!$Preset) {
            return $this->redirect([$redirect, 'status' => 'not-exist']);
        }

        /* методист не может смотреть не свой пресет */
        if ($this->checkIsMethodist() && $Preset->methodist_user_id != $this->CurrentUser->user_id) {
            return $this->redirect([$redirect, 'status' => 'error-access-denied']);
        }

        return $this->render('/common/view-preset', [
            'CurrentUser' => $this->CurrentUser,
            'Preset' => $Preset,
        ]);
    }



    //------------------------ только admin оператор методист и учитель ------------------------------//

    /**
     * @param $user_id
     * @return string
     */
    public function actionGetUserInfo($user_id)
    {
.....
    }

}
