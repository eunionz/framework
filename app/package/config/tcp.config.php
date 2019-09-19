<?php
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework global core config                    ///////
///////    All copyright at Eunionz.cn                                ///////
///////    Email : master@Eunionz.cn                                  ///////
///////    create at 2015-04-30  上午9:47                               ///////
///////////////////////////////////////////////////////////////////////////////

defined('APP_IN') or exit('Access Denied');
/*
 *
 * TCP 服务器配置
 */
return array(

    /**
     * 默认TCP服务器配置
     * TCP服务器配置格式：
     *    'tcp服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'tcp_service_default' => array(
        'host' => '192.168.1.194',
        'port' => 9998,
        'timeout' => 1,
    ),

    /**
     * TCP服务器配置格式：
     *    'tcp服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'tcp_service_test' => array(
        'host' => '192.168.1.135',
        'port' => 8899,
        'timeout' => 1,
    ),
);
