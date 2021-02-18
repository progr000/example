let IS_GUEST = true;
let USER_TYPE = 4;
let USER_TYPES = {};

/**
 *
 */
function isSafari()
{
    return (navigator.userAgent.search("Safari") >= 0 && navigator.userAgent.search("Chrome") < 0);
}

/**
 *
 */
function hideSiteLoader()
{
    var $preloader = $('#site-loader-div');
    $preloader.delay(500).fadeOut('slow', function() {
        document.body.classList.remove('loaded');
    });
    if (!$preloader.length) {
        document.body.classList.remove('loaded');
    }
    setTimeout(function() {
        //document.body.classList.remove('loaded');
    }, 300);
}

/**
 * Pretty-Confirm-Window
 *
 * @param {function|boolean} funct_yes
 * @param {function|boolean} funct_no
 * @param {string} question
 * @param {string} button_yes
 * @param {string} button_no
 * @param {boolean} show_close_x
 */
function prettyConfirm(funct_yes=false, funct_no=false, question="", button_yes="", button_no="", show_close_x=false)
{
    let $pretty_confirm_modal = $('#pretty-confirm-modal');
    /* Устанавливаем текст вопроса для конфирма */
    if (question && typeof question == 'string' && $.trim(question) != '') {
        $('#pretty-confirm-question-text').html(question);
    }

    /* Устанавливаем текст для кнопки НЕТ */
    if ($.trim(button_no) != "") {
        $('#button-confirm-no').val(button_no);
    }

    /* Устанавливаем текст для кнопки ДА */
    if ($.trim(button_yes) != "") {
        $('#button-confirm-yes').val(button_yes);
    }

    /* если show_close_x true тогда показать крестик закрытия окна */
    if (show_close_x) {
        $('#confirm-close-x').show();
    } else {
        $('#confirm-close-x').hide();
    }

    if (typeof funct_yes == 'function') {
        /* Навешиваем событие на нажатие YES */
        $('.button-confirm-yes')
            .off("click")
            .on('click', function() {
                $('#button-confirm-yes').off("click");
                $('#button-confirm-no').off("click");
                $pretty_confirm_modal.css({ 'z-index': 0 });
                funct_yes();
            });
    } else {
        $('.button-confirm-yes')
            .off("click")
            .on('click', function() {
                $('#button-confirm-yes').off("click");
                $('#button-confirm-no').off("click");
                $pretty_confirm_modal.css({ 'z-index': 0 });
            });
    }

    /* Навешиваем событие на нажатие NO */
    if (typeof funct_no == 'function') {
        $('.button-confirm-no')
            .off("click")
            .on('click', function() {
                $('#button-confirm-yes').off("click");
                $('#button-confirm-no').off("click");
                $pretty_confirm_modal.css({ 'z-index': 0 });
                funct_no();
            });
    } else {
        $('.button-confirm-no')
            .off("click")
            .on('click', function() {
                $('#button-confirm-yes').off("click");
                $('#button-confirm-no').off("click");
                $pretty_confirm_modal.css({ 'z-index': 0 });
            });
    }

    /* Показываем попап конфирмации */
    $pretty_confirm_modal.addClass('_opened').css({ 'z-index': 99998 });
}

/**
 * @param {string} text
 * @param {function|boolean} funct_ok
 * @param {boolean} show_close_x
 * @param {string} button_ok_text
 */
