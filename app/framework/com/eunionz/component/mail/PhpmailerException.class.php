<?php
/**
 * Eunionz PHP Framework Mail component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\mail;

defined('APP_IN') or exit('Access Denied');


class PhpmailerException extends \Exception {
    public function errorMessage() {
        $errorMsg = '<strong>' . $this->getMessage() . "</strong><br />\n";
        return $errorMsg;
    }
}
