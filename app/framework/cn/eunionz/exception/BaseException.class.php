<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-5-2
 * Time: 下午10:35
 */

namespace cn\eunionz\exception;


class BaseException extends \Exception {
    private $title="";

    public function __construct($message="",$err_code=0){
        parent::__construct($message,$err_code);
    }

    public function setTitle($title){
        $this->title = $title;
    }

    public function getTitle(){
       return $this->title;
    }

} 