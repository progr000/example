<?php

namespace frontend\models\schedule;

use Yii;
use yii\base\Model;
use common\models\Users;

/**
 *
 * @property integer $user_id
 * @property integer $user_type
 * @property integer $user_timezone
 * @property array $schedule
 * @property integer $week_day
 * @property integer $work_hour
 * @property integer $hour_status
 *
 * @property integer $date_start_timestamp
 *
 */
class CommonScheduleForm extends Model
{
    const ON  = 1;
    const OFF = 0;

    const generateTimelinePeriod = 30 * 24*60*60;

    public $user_id;
    public $user_type;
    public $user_timezone;
    public $schedule;
    public $week_day, $work_hour, $hour_status;

    protected $date_start_timestamp;

    /**
     * inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'user_timezone', 'user_type'], 'required'],
            [['user_id', 'user_timezone', 'user_type'], 'integer'],
            ['schedule', 'checkSchedule'],
            [['week_day', 'work_hour', 'hour_status'], 'integer'],
            ['hour_status', 'in', 'range' => [self::ON, self::OFF]],
        ];
    }

    /**
     * @param $attribute
     */
    public function checkSchedule($attribute)
    {
        if (sizeof($this->schedule) != 7) {
            $this->addError($attribute, 'Wrong schedule format (count days must be 7)');
            return;
        }
        foreach ($this->schedule as $item) {
            if (sizeof($item) != 24) {
                $this->addError($attribute, 'Wrong schedule format (count hours in every day must be 24)');
                return;
            }
        }
    }

    /**
     * @return array
     */
    protected function getTableAndWhereField()
    {
        switch ($this->user_type) {
            case Users::TYPE_METHODIST:
                $ret['table'] = "{{%methodist_schedule}}";
                $ret['field_user_id'] = 'methodist_user_id';
                break;
            case Users::TYPE_TEACHER:
                $ret['table'] = "{{%teachers_schedule}}";
                $ret['field_user_id'] = 'teacher_user_id';
                break;
            case Users::TYPE_STUDENT:
                $ret['table'] = "{{%students_schedule}}";
                $ret['field_user_id'] = 'student_user_id';
                break;
            default:
                $ret['table'] = "{{%students_schedule}}";
                $ret['field_user_id'] = 'student_user_id';
        }
        return $ret;
    }

    /**
     * @param int $week_day
     * @param int $work_hour
     * @param int $tz
     * @return array [int $week_day, int $work_hour]
     */
    public static function dayAndHourFromGmtToTz($week_day, $work_hour, $tz)
    {
        $zone = intval(floor($tz / 3600));

        $tmp_work_hour = $work_hour + $zone;
        //var_dump($tmp_work_hour);
        if ($tmp_work_hour >= 0 && $tmp_work_hour <= 23) {
            $week_day = intval($week_day);
            $work_hour = $work_hour + $zone;
        } elseif ($tmp_work_hour < 0) {
            $week_day = $week_day - 1;
            if ($week_day < 1) { $week_day = 7; }
            $work_hour = $tmp_work_hour + 24 ;/**/
        } elseif ($tmp_work_hour > 23) {
            $week_day = $week_day + 1;
            if ($week_day > 7) { $week_day = 1; }
            $work_hour = $tmp_work_hour - 24;
        }

        return [
            'week_day'  => $week_day,
            'work_hour' => $work_hour,
        ];
    }

    /**
     * @param $week_day
     * @param $work_hour
     * @param $tz
     * @return array
     */
    public static function dayAndHourFromTzToGmt($week_day, $work_hour, $tz)
    {
        $zone = intval(floor($tz / 3600));

        $tmp_work_hour = $work_hour - $zone;
        if ($tmp_work_hour >= 0 && $tmp_work_hour <= 23) {
            $week_day = intval($week_day);
            $work_hour = $work_hour - $zone;
        } elseif ($tmp_work_hour < 0) {
            $week_day = $week_day - 1;
            if ($week_day < 1) { $week_day = 7; }
            $work_hour = $tmp_work_hour + 24;
        } elseif ($tmp_work_hour > 23) {
            $week_day = $week_day + 1;
            if ($week_day > 7) { $week_day = 1; }
            $work_hour = $work_hour - 24;
        }

        return [
            'week_day'  => $week_day,
            'work_hour' => $work_hour,
        ];
    }

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

