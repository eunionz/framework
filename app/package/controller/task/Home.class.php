<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 下午2:48
 */

namespace package\controller\task;


/**
 * Class Home
 * @package package\controller\task
 * @RPC_CLASS
 */
class Home extends \com\eunionz\core\Controller
{

    public function getProcessName()
    {
        return "TaskHomeProcess";
    }

    public function _run($a, $b)
    {
        go(function () use ($a, $b) {
            while (1) {
                self::consoleln("当前唯一协程ID: " . self::getRequestUniqueId() . " 参数a=" . $a . " 参数b=" . $b, APP_WARNING);
                \Swoole\Coroutine::sleep(1);
            }
        }
        );
    }

    public function _run1()
    {
        go(function () {
            while (1) {
                self::consoleln("当前唯一协程ID: " . self::getRequestUniqueId(), APP_WARNING);
                \Swoole\Coroutine::sleep(1);
            }
        }
        );
    }
}
