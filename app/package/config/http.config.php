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
 * HTTP 服务器配置
 */
return array(

    /**
     * 默认HTTP服务器配置
     * HTTP服务器配置格式：
     *    'HTTP服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *        'is_ssl' => true|false,  //是否支持 SSL 隧道协议
     *    )
     */
    'http_service_default' => array(
        'host' => '192.168.1.135',
        'port' => 80,
        'timeout' => 30,
        'is_ssl' => false,
    ),

    /**
     * RPC服务器配置格式：
     *    'rpc服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'http_service_test' => array(
        'host' => '192.168.1.135',
        'port' => 443,
        'timeout' => 0.5,
        'is_ssl' => true,
    ),
);
