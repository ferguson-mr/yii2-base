<?php

namespace ferguson\base\widgets\emailCaptcha;

use Yii;
use yii\base\Action;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use ferguson\base\components\ArrayHelper;

class EmailCaptchaAction extends Action
{

    /**
     * @var integer how many times should the phone CAPTCHA be sent daily.
     * [times, seconds]
     */
    public $phoneFrequency = 6;

    /**
     * @var integer the minimum length for randomly generated number. Defaults to 6.
     */
    public $length = 6;

    /**
     * @var integer
     */
    public $expire = 900;

    /**
     * @var ActiveForm
     */
    public $form;

    protected $email;

    protected $frequency = 60;

    /**
     * Initializes the action.
     */
    public function init()
    {
        if (!isset(Yii::$app->params['user.emailCaptchaExpire'])) {
            Yii::$app->params['user.emailCaptchaExpire'] = $this->expire;
        }

        $this->expire = Yii::$app->params['user.emailCaptchaExpire'];

        $data = Yii::$app->request->post(Yii::$app->request->get('formName', ''), []);
        $this->email = ArrayHelper::getValue($data, 'email');

    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    /**
     * Runs the action.
     */
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        if (!$this->validateCsrfToken()) {
            throw new BadRequestHttpException(Yii::t('yii', 'Unable to verify your data submission.'));
        }
        if (Yii::$app->request->isAjax && $this->validateParams()) {
            $this->initSession();
            $code = $this->getVerifyCode();
            $remaining = $this->getRemainingRefreshTime(true);

            return [
//                 'code' => $code,
                'refreshTime' => $remaining,
                'hash1' => $this->generateValidationHash($code),
                'hash2' => $this->generateValidationHash($code),
            ];
        } else {
            return [];
        }
    }

    public function initSession()
    {
        $session = Yii::$app->getSession();
        $session->open();
        $codeKey = $this->getSessionKey();
        $freshTimeKey = $this->getFreshTimeKey();

        if ($session[$codeKey]
            && $session[$freshTimeKey]
            && (time() - $session[$freshTimeKey]) < $this->frequency
        ) {
            // $session[$freshTimeKey] = time();
        } else {
            $this->generateVerifyCode();
        }
    }

    /**
     * Gets the verification code.
     * @return string the verification code.
     */
    public function getVerifyCode()
    {
        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getSessionKey();
        return $session[$name];
    }

    /**
     * Validates the input to see if it matches the generated code.
     * @param string $input user input
     * @return boolean whether the input is valid
     */
    public function validate($input)
    {
        $code = $this->getVerifyCode();
        $valid = ($input === $code);

        return $valid;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        $refreshTime = $this->getRefreshTime();

        return $refreshTime + $this->expire < time();
    }

    public function getRefreshTime()
    {
        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getFreshTimeKey();
        return $session[$name];
    }

    /**
     * @param bool $reset
     * @return int
     */
    public function getRemainingRefreshTime($reset = true)
    {
        $refreshTime = $this->getRefreshTime();
        $remaining = $this->frequency - (time() - $refreshTime);
        if ($remaining < 0 && $reset) {
            $remaining = $this->frequency;
        }

        return $remaining;
    }

    protected function validateParams()
    {
        return $this->email !== null;
    }

    protected function validateLimits()
    {
        $this->initSession();
    }

    /**
     * Returns the session variable name used to store verification code.
     * @return string the session variable name
     */
    protected function getSessionKey()
    {
        return '__email_captcha/' . $this->getUniqueId() . '_' . $this->email;
    }

    protected function getFreshTimeKey()
    {
        return $this->getSessionKey() . '_time';
    }

    /**
     * Generates a hash code that can be used for client side validation.
     * @param string $code the CAPTCHA code
     * @return string a hash code generated from the CAPTCHA code
     */
    public function generateValidationHash($code)
    {
        $code = (string)$code;
        for ($h = 0, $i = strlen($code) - 1; $i >= 0; --$i) {
            $h += ord($code[$i]);
        }

        return $h;
    }

    /**
     * Generates a new verification code.
     * @return string the generated verification code
     */
    protected function generateVerifyCode()
    {
        $length = $this->length;
        $min = pow(10, $length - 1);
        $max = pow(10, $length);
        $code = mt_rand($min, $max);
        //$code = date('dmH');
        $session = Yii::$app->getSession();
        $session->open();
        $name = $this->getSessionKey();
        $freshKey = $this->getFreshTimeKey();

        $counts = 1;
        if ($counts < $this->phoneFrequency) {
            Yii::$app->mailer->compose(
                ['html' => 'signupEmailToken-html', 'text' => 'signupEmailToken-text'],
                [
                    'code' => $code,
                    'email' => $this->email
                ]
            )->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
                ->setTo($this->email)
                ->setSubject(Yii::t('app','Verification code'))
                ->send();
        }

        $session[$name] = (string)$code;
        $session[$freshKey] = time();
        return $session[$name];
    }

    protected function validateCsrfToken()
    {
        $session = Yii::$app->session;
        $session->open();

        $csrf = Yii::$app->request->post(Yii::$app->request->csrfParam);
        if (!$csrf) {
            return false;
        }
        $key = '_csrfCount_' . md5($csrf);
        $csrfCount = $session->get($key, 0);
        $csrfCount++;
        $session->set($key, $csrfCount);

        if ($csrfCount > 3) {
            $csrf = Yii::$app->request->getCsrfToken(true);
        }
        return Yii::$app->getRequest()->validateCsrfToken($csrf);
    }
}
