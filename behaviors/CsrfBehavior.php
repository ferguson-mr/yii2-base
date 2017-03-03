<?php

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