function prettyAlert(text, funct_ok=false, show_close_x=true, button_ok_text="Ok")
{
    let $pretty_alert_modal = $('#pretty-alert-modal');
    /* Навешиваем событие на нажатие OK если оно есть */
    if (funct_ok && typeof funct_ok == 'function') {
        $('.button-alert-ok')
            .off("click")
            .on('click', function() {
                //$('#button-confirm-yes').off("click");
                //$('#button-confirm-no').off("click");
                $('#pretty-alert-modal-text').html('');
                $pretty_alert_modal.css({ 'z-index': 0 });
                funct_ok();
            });
    } else {
        $('.button-alert-ok')
            .off("click")
            .on('click', function() {
                //$('#button-confirm-yes').off("click");
                //$('#button-confirm-no').off("click");
                $('#pretty-alert-modal-text').html('')
                $pretty_alert_modal.css({ 'z-index': 0 });
            });
    }

    /* если show_close_x true тогда показать крестик закрытия окна */
    if (show_close_x) {
        $('#pretty-alert-close-x').show();
    } else {
        $('#pretty-alert-close-x').hide();
    }

    /* Устанавливаем текст кнопки OK*/
    $('#pretty-alert-button-ok').html(button_ok_text);

    /* Устанавливаем текст для алерта */
    $('#pretty-alert-modal-text').html(text);

    /* Показываем попап алерта */
    //$("#trigger-pretty-alert-modal").trigger( "click" );
    $pretty_alert_modal.addClass('_opened').css({ 'z-index': 99999 });
}


/**
 *
 */
$(document).ready(function() {

    /* глобально для ошибки аджакс запросов */
    $(document).ajaxError(function (event, xhr, ajaxOptions, thrownError) {
        if (xhr && ("status" in xhr) && ("statusText" in xhr) && ("responseText" in xhr)) {
            switch (xhr.status) {
                case 403:
                    console.log('Forbidden 403. Will be redirected to the main page.');
                    console.log(ajaxOptions);
                    //window.location.href = '/';
                    break;
                case 404:
                    console.log('Not Found 404. Will be redirected to the main page.');
                    console.log(ajaxOptions);
                    //window.location.href = '/';
                    break;
                case 500:
                    console.log(xhr.status);
                    console.log(xhr.statusText);
                    console.log(xhr.responseText);
                    console.log(ajaxOptions);
                    prettyAlert('An internal (500) server error occurred.');
                    break;
                default:
                    console.log(xhr.status);
                    console.log(xhr.statusText);
                    console.log(xhr.responseText);
                    console.log(ajaxOptions);
                    prettyAlert('An unknown (' + xhr.status + ') server error occurred.');
                    break;
            }
        } else {
            console.log(xhr);
            console.log(ajaxOptions);
        }
    });

    /**/
    if (window.location.href.indexOf('#') > 0) {
        let tmp = window.location.href.split('#');
        if (typeof tmp[1] != 'undefined') {
            let $target = $(`#${tmp[1]}`);
            if ($target.length) {
                setTimeout(function() {
                    let destination = $target.offset().top;
                    $('html, body').animate({scrollTop: destination}, 1100);
                }, 1000);
            }
        }
    }

    /**/
    $(document).on('click', '.void-0', function () {
        return false;
    });

    /**/
    $(document).on('click', '.js-alert', function () {
        prettyAlert($(this).data('alert-text'));
    });

    /**/
    if (window.location.href.indexOf('login') > 0) {
        if (IS_GUEST) {
            var $el = $(document).find('.page-header__login').first();
            if ($el.length) {
                $el[0].click();
            }
        }
    }

    /**/
    $(document).on('click', '.js-open-pdf-modal', function () {
        $('#pdf-title').html($(this).data('title'));
        $('#pdf-iframe').attr('src', $(this).data('content'));
    });

    /**/
    $(document).on('click', '.js-close-pdf-modal', function () {
        $('#pdf-title').html('{title}');
        $('#pdf-iframe').attr('src', '');
    });

    /**/
    $(document).on('click', '.js-full-comment-link', function () {
        let $this = $(this);
        let $modal = $(`#${$this.data('modal-id')}`);
        let $receiver_title = $modal.find('.modal__title-receiver').first();
        let $receiver_html = $modal.find('.receiver-container').first();
        $receiver_title.html($this.data('comment-title'));
        $receiver_html.html($this.data('comment-full-text'));
    });

});
