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

//定义GRPC默认超时时间常量
define('GRPC_DEFAULT_TIMEOUT', 3.0);

//定义GRPC无响应错误编号常量
define('GRPC_ERROR_NO_RESPONSE', -1);

//定义框架版本号
define('APP_FRAMEWORK_VERSION', "1.0.0");

//定义主进程ID文件存储文件名
define('APP_SWOOLE_MASTER_PID_DIR', APP_REAL_PATH . APP_STORAGE_NAME . APP_DS . 'pids' . APP_DS);

// 定义日志等级，以方便根据等级控制日志记录
define('APP_ERROR', 1);
define('APP_WARNING', 2);
define('APP_DEBUG', 3);
define('APP_INFO', 4);
define('APP_ALL', 5);
