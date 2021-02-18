<?php
defined('APPEND_TIMESTAMP_FOR_CSS_JS') or define('APPEND_TIMESTAMP_FOR_CSS_JS', true);  // исключит кеширование скриптов и стилей, если true

return [
    // Параметр коорый будет подставлен в яваскрипт document.domain = '{$js_document_domain}' нужен для работы с ифреймом
    'js_document_domain' => 'smart.my',
    'jitsi_domain' => 'jitsi.smart.my',

    // Использовать сжатые или несжатые файлы стилей (css) и файлы яваскрипов (js)
    'use_minimized_css' => false,
    'use_minimized_js'  => false,

    'tinkoff_terminal_key'  => "",
    'tinkoff_terminal_pass' => "",
];
