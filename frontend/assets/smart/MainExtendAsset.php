<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace frontend\assets\smart;

use Yii;
use yii\web\AssetBundle;
use common\helpers\FileSys;
use common\helpers\Functions;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class MainExtendAsset extends AssetBundle
{
    const TYPE_CSS = 'css';
    const TYPE_JS  = 'js';

    /*
     * если нужно что бы все скрипты (css и js) копировались
     * в папку web/assets тогда рскоментировать эту строку ($sourcePath)
     * и закоментировать две следующие ($basePath и $baseUrl)
     * и так же заменить пути для js и css (убрать из них часть пути themes/smart/)
     * аналогичные действия можно проделать тогда во всех ассетсах этого дизайна
     * но пока в этом не видно особого смысла, лишь увеличение нагрузки
     */

    //public $sourcePath = '@frontend/web/themes/smart/';
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $fullPathToSource       = '@frontend/web/';
    public $fullPathToMinimizedCss = '@frontend/web/assets/smart-min/css';
    public $webPathToMinimizedCss  = 'assets/smart-min/css/';
    public $fullPathToMinimizedJs  = '@frontend/web/assets/smart-min/js';
    public $webPathToMinimizedJs   = 'assets/smart-min/js/';
    public $pathToLinkMediaSources = '@frontend/web/assets/smart-min';
    public $mediaSourcesForLink = [
        '@frontend/web/themes/smart/files',
        '@frontend/web/themes/smart/fonts',
        '@frontend/web/themes/smart/icons',
        '@frontend/web/themes/smart/images',
        '@frontend/web/themes/smart/sounds',
        '@frontend/web/themes/smart/videos',
        '@frontend/web/themes/smart/icons',
    ];

    public $jsOptions = [
        'defer' => true,
    ];

    protected $useMinimized_css;
    protected $useMinimized_js;


    /**
     * @param string $file_web
     * @param string $type
     * @param string|null $sub_folder
     * @param boolean $do_not_minimize
     * @return string
     */
    protected function compressFile($file_web, $type=self::TYPE_CSS, $sub_folder=null, $do_not_minimize=false)
    {
        /* подготовка путей в зависимости от типа файла (css|js) */
        if ($type == self::TYPE_CSS) {
            $webPathToMinimized  = $this->webPathToMinimizedCss;
            $fullPathToMinimized = $this->fullPathToMinimizedCss;
        } else {
            $webPathToMinimized  = $this->webPathToMinimizedJs;
            $fullPathToMinimized = $this->fullPathToMinimizedJs;
        }

        /* доаботка путей, если передан параметр $sub_folder */
        if (isset($sub_folder)) {
            $webPathToMinimized  .= "{$sub_folder}/";
            $fullPathToMinimized .= "/{$sub_folder}";
        }

        /* подготовка путей */
        $file_path = str_replace('@frontend', Yii::getAlias('@frontend'), $this->fullPathToSource . $file_web);
        $tmp = FileSys::pathinfo($file_web);
        $file_web_min = $webPathToMinimized . $tmp['filename'] . '.minimized.' . $tmp['extension'];
        $file_path_min = str_replace('@frontend', Yii::getAlias('@frontend'), $fullPathToMinimized . DIRECTORY_SEPARATOR . $tmp['filename'] . '.minimized.' . $tmp['extension']);

        /* для отладки файл будет пересоздаваться каждый раз если откоментировать*/
        //@unlink($file_path_min);

        /* если сжатие не удалось из за отсутствия файла */
        if (!file_exists($file_path)) {
            return null;
        }

        /* если файл уже минифицирован ранее и не было изменений после этого в оригинальном файле, то отдаем минифицированый ранее */
        if (file_exists($file_path_min) && filesize($file_path_min)) {
            $orig_mtime = filemtime($file_path);
            $min_mtime  = filemtime($file_path_min);
            //$min_ctime  = filectime($file_path_min);
            if ($min_mtime > $orig_mtime) {
                return $file_web_min;
            }
        }

        /* Создаем дирректорию для минифицированых файлов */
        $directory_to_write = dirname($file_path_min);
        if (!file_exists($directory_to_write)) {
            FileSys::mkdir($directory_to_write, 0777, true);
        }

        /* если в параметрах системы указано что не используем минификцированнные css, то вернем как есть */
        if (!$this->useMinimized_css && $type == self::TYPE_CSS) {
            //return $file_web;
            if ($this->onlyMoveCompressed($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* если в параметрах системы указано что не используем минификцированнные js, то вернем как есть */
        if (!$this->useMinimized_js && $type == self::TYPE_JS) {
            //return $file_web;
            if ($this->onlyMoveCompressed($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* если какой то конкретный файл не нужно минифицировать, то можно установить для него $do_not_minimize=true */
        if ($do_not_minimize) {
            //return $file_web;
            if ($this->onlyMoveCompressed($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* если передается файл который уже предположительно минифицирован сторонними сервисами, то ну его нахер, отдаем как есть */
        if (strrpos($file_web, '.min.') !== false) {
            if ($this->onlyMoveCompressed($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* Сжимаем СSS */
        if ($type == self::TYPE_CSS) {
            if (Functions::compressCss($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* Сжимаем JS */
        if ($type == self::TYPE_JS) {
            if (Functions::compressJs($file_path, $file_path_min)) {
                return $file_web_min;
            }
        }

        /* Если сжатие НЕ удалось, возвращаем неминифицированный */
        return $file_web;
    }

    /**
     * @param string $file_path
     * @param string $file_path_min
     * @return bool
     */
    protected function onlyMoveCompressed($file_path, $file_path_min)
    {
        return @copy($file_path, $file_path_min);
    }

    /**
     *
     */
    protected function makeMediaLinks()
    {
        $pathToLinkMediaSources = str_replace('@frontend', Yii::getAlias('@frontend'), $this->pathToLinkMediaSources);
        if (!file_exists($pathToLinkMediaSources)) {
            FileSys::mkdir($pathToLinkMediaSources, 0777, true);
        }

        foreach ($this->mediaSourcesForLink as $v) {
            $baseName = basename($v);
            if (!file_exists($pathToLinkMediaSources . DIRECTORY_SEPARATOR . $baseName)) {
                $target = str_replace('@frontend', Yii::getAlias('@frontend'), $v);
                if (file_exists($target)) {
                    symlink($target, $pathToLinkMediaSources . DIRECTORY_SEPARATOR . $baseName);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        //$this->jsOptions['defer'] = !(Yii::$app->controller->id == 'conferences');

        $this->makeMediaLinks();
        $this->useMinimized_css = (isset(Yii::$app->params['use_minimized_css']) && Yii::$app->params['use_minimized_css']);
        $this->useMinimized_js  = (isset(Yii::$app->params['use_minimized_js'])  && Yii::$app->params['use_minimized_js']);
    }
}
