<?php
return [
    'date_format' => "d/m/Y",
    'date_short_format' => "d/m",
    'datetime_format' => "d/m/Y, H:i:s",
    'datetime_short_format' => "d/m/Y, H:i",
    'datetime_fancy_format' => "\$1, H:i",
    'time_format' => "H:i:s",
    'time_short_format' => "H:i",

    'user.passwordResetTokenExpire' => 3600,

    'profileDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'profileImg',

    'profileDirWeb' => "/assets/smart-min/profile",

    'videoUploadsDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'videoUploads',

    'homeWorkUploadsDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'homeWorkUploads',

    'homeWorkDirWeb' => "/homeWorkUploads",

    'presetsUploadsDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'presetsUploads',

    'presetsDirWeb' => "/assets/smart-min/presets",

    'jsConsoleLogDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'jsConsoleLogDir',

    'indexRequestSaveDir' => str_replace('/', DIRECTORY_SEPARATOR, Yii::getAlias('@frontend'))
        . DIRECTORY_SEPARATOR
        . 'runtime'
        . DIRECTORY_SEPARATOR
        . 'indexRequestSaveDir',
];
