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

namespace ferguson\based\components;


class StringHelper extends \yii\helpers\StringHelper
{
    public static function generateRandomString($length = 32)
    {
        $str = null;
        $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
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
