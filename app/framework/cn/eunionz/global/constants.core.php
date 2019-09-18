<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework core constant define
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */

defined('APP_IN') or exit('Access Denied');
//定义框架版本号
define('APP_FRAMEWORK_VERSION', "1.0.0");
//定义框架是否在SWOOLE中，
define('APP_IS_IN_SWOOLE', true);

//定义文件存储文件夹名称，文件存储文件夹必须与单一入口index.php同层
define('APP_STORAGE_NAME', 'storage');

//定义临时存储物理路径
define('APP_STORAGE_REAL_PATH', APP_REAL_PATH . APP_STORAGE_NAME . APP_DS);

//定义运行时文件夹名称，运行时文件夹必须位于 APP_STORAGE_NAME 文件夹下，在未获取到SHOP_ID时对应 /storage/runtime的获取到  SHOP_ID之后，对应/storage/{SHOP_ID}/runtime文件夹
define('APP_RUNTIME_NAME',  'runtime');

//定义主进程ID文件存储文件名
define('APP_SWOOLE_MASTER_PID_DIR', APP_REAL_PATH . APP_STORAGE_NAME . APP_DS . 'pids' . APP_DS);

//定义PHP中$_REQUEST数组中变量优化级  G--GET  P--POST  C--COOKIE
define('APP_PHP_REQUEST_ORDER' , 'PGC');

// 定义日志等级，以方便根据等级控制日志记录
define('APP_ERROR', 1);
define('APP_WARNING', 2);
define('APP_DEBUG', 3);
define('APP_INFO', 4);
define('APP_ALL', 5);
