<?php
/**
 * +------------------------------------------------------------------------
 * | Copyright (c) 2017, 梦落芳华
 * +------------------------------------------------------------------------
 * | Author : Ferguson <Ferguson.Mr.F@gmail.com>
 * +------------------------------------------------------------------------
 * | Time   : 2017-02-28 17:49
 * +------------------------------------------------------------------------
 */

namespace ferguson\base\behaviors;

use \yii\base\Behavior;
use \yii\db\BaseActiveRecord;
use ferguson\base\components\StringHelper;

class GenerateStringBehavior extends Behavior
{
    public $attributes = [];

    public function events()
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }

    public function beforeInsert($event)
    {
        foreach ((array) $this->attributes as $attribute => $length)
        {
            $this->owner->{$attribute} = StringHelper::generateRandomString($length, true, []);
        }
    }
}