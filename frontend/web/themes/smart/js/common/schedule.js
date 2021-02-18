let total_selected_count_lesson = 0;
let user_schedule;

/**
 *
 */
function getSchedule()
{
    $.ajax({
        type: 'get',
        url: '/user/get-schedule',
        dataType: 'json'
    }).done(function (response) {
        if ("data" in response && "status" in response && response.status) {

            user_schedule = response.data;
            //console.log(user_schedule);
            for (let i = 0; i < user_schedule.length; i++) {
                //console.log(user_schedule[i]);
                for (let j = 0; j < user_schedule[i].length; j++) {
                    total_selected_count_lesson += (user_schedule[i][j].status > 0) ? 1 : 0;
                    let $ch = $(`#time-${j}-${i}`);
                    if ($ch.length) {
                        if (user_schedule[i][j].status > 0) {
                            $(`#day-${i}`).addClass('_active');
                            //console.log(`#time-${j}-${i}`);
                        } else {
                            //$(`#day-${i}`).removeClass('_active');
                        }
                        //console.log(`${i} -- ${j} == ${user_schedule[i][j]}`);
                        //console.log(`#time-${j}-${i}`);
                        //console.log($(`#time-${j}-${i}`).length);
                        $ch[0].checked = (user_schedule[i][j].status > 0);
                        if (user_schedule[i][j].status == 2) {
                            $ch.addClass('_active');
                            $ch.attr('data-students', user_schedule[i][j].users)
                        }
                    }
                }
            }

            /**/
            //if (typeof getCountLessonsLeftToDistribute == 'function') {
            //    getCountLessonsLeftToDistribute(null)
            //}


        } else {
            console.log(response);
            prettyAlert('An internal server error occurred.');
        }
    });
}

/**
 * @param {object} $obj
 */
function changeWorkHour($obj)
{
    //console.log($obj[0].checked); return false;
    //if (typeof MAX_LESSONS_FOR_WEEK !== 'undefined') {
    //    if (total_selected_count_lesson >= MAX_LESSONS_FOR_WEEK) {
    //        prettyAlert('Нельзя выбрать больше ' + MAX_LESSONS_FOR_WEEK + ' занятий');
    //        return false;
    //    }
    //}

    /**/
    let hour_status;
    let work_hour = $obj.data('time');
    let week_day  = $obj.data('day');
    if ($obj[0].checked) {
        hour_status = 1;
    } else {
        hour_status = 0;
    }

    let data = {
        week_day: week_day,
        work_hour: work_hour,
        hour_status: hour_status
    };
    if ((typeof $date_start !== 'undefined') && $date_start.length) {
        data.date_start = $date_start.val();
    }
    setPendingData(
        `Обработка запроса...`,
        'working',
        10000
    );
    $.ajax({
        type: 'get',
        url: '/user/change-schedule',
        data: data,
        dataType: 'json'
    }).done(function (response) {
        if ("data" in response && "status" in response && response.status) {

            if (response.data.changed) {
                if (hour_status == 1) {
                    //$obj.addClass('active');
                    user_schedule[week_day][work_hour].status = 1;
                    total_selected_count_lesson++;
                    setPendingData(
                        `Вы успешно добавили новое время в свое расписание`,
                        'success',
                        10000
                    );
                } else {
                    //$obj.removeClass('active');
                    user_schedule[week_day][work_hour].status = 0;
                    total_selected_count_lesson--
                    setPendingData(
                        `Вы успешно отменили время в своем расписании`,
                        'success',
                        10000
                    );
                }
            } else {
                $obj[0].checked = !$obj[0].checked;
                setPendingData(
                    response.data.info,
                    'danger',
                    10000
                );
            }

            /**/
            try {
                $.pjax.reload({container: "#dashboard-schedule-list", async: false});
                $.pjax.reload({container: "#popup-dashboard-schedule-list", async: false});
            } catch (e) {
                console.log('info:: Skipped. Not found pjax container for reload.')
            }

        } else {
            console.log(response);
            prettyAlert('An internal server error occurred.');
        }

        let total_check = 0;
        for (let j = 0; j < user_schedule[week_day].length; j++) {
            total_check += (user_schedule[week_day][j].status > 0) ? 1 : 0;
        }
        if (total_check > 0) {
            $(`#day-${week_day}`).addClass('_active');
        } else {
            $(`#day-${week_day}`).removeClass('_active');
        }

    });
}

/**
 * @param text
 * @param class_name
 * @param timeout
 */
let pnd_tmt = null;
function setPendingData(text, class_name='success', timeout=0)
{
    let $pending = $('.time-pending').first();
    $pending.html(text);
    $pending
        .removeClass()
        .addClass('time-pending')
        .addClass(class_name);
    $pending.show();
    if (timeout) {
        clearTimeout(pnd_tmt);
        pnd_tmt = setTimeout(function () {
            $pending
                .removeClass()
                .addClass('time-pending')
                .hide();
        }, timeout);
    }
}
