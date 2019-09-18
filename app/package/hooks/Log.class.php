<?php
/**
 * Eunionz PHP Framework Log class (for hook do some thing )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午11:55
 */
namespace package\hooks;

defined('APP_IN') or exit('Access Denied');
class Log extends \cn\eunionz\core\Kernel{

    public function before_launch($param1, $param2)
    {
        $this->loadCore('log')->log(APP_DEBUG, 'test befor_launch: param1=' . $param1 . ' param2=' . $param2  , 'hooks');
//        $this->write("error");
        return true;
    }

    public function after_launch($param1, $param2)
    {
        //$this->log(APP_DEBUG, 'test after_launch');
        return true;
    }

    public function before_controller($param1, $param2)
    {
        //$this->log(APP_DEBUG, 'test befor_controller');
        return true;
    }

    public function after_controller($param1, $param2)
    {
        //$this->log(APP_DEBUG, 'test after_controller');
        return true;
    }

    public function override_output($param1, $param2)
    {
//        $this->log(APP_DEBUG, 'test override_output');
        return true;
    }

    public function override_router($param1, $param2)
    {
        //$this->log(APP_DEBUG, 'test override_router');
        return true;
    }
} 