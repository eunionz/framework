<?php
/**
 * EUnionZ PHP Framework Aptchacode Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\aptchacode;


defined('APP_IN') or exit('Access Denied');

class Aptchacode extends \cn\eunionz\core\Plugin
{
    public function imageVerify($tmp = 'verifycode')
    {
        // 初始化SESSION
        $SESSION = ctx()->session();
        ob_clean();
        $now_time = time();
//        $limit_time = isset($_SESSION['limt_time']) ? $_SESSION['limt_time'] : $now_time;
//        $APP_LIMIT_GETCODE_TIME = self::getConfig('app','APP_LIMIT_GETCODE_TIME');
//        if($now_time - $limit_time > $APP_LIMIT_GETCODE_TIME || !isset($_SESSION[$tmp]) ){
        $checkcode = rand(1000, 10000);
//        $this->loadCore('log')->log(APP_INFO, 'session存入值:'.$checkcode, 'test123');
        ctx()->session($tmp, $checkcode);
//        $this->loadCore('log')->log(APP_INFO, 'session值'.$tmp.':'.$_SESSION[$tmp], 'test123');
        ctx()->session('limt_time' , $now_time);
//        }else{
//            $checkcode = $_SESSION[$tmp];
//        }

        // 创建验证码图片
        $this->loadComponent('Image')->create(80, 30);
        $this->loadComponent('Image')->drawText(APP_FONT_REAL_PATH . 'simsun.ttc', $checkcode, 5, -1, 20, array(255, 255, 255));
        $this->loadComponent('Image')->drawText(APP_FONT_REAL_PATH . 'simsun.ttc', $checkcode, 7, 1, 20, array(0, 0, 0));
        $this->loadComponent('Image')->drawText(APP_FONT_REAL_PATH . 'simsun.ttc', $checkcode, 6, 0, 20);
        $this->loadComponent('Image')->render();
        exit;
    }
}