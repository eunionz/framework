<?php
declare(strict_types=1);
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework        EunionzPHP框架                ///////
///////    All copyright at Eunionz.com                                ///////
///////    Email : master@Eunionz.com                                  ///////
///////    create at 2015-04-30                                         ///////
///////////////////////////////////////////////////////////////////////////////

//define APP_DS to directory path separator  //定义 APP_DS 常量值为当前系统路径分隔符，用于进行路径分隔
define('APP_DS', DIRECTORY_SEPARATOR);
//define APP_IN to current filename          //定义 APP_IN 值为当前PHP脚本执行文件名，用于检查系统所有php文件是否通过单一入口页面加载防止黑客攻击
define('APP_IN', basename(__FILE__));
//define APP_REAL_PATH to app phisycal path  //定义 APP_REAL_PATH 指向当前应用物理路径(即单一入口页面index.php所在物理路径)
define('APP_REAL_PATH', __DIR__ . APP_DS);

//define App program name  //定义 APP_PROGRAM_NAME 常量为应用程序代码文件夹名称，其路径应为： APP_REAL_PATH . APP_DS . APP_PROGRAM_NAME
define('APP_PROGRAM_NAME', 'app');

//define App PACKAGE BASE phisycal path      //定义 PACKAGE BASE 基物理路径，在该路径下将包含framework框架文件夹，以及默认应用文件夹package以及其它应用如shop/package文件夹
define('APP_PACKAGE_BASE_PATH', APP_REAL_PATH . APP_PROGRAM_NAME . APP_DS);

//包含全局函数以及注册类加载器
require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'com' . APP_DS . 'eunionz' . APP_DS . 'global' . APP_DS . 'functions.core.php';
require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'com' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'Kernel.class.php';
require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'com' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'Launcher.class.php';
new \com\eunionz\core\Launcher();
