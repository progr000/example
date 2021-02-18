$(document).ready(function() {

    /**/
    $(document).on('click', '.js-send-student-request', function () {

        let $this_button = $(this);
        let $this_button_text = $this_button.find('span').first();

        let $form = $(`#${$(this).data('form-id')}`);

        $form.yiiActiveForm('data').submitting = true;
        $form.yiiActiveForm('validate');
        //$form.yiiActiveForm;



        window.setTimeout(function () {
            //$form.yiiActiveForm('validate');
            if ($form.find('.has-error').length) {
                flash_msg('Форма не заполнена до конца или имеются ошибки в полях', 'error', FLASH_TIMEOUT);
                //$this_button.removeClass('in-progress');
                //$this_button_text.html($this_button.data('ready-to-send'));
                return false;
            }

            $this_button.addClass('in-progress');
            $this_button_text.html($this_button.data('sent-in-progress'));

            let inp_obj = {};
            $form.find('.js-request-inputs').each(function () {
                let $el = $(this);
                inp_obj[$el.attr('name')] = $el.val();
                if ($(this).hasClass('js-request-select')) {
                    inp_obj[$el.attr('name') + '_text'] = $(this).find('option:selected').first().text();
                }
            });

            console.log('student-request::inp_obj=', inp_obj);
            $.ajax({
                type: 'post',
                url: '/site/save-index-request-form',
                data: inp_obj,
                dataType: 'json'
            }).done(function (response) {

                if ("status" in response && response.status) {

                    $('#request-form-sent-link')[0].click();
                    $form.find('.js-request-inputs').each(function () {
                        if ($(this).attr('type') != 'hidden') {
                            $(this).val('');
                        }
                    });

                } else {
                    console.log(response);
                    prettyAlert('An internal server error occurred.');
                }
                $this_button.removeClass('in-progress');
                $this_button_text.html($this_button.data('ready-to-send'));

            }).fail(function (response) {
                $this_button.removeClass('in-progress');
                $this_button_text.html($this_button.data('ready-to-send'));
            });

        }, 1000);

    });

})