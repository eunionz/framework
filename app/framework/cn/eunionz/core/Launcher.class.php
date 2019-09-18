<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Launcher (bootstrap class ,to complete  load *.config.php,parse url,find controller ,execute controller , render view, cache view  )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */

namespace cn\eunionz\core;

use cn\eunionz\component\consul\Consul;
use RuntimeException;

defined('APP_IN') or exit('Access Denied');

/**
 * 启动类
 * Class Launcher
 * @package cn\eunionz\core
 */
class Launcher extends \cn\eunionz\core\Kernel
{

    private $support_server_types = ['http', 'https', 'rpc', 'grpc', 'websocket', 'tcp', 'udp'];
    private $sub_actions = ['start', 'stop', 'restart'];
    private $console_line = "--------------------------------------------------------------------------------------------------------------------";

    private function stop_monitor_server($monitor_process_name, $monitor_pid_file)
    {
        if (self::checkProcessExistsByName($monitor_process_name)) {
            if (is_file($monitor_pid_file)) {
                $pid = file_get_contents($monitor_pid_file);
                if (is_numeric($pid)) {
                    @exec('sudo kill -9 ' . $pid, $a, $b);
                }
            }
            consoleln(date('Y-m-d H:i:s') . " 停止【平滑重启服务】成功");
        } else {
            consoleln(" 【平滑重启服务】没有启动", APP_ERROR);
        }
    }

