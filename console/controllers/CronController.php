<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\helpers\Functions;
use common\models\Users;
use common\models\MethodistTimeline;
use common\models\StudentsTimeline;
use frontend\models\schedule\MethodistScheduleForm;
use frontend\models\schedule\StudentsScheduleForm;

/**
 * Site controller
 */
class CronController extends Controller
{

    public $task_start;
    public $task_finish;
    public $task_log;

    /**
     * При разработке консольного приложения принято использовать код возврата.
     * Принято, код 0 (ExitCode::OK) означает, что команда выполнилась удачно.
     * Если команда вернула код больше нуля, то это говорит об ошибке.
     */


    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $this->task_start = date(SQL_DATE_FORMAT);
        $this->task_log = "In progress...";
        echo "\n";
        return parent::beforeAction($action);
    }

    /**
     * @return string
     */
    public function setTaskFinish()
    {
        $this->task_finish = date(SQL_DATE_FORMAT);
        return $this->task_finish;
    }

    /**
     * Очистка (установка в null) поля schedule_id в таблицах таймлайнов для строк у которых дата уже прошла
     * Запускать в 00:00 каждый день
     * пример строки в крон файле: "0 0 * * * /var/www/smart/yii cron/delete-schedule-id-for-old-timelines"
     * @return int
     */
    public function actionDeleteScheduleIdForOldTimelines()
    {
        MethodistTimeline::updateAll(['schedule_id' => null], 'timeline_timestamp < :now', [
            'now' => time(),
        ]);
        MethodistTimeline::deleteAll('(timeline_timestamp < :now) AND (student_user_id IS NULL)', [
            'now' => time(),
        ]);


        StudentsTimeline::updateAll(['schedule_id' => null], 'timeline_timestamp < :now', [
            'now' => time(),
        ]);

        echo "OK\n";

        return ExitCode::OK;
    }

    /**
     * Генерация таймлайнов для StudentsTimeline на основе расписания
     * Запускать в 00:00 каждый день
     * пример строки в крон файле: "0 0 * * * /var/www/smart/yii cron/generate-students-timeline"
     * @return int
     */
    public function actionGenerateStudentsTimeline()
    {
        $Students =  Users::find()->where('(user_type = :user_type) AND (user_status = :user_status) AND (teacher_user_id IS NOT NULL)', [
            'user_type'   => Users::TYPE_STUDENT,
            'user_status' => Users::STATUS_ACTIVE,
        ])->all();

        /** @var \common\models\Users $student */
        foreach ($Students as $student) {

            StudentsScheduleForm::updateStudentsTimelineAfterPayOrByCron($student);

        }

        echo "OK\n";
        return ExitCode::OK;
    }

    /**
     * Генерация таймлайнов для MethodistTimeline на основе расписания
     * Запускать в 00:00 каждый день
     * пример строки в крон файле: "0 0 * * * /var/www/smart/yii cron/generate-methodist-timeline"
     * @return int
     */
    public function actionGenerateMethodistTimeline()
    {
        $Methodists = Users::findAll([
            'user_type'   => Users::TYPE_METHODIST,
            'user_status' => Users::STATUS_ACTIVE,
        ]);

        foreach ($Methodists as $methodist) {
            $model = new MethodistScheduleForm();

            if ($model->load([$model->formName() => [
                    'user_id'       => $methodist->user_id,
                    'user_type'     => $methodist->user_type,
                    'user_timezone' => $methodist->user_timezone,
                ]]) && $model->validate()) {

                $schedule = $model->getScheduleForTimeline();
                $date_start_timestamp = Functions::getTimestampBeginOfDayByTimestamp(time());
                $model->generateTimeline(
                    $date_start_timestamp,
                    $schedule
                );

            }
        }

        echo "OK\n";
        return ExitCode::OK;
    }

    /**
     * ExitCode::NOINPUT;
     * ExitCode::DATAERR;
     * ExitCode::OK;
     */

}
