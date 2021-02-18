<?php

/** @var $this yii\web\View */
/** @var $CurrentUser \common\models\Users */
/** @var $newStudentForm \common\models\Users */
/** @var $modelRequestSearch \frontend\models\search\RequestSearch */
/** @var $dataProviderRequest \yii\data\ActiveDataProvider */
/** @var $listOperators \yii\db\ActiveRecord[] */
/** @var $modelIntroduceSearch \frontend\models\search\StudentsListSearch */
/** @var $dataProviderIntroduce \yii\data\ActiveDataProvider */
/** @var $dataProviderIntroduceFinished \yii\data\ActiveDataProvider */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use common\helpers\Functions;
use common\models\MethodistTimeline;
use frontend\assets\smart\admin\UsersListAsset;

UsersListAsset::register($this);

$this->title = Html::encode('Dashboard | Admin area');

?>

<div class="dashboard dashboard--grid">

    <!-- Лиды с сайта -->
    <?php Pjax::begin([
        'id' => 'students-request-content',
        'timeout' => PJAX_TIMEOUT,
        'options'=> ['tag' => 'div', 'class' => 'dashboard__section']
    ]); ?>
        <div class="dashboard__section-title title">
            <div>Лиды с сайта</div>
            <div class="dashboard__section-controls">
                <div class="totals">
                    <div class="total-number">
                        <div class="total-number__label">Покказать за: </div>
                        &nbsp;&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'RequestSearch[period]' => date('Y-m-d', time()) . ' - ' . date('Y-m-d', time())
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">сегодня</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'RequestSearch[period]' => date('Y-m-d', strtotime('-7 days')) . ' - ' . date('Y-m-d', time())
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">7 дней</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to(['index'], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">все</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- leads-list -->
        <?=
        ListView::widget([
            'dataProvider' => $dataProviderRequest,
            //'itemOptions' => ['class' => 'item'],
            'itemOptions' => [
                'tag' => false,
                'class' => '',
            ],
            'summary' => 'Страница <b>{page, number}</b>. Показаны записи с <b>{begin, number}</b> по <b>{end, number}</b> из <b>{totalCount, number}</b>.',
            'layout' => '

                        <div class="info-sowed-num-off-num">

                            {summary}

                        </div>

                        <div class="leads-list">

                            {items}

                        </div>

                        <div class="pages">

                            {pager}

                        </div>

                ',
            'emptyText' => '<div class="presets-empty">' . ($modelRequestSearch->period ? 'По заданному поиску нет результатов' : 'Нет новых заявок от учеников' ) . '</div>',
            'emptyTextOptions' => ['tag' => false],
            'itemView' => function ($model, $key, $index, $widget) use ($CurrentUser) {
                /** @var $model \frontend\models\search\RequestSearch */

                $operator_user_full_name = 'Не назначен';
                /** @var \common\models\Users $Operator */

                /**/
                $Operator = $model->getOperatorUser();
                if ($Operator) {
                    $active_class = '';
                    $operator_user_full_name = $Operator->user_full_name;
                    $operator_block = '
                        <div class="user-block user-block--sm">
                            <div class="user-block__ava">
                                <img src="' . $Operator->getProfilePhotoForWeb('/assets/smart-min/images/no_photo.png') . '" alt="" />
                            </div>
                            <div class="user-block__data">
                                <div class="user-block__position">Оператор</div>
                                <div class="user-block__name">
                                    <a class="user-block__name-link js-open-modal-user-info void-0"
                                       href="#"
                                       data-user-id="' . $Operator->user_id . '"
                                       data-modal-id="operator-info-modal">' . $Operator->user_full_name . '</a>
                                </div>
                                <div class="user-block__name">
                                    <a class="user-block__name-link js-open-modal js-open-operators-list-modal void-0"
                                       data-modal-id="operators-list-modal"
                                       data-lead_id="' . $model->lead_id . '"
                                       href="#">(изменить)</a>
                                </div>
                            </div>
                        </div>
                    ';
                } else {
                    $active_class = '_active';
                    $operator_block = '
                        <div class="operator-actions">
                            <div class="operator-actions__label">Оператор</div>
                            <a class="operator-actions__action-link js-open-modal js-open-operators-list-modal void-0"
                               href="#"
                               data-lead_id="' . $model->lead_id . '"
                               data-modal-id="operators-list-modal">Назначить</a>
                        </div>
                    ';
                }

                /**/
                $time_passed_str = '';
                $time_passed_minutes = intval( (time() - strtotime($model->lead_created)) / 60);
                if ($time_passed_minutes > 60 * 24 ) {
                    $days = intval($time_passed_minutes / (60 * 24));
                    $hours = intval(($time_passed_minutes % (60 * 24)) / 60);
                    //$minutes = $time_passed_minutes % 60;
                    $time_passed_str = $days . ' ' . Functions::in_days_ru_text($days)[0] . '<br />';
                    if ($hours > 0) {
                        $time_passed_str .= $hours . ' ' . Functions::in_hours_ru_text($hours)[0];
                    }
                } else if ($time_passed_minutes > 60) {
                    $hours = intval($time_passed_minutes / 60);
                    $minutes = intval($time_passed_minutes % 60);
                    $time_passed_str .= $hours . ' ' . Functions::in_hours_ru_text($hours)[0] . '<br />';
                    if ($minutes > 0) {
                        $time_passed_str .= $minutes . ' ' . Functions::left_minutes_ru_text($minutes)[2];
                    }
                } else if ($time_passed_minutes > 0) {
                    $time_passed_str .= $time_passed_minutes . ' ' . Functions::left_minutes_ru_text($time_passed_minutes)[2];
                } else {
                    $time_passed_str = 'Меньше минуты';
                }

                /* return html */
                return '
                    <!--.request-details-->
                    <div class="lead-item leads-list__item ' . $active_class . '">
                        <div class="lead-item__cell">
                            <div class="user-block user-block--sm">
                                <div class="user-block__ava"><img src="/assets/smart-min/files/profile/user-avatar.svg" alt=""></div>
                                <div class="user-block__data">
                                    <div class="user-block__position">Клиент (id: ' . $model->lead_id . ')</div>
                                    <div class="user-block__name">
                                        <a class="user-block__name-link js-open-modal js-open-request-info-modal void-0"
                                           href="#"
                                           data-is-teacher="0"
                                           data-lead_id="' . $model->lead_id . '"
                                           data-lead_name="' . $model->lead_name . '"
                                           data-lead_phone="' . $model->lead_phone . '"
                                           data-lead_email="' . $model->lead_email . '"
                                           data-operator_user_id="' . $model->operator_user_id . '"
                                           data-operator_notice="' . $model->operator_notice . '"
                                           data-additional_service_info="' . $model->additional_service_info . '"
                                           data-operator_user_full_name="' . $operator_user_full_name . '"
                                           data-lead_created="' . $CurrentUser->getDateInUserTimezoneByDateString($model->lead_created) . '"
                                           data-modal-id="request-info-modal">' . $model->lead_name . '</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Дата заявки</div>
                                <div class="item__value">
                                    <a href=""
                                       data-modal-id="schedule-modal"
                                       class="js-open-modal">' . $CurrentUser->getDateInUserTimezoneByDateString($model->lead_created) . '</a>
                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Прошло времени с момента подачи заявки</div>
                                <div class="item__value">

                                    ' . $time_passed_str . '

                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Телефон</div>
                                <div class="item__value">
                                    ' . $model->lead_phone . '
                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Электронная почта</div>
                                <div class="item__value">
                                    ' . $model->lead_email . '
                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell">

                            ' . $operator_block . '

                        </div>

                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Взят в работу</div>
                                <div class="item__value">
                                    ' .
                                    (
                                        $model->lead_in_work
                                            ? $CurrentUser->getDateInUserTimezoneByDateString($model->lead_in_work)
                                            : '-'
                                    ) .
                                    '
                                </div>
                            </div>
                        </div>
                        <div class="lead-item__cell lead-controls">
                            <a class="bid-controls__btn bid-controls__btn--accept btn js-open-modal js-btn-add-new-student void-0"
                               href="#"
                               data-is-new="1"
                               data-user_id="0"
                               data-user_first_name="' . $model->lead_name . '"
                               data-user_middle_name=""
                               data-user_last_name=""
                               data-user_phone="' . $model->lead_phone . '"
                               data-user_email="' . $model->lead_email . '"
                               data-_user_skype=""
                               data-_user_telegram=""
                               data-additional_service_notice=""
                               data-admin_notice=""
                               data-lead-id="' . $model->lead_id . '"
                               data-modal-id="new-student-modal">Обработать</a>
                            <a class="bid-controls__btn bid-controls__btn--cancel btn js-btn-reject-new-request void-0"
                               href="#"
                               data-href="' . Url::to(['reject-student-request', 'lead_id' => $model->lead_id]) . '">Удалить</a>
                        </div>
                    </div>
                    <!--.request-details-->
                ';
            },
        ]);
        ?>
        <!-- leads-list -->

        <!--
        <div class="dashboard__section-controls">
            <div class="totals">
                <div class="total-number">
                    <div class="total-number__label">Подвисшие за 7 дней:</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">3</a></div>
                </div>
                <div class="total-number">
                    <div class="total-number__label">Мертвые (более 7 дней):</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">12</a></div>
                </div>
            </div>
        </div>
        -->
    <?php Pjax::end(); ?>


    <!-- Предстоящие вводные уроки -->
    <?php Pjax::begin([
        'id' => 'students-introduce-lessons',
        'timeout' => PJAX_TIMEOUT,
        'options'=> ['tag' => 'div', 'class' => 'dashboard__section']
    ]); ?>
        <div class="dashboard__section-title title">
            <div>Предстоящие вводные уроки</div>
            <div class="dashboard__section-controls">
                <div class="totals">
                    <div class="total-number">
                        <div class="total-number__label">Покказать за: </div>
                        &nbsp;&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'StudentsListSearch[period]' => date('Y-m-d', time()) . ' - ' . date('Y-m-d', time())
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">сегодня</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'StudentsListSearch[period]' => date('Y-m-d', time()) . ' - ' . date('Y-m-d', strtotime('+7 days'))
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">7 дней</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to(['index'], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">все</a>
                        </div>
                    </div>
                </div>
                <a class="new-object-link js-open-modal btn-add-new-student js-btn-add-new-student void-0"
                   href="#"
                   data-is-new="1"
                   data-user_id="0"
                   data-user_first_name=""
                   data-user_phone=""
                   data-user_email=""
                   data-_user_skype=""
                   data-_user_telegram=""
                   data-operator_user_id="0"
                   data-methodist_user_id="0"
                   data-teacher_user_id="0"
                   data-introduce_lesson_time="0"
                   data-additional_service_notice=""
                   data-admin_notice=""
                   data-modal-id="new-student-modal">
                    <span class="new-object-link__icon-wrap">
                        <svg class="svg-icon--plus svg-icon" width="13" height="13">
                            <use xlink:href="#plus"></use>
                        </svg>
                    </span>
                    <span class="new-object-link__text">Добавить ученика</span>
                </a>
            </div>
        </div>

        <!-- introduce-list -->
        <?=
        ListView::widget([
            'dataProvider' => $dataProviderIntroduce,
            //'itemOptions' => ['class' => 'item'],
            'itemOptions' => [
                'tag' => false,
                'class' => '',
            ],
            'summary' => 'Страница <b>{page, number}</b>. Показаны записи с <b>{begin, number}</b> по <b>{end, number}</b> из <b>{totalCount, number}</b>.',
            'layout' => '

                        <div class="info-sowed-num-off-num">

                            {summary}

                        </div>

                        <div class="leads-list">

                            {items}

                        </div>

                        <div class="pages">

                            {pager}

                        </div>

                ',
            'emptyText' => '<div class="presets-empty">' . ($modelIntroduceSearch->period ? 'По заданному поиску нет результатов' : 'Нет предстоящих вводных уроков' ) . '</div>',
            'emptyTextOptions' => ['tag' => false],
            'itemView' => function ($model, $key, $index, $widget) use ($CurrentUser) {

                /** @var $model \frontend\models\search\StudentsListSearch */

                $Methodist = $model->getMethodistForThisUser();

                /* return html */
                return '
                    <!--.introduce-item-->
                    <div class="lead-item leads-list__item">
                        <div class="lead-item__cell">
                            <div class="user-block user-block--sm user-block--top">
                                <div class="user-block__ava">
                                    <img src="' . $model->getProfilePhotoForWeb('/assets/smart-min/images/no_photo.png') . '" alt="" />
                                </div>
                                <div class="user-block__data">
                                    <div class="user-block__position">Ученик (id: ' . $model->user_id . ')</div>
                                    <div class="user-block__name">
                                        <a class="user-block__name-link js-open-modal-user-info void-0"
                                           href="#"
                                           data-user-id="' . $model->user_id . '"
                                           data-modal-id="student-info-modal">' . $model->user_full_name . '</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Телефон</div>
                                <div class="item__value">' . $model->user_phone . '</div>
                            </div>
                        </div>

                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Дата вводного урока</div>
                                <div class="item__value">' . $CurrentUser->getDateInUserTimezoneByTimestamp($model->timeline_timestamp) . '</div>
                            </div>
                        </div>

                        <div class="lead-item__cell">
                            <div class="user-block user-block--sm user-block--top">
                                <div class="user-block__ava">
                                    <img src="' . $Methodist->getProfilePhotoForWeb('/assets/smart-min/images/no_photo.png') . '" alt="" />
                                </div>
                                <div class="user-block__data">
                                    <div class="user-block__position">Методист (id: ' . $Methodist->user_id . ')</div>
                                    <div class="user-block__name">
                                        <a class="user-block__name-link js-open-modal-user-info void-0"
                                           href="#"
                                           data-user-id="' . $Methodist->user_id . '"
                                           data-modal-id="methodist-info-modal">' . $Methodist->user_full_name . '</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lead-item__cell">
                            <div class="item">
                                <div class="item__label">Телефон</div>
                                <div class="item__value">
                                    ' . $Methodist->user_phone . '
                                </div>
                            </div>
                        </div>


                    </div>

                    <!--.introduce-item-->
                ';
            },
        ]);
        ?>
        <!-- introduce list -->

        <!--
        <div class="dashboard__section-controls">
            <div class="totals">
                <div class="total-number">
                    <div class="total-number__label">Подвисшие за 7 дней:</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">3</a></div>
                </div>
                <div class="total-number">
                    <div class="total-number__label">Мертвые (более 7 дней):</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">12</a></div>
                </div>
            </div>
        </div>
        -->
    <?php Pjax::end(); ?>


    <!-- прошедшие вводные уроки -->
    <?php Pjax::begin([
        'id' => 'students-introduce-lessons-finished',
        'timeout' => PJAX_TIMEOUT,
        'options'=> ['tag' => 'div', 'class' => 'dashboard__section']
    ]); ?>
        <div class="dashboard__section-title title">
            <div>Прошедшие вводные уроки</div>
            <div class="dashboard__section-controls">
                <div class="totals">
                    <div class="total-number">
                        <div class="total-number__label">Покказать за: </div>
                        &nbsp;&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'StudentsListSearch[period]' => date('Y-m-d', time()) . ' - ' . date('Y-m-d', time())
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">сегодня</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to([
                                'index',
                                'StudentsListSearch[period]' => date('Y-m-d', strtotime('-7 days')) . ' - ' . date('Y-m-d', time())
                            ], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">7 дней</a>
                        </div>
                        &nbsp;|&nbsp;
                        <div class="total-number__value">
                            <a href="<?= Url::to(['index'], CREATE_ABSOLUTE_URL) ?>"
                               data-pjax="1">все</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- introduce-finished-list -->
        <?=
        ListView::widget([
            'dataProvider' => $dataProviderIntroduceFinished,
            //'itemOptions' => ['class' => 'item'],
            'itemOptions' => [
                'tag' => false,
                'class' => '',
            ],
            'summary' => 'Страница <b>{page, number}</b>. Показаны записи с <b>{begin, number}</b> по <b>{end, number}</b> из <b>{totalCount, number}</b>.',
            'layout' => '

                            <div class="info-sowed-num-off-num">

                                {summary}

                            </div>

                            <div class="leads-list">

                                {items}

                            </div>

                            <div class="pages">

                                {pager}

                            </div>

                    ',
            'emptyText' => '<div class="presets-empty">' . ($modelIntroduceSearch->period ? 'По заданному поиску нет результатов' : 'Нет предстоящих вводных уроков' ) . '</div>',
            'emptyTextOptions' => ['tag' => false],
            'itemView' => function ($model, $key, $index, $widget) use ($CurrentUser) {

                /** @var $model \frontend\models\search\StudentsListSearch */

                $Methodist = $model->getMethodistForThisUser();

                if ($model->lesson_status == MethodistTimeline::STATUS_FAILED) {
                    $class_status = 'state-canceled';
                    $text_status = 'Урок не состоялся';
                } else {
                    $class_status = 'state';
                    $text_status = 'Урок состоялся';
                }

                /* return html */
                return '
                        <!--.introduce-item-->
                        <div class="lead-item leads-list__item">
                            <div class="lead-item__cell">
                                <div class="user-block user-block--sm user-block--top">
                                    <div class="user-block__ava">
                                        <img src="' . $model->getProfilePhotoForWeb('/assets/smart-min/images/no_photo.png') . '" alt="" />
                                    </div>
                                    <div class="user-block__data">
                                        <div class="user-block__position">Ученик (id: ' . $model->user_id . ')</div>
                                        <div class="user-block__name">
                                            <a class="user-block__name-link js-open-modal-user-info void-0"
                                               href="#"
                                               data-user-id="' . $model->user_id . '"
                                               data-modal-id="student-info-modal">' . $model->user_full_name . '</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lead-item__cell">
                                <div class="item">
                                    <div class="item__label">Телефон</div>
                                    <div class="item__value">' . $model->user_phone . '</div>
                                </div>
                            </div>

                            <div class="lead-item__cell" style="grid-area: unset;">
                                <div class="item">
                                    <div class="item__label">Дата вводного урока</div>
                                    <div class="item__value">' . $CurrentUser->getDateInUserTimezoneByTimestamp($model->timeline_timestamp) . '</div>
                                </div>
                            </div>

                            <div class="lead-item__cell">
                                <div class="user-block user-block--sm user-block--top">
                                    <div class="user-block__ava">
                                        <img src="' . $Methodist->getProfilePhotoForWeb('/assets/smart-min/images/no_photo.png') . '" alt="" />
                                    </div>
                                    <div class="user-block__data">
                                        <div class="user-block__position">Методист (id: ' . $Methodist->user_id . ')</div>
                                        <div class="user-block__name">
                                            <a class="user-block__name-link js-open-modal-user-info void-0"
                                               href="#"
                                               data-user-id="' . $Methodist->user_id . '"
                                               data-modal-id="methodist-info-modal">' . $Methodist->user_full_name . '</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="lead-item__cell">
                                <div class="item">
                                    <div class="item__label">Телефон</div>
                                    <div class="item__value">
                                        ' . $Methodist->user_phone . '
                                    </div>
                                </div>
                            </div>

                            <div class="lead-item__cell" style="grid-area: unset;">
                                <div class="item">
                                    <div class="item__label">Статус урока</div>
                                    <div class="item__value ' . $class_status . '">
                                        <a class="full-comment-link light-link js-open-modal js-full-comment-link void-0"
                                           href="#"
                                           data-comment-title="Комментарии к уроку"
                                           data-comment-full-text="' . $model->lesson_notice . '"
                                           data-modal-id="comment-text-modal">' . $text_status . '</a>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <!--.introduce-item-->
                    ';
            },
        ]);
        ?>
        <!-- introduce-finished list -->

        <!--
        <div class="dashboard__section-controls">
            <div class="totals">
                <div class="total-number">
                    <div class="total-number__label">Подвисшие за 7 дней:</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">3</a></div>
                </div>
                <div class="total-number">
                    <div class="total-number__label">Мертвые (более 7 дней):</div>
                    <div class="total-number__value"><a href="javascript:;" data-modal-id="lead-modal" class="js-open-modal">12</a></div>
                </div>
            </div>
        </div>
        -->
    <?php Pjax::end(); ?>


    <!-- статистика -->
    <div class="dashboard__section">
        <div class="dashboard__section-title title">Статистика по всей системе</div>
        <div class="win win--grey">
            <div class="dashboard__top win__top"></div>
            <div class="dashboard__inner">
                <div class="stat-grid">
                    <div class="stat-grid__section stat-grid__section--wide">
                        <div class="stat-item stat-item--lg">
                            <div class="stat-item__num">
                                <a href="#"
                                   data-modal-id="users-modal"
                                   class="-js-open-modal void-0">{Num}</a>
                            </div>
                            <div class="stat-item__desc">Обработано всего заявок <br>(лиды + телефонные лиды)</div>
                        </div>
                    </div>
                    <div class="stat-grid__section">
                        <div class="stat-item">
                            <div class="stat-item__num">
                                <a href="#"
                                   data-modal-id="users-modal"
                                   class="-js-open-modal highlight-c2 void-0">{Num}</a>
                            </div>
                            <div class="stat-item__desc">Вводных уроков <br>успешно проведено</div>
                        </div>
                    </div>
                    <div class="stat-grid__section">
                        <div class="stat-item">
                            <div class="stat-item__num">
                                <a href="#"
                                   data-modal-id="users-modal"
                                   class="-js-open-modal highlight-c1 void-0">{Num}</a>
                            </div>
                            <div class="stat-item__desc">Вводных уроков <br>не проведено</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?= $this->render("../modals/admin-modals/request-info-modal") ?>
<?= $this->render("../modals/admin-modals/student-info-modal") ?>
<?= $this->render("../modals/admin-modals/methodist-info-modal") ?>
<?= $this->render("../modals/admin-modals/operator-info-modal") ?>
<?= $this->render("../modals/admin-modals/operators-list-modal", [
    'listOperators'  => $listOperators
]) ?>
<?= $this->render("../modals/admin-modals/new-student-modal", [
    'newStudentForm'  => $newStudentForm,
    'listOperators'  => $listOperators,
]) ?>