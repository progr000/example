<?php

namespace frontend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use common\models\Users;
use common\models\MethodistTimeline;

/**
 * MethodistsListSearch
 *
 * @property string $filter
 */
class MethodistsListSearch extends Users
{
    public $filter;
    public $sort;

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //['sort', 'required'],
            [[
                'user_id',
                'user_created',
                'user_updated',
                'user_first_name',
                'user_full_name',
                'user_email',
                'user_photo',
                'filter',
                'sort',
            ], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStudentsForThisMethodist()
    {
        return $this->hasMany(MethodistsListSearch::className(), ['methodist_user_id' => 'user_id'])
            ->where([
                'user_type' => self::TYPE_STUDENT
            ])
            ->all();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTeachersForThisMethodist()
    {
        return $this->hasMany(MethodistsListSearch::className(), ['methodist_user_id' => 'user_id'])
            ->where([
                'user_type' => self::TYPE_TEACHER
            ])
            ->all();
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getLessonsForThisMethodist()
    {
        return $this->hasMany(MethodistTimeline::className(), ['methodist_user_id' => 'user_id'])
            ->alias('t2')
            ->select('t1.*, t2.*')
            ->innerJoin('{{%users}} as t1', 't1.user_id = t2.student_user_id')
            ->where([
                't1.user_type'         => self::TYPE_STUDENT,
                //'t1.user_status'       => self::STATUS_ACTIVE,
            ])
            ->andWhere('(t2.student_user_id IS NOT NULL) AND (t2.timeline_timestamp > :now)', [
                'now' => time() - NextLessons::ENTER_INTO_CLASS_AFTER_BEGINING_TIME_ALLOWED
            ])
            ->orderBy(['t2.timeline_timestamp' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            't1.user_id' => 'ID',
            't1.user_created' => 'дате создания',
            't1.user_updated' => 'дате обновления',
            't1.user_first_name' => 'Имени',
            't1.user_full_name' => 'ФИО',
            't1.user_email' => 'методисту',
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = self::find()->alias('t1');

        /**/
        if (isset($params['sort']) && in_array($params['sort'], ['tmt', '-tmt'])) {

            $fields = [
                't1.user_id',
                't1.user_email',
                't1.user_full_name',
                't1.user_photo',
                't1.user_updated',
            ];
            $aggregate_fields = [
                'max(t2.timeline) as tm',
                'max(t2.timeline_timestamp) as tmt',
            ];
            $query->select(array_merge($fields, $aggregate_fields));
            $query->leftJoin(
                '{{%methodist_timeline}} as t2',
                '(t1.user_id = t2.methodist_user_id) AND (t2.timeline_timestamp > :now) AND (t2.student_user_id IS NOT NULL)',
                ['now' => time() - NextLessons::ENTER_INTO_CLASS_AFTER_BEGINING_TIME_ALLOWED]
            );
            $query->groupBy($fields);

        } else {

        }

        $query->andWhere([
            't1.user_type'   => self::TYPE_METHODIST,
            't1.user_status' => self::STATUS_ACTIVE,
        ]);


        /**/
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => ['created' => SORT_DESC],
                'attributes' => [
                    'id'  => [
                        'asc' =>  ['t1.user_id' => SORT_ASC],
                        'desc' => ['t1.user_id' => SORT_DESC],
                        'default' => SORT_DESC,
                        'label' => 'ID',
                    ],
                    'created'  => [
                        'asc' =>  ['t1.user_created' => SORT_ASC,  't1.user_id' => SORT_ASC],
                        'desc' => ['t1.user_created' => SORT_DESC, 't1.user_id' => SORT_ASC],
                        'default' => SORT_DESC,
                        'label' => 'Created',
                    ],
                    'name' => [
                        'asc' =>  ['t1.user_full_name' => SORT_ASC,  't1.user_id' => SORT_ASC],
                        'desc' => ['t1.user_full_name' => SORT_DESC, 't1.user_id' => SORT_ASC],
                        'default' => SORT_DESC,
                        'label' => 'Name',
                    ],
                    'tmt' => [
                        'asc' =>  [new Expression('tmt ASC NULLS LAST')],
                        'desc' => [new Expression('tmt DESC NULLS LAST')],
                        'default' => SORT_DESC,
                        'label' => 'Name',
                    ]
                ]
            ],
            'pagination' => [ 'pageSize' => 5 ],
        ]);

        /**/
        $this->load($params);

        /**/
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        /**/
        if ($this->filter) {
            $query->andFilterWhere([
                'or',
                ['like', 't1.user_full_name', $this->filter],
                ['like', 't1.user_first_name', $this->filter],
                ['like', 't1.user_middle_name', $this->filter],
                ['like', 't1.user_last_name', $this->filter],
                ['like', 't1.user_email', $this->filter],
                ['like', 't1.user_phone', $this->filter],
            ]);
        }

        return $dataProvider;
    }
}
