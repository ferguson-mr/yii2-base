/**
 * Yii Captcha widget.
 *
 * This is the JavaScript widget used by the yii\captcha\Captcha widget.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
(function ($) {
    $.fn.emailCaptcha = function (method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on jQuery.emailCaptcha');
            return false;
        }
    };

    var defaults = {
        refreshUrl: undefined,
        hashKey: undefined,
        emailId: undefined
    };
    var refreshTime = 60;
    var refreshTimer = null;

    var methods = {
        init: function (options) {
            return this.each(function () {
                var $e = $(this);
                var $form = $e.parents('form');
                var settings = $.extend({}, defaults, options || {});
                $e.data('emailCaptcha', {
                    settings: settings
                });
                var emailIdString = $e.data('emailCaptcha').settings.emailId;

                $e.on('click.emailCaptcha', function () {
                    $emailAttribute = $form.yiiActiveForm('find', emailIdString);

                    if ($emailAttribute && $emailAttribute.status == 0) {
                        $form.yiiActiveForm('validateAttribute', emailIdString);
                    }
                    if ((!$emailAttribute || $emailAttribute.status == 1 
                        && !$($emailAttribute.container).hasClass('has-error'))
                    ) {
                        methods.send.apply($e);
                    }

                    return false;
                });

            });
        },

        send: function () {
            var $e = this,
                settings = this.data('emailCaptcha').settings;
            var $form = $e.parents('form');
            //TODO: Need a setting???
            // if (!$form.find('input[name$="[verifyCode]"]').val()) {
            //     return false;
            // }
            $e.addClass("disabled");
            $.ajax({
                url: $e.data('emailCaptcha').settings.refreshUrl,
                dataType: 'json',
                data: $form.serialize(),
                method: 'POST',
                //cache: false,
                success: function (data) {
                    refreshTime = data.refreshTime;
                    refreshTimer = setInterval(function(){
                        methods.setInterval.apply($e);
                    }, 1000);
                    $('body').data(settings.hashKey, [data.hash1, data.hash2]);
                },
                statusCode: {
                    400: function () {
                        alert('您提交的数据无法被验证,系统将自动刷新页面。');
                        //window.location.reload();
                    }
                }
            });
        },

        setInterval: function() {
           var $e = this,
               settings = this.data('emailCaptcha').settings;
            if (refreshTime == 0) {
                clearInterval(refreshTimer);
                $e.removeClass('disabled');
                if(document.documentElement.lang === 'en'){
                    $e.html("Resend");
                } else {
                    $e.html("重新获取");
                }
            } else {
                refreshTime--;
                $e.addClass('disabled');
                if(document.documentElement.lang === 'en'){
                    $e.html(refreshTime + "s");
                } else{
                    $e.html(refreshTime + "秒后重新发送");
                }
            }
        },

        destroy: function () {
            return this.each(function () {
                $(window).unbind('.emailCaptcha');
                $(this).removeData('emailCaptcha');
            });
        },

        data: function () {
            return this.data('emailCaptcha');
        }
    };
})(window.jQuery);

