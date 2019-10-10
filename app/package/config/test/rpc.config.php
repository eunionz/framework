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
 * RPC 服务器配置
 */
return array(

    /**
     * 默认RPC服务器配置
     * RPC服务器配置格式：
     *    'rpc服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'rpc_service_default' => array(
        'host' => '192.168.1.135',
        'port' => 8899,
        'timeout' => 1,
    ),

    /**
     * RPC服务器配置格式：
     *    'rpc服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'rpc_service_test' => array(
        'host' => '192.168.1.135',
        'port' => 8899,
        'timeout' => 1,
    ),

    /**
     * RPC服务器配置格式：
     *    'rpc服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'grpc_service_default' => array(
        'host' => '192.168.1.135',
        'port' => 8888,
        'timeout' => 1,
    ),

    /**
     * RPC服务器配置格式：
     *    'rpc服务名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'timeout' => 超时时间,  //单位：秒
     *    )
     */
    'grpc_service_nodejs' => array(
        'host' => '192.168.1.135',
        'port' => 50051,
        'timeout' => 1,
    ),
);
