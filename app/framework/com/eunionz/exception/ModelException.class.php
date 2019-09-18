<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-5-2
 * Time: 下午5:48
 */

namespace com\eunionz\exception;


class ModelException extends BaseException {

    public function __construct($title,$message="",$err_code=0){
        parent::__construct($message,$err_code);
        $this->setTitle($title);
    }

} 