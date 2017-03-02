<?php

namespace ferguson\base\widgets\emailCaptcha;

use yii\web\AssetBundle;

class EmailCaptchaAsset extends AssetBundle
{
    public $sourcePath = '@ferguson/base/widgets/emailCaptcha/assets';
    public $js = [
        'jquery.email-captcha.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}