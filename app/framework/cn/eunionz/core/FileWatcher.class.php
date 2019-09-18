<?php

namespace cn\eunionz\core;

/**
 * 文件监视器类
 * Class FileWatcher
 * @package cn\eunionz\core\FileWatcher
 */
class FileWatcher extends Kernel
{


    /**
     * inotify 监控事件句柄
     * @var null
     */
    private static $inotify_handle = null;

    /**
     * inotify 监控文件夹句柄列表，格式：arrray('文件夹路径' => 监控句柄,...)
     * @var null
     */
    private static $inotify_watch_folder_handles = array();

    /**
     * inotify 可监控的事件->描述 列表
     * @var array
     */
    private static $events = [
//        IN_ACCESS => 'File Accessed',
        IN_MODIFY => 'File Modified',
//        IN_ATTRIB => 'File Metadata Modified',
//        IN_CLOSE_WRITE => 'File Closed, Opened for Writing',
//        IN_CLOSE_NOWRITE => 'File Closed, Opened for Read',
//        IN_OPEN => 'File Opened',
        IN_MOVED_TO => 'File Moved In',
        IN_MOVED_FROM => 'File Moved Out',
        IN_CREATE => 'File Created',
        IN_DELETE => 'File Deleted',
    ];

    /**
     * 要监控的文件夹列表
     * @var array
     */
    private static $APP_INOFITY_FOLDER_LISTS = array();

    /**
     * 是否阻塞模式  0--非阻塞模式  1--阻塞模式
     * @var int
     */
    private static $APP_INOFITY_BLOCKING_MODE = 1;

    //定义主进程ID文件存储文件夹
    private static $APP_SWOOLE_MASTER_PID_DIR = APP_REAL_PATH . APP_STORAGE_NAME . APP_DS . 'pids' . APP_DS;

    /**
     * 在多少秒之内仅处理1次
     */
    private static $APP_INOFITY_SECONDS = 5;

    /**
     * 上次处理时间，单位：秒
     * @var int
     */
    private static $APP_INOFITY_LAST_TIME = 0;

    private static $event_lists = array();

