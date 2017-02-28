<?php
/**
 * +------------------------------------------------------------------------
 * | Copyright (c) 2016, 梦落芳华
 * +------------------------------------------------------------------------
 * | Author : Ferguson <Ferguson.Mr.F@gmail.com>
 * +------------------------------------------------------------------------
 * | Time   : 2016-06-30 11:17
 * +------------------------------------------------------------------------
 */

namespace ferguson\base\behaviors;

use \yii\base\Behavior;
use \yii\web\Controller;

class CsrfBehavior extends Behavior
{

    public $action = [];
    public $controller;

    public function events()
    {
        return [Controller::EVENT_BEFORE_ACTION => 'beforeAction'];
    }

    public function beforeAction($event)
    {
        $action = $event->action->id;
        if (empty($this->action) || in_array('*', $this->action) || in_array($action, $this->action)) {
            $this->controller->enableCsrfValidation = false;
        }
    }
}
