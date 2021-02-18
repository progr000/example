<?php
namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use frontend\models\search\NextLessons;
use frontend\models\schedule\CommonScheduleForm;

/**
 * User controller
 */
class TeacherController extends UserController
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
                        'allow' => $this->checkIsTeacher(),
                        'roles' => ['@'],
//                        'matchCallback' => function(/*$rule, $action*/) {
//                            if (!$this->checkIsTeacher()) {
//                                return $this->redirect(['user/']);
//                                //return false;
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
            'NextLesson'  => NextLessons::getTeacherLesson($this->CurrentUser->user_id),
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
            'NextLesson'  => NextLessons::getTeacherLesson($this->CurrentUser->user_id),
            'DashboardSchedule' => $model->getScheduleForDashboard(),
        ]);
    }
}
