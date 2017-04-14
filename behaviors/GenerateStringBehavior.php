<?php

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
            if(!$this->owner->{$attribute}) {
                $this->owner->{$attribute} = StringHelper::generateRandomString($length, true, []);
            }
        }
    }
}