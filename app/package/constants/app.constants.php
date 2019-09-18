<?php
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework global core config                    ///////
///////    All copyright at Eunionz.com                                ///////
///////    Email : master@Eunionz.com                                  ///////
///////    create at 2015-04-30  上午9:47                               ///////
///////////////////////////////////////////////////////////////////////////////

defined('APP_IN') or exit('Access Denied');

//define APP_PATH to app web path  //定义应用程序WEB访问路径
define('APP_PATH', '/');

//默认伪静态，URL后缀，如果要实现伪静态，请设置action的后缀 .shtml
define('URL_HTML_SUFFIX', '.shtml');

//对于站点根目录下特殊文件访问的重写规则，将优先从客户站点Runtime文件夹下查找【 ctx()->getAppRuntimeRealPath()】，如果文件不存在将继续在【APP_REAL_PATH】下查找，否则执行控制器
define('URL_FILE_CONTENT_REWRITE_RULES', array("^/?(MP_verify.+\\.txt)$"=>"/mp/$1",));
