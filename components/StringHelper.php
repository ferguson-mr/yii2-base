<?php
/**
 * +------------------------------------------------------------------------
 * | Copyright (c) 2016, 梦落芳华
 * +------------------------------------------------------------------------
 * | Author : Ferguson <Ferguson.Mr.F@gmail.com>
 * +------------------------------------------------------------------------
 * | Time   : 2016-06-30 15:21
 * +------------------------------------------------------------------------
 */

namespace ferguson\base\components;

class StringHelper extends \yii\helpers\StringHelper
{
    public static function generateRandomString($length = 32, $lower = false, $replace = null)
    {
        $string = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $string .= $strPol[rand(0, $max)];
        }

        if ($lower) {
            $string = strtolower($string);
        }

        if ($replace !== null) {
            $_replace = [
                '-' => '',
                '_' => ''
            ];
            $replace = \yii\helpers\ArrayHelper::merge($_replace, $replace);
            $string = strtr($string, $replace);
        }

        return $string;
    }

    /**
     *
     * @param int $length max length 64
     * @return mixed
     */
    public static function generateRandomGuid($length = 32)
    {
        return substr(md5(uniqid()) . md5(time()), 0, $length > 64 ? 64 : $length);
    }

    public static function generateFileName()
    {
        return time() . mt_rand(10000, 99999);
    }
}
