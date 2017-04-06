<?php

namespace ferguson\base;


class AssetBundle extends \yii\web\AssetBundle
{
    const EMPTY_ASSET = 'NO/@##$$';

    const EMPTY_PATH = 'NO/QF$$';

    const BASE_ASSET = 'BA/@##$$';

    const BASE_PATH = 'BA/QF$$';

    public $js = self::BASE_ASSET;

    public $css = self::BASE_ASSET;

    public $sourcePath = self::BASE_PATH;

    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    public function init()
    {
        parent::init();
        if ($this->js === self::BASE_ASSET) {
            $this->js = [];
        }
        if ($this->css === self::BASE_ASSET) {
            $this->css = [];
        }
        if ($this->sourcePath === self::BASE_PATH) {
            $this->sourcePath = null;
        }
    }

    /**
     * Set up CSS and JS asset arrays based on the base-file names
     *
     * @param string $type whether 'css' or 'js'
     * @param array $files the list of 'css' or 'js' basefile names
     */
    protected function setupAssets($type, $files = [])
    {
        if ($this->$type === self::BASE_ASSET) {
            $srcFiles = [];
            $minFiles = [];
            foreach ($files as $file) {
                $srcFiles[] = "{$file}.{$type}";
                $minFiles[] = "{$file}.min.{$type}";
            }
            $this->$type = YII_DEBUG ? $srcFiles : $minFiles;
        } elseif ($this->$type === self::EMPTY_ASSET) {
            $this->$type = $files;
        }
    }

    /**
     * Sets the source path if empty
     *
     * @param string $path the path to be set
     */
    protected function setSourcePath($path)
    {
        if ($this->sourcePath === self::BASE_PATH) {
            $this->sourcePath = $path;
        } elseif ($this->sourcePath === self::EMPTY_PATH) {
            $this->sourcePath = null;
        }
    }
}