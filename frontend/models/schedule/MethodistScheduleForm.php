<?php

namespace frontend\models\schedule;

use Yii;
use common\models\Users;
use common\helpers\Functions;
use common\models\MethodistSchedule;
use common\models\MethodistTimeline;

/**
 *
 * @property integer $methodist_user_id
 *
 */
class MethodistScheduleForm extends CommonScheduleForm
{

    /**
     * @return array
     */
    public function getSchedule()
    {
        /*
        status:
            0: not selected you can change (select it)
            1: selected by you and you can change (un-select it)
            2: enable student for methodist and you can't change it (selected and enable student on this time)
        */

        /* общий отбор данных по расписанию */
        $userSchedule = parent::getSchedule();

        /* добавочный отбор параметров для методиста (будет показано какие часы уже заняты учениками) */
        if ($this->user_type == Users::TYPE_METHODIST) {
            $userTimelineQuery = "
                SELECT
                    t1.week_day,
                    t1.work_hour,
                    string_agg(concat(t2.user_first_name::VARCHAR, ' (id: ', t1.student_user_id::VARCHAR, ')'), ', ' ORDER BY t1.student_user_id ASC) as student_users,
                    string_agg(t1.student_user_id::VARCHAR, ',' ORDER BY t1.student_user_id ASC) as student_user_ids,
                    string_agg(t2.user_first_name::VARCHAR, ',' ORDER BY t1.student_user_id ASC) as student_user_names
                FROM {{%methodist_timeline}} as t1
                INNER JOIN {{%users}} as t2 ON t1.student_user_id = t2.user_id
                WHERE (t1.methodist_user_id = :user_id)
                AND (t1.student_user_id IS NOT NULL)
                AND (t1.timeline_timestamp > :now)
                GROUP BY t1.week_day, t1.work_hour
                ORDER BY t1.week_day ASC, t1.work_hour ASC";

            $res = Yii::$app->db->createCommand($userTimelineQuery, [
                'user_id' => $this->user_id,
                'now' => time(),
            ])->queryAll();

            /**/
            foreach ($res as $item) {
                $item_week_day  = intval($item['week_day']);
                $item_work_hour = intval($item['work_hour']);

                /* учет таймзоны юзера */
                $tmp = self::dayAndHourFromGmtToTz($item_week_day, $item_work_hour, $this->user_timezone);
                $item_week_day  = intval($tmp['week_day']);
                $item_work_hour = intval($tmp['work_hour']);

                if (isset($userSchedule[$item_week_day][$item_work_hour])) {
                    $userSchedule[$item_week_day][$item_work_hour] = ['status' => 2, 'users' => $item['student_users']];
                }
            }
        }

        return $userSchedule;
    }

    /**
     * @return array
     */
    public function changeSchedule()
    {
        $transaction = Yii::$app->db->beginTransaction();

        if ($this->hour_status == self::OFF) {
            /* проверка что этот час в расписании никем из студентов еще не занят, тогда можно его снять */
            /** @var \common\models\MethodistTimeline $res */
            $res = MethodistTimeline::find()->where('
            (methodist_user_id = :methodist_user_id) AND
            (week_day = :week_day) AND
            (work_hour = :work_hour) AND
            (timeline_timestamp >= :now) AND
            (student_user_id IS NOT NULL)', [
                'methodist_user_id' => $this->user_id,
                'week_day'          => $this->week_day,
                'work_hour'         => $this->work_hour,
                'now'               => time(),
            ])->one();
            if ($res) {
                $transaction->rollBack();
                return [
                    'changed' => false,
                    'info'    => "На это время стоит вводное занятие у ученика <{$res->getStudent()->one()->user_email}>. Нельзя отменить",
                ];
            }

            /* снимаем час с расписания */
            if (MethodistSchedule::deleteAll([
                'methodist_user_id' => $this->user_id,
                'week_day'          => $this->week_day,
                'work_hour'         => $this->work_hour,
            ])) {
                $transaction->commit();
                return [
                    'changed' => true,
                    'info' => "OK",
                ];
            }
        } else {
            /* устанавливаем час на расписание */
            $Schedule = new MethodistSchedule();
            $Schedule->methodist_user_id = $this->user_id;
            $Schedule->student_user_id = null;
            $Schedule->week_day = $this->week_day;
            $Schedule->work_hour = $this->work_hour;
            if ($Schedule->save()) {

                /* генерация таймлайнов */
                $schedule_for_generate_timeline[$Schedule->week_day][$Schedule->work_hour] = $Schedule->schedule_id;
                $date_start_timestamp = Functions::getTimestampBeginOfDayByTimestamp(time());
                if (!$this->generateTimeline(
                    $date_start_timestamp,
                    $schedule_for_generate_timeline
                )) {
                    $transaction->rollBack();
                    return [
                        'status' => false,
                        'data'   => 'Some errors during generate timelines',
                    ];
                }

                $transaction->commit();
                return [
                    'changed' => true,
                    'info' => "OK",
                ];
            }
        }

        /* оибка БД */
        $transaction->rollBack();
        return [
            'changed' => false,
            'info' => "DB error",
        ];
    }

    /**
     * @param integer $start_timestamp
     * @param array $schedule_for_generate_timeline
     * @return bool
     * @throws \yii\db\Exception
     */
    public function generateTimeline($start_timestamp, &$schedule_for_generate_timeline)
    {
        $status = true;

        /**/
        $current_timestamp = $start_timestamp;
        $finish_timestamp = $start_timestamp + self::generateTimelinePeriod;

        /**/
        while ($current_timestamp < $finish_timestamp) {

            /**/
            $week_day = Functions::getDayOfWeek($current_timestamp);
            $work_hour = intval(date('H', $current_timestamp));

            /* учет таймзоны юзера */
            $tmp = self::dayAndHourFromTzToGmt($week_day, $work_hour, $this->user_timezone);
            $week_day = intval($tmp['week_day']);
            $work_hour = intval($tmp['work_hour']);

            //var_dump($week_day . ' -- ' . $work_hour );
            if (isset($schedule_for_generate_timeline[$week_day][$work_hour])) {

                $test = MethodistTimeline::findOne([
                    'methodist_user_id' => $this->user_id,
                    'timeline_timestamp' => $current_timestamp - (intval($this->user_timezone / 3600) * 3600),
                ]);

                if (!$test) {

                    //var_dump($week_day . ' - ' . $work_hour );
                    $timeline = new MethodistTimeline();
                    $timeline->schedule_id = $schedule_for_generate_timeline[$week_day][$work_hour];
                    $timeline->methodist_user_id = $this->user_id;
                    $timeline->student_user_id = null;
                    $timeline->week_day = $week_day;
                    $timeline->work_hour = $work_hour;
                    $timeline->timeline = date(SQL_DATE_FORMAT, $current_timestamp - (intval($this->user_timezone / 3600) * 3600));
                    $timeline->timeline_timestamp = $current_timestamp - (intval($this->user_timezone / 3600) * 3600);
                    $timeline->room_hash = md5(uniqid());
                    if (!$timeline->save()) {

                        //var_dump($timeline->getErrors());
                        $status = false;

                    }

                }
            }
            $current_timestamp = $current_timestamp + 3600;
        }

        return $status;
    }
}
