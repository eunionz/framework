<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/16
 * Time: 19:30
 */

namespace package\service\sessions;


use com\eunionz\core\Service;

class Sessions extends Service
{

    public function find($opts){
        $db =$this->M('sessions')->curr_db();

        return $this->M('sessions')->find($opts);
    }
}