    public function __construct()
    {
        //define runtime dir
        $GLOBALS['APP_RUNTIME_REAL_PATH'] = APP_REAL_PATH . APP_STORAGE_NAME . APP_DS . APP_RUNTIME_NAME . APP_DS;
        $GLOBALS['APP_KID_SPLIT_DATABASE_CONFIG_RULES'] = F('app', 'APP_KID_SPLIT_DATABASE_CONFIG_RULES');
        $GLOBALS['app_route_datas'] = getConfig('routes');

        $i18n = new I18n();
        $this->checkRuntime();
        $action = (isset($_SERVER['argv']) && isset($_SERVER['argv'][1])) ? strtolower($_SERVER['argv'][1]) : '';
        consoleln($this->console_line);
        self::showLogo();
        consoleln($this->console_line);
        if ($action == 'monitor') {
            $monitor_process_name = "eunionz_monitor_process";
            $monitor_pid_file = APP_SWOOLE_MASTER_PID_DIR . 'monitor.start.pid.php';
            $this->stop_monitor_server($monitor_process_name, $monitor_pid_file);
            $pid = posix_getpid();
            cli_set_process_title($monitor_process_name);
            consoleln(date('Y-m-d H:i:s') . " 【平滑重启服务】启动成功");
            consoleln(date('Y-m-d H:i:s') . " 进程名称为【{$monitor_process_name}】，进程PID为【{$pid}】");
            consoleln($this->console_line);
            file_put_contents($monitor_pid_file, $pid);
            while (true) {
                FileWatcher::inotify_Init();
                sleep(1);
            }
            return;
        } elseif ($action == 'task') {

            $task_controller = (isset($_SERVER['argv']) && isset($_SERVER['argv'][2])) ? $_SERVER['argv'][2] : "\\package\\controller\\task\\Home::run";
            $task_params = (isset($_SERVER['argv']) && isset($_SERVER['argv'][3])) ? $_SERVER['argv'][3] : [];
            if (!empty($task_params)) {
                $task_params = json_decode($task_params, true);
            }

            $task_controllers = explode("::", $task_controller);
            $task_controller_class = $task_controllers[0];
            $task_controller_action = $task_controllers[1] ?? 'run';

            if (!class_exists($task_controller_class)) {
                consoleln(" 计划任务类【{$task_controller_class}】不存在，启动计划任务失败", APP_ERROR);
                return;
            }
            $controller = new $task_controller_class();
            if (!method_exists($controller, 'getProcessName')) {
                $task_process_name = str_replace("\\package\\controller\\", "", $task_controller);
                $task_process_name = str_replace("\\", "_", $task_process_name);
                $task_process_name = str_replace("::", "_", $task_process_name);
            } else {
                $task_process_name = $controller->getProcessName();
            }
            $task_process_name = "eunionz_task_" . $task_process_name;
            consoleln(date('Y-m-d H:i:s') . " 启动计划任务【{$task_controller_class}->{$task_controller_action}】服务成功");
            consoleln(date('Y-m-d H:i:s') . " 设置当前进程名称为【{$task_process_name}】成功");

            $pid = posix_getpid();
            file_put_contents(APP_SWOOLE_MASTER_PID_DIR . 'task.start.pid.php', $pid);
            cli_set_process_title($task_process_name);
            consoleln($this->console_line);

            if (!method_exists($controller, '_' . $task_controller_action)) {
                consoleln(" 计划任务类【{$task_controller_class}】Action方法【_{$task_controller_action}】不存在，启动计划任务失败", APP_ERROR);
                return;
            }


            // get method
            $method = new \ReflectionMethod($controller, '_' . $task_controller_action);

            // call
            $method->invokeArgs(
                $controller,
                array_pad($task_params, $method->getNumberOfParameters(), null)
            );
            return;
        } elseif ($action == 'start') {
            $server_index = (isset($_SERVER['argv']) && isset($_SERVER['argv'][2])) ? $_SERVER['argv'][2] : "";
            if ($server_index) {
                //仅启动某个服务器
                try {

                    $app_constants_file = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'constants' . APP_DS . 'app.constants.php';
                    if (is_file($app_constants_file)) {
                        require_once $app_constants_file;
                    }

                    $server_cfgs = getConfig('server', 'server_cfgs');

                    if (empty($server_cfgs) || !is_array($server_cfgs)) {
                        throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_server_config'));
                    }
                    if (!isset($server_cfgs[$server_index])) {
                        throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_config'));
                    }
                    $main_server_cfg = $server_cfgs[$server_index];

                    if (!in_array(strtolower($main_server_cfg['server_type']), $this->support_server_types)) {
                        self::consoleln("不支持的服务器类型【" . $server_index . "(" . $main_server_cfg['server_type'] . ")】，系统支持的服务器类型列表为：" . implode(',', $this->support_server_types), APP_ERROR);
                        return;
                    }

                    $server_process_name = "eunionz_" . $server_index . "_" . $main_server_cfg['server_type'] . "_start_process";
                    $server_pid_file = APP_SWOOLE_MASTER_PID_DIR . $server_index . "_" . $main_server_cfg['server_type'] . '.start.pid.php';
                    $pid = posix_getpid();
                    cli_set_process_title($server_process_name);
                    file_put_contents($server_pid_file, $pid);


                    if (strtolower($main_server_cfg['server_type']) == 'http') {
                        self::$_swoole_main_server = new \Swoole\Http\Server($main_server_cfg['host'], $main_server_cfg['port'], $main_server_cfg['mode'], $main_server_cfg['sock_type']);
                        if (!self::$_swoole_main_server) {
                            throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                        }
                        $main_server_params = getConfig('server', 'main_server_params');
                        $main_server_set_params = array_merge($main_server_params, $main_server_cfg['server_params']);
                        self::$_swoole_main_server->set($main_server_set_params);


                    } elseif (strtolower($main_server_cfg['server_type']) == 'https') {
                        self::$_swoole_main_server = new \Swoole\Http\Server($main_server_cfg['host'], $main_server_cfg['port'], $main_server_cfg['mode'], $main_server_cfg['sock_type']);
                        if (!self::$_swoole_main_server) {
                            throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                        }
                        $main_server_params = getConfig('server', 'main_server_params');
                        $main_server_set_params = array_merge($main_server_params, $main_server_cfg['server_params']);
                        self::$_swoole_main_server->set($main_server_set_params);
                    } elseif (strtolower($main_server_cfg['server_type']) == 'websocket') {
                        self::$_swoole_main_server = new \Swoole\WebSocket\Server($main_server_cfg['host'], $main_server_cfg['port']);
                        if (!self::$_swoole_main_server) {
                            throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                        }
                        $main_server_params = getConfig('server', 'main_server_params');
                        $main_server_set_params = array_merge($main_server_params, $main_server_cfg['server_params']);
                        self::$_swoole_main_server->set($main_server_set_params);

                    } elseif (strtolower($main_server_cfg['server_type']) == 'tcp' || strtolower($main_server_cfg['server_type']) == 'udp') {
                        self::$_swoole_main_server = new \swoole_server($main_server_cfg['host'], $main_server_cfg['port'], $main_server_cfg['mode'], $main_server_cfg['sock_type']);
                        if (!self::$_swoole_main_server) {
                            throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                        }
                        $main_server_params = getConfig('server', 'main_server_params');
                        $main_server_set_params = array_merge($main_server_params, $main_server_cfg['server_params']);
                        self::$_swoole_main_server->set($main_server_set_params);

                    } elseif (in_array(strtolower($main_server_cfg['server_type']), ['rpc', 'grpc'])) {
                        self::$_swoole_main_server = new \swoole_server($main_server_cfg['host'], $main_server_cfg['port']);
                        if (!self::$_swoole_main_server) {
                            throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                        }
                        $main_server_set_params = $main_server_cfg['server_params'];
                        self::$_swoole_main_server->set($main_server_set_params);

                    }


                    consoleln(date('Y-m-d H:i:s') . "  主服务器【" . $server_index . '(' . $main_server_cfg['server_type'] . ")】启动：  Host: " . $main_server_cfg['host'] . " Port: " . $main_server_cfg['port']);


                    //主服务器启动时启动此事件
                    if (isset($main_server_cfg['on_start']) && $main_server_cfg['on_start']) {
                        self::$_swoole_main_server->on("start", function ($server) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_start'];
                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $main_server_cfg);
                        });
                    }

                    //管理进程触发时启动此事件
                    if (isset($main_server_cfg['on_managerstart']) && $main_server_cfg['on_managerstart']) {
                        self::$_swoole_main_server->on("managerstart", function ($server) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_managerstart'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $main_server_cfg);
                        });
                    }

                    //工作进程启动时触发此事件
                    if (isset($main_server_cfg['on_workerstart']) && $main_server_cfg['on_workerstart']) {
                        self::$_swoole_main_server->on("workerstart", function ($server, $worker_id) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_workerstart'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $worker_id, $main_server_cfg);
                        });
                    }


