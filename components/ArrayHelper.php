<?php

namespace ferguson\base\components;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    public static function combine(array $key, array $value)
    {
        return array_combine($key, $value);
    }

    public static function mapTree(array $array, $parent = 'parent', $children = 'children', $primary = 'id')
    {
        $array = static::toArray($array);

        $result = [];
        $keys  = static::getColumn($array, $primary);
        $array = static::combine($keys, $array);

        foreach ($array as $item) {
            if (isset($array[$item[$parent]])) {
                $array[$item[$parent]][$children][] = &$array[$item[$primary]];
            } else {
                $result[] = &$array[$item[$primary]];
            }
        }
        return $result;
    }
}