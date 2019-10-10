<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework routes config
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */

defined('APP_IN') or exit('Access Denied');

/**
 * inotify 配置
 */
return array(

    /**
     * 在多少秒之内仅处理1次
     */
    'APP_INOFITY_SECONDS' => 10,

    /**
     * 要监控的事件列表
     */
    'APP_INOFITY_EVENTS' => array(
        //        IN_ACCESS => 'File Accessed',
        IN_MODIFY => 'File Modified',
        IN_ATTRIB => 'File Metadata Modified',
        IN_CLOSE_WRITE => 'File Closed, Opened for Writing',
//        IN_CLOSE_NOWRITE => 'File Closed, Opened for Read',
//        IN_OPEN => 'File Opened',
        IN_MOVED_TO => 'File Moved In',
        IN_MOVED_FROM => 'File Moved Out',
        IN_CREATE => 'File Created',
        IN_DELETE => 'File Deleted',
    ),

    /**
     * 要监控的文件夹列表
     * @var array
     */
    'APP_INOFITY_FOLDER_LISTS' => array(APP_PACKAGE_BASE_PATH),
    /**
     * 是否阻塞模式  0--非阻塞模式  1--阻塞模式
     * @var int
     */
    'APP_INOFITY_BLOCKING_MODE' => 1,
);