    /**
     * 递归将文件夹下的所有子文件夹加入监听
     * @param $path
     */
    public static function recursion_add_dir($path, $my_event)
    {
        self::$inotify_watch_folder_handles[$path] = inotify_add_watch(self::$inotify_handle, $path, $my_event);
        $dir = opendir($path);
        if ($dir) {
            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..') {
                    if (is_dir(rtrim($path, APP_DS) . APP_DS . $file)) {
                        self::recursion_add_dir($path . APP_DS . $file, $my_event);
                    }
                }
            }
            closedir($dir);
        }
    }

    /**
     * 应用程序针对指定文件夹启动监控初始化方法
     */
    public static function inotify_Init()
    {
        self::$event_lists = array();
        self::$events = F('inotify', 'APP_INOFITY_EVENTS');
        self::$APP_INOFITY_FOLDER_LISTS = F('inotify', 'APP_INOFITY_FOLDER_LISTS');
        self::$APP_INOFITY_BLOCKING_MODE = F('inotify', 'APP_INOFITY_BLOCKING_MODE');
        if (is_array(self::$APP_INOFITY_FOLDER_LISTS) && self::$APP_INOFITY_FOLDER_LISTS) {
            $my_event = array_sum(array_keys(self::$events));
            if (!self::$inotify_handle) {
                self::$inotify_handle = inotify_init();
            }
            foreach (self::$APP_INOFITY_FOLDER_LISTS as $dir_path) {
                self::recursion_add_dir($dir_path, $my_event);
            }
            stream_set_blocking(self::$inotify_handle, self::$APP_INOFITY_BLOCKING_MODE);
            while ($event_list = inotify_read(self::$inotify_handle)) {
                self::inotify_Handle($event_list);
            }
            foreach (self::$inotify_watch_folder_handles as $dir_path => $watch_handle) {
                inotify_rm_watch(self::$inotify_handle, $watch_handle);
            }
            fclose(self::$inotify_handle);
        }
    }

    /**
     * 应用程序针对指定文件夹监控事件处理方法
     * @param $evet_list
     */
    public static function inotify_Handle($evet_list)
    {
        foreach ($evet_list as $event) {
            $mask = $event['mask'];
            $name = $event['name'];
            if (endsWith(strtolower($name), ".php") && !endsWith(strtolower($name), ".log.php")) {
                switch ($mask) {
                    case IN_MODIFY:
                        array_push(self::$event_lists, "[MODIFY ] " . date("Y-m-d H:i:s") . ": {$name} 文件内容被修改" . PHP_EOL);
                        break;
                    case IN_MOVED_TO:
                        array_push(self::$event_lists, "[MOVETO ] " . date("Y-m-d H:i:s") . ": {$name} 文件被移入" . PHP_EOL);
                        break;
                    case IN_MOVED_FROM:
                        array_push(self::$event_lists, "[MOVEOUT] " . date("Y-m-d H:i:s") . ": {$name} 文件被移出" . PHP_EOL);
                        break;
                    case IN_CREATE:
                        array_push(self::$event_lists, "[CREATE ] " . date("Y-m-d H:i:s") . ": {$name} 文件被创建" . PHP_EOL);
                        break;
                    case IN_DELETE:
                        array_push(self::$event_lists, "[DELETE ] " . date("Y-m-d H:i:s") . ": {$name} 文件被删除" . PHP_EOL);
                        break;

                }
            }
        }
        if (self::$event_lists && is_dir(self::$APP_SWOOLE_MASTER_PID_DIR)) {
            $dir = opendir(self::$APP_SWOOLE_MASTER_PID_DIR);
            if($dir){
                while($name = readdir($dir)){
                    $filename = self::$APP_SWOOLE_MASTER_PID_DIR . $name;
                    if($name!='.' && $name !='..' && is_file($filename)){
                        $master_id = file_get_contents($filename);
                        if ($master_id) {
                            if (time() - self::$APP_INOFITY_LAST_TIME > self::$APP_INOFITY_SECONDS) {
                                foreach (self::$event_lists as $msg) {
                                    consoleln($msg);
                                }
                                if(endsWith($name , 'manager.pid.php')){
                                    //管理进程则使用USR1以及USR2进行平滑重启
                                    exec('sudo kill -USR1 ' . $master_id, $a, $b);
                                    if ($b == 1) {
                                        consoleln("[USR1  ] " . date("Y-m-d H:i:s") . ": 主进程 【主进程 PID={$master_id}】不存在，重启所有工作进程失败 ");
                                        //exec('sudo php ' . APP_REAL_PATH . "index.php", $a, $b);
                                    } else {
                                        consoleln("[USR1  ] " . date("Y-m-d H:i:s") . ": 重启所有工作进程 【主进程 PID={$master_id}】成功");
                                    }

                                    sleep(5);
                                    exec('sudo kill -USR2 ' . $master_id, $a, $b);
                                    if ($b == 1) {
                                        console("[USR2  ] " . date("Y-m-d H:i:s") . ": 重启所有TASK进程 【主进程 PID={$master_id}】不存在，重启所有TASK进程失败 ");
                                        //exec('sudo php ' . APP_REAL_PATH . "index.php", $a, $b);
                                    } else {
                                        console("[USR2  ] " . date("Y-m-d H:i:s") . ": 重启所有TASK进程 【主进程 PID={$master_id}】成功");
                                    }
                                }else{
                                    //其它启动进程直接杀死
                                    exec('sudo kill -9 ' . $master_id, $a, $b);
                                    if ($b == 1) {
                                        consoleln("[KILL  ] " . date("Y-m-d H:i:s") . ": 启动进程 【启动进程 PID={$master_id}】不存在，结束启动进程失败 ");
                                        //exec('sudo php ' . APP_REAL_PATH . "index.php", $a, $b);
                                    } else {
                                        consoleln("[KILL  ] " . date("Y-m-d H:i:s") . ": 启动进程 【启动进程 PID={$master_id}】结束成功");
                                    }
                                }

                                self::$event_lists = array();
                            }
                            self::$APP_INOFITY_LAST_TIME = time();
                        }
                    }
                }
                @closedir($dir);
            }
        }

    }
}