<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace frontend\assets\smart;

use common\helpers\Functions;
use Yii;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class MainCssAsset extends MainExtendAsset
{
    /**
     * Сжать файлы стилей можно тут:
     * https://cssresizer.com/
     * http://refresh-sf.com/  (вроде тут лучше)
     */

    public $css = [
    ];

    public $js = [
    ];

    public $depends = [
    ];

    public $cssOptions = [
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $browser = Functions::getBrowserByUserAgent(Yii::$app->request->userAgent);

        $this->css = [
            $this->compressFile("themes/smart/css/bundle.css", self::TYPE_CSS, null, true),
            $this->compressFile("themes/smart/css/bundle-repair.css", self::TYPE_CSS),
            $this->compressFile("themes/smart/css/bundle-repair-{$browser}.css", self::TYPE_CSS),
        ];
    }
}