        /**/
        $userSchedule = [0 => []];
        for ($week_day=1; $week_day<=7; $week_day++) {
            for ($work_hour=0; $work_hour<=23; $work_hour++) {
                $userSchedule[$week_day][$work_hour] = ['status' => 0, 'users' => null];
            }
        }

        /**/
        $ret = $this->getTableAndWhereField();

        /**/
        $userScheduleQuery = "
          SELECT
            week_day,
            work_hour
          FROM {$ret['table']}
          WHERE {$ret['field_user_id']} = :user_id
          GROUP BY week_day, work_hour
          ORDER BY week_day ASC, work_hour ASC";
        $res = Yii::$app->db->createCommand($userScheduleQuery, [
            'user_id' => $this->user_id,
        ])->queryAll();

        /**/
        foreach ($res as $item) {
            $item_week_day  = intval($item['week_day']);
            $item_work_hour = intval($item['work_hour']);

            /* учет таймзоны юзера */
            $tmp = self::dayAndHourFromGmtToTz($item_week_day, $item_work_hour, $this->user_timezone);
            $item_week_day  = $tmp['week_day'];
            $item_work_hour = $tmp['work_hour'];

            if (isset($userSchedule[$item_week_day][$item_work_hour])) {
                $userSchedule[$item_week_day][$item_work_hour] = ['status' => 1, 'users' => null];
            }
        }

        /**/
        return $userSchedule;
    }

    /**
     * @return array
     */
    public function getScheduleForDashboard()
    {
        $userSchedule = [];

        /**/
        $ret = $this->getTableAndWhereField();

        /**/
//        $userScheduleQuery = "
//            SELECT
//                week_day,
//                string_agg(work_hour::VARCHAR, ',' ORDER BY work_hour ASC) as work_hours,
//                array_agg(work_hour  ORDER BY work_hour ASC) as work_hours_arr,
//            FROM {$ret['table']}
//            WHERE {$ret['field_user_id']} = :user_id
//            GROUP BY week_day
//            ORDER BY week_day ASC";
        $userScheduleQuery = "
            SELECT
                week_day,
                work_hour
            FROM {$ret['table']}
            WHERE {$ret['field_user_id']} = :user_id
            GROUP BY week_day, work_hour
            ORDER BY week_day ASC, work_hour ASC";
        $res = Yii::$app->db->createCommand($userScheduleQuery, [
            'user_id' => $this->user_id,
        ])->queryAll();

        /**/
        $tz_lost = $this->user_timezone / 3600;
        $minutes = intval(($tz_lost - floor($tz_lost)) * 60);
        if ($minutes < 10) { $minutes = "0{$minutes}"; }

        /**/
        foreach ($res as $item) {
            $item_week_day  = intval($item['week_day']);
            $item_work_hour = intval($item['work_hour']);

            /* учет таймзоны юзера */
            $tmp = self::dayAndHourFromGmtToTz($item_week_day, $item_work_hour, $this->user_timezone);
            $item_week_day  = $tmp['week_day'];
            $item_work_hour = $tmp['work_hour'];

            if ($item_work_hour < 10) {
                $_prn = "0{$item_work_hour}:{$minutes}";
            } else {
                $_prn = "{$item_work_hour}:{$minutes}";
            }

            $userSchedule[$item_week_day][$item_work_hour] = $_prn;
        }

        /**/
        return $userSchedule;
    }

    /**
     * @return array
     */
    public function getScheduleForTimeline()
    {
        /**/
        $ret = $this->getTableAndWhereField();

        /**/
        $scheduleQuery = "
            SELECT
                schedule_id,
                week_day,
                work_hour
            FROM {$ret['table']}
            WHERE {$ret['field_user_id']} = :user_id
            ORDER BY week_day ASC, work_hour ASC";
        $res = Yii::$app->db->createCommand($scheduleQuery, [
            'user_id' => $this->user_id,
        ])->queryAll();

        $userSchedule = [];
        foreach ($res as $item) {
            $item_week_day  = intval($item['week_day']);
            $item_work_hour = intval($item['work_hour']);
            $userSchedule[$item_week_day][$item_work_hour] = $item['schedule_id'];
        }

        /**/
        return $userSchedule;
    }
}
