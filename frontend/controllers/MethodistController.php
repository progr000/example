<?php
namespace frontend\controllers;

use Yii;
use yii\base\ErrorException;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\UploadedFile;
use yii\web\Response;
use common\models\Users;
use frontend\models\forms\AddHomeWorkForm;
use frontend\models\search\PresetsSearch;
use frontend\models\search\NextLessons;
use frontend\models\forms\AddPresetForm;
use frontend\models\schedule\CommonScheduleForm;

/**
 * User controller
 */
class MethodistController extends UserController
{
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

                    /**/
                    [
                        'allow' => $this->checkIsMethodist(),
                        'roles' => ['@'],
//                        'matchCallback' => function($rule, $action) {
//                            if (!$this->checkIsMethodist()) {
//                                //return $this->redirect(['user/']);
//                                return false;
//                            }
//                            return true;
//                        },
                        'denyCallback' => function ($rule, $action) {
                            return $this->denyCallbackFunct();
                        },
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays homepage.
     * @return mixed
     */
    public function actionIndex()
    {
        $model = new CommonScheduleForm();
        $model->user_id = $this->CurrentUser->user_id;
        $model->user_type = $this->CurrentUser->user_type;
        $model->user_timezone = $this->CurrentUser->user_timezone;

        return $this->render('index', [
            'CurrentUser' => $this->CurrentUser,
            'NextLesson'  => NextLessons::getMethodistLesson($this->CurrentUser->user_id),
            'DashboardSchedule' => $model->getScheduleForDashboard(),
        ]);
    }

    /**
     * @return string
     */
    public function actionScheduleOld()
    {
        return $this->render('schedule-old', [
            'CurrentUser' => $this->CurrentUser,
        ]);
    }

    /**
     * @return string
     */
    public function actionSchedule()
    {
        $model = new CommonScheduleForm();
        $model->user_id = $this->CurrentUser->user_id;
        $model->user_type = $this->CurrentUser->user_type;
        $model->user_timezone = $this->CurrentUser->user_timezone;

        return $this->render('schedule', [
            'CurrentUser' => $this->CurrentUser,
            'NextLesson'  => NextLessons::getMethodistLesson($this->CurrentUser->user_id),
            'DashboardSchedule' => $model->getScheduleForDashboard(),
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionAddHomeWork()
    {
        $model = new AddHomeWorkForm();
        //var_dump($model->uploadedFile);exit;
        if ($model->load(Yii::$app->request->post())) {
            $model->uploadedFile = UploadedFile::getInstance($model, 'uploadedFile');
            if ($model->upload($this->CurrentUser)) {
                return $this->redirect(['/user/home-works']);
            }
        }

        return $this->render('add-home-work', [
            'CurrentUser' => $this->CurrentUser,
            'model'       => $model,
        ]);
    }

    /**
     * @return string|\yii\web\Response
     */
    public function actionPresets()
    {
        $presetAddForm = new AddPresetForm();
        $presetsModel = new PresetsSearch($this->CurrentUser);
        $presetsDataProvider = $presetsModel->search(Yii::$app->request->queryParams);

        //var_dump($presetsDataProvider);exit;
        return $this->render('/methodist/presets', [
            'CurrentUser'         => $this->CurrentUser,
            'presetAddForm'       => $presetAddForm,
            'presetsModel'        => $presetsModel,
            'presetsDataProvider' => $presetsDataProvider,
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionAddPreset()
    {
        $model = new AddPresetForm();
        if ($model->load(Yii::$app->request->post())) {
            $model->preset_upl_image = UploadedFile::getInstance($model, 'preset_upl_image');
            $model->preset_upl_file  = UploadedFile::getInstance($model, 'preset_upl_file');
            if ($model->upload($this->CurrentUser)) {
                return $this->redirect(['methodist/presets', 'status' => 'success']);
            }
        }

        return $this->redirect(['methodist/presets', 'status' => 'error']);
    }

    /**
     * @return string
     */
    public function actionSaveStudentResult()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $room_hash = Yii::$app->request->post('room_hash', null);
        $student_user_id = intval(Yii::$app->request->post('student_user_id', null));

        if (!$room_hash || !$student_user_id) {
            return [
                'status' => false,
                'error'  => 'Wrong POST data',
            ];
        }

        $NextLessons = NextLessons::checkIntroductoryLessonRoomHash($room_hash, $this->CurrentUser);
        if (!$NextLessons) {
            return [
                'status' => false,
                'error'  => 'Wrong room_hash',
            ];
        }

        if ($NextLessons->student_user_id !== $student_user_id) {
            return [
                'status' => false,
                'error'  => 'Wrong student_user_id',
                'data'   => Yii::$app->request->post(),
            ];
        }

        $User = Users::findById($student_user_id);
        if (!$User) {
            return [
                'status' => false,
                'error'  => 'Student not found for this room',
            ];
        }

        //$User->notes_close = intval()
        if (!$User->load(['UserData' => Yii::$app->request->post()], 'UserData')) {
            return [
                'status' => false,
                'error'  => $User->getErrors(),
            ];
        }

        $User->user_status = Users::STATUS_AFTER_INTRODUCE;

        if (!$User->validate()) {
            return [
                'status' => false,
                'error'  => $User->getErrors(),
            ];
        }

        if (!$User->save()) {
            return [
                'status' => false,
                'error'  => $User->getErrors(),
            ];
        }

        return [
            'status' => true,
            'data'   => [
                'url' => Url::to(['student/after-introduce'], CREATE_ABSOLUTE_URL),
            ],
        ];
    }
}
