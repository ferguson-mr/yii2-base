<?php

namespace ferguson\base\components;


class TranslateHelper
{
    public static function words($category, $string)
    {
        $arr = explode(' ', $string);
        $replace = [];
        foreach ($arr as $s) {
            $replace[] = \Yii::t($category, $s);
        }
        return implode(' ', $replace) === $string ? $string : implode('', $replace);
    }
}