                    //每次连接时(相当于每个浏览器第一次打开页面时)执行一次, reload 时连接不会断开, 也就不会再次触发该事件
                    if (isset($main_server_cfg['on_connect']) && $main_server_cfg['on_connect'] && self::$_swoole_main_server) {
                        self::$_swoole_main_server->on('connect', function ($server, $fd) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_connect'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $fd, $main_server_cfg);
                        });
                    }

                    //有http请求时触发该事件
                    if (isset($main_server_cfg['on_request']) && $main_server_cfg['on_request'] && self::$_swoole_main_server) {
                        self::$_swoole_main_server->on('request', function ($request, $response) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_request'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($request, $response, $main_server_cfg);
                        });
                    }


                    //连接关闭时触发该事件
                    if (isset($main_server_cfg['on_close']) && $main_server_cfg['on_close'] && self::$_swoole_main_server) {
                        self::$_swoole_main_server->on('close', function ($server, $fd) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_close'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $fd, $main_server_cfg);
                        });
                    }


                    //工作进程结束时触发此事件
                    if (isset($main_server_cfg['on_workerstop']) && $main_server_cfg['on_workerstop']) {
                        self::$_swoole_main_server->on("workerstop", function ($server, $worker_id) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_workerstop'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $worker_id, $main_server_cfg);
                        });
                    }

                    //有Tcp服务数据接收时触发该事件
                    if (isset($main_server_cfg['on_receive']) && $main_server_cfg['on_receive'] && self::$_swoole_main_server) {
                        self::$_swoole_main_server->on('receive', function ($server, $fd, $reactor_id, $data) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_receive'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $fd, $reactor_id, $data, $main_server_cfg);
                        });
                    }


                    //有任务进程被投递时触发此事件
                    if (isset($main_server_cfg['on_task']) && $main_server_cfg['on_task']) {
                        self::$_swoole_main_server->on("task", function ($server, $task_id, $src_worker_id, $data) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_task'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $task_id, $src_worker_id, $data, $main_server_cfg);
                        });
                    }


                    //有任务完成时触发此事件
                    if (isset($main_server_cfg['on_finish']) && $main_server_cfg['on_finish']) {
                        self::$_swoole_main_server->on("finish", function ($server, $task_id, $data) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_finish'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $task_id, $data, $main_server_cfg);
                        });
                    }

                    //有WebSocket握手时触发此事件
                    if (isset($main_server_cfg['on_handshake']) && ($main_server_cfg['server_type'] == 'websocket') && $main_server_cfg['on_handshake']) {
                        self::$_swoole_main_server->on("handshake", function ($request, $response) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_handshake'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($request, $response, $main_server_cfg);
                        });
                    }

                    //有WebSocket 握手完成时触发此事件
                    if (isset($main_server_cfg['on_open']) && (isset($main_server_cfg['server_params']['open_websocket_protocol']) && $main_server_cfg['server_params']['open_websocket_protocol']) && $main_server_cfg['on_open']) {
                        self::$_swoole_main_server->on("open", function ($server, $request) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_open'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $request, $main_server_cfg);
                        });
                    }

                    //有WebSocket消息时触发此事件
                    if (isset($main_server_cfg['on_message']) && (isset($main_server_cfg['server_params']['open_websocket_protocol']) && $main_server_cfg['server_params']['open_websocket_protocol']) && $main_server_cfg['on_message']) {
                        self::$_swoole_main_server->on("message", function ($server, $frame) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_message'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $frame, $main_server_cfg);
                        });
                    }

                    //有Udp服务器消息时触发此事件
                    if (isset($main_server_cfg['on_packet']) && $main_server_cfg['on_packet']) {
                        self::$_swoole_main_server->on("packet", function ($server, $data, $client_info) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_packet'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $data, $client_info, $main_server_cfg);
                        });
                    }

                    consoleln($this->console_line);
                    self::$_swoole_main_server->start();

                    if (isset($main_server_cfg['on_end']) && $main_server_cfg['on_end']) {
                        list($class, $method) = $main_server_cfg['on_end'];

                        $object = new $class;

                        if (!method_exists($object, $method)) {
                            throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                        }
                        $object->$method();
                    }

                } catch (\Exception $err) {
                    consoleln($err->getMessage(), APP_ERROR);
                }
            } else {
                //启动所有服务器
                try {
                    $app_constants_file = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'constants' . APP_DS . 'app.constants.php';
                    if (is_file($app_constants_file)) {
                        require_once $app_constants_file;
                    }

                    $server_cfgs = getConfig('server', 'server_cfgs');
                    if (empty($server_cfgs) || !is_array($server_cfgs)) {
                        throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_server_config'));
                    }
                    if (!isset($server_cfgs['main'])) {
                        throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_config'));
                    }
                    $main_server_cfg = $server_cfgs['main'];
                    if ($main_server_cfg['server_type'] != 'http' && $main_server_cfg['server_type'] != 'https') {
                        consoleln("  主服务器类型【main(" . $main_server_cfg['server_type'] . ")】必须为 http或https ，启动失败", APP_ERROR);
                        return;
                    }
                    if (!$main_server_cfg['enable']) {
                        consoleln("  主服务器已禁用 ，启动失败", APP_ERROR);
                        return;
                    }


                    $server_process_name = "eunionz_main_" . $main_server_cfg['server_type'] . "_start_process";
                    $server_pid_file = APP_SWOOLE_MASTER_PID_DIR . "main_" . $main_server_cfg['server_type'] . '.start.pid.php';
                    $pid = posix_getpid();
                    cli_set_process_title($server_process_name);
                    file_put_contents($server_pid_file, $pid);


                    foreach ($server_cfgs as $index => $cfg) {
                        if ($index == 'main') {
                            self::$_swoole_main_server = new \Swoole\WebSocket\Server($cfg['host'], $cfg['port'], $cfg['mode'], $cfg['sock_type']);
                            consoleln(date('Y-m-d H:i:s') . "  主服务器【" . $index . "(" . $main_server_cfg['server_type'] . ")】启动：  Host: " . $cfg['host'] . " Port: " . $cfg['port']);
                        } else {
                            if (self::$_swoole_main_server && $cfg['enable'] && (in_array($cfg['server_type'], ['http', 'https', 'rpc', 'grpc', 'tcp', 'websocket', 'udp']))) {
                                self::$_swoole_other_servers[$index] = self::$_swoole_main_server->listen($cfg['host'], $cfg['port'], $cfg['sock_type']);
                                consoleln(date('Y-m-d H:i:s') . "  其它服务器【" . $index . "(" . $cfg['server_type'] . ")启动：Host: " . $cfg['host'] . " Port: " . $cfg['port']);
                            }
                        }
                    }
                    if (!self::$_swoole_main_server) {
                        throw new \cn\eunionz\exception\BaseException($i18n->getLang('error_main_server_create_failure'));
                    }
                    $main_server_params = getConfig('server', 'main_server_params');
                    $main_server_set_params = array_merge($main_server_params, $main_server_cfg['server_params']);

                    self::$_swoole_main_server->set($main_server_set_params);
                    foreach ($server_cfgs as $index => $cfg) {
                        if ($index != 'main') {
                            $service_params = $main_server_params;
                            if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                if (isset($cfg['server_params']) && is_array($cfg['server_params']) && $cfg['server_params']) {
                                    $service_params = array_merge($service_params, $cfg['server_params']);
                                }
                                if (in_array(strtolower($main_server_cfg['server_type']), ['rpc', 'grpc'])) {
                                    $service_params = $cfg['server_params'];
                                }
                                self::$_swoole_other_servers[$index]->set($service_params);
                            }
                        }
                    }

                    //主服务器启动时启动此事件
                    if (isset($main_server_cfg['on_start']) && $main_server_cfg['on_start']) {
                        self::$_swoole_main_server->on("start", function ($server) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_start'];
                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $main_server_cfg);
                        });
                    }

                    //管理进程触发时启动此事件
                    if (isset($main_server_cfg['on_managerstart']) && $main_server_cfg['on_managerstart']) {
                        self::$_swoole_main_server->on("managerstart", function ($server) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_managerstart'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $main_server_cfg);
                        });
                    }

                    //工作进程启动时触发此事件
                    if (isset($main_server_cfg['on_workerstart']) && $main_server_cfg['on_workerstart']) {
                        self::$_swoole_main_server->on("workerstart", function ($server, $worker_id) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_workerstart'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $worker_id, $main_server_cfg);
                        });
                    }


                    //每次连接时(相当于每个浏览器第一次打开页面时)执行一次, reload 时连接不会断开, 也就不会再次触发该事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_connect']) && $cfg['on_connect']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }

                        if ($swoole_server_object) {
                            $swoole_server_object->on('connect', function ($server, $fd) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_connect'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $fd, $cfg);
                            });
                        }
                    }


                    //每次请求时触发该事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_request']) && $cfg['on_request']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }

                        if ($swoole_server_object) {
                            $swoole_server_object->on('request', function ($request, $response) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_request'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($request, $response, $cfg);
                            });
                        }
                    }


                    //连接关闭时触发该事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_close']) && $cfg['on_close']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }

                        if ($swoole_server_object) {
                            $swoole_server_object->on('close', function ($server, $fd) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_close'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $fd, $cfg);
                            });
                        }
                    }


                    //工作进程结束时触发此事件
                    if (isset($main_server_cfg['on_workerstop']) && $main_server_cfg['on_workerstop']) {
                        self::$_swoole_main_server->on("workerstop", function ($server, $worker_id) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_workerstop'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $worker_id, $main_server_cfg);
                        });
                    }

                    //数据接收时触发该事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_receive']) && $cfg['on_receive']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }

                        if ($swoole_server_object) {
                            $swoole_server_object->on('receive', function ($server, $fd, $reactor_id, $data) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_receive'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $fd, $reactor_id, $data, $cfg);
                            });
                        }
                    }


                    //有任务进程被投递时触发此事件
                    if (isset($main_server_cfg['on_task']) && $main_server_cfg['on_task']) {
                        self::$_swoole_main_server->on("task", function ($server, $task_id, $src_worker_id, $data) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_task'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $task_id, $src_worker_id, $data, $main_server_cfg);
                        });
                    }


                    //有任务完成时触发此事件
                    if (isset($main_server_cfg['on_finish']) && $main_server_cfg['on_finish']) {
                        self::$_swoole_main_server->on("finish", function ($server, $task_id, $data) use ($main_server_cfg, $i18n) {
                            list($class, $method) = $main_server_cfg['on_finish'];

                            $object = new $class;

                            if (!method_exists($object, $method)) {
                                throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                            }
                            $object->$method($server, $task_id, $data, $main_server_cfg);
                        });
                    }


                    //有WebSocket握手时触发此事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_handshake']) && (isset($cfg['server_params']['open_websocket_protocol']) && $cfg['server_params']['open_websocket_protocol']) && $cfg['on_handshake']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }
                        if ($swoole_server_object) {
                            $swoole_server_object->on("handshake", function ($request, $response) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_handshake'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($request, $response, $cfg);
                            });
                        }
                    }

                    //有WebSocket握手完成时触发此事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_open']) && (isset($cfg['server_params']['open_websocket_protocol']) && $cfg['server_params']['open_websocket_protocol']) && $cfg['on_open']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }

                        if ($swoole_server_object) {
                            $swoole_server_object->on("open", function ($server, $request) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_open'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $request, $cfg);
                            });
                        }
                    }


                    //有WebSocket消息时触发此事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_message']) && (isset($cfg['server_params']['open_websocket_protocol']) && $cfg['server_params']['open_websocket_protocol']) && $cfg['on_message']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }
                        if ($swoole_server_object) {
                            $swoole_server_object->on("message", function ($server, $frame) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_message'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $frame, $cfg);
                            });
                        }
                    }

                    //有Udp服务器消息时触发此事件
                    foreach ($server_cfgs as $index => $cfg) {
                        $swoole_server_object = null;
                        if (isset($cfg['on_packet']) && $cfg['on_packet']) {
                            if ($index == 'main') {
                                if (self::$_swoole_main_server) {
                                    $swoole_server_object = self::$_swoole_main_server;
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index]) {
                                    $swoole_server_object = self::$_swoole_other_servers[$index];
                                }
                            }
                        }
                        if ($swoole_server_object) {
                            $swoole_server_object->on("packet", function ($server, $data, $client_info) use ($cfg, $i18n) {
                                list($class, $method) = $cfg['on_packet'];

                                $object = new $class;

                                if (!method_exists($object, $method)) {
                                    throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                                }
                                $object->$method($server, $data, $client_info, $cfg);
                            });
                        }
                    }

                    consoleln($this->console_line);


                    $consul = new Consul();
                    $consul_cfg = getConfig('consul');


                    foreach ($server_cfgs as $index => $cfg) {
                        if(isset($cfg['microservice']) && isset($cfg['microservice']['service_id']) && $cfg['microservice']['service_id']){
                            $consul->service_deregister($cfg['microservice']['service_id']);
                        }
                    }
                    //注册微服务开始
                    foreach ($server_cfgs as $index => $cfg) {
                        if (empty($server_index) || ($server_index == $index)) {
                            if ($index == 'main' && self::$_swoole_main_server && isset($cfg['microservice']) && $cfg['microservice'] && isset($cfg['microservice']['enable']) && $cfg['microservice']['enable']) {
                                $rs = $consul->service_register(
                                    $cfg['microservice']['service_id'],
                                    $cfg['microservice']['service_name'],
                                    $cfg['microservice']['service_address'],
                                    $cfg['microservice']['service_port'],
                                    $cfg['microservice']['service_tags'],
                                    $cfg['microservice']['service_metas'],
                                    $cfg['microservice']['service_health_check'],
                                    $cfg['microservice']['service_weights']
                                );
                                if ($rs) {
                                    consoleln(date('Y-m-d H:i:s') . "  注册微服务【Name = " . $cfg['microservice']['service_name'] . " , ID = " . $cfg['microservice']['service_id'] . " , IP = " . $cfg['microservice']['service_address'] . " , PORT = " . $cfg['microservice']['service_port'] . "】到服务注册中心【" . $consul_cfg['host'] . ':' . $consul_cfg['port'] . "】成功");
                                }
                            } else {
                                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index] && isset($cfg['microservice']) && $cfg['microservice'] && isset($cfg['microservice']['enable']) && $cfg['microservice']['enable']) {
                                    $rs = $consul->service_register(
                                        $cfg['microservice']['service_id'],
                                        $cfg['microservice']['service_name'],
                                        $cfg['microservice']['service_address'],
                                        $cfg['microservice']['service_port'],
                                        $cfg['microservice']['service_tags'],
                                        $cfg['microservice']['service_metas'],
                                        $cfg['microservice']['service_health_check'],
                                        $cfg['microservice']['service_weights']
                                    );
                                    if ($rs) {
                                        consoleln(date('Y-m-d H:i:s') . "  注册微服务【Name = " . $cfg['microservice']['service_name'] . " , ID = " . $cfg['microservice']['service_id'] . " , IP = " . $cfg['microservice']['service_address'] . " , PORT = " . $cfg['microservice']['service_port'] . "】到服务注册中心【" . $consul_cfg['host'] . ':' . $consul_cfg['port'] . "】成功");
                                    }
                                }
                            }
                        }
                    }
                    self::consoleln($this->console_line);

                    self::$_swoole_main_server->start();

                    if (isset($main_server_cfg['on_end']) && $main_server_cfg['on_end']) {
                        list($class, $method) = $main_server_cfg['on_end'];

                        $object = new $class;

                        if (!method_exists($object, $method)) {
                            throw new \cn\eunionz\exception\MethodNotFoundException($i18n->getLang('error_hook_method_not_found_title'), $i18n->getLang('error_hook_method_not_found', array($class, $method)));
                        }
                        $object->$method();
                    }

                } catch (\Exception $err) {
                    consoleln($err->getMessage(), APP_ERROR);
                }
            }
        } else {
            $cmd_helper_string =
                "    系统支持的命令为：
    1、start
      格式：php index.php start 服务名    
      功能：启动指定【服务名】的服务
      参数说明：
            1)、如果服务名为空，则将启动/app/config/server.config.php中配置的所有可用服务器，
                并将server_cfgs配置数组中key为main的服务器做为主服务器，其它服务器做为子服务启动。
            2)、如果服务名不为空，则仅将启动server_cfgs配置数组中key为【服务名】的服务器，并做为主服务器启动，其它服务器并不启动。
      示例：
          1)、启动所有服务：
              php index.php start
          2)、启动main主服务
              php index.php start main
              
    2、monitor
      格式：php index.php monitor
      功能：启动针对当前应用文件夹下(包含所有下级文件夹)*.php文件(*.log.php除外)的文件变化监控服务，
            当文件发生变化将启动重新启动相关服务(有可能会中止相关服务)。
      示例：
            php index.php monitor
             
    3、task
      格式：php index.php task \"完全限定名称控制器类名::Action方法名\" \"[值1,值2,...,值n]\"    
      功能：执行当前应用指定控制器指定【Action方法名】方法，用于实现计划任务，需要自行实现指定控制器类的【Action方法名】，
            在该【Action方法名】方法中通常使用死循环实现定时计划任务。
      参数说明：
            1)、完成限定名称控制器类名：
                指定要执行的控制器完整类名，例如：\\package\\controller\\task\\Home
                可在该控制器中实现   public function getProcessName(){  return \"计划任务进程名称\";   }  方法，
                用于指定计划任务进程名称，如果没有实现此方法，计划任务进程名称由系统决定。
            2)、Action方法名：
                指定控制器中的Action方法，必须为public非static方法，定义格式如下：
                public function _Action方法名(\$参数1,\$参数2,...,\$参数n){}
            3)、\"[值1,值2,...,值n]\"：
                用于指定传递给Action方法的参数值，为通过\"\"引起来的json数组，如果Action方法并不需要接收任何参数，
                则这个部份请省去，否则请根据Action方法定义的参数格式及顺序依次设定参数值。   
      示例：
            php index.php task \"\\package\\controller\\task\\Home::run\" \"[2,\\\"aa\\\"]\"

            ";


            consoleln($cmd_helper_string);
            consoleln($this->console_line);
            return;

        }

    }

    /**
     * Check runtime extension conflict
     *
     * @param string $minPhp
     * @param string $minSwoole
     */
    public static function checkRuntime(string $minPhp = '7.3', string $minSwoole = '4.4.4'): void
    {
        // if (!EnvHelper::isCli()) {
        //     throw new RuntimeException('Server must run in the CLI mode.');
        // }

        if (version_compare(PHP_VERSION, $minPhp, '<')) {
            throw new RuntimeException('Run the server requires PHP version > 7.3! current is ' . PHP_VERSION);
        }

        if (!extension_loaded('swoole')) {
            throw new RuntimeException("Run the server, extension 'swoole' is required!");
        }

        if (version_compare(SWOOLE_VERSION, $minSwoole, '<')) {
            throw new RuntimeException('Run the server requires swoole version > 4.4.4! current is ' . SWOOLE_VERSION);
        }

        $conflicts = [
            'blackfire',
            'xdebug',
            'uopz',
            'xhprof',
            'zend',
            'trace',
        ];

        foreach ($conflicts as $ext) {
            if (extension_loaded($ext)) {
                throw new RuntimeException("The extension of '{$ext}' must be closed, otherwise eunionz will be affected!");
            }
        }

    }

}
