<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 上午10:47
 */
defined('APP_IN') or exit('Access Denied');

/*
 * 视图配置文件
 */

return array(

    // 全局模板变量
    'VIEW_VARS' => array(
        'SITE_URL'	=> 'http://www.example.com/',
    ),

    'VIEW_CACHE' => true,//(true | false) true--开启视图缓存  false--关闭视图缓存
    // 视图缓存目录
    // 相对于常量 APP_RUNTIME_REAL_PATH 目录之下
    'VIEW_CACHE_DIR'              => 'html',
    //视图缓存文件后缀
    'VIEW_HTML_SUFFIX'          => '.html',
    // 生成静态文件名称规则（MD5:MD5编码）
    'VIEW_BUILD_RENAME' => 'MD5',
    'VIEW_CACHE_EXPIRES' => 60,//秒,0表示永不过期
);
