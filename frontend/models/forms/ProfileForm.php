<?php
namespace frontend\models\forms;

use Yii;
use common\helpers\Functions;
use common\models\Users;

/**
 * Signup form
 *
 * @property string $_old_user_email
 * @property string $password
 *
 * @property integer $user_birthday_day
 * @property integer $user_birthday_month
 * @property integer $user_birthday_year
 *
 * @property array $_user_music_experience
 * @property array $_user_learning_objectives
 * @property array $_user_music_genres
 *
 *
 */
class ProfileForm extends Users
{
    public $_old_user_email;
    public $password;

    public $user_birthday_day;
    public $user_birthday_month;
    public $user_birthday_year;

    public $_user_music_experience;
    public $_user_learning_objectives;
    public $_user_music_genres;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            //['password', 'required'],
            ['password', 'string', 'min' => 6],
            [['user_birthday_day', 'user_birthday_month', 'user_birthday_year'], 'required'],
            ['user_birthday_day', 'integer', 'min' => 1, 'max' => 31],
            ['user_birthday_month', 'integer', 'min' => 1, 'max' => 12],
            ['user_birthday_year', 'integer', 'min' => 1970, 'max' => intval(date('Y'))],

            [['_user_learning_objectives', '_user_music_experience', '_user_music_genres'], 'checkIsArray'],
        ]);
    }

    /**
     * @param $attribute
     */
    public function checkIsArray($attribute)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Must be an array');
            return;
        }
    }

    /**
     * @return bool
     */
    public function saveProfile()
    {
        //var_dump(Yii::$app->request->post());exit;
        /**/
        $this->user_birthday = date(
            SQL_DATE_FORMAT,
            Functions::getTimestampBeginOfDayByTimestamp(
                strtotime(
                    $this->user_birthday_year . '-' .
                    $this->user_birthday_month . '-' .
                    $this->user_birthday_day
                )
            )
        );
        /**/
        if (is_array($this->_user_music_experience)) {
            foreach ($this->_user_music_experience as $key => $item) {
                if (!isset(Users::$_music_experience[$key])) {
                    unset($this->_user_music_experience[$key]);
                }
            }
        }
        $this->user_music_experience = serialize($this->_user_music_experience);
        /**/
        if (is_array($this->_user_music_genres)) {
            foreach ($this->_user_music_genres as $key => $item) {
                if (!isset(Users::$_music_genres[$key])) {
                    unset($this->_user_music_genres[$key]);
                }
            }
        }
        $this->user_music_genres = serialize($this->_user_music_genres);
        /**/
        if (is_array($this->_user_learning_objectives)) {
            foreach ($this->_user_learning_objectives as $key => $item) {
                if (!isset(Users::$_learning_objectives[$key])) {
                    unset($this->_user_learning_objectives[$key]);
                }
            }
        }
        $this->user_learning_objectives = serialize($this->_user_learning_objectives);
        /**/
        if ($this->password) {
            $this->setPassword($this->password);
            $this->generateAuthKey();
            $this->user_need_set_password = Users::NO;
        }
        /**/
        if ($this->_old_user_email !== $this->user_email) {
            $this->generateEmailVerificationToken();
        }
        /**/
        return $this->save();
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        parent::afterFind();
        $this->_old_user_email = $this->user_email;

        $this->user_birthday_day   = intval(date('d', strtotime($this->user_birthday)));
        $this->user_birthday_month = intval(date('m', strtotime($this->user_birthday)));
        $this->user_birthday_year  = intval(date('Y', strtotime($this->user_birthday)));
    }
}
