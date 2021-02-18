<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\helpers\Functions;

/**
 * This is the model class for table "{{%presets}}".
 *
 * @property int $preset_id
 * @property string $preset_created
 * @property string $preset_updated
 * @property string $preset_name
 * @property string|null $preset_description
 * @property string $preset_file
 * @property string $preset_image
 * @property int $preset_status
 * @property int $preset_level
 * @property int|null $admin_user_id
 * @property int|null $operator_user_id
 * @property int $methodist_user_id
 *
 * @property Users $adminUser
 * @property Users $operatorUser
 * @property Users $methodistUser
 */
class Presets extends ActiveRecord
{

    const STATUS_AWAITING = 0;
    const STATUS_REJECTED = 1;
    const STATUS_APPROVED = 2;

    const LEVEL_LOW    = 0;
    const LEVEL_MEDIUM = 1;
    const LEVEL_HIGH   = 2;

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_AWAITING => 'в ожидании модерации',
            self::STATUS_REJECTED => 'отклонён',
            self::STATUS_APPROVED => 'одобрен',
        ];
    }

    /**
     * @param $preset_status
     * @return mixed
     */
    public static function getStatus($preset_status)
    {
        return self::getStatuses()[$preset_status];
    }

    /**
     * @return array
     */
    public static function getLevels()
    {
        return [
            self::LEVEL_LOW    => 'Низкий',
            self::LEVEL_MEDIUM => 'Средний',
            self::LEVEL_HIGH   => 'Высокий',
        ];
    }

    /**
     * @param $preset_level
     * @return mixed
     */
    public static function getLevel($preset_level)
    {
        return self::getLevels()[$preset_level];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%presets}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'preset_created',
                'updatedAtAttribute' => 'preset_updated',
                'value' => function() { return date(SQL_DATE_FORMAT); }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['preset_name', 'preset_file', 'preset_image', 'methodist_user_id'], 'required'],
            [['preset_created', 'preset_updated'], 'validateDateField', 'skipOnEmpty' => true],
            [['preset_created', 'preset_updated'], 'safe'],
            [['preset_name', 'preset_description'], 'string'],
            [['preset_status', 'preset_level', 'admin_user_id', 'operator_user_id'], 'default', 'value' => null],
            [['preset_status', 'preset_level', 'admin_user_id', 'operator_user_id', 'methodist_user_id'], 'integer'],
            [['preset_status'], 'in', 'range' => [self::STATUS_AWAITING, self::STATUS_REJECTED, self::STATUS_APPROVED]],
            [['preset_level'], 'in', 'range' => [self::LEVEL_LOW, self::LEVEL_MEDIUM, self::LEVEL_HIGH]],
            [['preset_file', 'preset_image'], 'string', 'max' => 255],
            [['admin_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['admin_user_id' => 'user_id']],
            [['operator_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['operator_user_id' => 'user_id']],
            [['methodist_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['methodist_user_id' => 'user_id']],
        ];
    }

    /**
     * @param $attribute
     */
    public function validateDateField($attribute/*, $params*/)
    {
        $check = Functions::checkDateIsValidForDB($this->$attribute);
        if (!$check) {
            $this->addError($attribute, 'Invalid date format');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'preset_id' => 'Preset ID',
            'preset_created' => 'Preset Created',
            'preset_updated' => 'Preset Updated',
            'preset_name' => 'Preset Name',
            'preset_description' => 'Preset Description',
            'preset_file' => 'Preset File',
            'preset_image' => 'Preset Image',
            'preset_status' => 'Preset Status',
            'preset_level' => 'Preset Level',
            'admin_user_id' => 'Admin User ID',
            'operator_user_id' => 'Operator User ID',
            'methodist_user_id' => 'Methodist User ID',
        ];
    }

    /**
     * Gets query for [[AdminUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAdminUser()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'admin_user_id']);
    }

    /**
     * Gets query for [[OperatorUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOperatorUser()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'operator_user_id']);
    }

    /**
     * Gets query for [[MethodistUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMethodistUser()
    {
        return $this->hasOne(Users::className(), ['user_id' => 'methodist_user_id'])->one();
    }

    /**
     * @return string
     */
    public function getPresetWebPath()
    {
        return Yii::$app->params['presetsDirWeb'] . '/' . $this->preset_file;
        //return Yii::getAlias('@frontendWeb') . Yii::$app->params['presetsDirWeb'] . '/' . $this->preset_file;
    }

    protected static function createPresetsDirSymlink()
    {
        $targetLink = Yii::getAlias('@frontend') . "/web" . Yii::$app->params['presetsDirWeb'];
        if (!file_exists($targetLink) && file_exists(Yii::$app->params['presetsUploadsDir'])) {
            @symlink(Yii::$app->params['presetsUploadsDir'], $targetLink);
        }
    }

    /**
     *  @inheritdoc
     */
    public function afterFind()
    {
        parent::afterFind();

        self::createPresetsDirSymlink();
    }
}
