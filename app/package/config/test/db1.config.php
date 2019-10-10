<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 上午10:46
 */
defined('APP_IN') or exit('Access Denied');

/*
 * 数据库组件
 *
 * 数据库组件的配置文件
 */

return array(
    // 数据库类型
    'APP_DB_TYPE' => 'mysql',//'oci'

    // 使用分布式数据库模式，则不同的表可以定义不同的数据库连接集群
    'APP_DB_DEPLOY' => true,

    // 使用读写分离模式
    'APP_DB_RW_SEPARATE' => false,

    // 使用持久连接
    'APP_DB_PCONNECT' => false,
    //是否记录查询sql日志
    'APP_DB_SELECT_LOG_ENABLED'=>false,
    'APP_DB_SELECT_LOG_TABLE_NAME'=>'shop_select_logs',//主数据库该表必须有如下字段,querylog_id bigint auto_increment pk,querylog_shop_id int,querylog_query text,querylog_params text,querylog_seconds float,querylog_url text,querylog_created int

    // 数据库字符集
    'APP_DB_CHAR' => 'utf8mb4',//zhs16gbk', //utf8 utf8mb4


    //数据库服务器定义
    'APP_DB_SERVERS' => array(
        // 默认所有表对应的数据库服务器集群，如果不使用分布式数据库模式时，则使用该数据库服务器群集进行数据库操作，如果使用分布式数据库模式时
        //在没有定义表对应的数据库服务器集群时，均使用该数据库服务器集群进行数据库操作
        // 非读写分离模式将使用数据库连接中的第一个数据库连接作为主连接
        // 读写分离模式将使用数据库连接池中的第一个数据库连接作为写入服务器，写入服务器(索引为0)及其它服务器做只读服务器
        'default' => array(  //默认所有表对应的数据库服务器集群
            array(  //读写分离时，写数据库
                'HOST' => '192.168.1.125',
                'PORT' => '3306',
                'NAME' => 'kshopxdb',// kshopxdb
                'USER' => 'root',
                'PASS' => '123456',
            ),
        ),

        'test' => array(  //默认所有表对应的数据库服务器集群
            array(  //读写分离时，写数据库
                'HOST' => '192.168.1.125',
                'PORT' => '3306',
                'NAME' => 'kiddevdb',// kshopxdb
                'USER' => 'root',
                'PASS' => '123456',
            ),
        ),
    ),

    // 是否启用数据库结构缓存（建议开启），基于核心缓存进行存储
    'APP_DB_STRUCTURE_CACHE' => false,

    // 使用数据语句缓存，基于核心缓存进行存储
    'APP_DB_DATA_STATEMENT_CACHE' => false,

    // 使用数据语句缓存的默认过期时间，单位：秒
    'APP_DB_DATA_STATEMENT_CACHE_EXPIRES' => 3600,

    // 数据库表中自动日期时间字段数据类型后缀，取值: 0--禁用针对自动日期时间类型字段赋值 1--当前时间戳  字符串--对当前日期时间格式为字符串例如: "Y-m-d H:i:s"  1--时间戳
    'APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE' => "Y-m-d H:i:s",

    // 数据库表中自动创建赋值日期时间字段后缀，当添加记录时这样后缀的字段将自动使用当前日期时间值
    'APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX' => ['_created' , '_updated'],

    // 数据库表中自动修改赋值日期时间字段后缀，当修改记录时这样后缀的字段将自动使用当前日期时间值
    'APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX' => ['_updated'],

);
