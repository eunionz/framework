<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework routes config
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */

defined('APP_IN') or exit('Access Denied');


//自定义路由配置，格式：
//    array(
//         '不含协议端口、不含?及查询串部份的完整url路径部份' => array('完全限定控制器类名::index', array('路由参数值1','路由参数值2',)))
//    )
return array(
    '/' => array('\package\controller\home\Home::main'),
    '/2345.shtml' => array('\package\controller\home\Home::index',array(244)),
    '/grpc.hi/sayHello'=> array('\package\controller\home\Home::sayHello'),

);