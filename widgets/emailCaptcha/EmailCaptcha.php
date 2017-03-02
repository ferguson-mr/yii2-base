<?php

namespace ferguson\base\widgets\emailCaptcha;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use ferguson\base\components\ArrayHelper;

class EmailCaptcha extends InputWidget
{
    /**
     * @var string|array the route of the action that generates the CAPTCHA images.
     * The action represented by this route must be an action of [[CaptchaAction]].
     * Please refer to [[\yii\helpers\Url::toRoute()]] for acceptable formats.
     */
    public $captchaAction = 'site/email-captcha';
    /**
     * @var array HTML attributes to be applied to the CAPTCHA image tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $actionOptions = [];
    public $actionLabel = 'Get Captcha';
    /**
     * @var string the template for arranging the CAPTCHA image tag and the text input tag.
     * In this template, the token `{image}` will be replaced with the actual image tag,
     * while `{input}` will be replaced with the text input tag.
     */
    public $template = '{input} {action}';
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-control'];

    public $emailAttribute = 'email';
    public $emailId;

    protected $formName;
    /**
     * Initializes the widget.
     */
    public function init()
    {
        parent::init();

        if (!isset($this->actionOptions['id'])) {
            $this->actionOptions['id'] = $this->options['id'] . '-action';
        }
        if (!isset($this->actionOptions['class'])) {
            $this->actionOptions['class'] = 'btn btn-gold btn-sm phone-captcha';
        }

        $this->actionLabel = \Yii::t('app', $this->actionLabel);
    }

    /**
     * Renders the widget.
     */
    public function run()
    {
        if ($this->hasModel()) {
            $input = Html::activeTextInput($this->model, $this->attribute, $this->options);
            $this->emailId = Html::getInputId($this->model, $this->emailAttribute);
            $this->formName = $this->model->formName();
        } else {
            //TODO We should not support this.
            $input = Html::textInput($this->name, $this->value, $this->options);
            $this->emailId = $this->name .'_'. $this->getId;
            $this->formName = '';
        }
        $this->registerClientScript();
        $action = Html::a($this->actionLabel, 'javascript:void(0);', $this->actionOptions);
        echo strtr($this->template, [
            '{input}' => $input,
            '{action}' => $action,
        ]);
    }

    /**
     * Registers the needed JavaScript.
     */
    public function registerClientScript()
    {
        $options = $this->getClientOptions();
        $options = empty($options) ? '' : Json::htmlEncode($options);
        $id = $this->actionOptions['id'];
        $view = $this->getView();
        EmailCaptchaAsset::register($view);
        $view->registerJs("jQuery('#$id').emailCaptcha($options);");
    }

    /**
     * Returns the options for the captcha JS widget.
     * @return array the options
     */
    protected function getClientOptions()
    {
        $route = $this->captchaAction;
        if (is_array($route)) {
        } else {
            $route = [$route];
        }
        $route = ArrayHelper::merge($route, ['formName' => $this->formName]);

        $options = [
            'refreshUrl' => Url::toRoute($route),
            'hashKey' => "emailCaptcha/{$route[0]}",
            'emailId' => $this->emailId
        ];

        return $options;
    }
}
