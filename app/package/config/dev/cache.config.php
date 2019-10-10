<?php
/**
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:47
 */
defined('APP_IN') or exit('Access Denied');
return array(
    'is_cache' => true,                          //是否启用二级缓存    true  -- 启用   false  -- 启用
    'cache_type' => 'file',                     //缓存模式: file|redis, file  -- 文件缓存   redis -- REDIS 缓存
    'cache_life_seconds' => 3600,                  //默认缓存过期时间 0--永不过期   单位:秒
    'cache_driver_data' => array(
        'cache_dir' => '%RUNTIME%shop_dbcache',      //仅file模式有效，相对于 storage 文件夹的路径，如果为%RUNTIME%代表当前站点下的runtime文件夹
        'redis_servers' => array(              //仅redis模式有效,必须安装redis扩展，用于配置使用redis做核心缓存时的相关参数
            'isUseCluster'=>true,              //是否启用主从配置(主用于读写，从仅读，并随机选择主从进行读)
            'isPersistent'=>true,              //是否启用持久链接
            'connect_timeout'=>5,              //链接超时时间，单位：秒
            'dbname'=>15,                         //主从redis服务器选择的数据库编号
            'add_servers'=>array(               //配置从redis服务器
                array(//主(写)服务器
                    'server' => '127.0.0.1',              //从redis服务器地址或域名
                    'port' => '6379',                //从redis服务器端口
                    'password' => '123456',            //从redis密码
                ),
                array(
                    'server' => '127.0.0.1',              //从redis服务器地址或域名
                    'port' => '6380',                //从redis服务器端口
                    'password' => '123456',            //从redis密码
                ),
                array(
                    'server' => '127.0.0.1',              //从redis服务器地址或域名
                    'port' => '6381',                //从redis服务器端口
                    'password' => '123456',            //从redis密码
                ),
            ),//从服务器配置
        ),
    ),
);