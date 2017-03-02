<?php

namespace ferguson\base\widgets\emailCaptcha;

use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\validators\ValidationAsset;
use yii\validators\Validator;

class EmailCaptchaValidator extends Validator
{
    /**
     * @var boolean whether to skip this validator if the input is empty.
     */
    public $skipOnEmpty = false;
    /**
     * @var string the route of the controller action that renders the CAPTCHA image.
     */
    public $captchaAction = 'site/email-captcha';

    public $emailAttribute = 'email';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', 'The verification code is incorrect.');
        }
    }

    /**
     * Validates a single attribute.
     * Child classes must implement this method to provide the actual validation logic.
     * @param \yii\base\Model $model the data model to be validated
     * @param string $attribute the name of the attribute to be validated.
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $captcha = $this->createCaptchaAction($model);
        $valid = !is_array($value) && $captcha->validate($value);
        $isExpired = $captcha->isExpired();

        $result = $isExpired ? [$this->getMessageForExpiredCaptcha(), []] : ($valid ? null : [$this->message, []]);
//        $result = $this->validateValue($model->$attribute, $model);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
     * Creates the CAPTCHA action object from the route specified by [[captchaAction]].
     * @return PhoneCaptchaAction the action object
     * @throws InvalidConfigException
     */
    public function createCaptchaAction($model)
    {
        $ca = Yii::$app->createController($this->captchaAction);
        if ($ca !== false) {
            /* @var $controller \yii\base\Controller */
            list($controller, $actionID) = $ca;
            $action = $controller->createAction($actionID);
            if ($action !== null) {
                $action->setEmail($model->{$this->emailAttribute});
                return $action;
            }
        }
        throw new InvalidConfigException('Invalid CAPTCHA action ID: ' . $this->captchaAction);
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($object, $attribute, $view)
    {
        $captcha = $this->createCaptchaAction($object);
        $code = $captcha->getVerifyCode();
        $hash = $captcha->generateValidationHash($code);
        $options = [
            'hash' => $hash,
            'hashKey' => 'emailCaptcha/' . $this->captchaAction,
            'message' => Yii::$app->getI18n()->format($this->message, [
                'attribute' => $object->getAttributeLabel($attribute),
            ], Yii::$app->language),
        ];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        ValidationAsset::register($view);

        return 'yii.validation.captcha(value, messages, ' . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }

    protected function getMessageForExpiredCaptcha()
    {
        return Yii::t('app', 'The verification code has expired.');
    }
}