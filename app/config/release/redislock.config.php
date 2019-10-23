<?php
declare(strict_types=1);
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework global core config                    ///////
///////    All copyright at Eunionz.cn                                ///////
///////    Email : master@Eunionz.cn                                  ///////
///////    create at 2015-04-30  上午9:47                               ///////
///////////////////////////////////////////////////////////////////////////////

defined('APP_IN') or exit('Access Denied');
/*
 *
 * Redis分布式锁配置文件
 */
return array(
        'server' => '127.0.0.1',     //从redis服务器地址或域名
        'port' => '6379',                //从redis服务器端口
        'password' => '123456',        //从redis密码
        'dbname' => 2,                     ////redis服务器选择的数据库编号
);
