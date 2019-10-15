<?php

declare(strict_types=1);
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework        EunionzPHP框架                ///////
///////    All copyright at Eunionz.cn                                ///////
///////    Email : master@Eunionz.cn                                  ///////
///////    create at 2015-04-30                                         ///////
///////////////////////////////////////////////////////////////////////////////

//定制路径分隔符常量
define('APP_DS', DIRECTORY_SEPARATOR);

//定义 APP_IN 常量，用于防止未经 index.php 单一入口文件的非法访问
define('APP_IN', basename(__FILE__));

//定义当前应用物理路径(即单一入口文件index.php所在物理路径)
define('APP_REAL_PATH', __DIR__ . APP_DS . '..' . APP_DS . '..' . APP_DS . '..' . APP_DS);

//定义应用程序代码所在文件夹名称，应用程序代码均位于APP_REAL_PATH 下的 APP_PROGRAM_NAME 文件夹中
define('APP_PROGRAM_NAME', 'app');

//定义应用程序包基础物理路径(即package的父物理路径)，应用程序包基础物理路径即 APP_PROGRAM_NAME 对应文件夹物理路径
define('APP_PACKAGE_BASE_PATH', APP_REAL_PATH . APP_PROGRAM_NAME . APP_DS);

//定义框架物理路径，即 APP_PACKAGE_BASE_PATH 下 framework 文件夹物理路径
define('APP_FRAMEWORK_REAL_PATH', APP_PACKAGE_BASE_PATH . 'framework' . APP_DS);

//定义应用程序包package物理路径，即 APP_PACKAGE_BASE_PATH 下 package 文件夹物理路径
define('APP_PACKAGE_REAL_PATH', APP_PACKAGE_BASE_PATH . 'package' . APP_DS);

//定义字体文件夹物理路径，即 APP_FRAMEWORK_REAL_PATH 下 fonts 文件夹物理路径
define('APP_FONT_REAL_PATH', APP_FRAMEWORK_REAL_PATH . 'fonts' . APP_DS);

//定义存储文件夹名称(存储文件夹用于存放运行时生成或上传文件，也包括站点视图文件)，存储文件夹必须与单一入口文件index.php同层
define('APP_STORAGE_NAME', 'storage');

//定义存储文件夹物理路径，存储文件夹必须可读可写
define('APP_STORAGE_REAL_PATH', APP_REAL_PATH . APP_STORAGE_NAME . APP_DS);

//定义框架是否在SWOOLE中
define('APP_IS_IN_SWOOLE', false);

//定义运行时文件夹名称，运行时文件夹必须位于 APP_STORAGE_REAL_PATH 文件夹下，在未获取到SHOP_ID时对应 /storage/runtime的获取到  SHOP_ID之后，对应/storage/{SHOP_ID}/runtime文件夹
define('APP_RUNTIME_NAME', 'runtime');

//定义PHP中$_REQUEST数组中变量优先级  G--GET  P--POST  C--COOKIE
define('APP_PHP_REQUEST_ORDER', 'PGC');

//定义 URL后缀
define('URL_HTML_SUFFIX' , '.shtml');

//定义当前应用程序环境  release -- 生产环境    pre--预发布环境   test--内网测试环境    dev--开发环境
define('APP_APPLICATION_ENV' , 'dev');


//包含全局函数以及注册类加载器
require_once APP_FRAMEWORK_REAL_PATH . 'cn' . APP_DS . 'eunionz' . APP_DS . 'global' . APP_DS . 'functions.core.php';

$GLOBALS['APP_RUNTIME_REAL_PATH'] = APP_STORAGE_REAL_PATH . APP_RUNTIME_NAME . APP_DS;
$GLOBALS['APP_KID_SPLIT_DATABASE_CONFIG_RULES'] = F('app', 'APP_KID_SPLIT_DATABASE_CONFIG_RULES');
$GLOBALS['app_route_datas'] = F('routes');

$kernel = new \cn\eunionz\core\Kernel();
$kernel->loadConstrants();
//启动应用
\cn\eunionz\core\Kernel::setContext(new \cn\eunionz\core\Context());
