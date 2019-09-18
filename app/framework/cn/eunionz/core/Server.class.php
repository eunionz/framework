<?php

namespace cn\eunionz\core;

use cn\eunionz\exception\ControllerNotFoundException;
use cn\eunionz\exception\MethodNotFoundException;
use InvalidArgumentException;
use SebastianBergmann\CodeCoverage\Report\PHP;

use Swoole\Coroutine as co;

defined('APP_IN') or exit('Access Denied');

/**
 * 服务器基类
 * 用于编写服务器监听事件
 * Class Server
 */
class Server extends Kernel
{

    /**
     * 应用程序启动事件，当应用程序启动时触发该事件
     * 说明：
     *       仅主服务器才能设置 onStart 事件
     * @param $server  为当前的服务器对像
     * @param $cfg     为当前服务器配置
     */
    public function onStart($server, $cfg)
    {
        require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'cn' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'ClassAutoLoader.class.php';
        require_once APP_REAL_PATH . 'vendor' . APP_DS . 'autoload.php';
        spl_autoload_register(array('\cn\eunionz\core\ClassAutoLoader', 'autoload'));
        swoole_set_process_name($cfg['main_process_name']);
        file_put_contents(APP_SWOOLE_MASTER_PID_DIR . $cfg['main_process_name'] . '.manager.pid.php', $server->master_pid);
    }

    /**
     * 当有新的任务被投递时触发该事件
     * 说明：
     *       仅主服务器才能设置 onTask 事件
     *       在task_worker进程内被调用。worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
     *       当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，这时将不再接收新的Task，
     *       当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task。
     *       当任务处理完成之后需要调用：$server->finish($data); 标识当前任务完成，并同时触发任务onFinish事件
     * @param $server               当前server对象
     * @param $task_id              任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
     * @param $src_worker_id        源worker_id，说明来自于哪个worker进程，
     * @param $data                 任务的内容
     * @param $cfg                  当前服务器配置
     */
    public function onTask($server, $task_id, $src_worker_id, $data, $cfg)
    {
//        self::consoleln(date('Y-m-d H:i:s') . ' 有新的任务【taskid=' . $task_id . '】');

    }

    /**
     * 任务完成时触发该事件
     * 说明：
     *       仅主服务器才可以设置 onFinish 事件回调
     *       当任务在OnTask事件中处理完成之后，应该调用：  $server->finish($data); 标识当前任务完成以触发该事件
     *
     * @param $server       当前server对象
     * @param $task_id      任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
     * @param $data         任务的内容
     * @param $cfg          当前服务器配置
     */
    public function onFinish($server, $task_id, $data, $cfg)
    {
        //self::consoleln( date('Y-m-d H:i:s') . ' 任务完成【taskid=' . $task_id . '】');

    }

    /**
     * 主服务器管理进程启动时触发该事件
     * 说明：
     *      仅主服务器才能设置 onManagerStart 事件回调
     *      管理进程在主服务器中仅有一个，因此此事件仅回调一次
     * @param $server   当前的服务器对像
     * @param $cfg      当前服务器配置
     */
    public function onManagerStart($server, $cfg)
    {
        //self::consoleln( date('Y-m-d H:i:s') . ' 管理进程启动【PID=' . $server->manager_pid . '】');

    }


    /**
     * 主服务器工作进程启动时触发该事件
     * 说明：
     *      仅主服务器才能设置 onWorkerStart 事件回调
     *      工作进程在主服务器中可启动多个，因此此事件回调将调用多次
     *      对于http或者https  web服务器通常用于进行初始化包括类加载器注册
     * @param $server           当前的服务器对像
     * @param $worker_id        工作进程ID
     * @param $cfg              当前服务器配置
     */
    public function onWorkerStart($server, $worker_id, $cfg)
    {
        require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'cn' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'ClassAutoLoader.class.php';
        spl_autoload_register(array('\cn\eunionz\core\ClassAutoLoader', 'autoload'));
        require_once APP_REAL_PATH . 'vendor' . APP_DS . 'autoload.php';
        swoole_set_process_name($cfg['worker_process_name']);


        //self::consoleln( date('Y-m-d H:i:s') . ' 工作进程启动【ID=' . $worker_id . '】');
    }

    /**
     * 主服务器工作进程结束时触发该事件
     * 说明：
     *      仅主服务器才能设置 onWorkerStop 事件回调
     *      工作进程在主服务器中可启动多个，因此此事件回调将调用多次
     * @param $server       当前的服务器对像
     * @param $worker_id    工作进程ID
     * @param $cfg          当前服务器配置
     */
    public function onWorkerStop($server, $worker_id, $cfg)
    {
        //self::consoleln( date('Y-m-d H:i:s') . ' 工作进程结束【ID=' . $worker_id . '】');

    }


    /**
     * 主服务器/其它服务器有新连接进入时触发该事件
     * 说明：
     *       主服务器/其它服务器均可设置 onConnect 事件回调
     *       主要用于初始化连接
     * @param $server   当前的服务器对像
     * @param $fd       客户端连接的文件描述符(连接句柄)，发送数据/关闭客户端连接时需要此参数
     * @param $cfg      当前服务器配置
     */
    public function onConnect($server, $fd, $cfg)
    {
//        self::consoleln( date('Y-m-d H:i:s') . ' 新的连接进入【FD=' . $fd . '】');

    }

    /**
     * 主服务器/其它服务器当前连接有数据被接收时触发该事件
     * 说明：
     *       主服务器/其它服务器均可设置 onReceive 事件回调
     *       主要用于对接收的数据进行处理
     *       对于http/https web服务器不需要对该事件进行处理而是针对 onRequest 事件进行处理
     *       主要用于tcp/udp服务器
     *       在事件回调中通常通过 $server->send($fd , '数据') 将数据发送回客户端进行响应
     * @param $server           当前的服务器对像
     * @param $fd               客户端连接的文件描述符(连接句柄)，发送数据/关闭客户端连接时需要此参数
     * @param $reactor_id       TCP连接所在的Reactor线程ID
     * @param $data             收到的数据内容，可能是文本或者二进制内容
     * @param $cfg              当前服务器配置
     */
    public function onReceive($server, $fd, $reactor_id, $data, $cfg)
    {
        //self::consoleln( date('Y-m-d H:i:s') . ' 新的数据接收【FD=' . $fd . '】');
    }

    /**
     * 主服务器/其它服务器当前连接关闭时触发该事件
     * 说明：
     *       主服务器/其它服务器均可设置 onClose 事件回调
     *       主要用于做连接关闭前的一些处理
     * @param $server       当前的服务器对像
     * @param $fd           客户端连接的文件描述符(连接句柄)，发送数据/关闭客户端连接时需要此参数
     * @param $cfg          当前服务器配置
     */
    public function onClose($server, $fd, $cfg)
    {
        //self::consoleln( date('Y-m-d H:i:s') . ' 连接关闭【FD=' . $fd . '】');

    }


    /**
     * 主服务器/其它监听服务器有http/https请求时触发该事件
     * 说明：
     *       主服务器/其它服务器均可设置 onRequest 事件回调
     *       主要用于对http/https web服务器新的http请求事件进行处理
     *
     * @param $request      当前请求对像
     * @param $response     当前响应对像
     * @param $cfg          当前服务器配置
     */
    public function onRequest($request, $response, $cfg)
    {

        @libxml_disable_entity_loader(true);

        //初始化请求对像及响应对像，有状态对像必须为短暂生命周期对像，无状态对像可为单例生命周期对像
        self::setRequestFd($request->fd);
        $controller = null;

        $ctx = new Context();
        $ctx->addTimeNode('system_launch');
        $ctx->setRequest(new Request($request, $cfg));
        $ctx->setResponse(new Response($response));
        $ctx->setI18n(new I18n());
        $ctx->setSession(new Session());
        self::setContext($ctx);
        try {
            //设置当前时区
            date_default_timezone_set(getConfig('app', 'APP_DEFAULT_TIMEZONE'));

            //设置脚本最大执行时间
            set_time_limit(getConfig('app', 'APP_DEFAULT_SCRIPT_EXECUTE_TIMEOUT_SECONDS'));

            //获取当前SHOP_ID
            $shop_id = 0;
            if (getConfig('app', 'APP_SET_SHOP_ID_BY_HTTP_HOST_CALLBACK')) {
                $CALLBACK = getConfig('app', 'APP_SET_SHOP_ID_BY_HTTP_HOST_CALLBACK');
                if (is_string($CALLBACK)) {
                    if (function_exists($CALLBACK)) {
                        $shop_id = $CALLBACK(ctx()->getRequest()->server("HTTP_HOST"));
                    }
                } elseif (is_array($CALLBACK)) {
                    if (class_exists($CALLBACK[0]) && method_exists($CALLBACK[0], $CALLBACK[1])) {
                        $obj = new $CALLBACK[0]();
                        $method = $CALLBACK[1];
                        $shop_id = $obj->$method(ctx()->getRequest()->server("HTTP_HOST"));
                    }
                }
            }


            ctx()->setShopId($shop_id);
            $site_name = (empty($shop_id) ? getConfig('app', 'APP_SHOP_ID_ZERO_FOLDER_NAME') : $shop_id);
            ctx()->setSiteName($site_name);
            //设置当前站点名称

            //当前站点临时文件夹物理路径
            ctx()->setAppStorageRealPath(APP_STORAGE_REAL_PATH . $site_name . APP_DS);

            //当前站点运行时文件夹物理路径，该文件夹位于 APP_CURRENT_SITE_TEMP_REAL_PATH 下
            ctx()->setAppRuntimeRealPath(ctx()->getAppStorageRealPath() . APP_RUNTIME_NAME . APP_DS);


            if (!is_dir(ctx()->getAppStorageRealPath())) {
                @mkdir(ctx()->getAppStorageRealPath(), 0x777);
                @mkdir(ctx()->getAppStorageRealPath() . 'view', 0x777);
            }

            //定义当前SHOP_ID对应的临时文件夹路径
            ctx()->setAppStoragePath(APP_PATH . APP_STORAGE_NAME . '/' . $site_name . '/');

            //初始化运行时文件夹
            if (!is_dir(ctx()->getAppRuntimeRealPath())) {
                @mkdir(ctx()->getAppRuntimeRealPath());
                @mkdir(ctx()->getAppRuntimeRealPath() . 'cache');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'html');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'logs');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'session');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'uploads');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'templates_c');
            }

            //定义当前SHOP_ID运行时文件夹路径
            ctx()->setAppRuntimePath(ctx()->getAppStoragePath() . 'runtime/');

            $site_favicon_ico_filename = ctx()->getAppStorageRealPath() . 'view' . APP_DS . trim(str_replace('/', APP_DS, ctx()->getRequest()->server('REQUEST_URI')), APP_DS);
            $favicon_ico_filename = APP_REAL_PATH . trim(str_replace('/', APP_DS, ctx()->getRequest()->server('REQUEST_URI')), APP_DS);
            $this->log(APP_DEBUG, $site_favicon_ico_filename . ' ' . $favicon_ico_filename, 'ico');

            if (ctx()->getRequest()->server('REQUEST_URI') == '/health.shtml') {
                ctx()->getResponse()->end("ok");
                return;
            }

            if (ctx()->getRequest()->server('REQUEST_URI') == '/favicon.ico') {
                if (file_exists($site_favicon_ico_filename)) {
                    ctx()->getResponse()->end(file_get_contents($site_favicon_ico_filename));
                } elseif (file_exists($favicon_ico_filename)) {
                    ctx()->getResponse()->end(file_get_contents($favicon_ico_filename));
                } else {
                    ctx()->getResponse()->status(404);
                    ctx()->getResponse()->end();
                }
                return;
            }

            //如果请求为针对'php' , 'inc' , 'shtml'源文件的请求则直接返回404错误
            $extension = strtolower(pathinfo($favicon_ico_filename, PATHINFO_EXTENSION));
            if (is_file($favicon_ico_filename)) {
                if (!in_array($extension, getConfig('app', 'APP_PHP_EXTENSIONS'))) {
                    ctx()->getResponse()->end(file_get_contents($favicon_ico_filename));
                } else {
                    ctx()->getResponse()->status(404);
                    ctx()->getResponse()->end();
                }
                return;
            }


            if (getConfig('app', 'APP_CROSS_DOMAIN_ALLOW')) {
                if (getConfig('app', 'APP_CROSS_DOMAIN_ALLOW_ORIGINS') == "*") {
                    ctx()->getResponse()->addHeader("Access-Control-Allow-Origin", "*");
                } else {
                    $allow_hosts = explode(',', strtolower(getConfig('app', 'APP_CROSS_DOMAIN_ALLOW_ORIGINS')));
                    if (in_array(strtolower(ctx()->getRequest()->server('REQUEST_SCHEME') . "://" . ctx()->getRequest()->server("HTTP_HOST")), $allow_hosts)) {
                        ctx()->getResponse()->addHeader("Access-Control-Allow-Origin", strtolower(ctx()->getRequest()->server('REQUEST_SCHEME') . "://" . ctx()->getRequest()->server("HTTP_HOST")));
                    }
                }
                ctx()->getResponse()->addHeader("Access-Control-Allow-Methods", getConfig('app', 'APP_CROSS_DOMAIN_ALLOW_METHODS'));
                ctx()->getResponse()->addHeader("Access-Control-Allow-Headers", "Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Accept,Accept-Language,Origin,Accept-Encoding," . getConfig('app', 'APP_CROSS_DOMAIN_ALLOW_HEADERS'));
                ctx()->getResponse()->addHeader("Access-Control-Expose-Headers", getConfig('app', 'APP_CROSS_DOMAIN_ALLOW_HEADERS'));
            }
            ctx()->getSession()->initSession();

            //加载核心语言包
            ctx()->getI18n()->getCoreLang('core');


            //加载全局语言包
            ctx()->getI18n()->getGlobalLang("global");

            //触发before_launch钩子事件，如果事件返回true，则继续执行

            if (!$this->loadCore('Hook')->call_hook('before_launch')) {
                ctx()->getResponse()->end();
                return;
            }


            //检查是否cli模式
            if (strtolower(php_sapi_name()) == 'cli') {
                ctx()->setIsCli(true);
            }


            @ini_set("session.gc_maxlifetime", getConfig('app', 'APP_SESSION_LIFETIME_SECONDS'));


            // parse controller
            $route_datas = $this->loadCore('Router')->parse_controller();
            if (is_string($route_datas) && is_file($route_datas)) {
                //为渲染静态文件，直接结束本次请求
                ctx()->getResponse()->end(file_get_contents($route_datas));
                self::destoryContext();
                return;
            }

            // fire hook event
            if (!$this->loadCore('Hook')->call_hook('override_router')) {
                ctx()->getResponse()->end();
                return;
            }
            ctx()->setRouter($route_datas);

            $classes = $route_datas['controller'];
            $action = $route_datas['act'];
            $params = $route_datas['params'];

            // reflect call
            $controller = new $classes;

            // compress output
            if (!isset($controller->_is_ob_start) || !$controller->_is_ob_start) {
                ctx()->getResponse()->_is_ob_start = false;
            }
            if (ctx()->getResponse()->_is_ob_start) {
                ctx()->getResponse()->ob_start();
            }

            $client_version_suffix = '';
            $clinetVersion = ctx()->getRequest()->getClinetVersion();

            $curr_client_version = 0;
            if ($clinetVersion > 0) {
                $client_version_suffixs = $this->cache('shop_0_shop_actions', array($classes, $action, $clinetVersion));
                if (!is_array($client_version_suffixs)) {
                    $APP_SHOP_VERSION_LISTS = getConfig('version', 'APP_VERSION_LISTS');
                    if ($APP_SHOP_VERSION_LISTS) {
                        for ($i = count($APP_SHOP_VERSION_LISTS) - 1; $i >= 0; $i--) {
                            $version = $APP_SHOP_VERSION_LISTS[$i];
                            if ($version <= $clinetVersion) {
                                if (method_exists($controller, '_' . $action . '_' . str_ireplace('.', '_', $version))) {
                                    $client_version_suffix = '_' . str_ireplace('.', '_', $version);
                                    $curr_client_version = $version;
                                    break;
                                }
                            }
                        }
                        $client_version_suffixs = null;
                        $client_version_suffixs['client_version_suffix'] = $client_version_suffix;
                        $client_version_suffixs['curr_client_version'] = $curr_client_version;

                        $this->cache('shop_0_shop_actions', array($classes, $action, $clinetVersion), $client_version_suffixs);
                    }
                }
                $client_version_suffix = $client_version_suffixs['client_version_suffix'];
                $curr_client_version = $client_version_suffixs['curr_client_version'];
            }

            $action = $action . $client_version_suffix;


            $is_allow_cache_view = false;
            //视图缓存处理
            if (isset($controller->_is_ob_start) && $controller->_is_ob_start) {
                $VIEW_CACHE = getConfig('view', 'VIEW_CACHE');
                $VIEW_BUILD_RENAME = getConfig('view', 'VIEW_BUILD_RENAME');
                if (ctx()->isCli() && !APP_IS_IN_SWOOLE) {
                    $view_cache_filename = md5($_SERVER['argv'][0] . isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '');
                } else {
                    $view_cache_filename = md5(ctx()->getRequest()->server('REQUEST_URI'));
                }

                if (strtolower($VIEW_BUILD_RENAME) != "md5") {
                    throw new \cn\eunionz\exception\ViewException(ctx()->getI18n()->getLang('view_cache_filename_mode_title'), ctx()->getI18n()->getLang('view_cache_filename_mode'));
                }

                $view_cache_file = ctx()->getAppRuntimeRealPath() . getConfig('view', 'VIEW_CACHE_DIR') . APP_DS . $view_cache_filename . getConfig('view', 'VIEW_HTML_SUFFIX');
                $view_cache_json_file = ctx()->getAppRuntimeRealPath() . getConfig('view', 'VIEW_CACHE_DIR') . APP_DS . $view_cache_filename . '_json' . getConfig('view', 'VIEW_HTML_SUFFIX');

                $VIEW_CACHE_EXPIRES = (isset($controller->_cache_view_lifetime)) ? $controller->_cache_view_lifetime : getConfig('view', 'VIEW_CACHE_EXPIRES');

                if ($VIEW_CACHE) {
                    if ($controller->is_cache_view) {
                        if (!is_array($controller->_disabled_cache_view_actions) || !in_array('_' . $route_datas['act'], $controller->_disabled_cache_view_actions)) {
                            $is_allow_cache_view = true;
                            if (isset($controller->_enabled_cache_view_actions['_' . $route_datas['act']])) {
                                $VIEW_CACHE_EXPIRES = intval($controller->_enabled_cache_view_actions['_' . $route_datas['act']]);
                            }
                        }
                    }
                }

                if ($is_allow_cache_view) {
                    $cache_file = '';
                    $is_cache_json = false;
                    if (is_file($view_cache_json_file)) {
                        $cache_file = $view_cache_json_file;
                        $is_cache_json = true;
                    } elseif (is_file($view_cache_file)) {
                        $cache_file = $view_cache_file;
                    }

                    if ($cache_file) {
                        if ($VIEW_CACHE_EXPIRES == 0 || ((time() - filemtime($cache_file)) < $VIEW_CACHE_EXPIRES)) {
                            $timer = ctx()->getUseSeconds();
                            $html = file_get_contents($cache_file);
                            $html = str_replace("__PAGE_EXECUTE_SECONDS__", $timer, $html);
                            $html = str_replace("__PAGE_EXECUTE_QUERYS__", ctx()->getExecuteQuerys(), $html);
                            $trace_html = '';
                            if (!$is_cache_json) {
                                $trace_html = ctx()->outputTrace($controller, true);
                            } else {
                                ctx()->getResponse()->addHeader("Content-Type", "application/json");
                            }
                            // fire hook event
                            $this->loadCore('Hook')->call_hook('after_controller');

                            // output content
                            $this->loadCore('Hook')->call_hook('override_output');

                            // fire hook event
                            $this->loadCore('Hook')->call_hook('after_launch');
                            ctx()->getResponse()->ob_end_clean();
                            ctx()->getResponse()->end($html . $trace_html);
                            return;
                        }
                    }
                }
            }


            if (isset($controller->_is_filter) && $controller->_is_filter) {
                // security filtering
                $this->loadCore('security')->global_filtering();
            }
            ctx()->getSession()->session('APP_CLIENT_VERSION', $clinetVersion);
            ctx()->getSession()->session('APP_CURR_CLIENT_VERSION', $curr_client_version);
            ctx()->getSession()->session('APP_CONTROLLER_ACTION', $action);

            if (method_exists($controller, 'initialize')) {
                $method = new \ReflectionMethod($controller, 'initialize');

                // call
                $method->invokeArgs($controller, array());
            }


            if (!method_exists($controller, '_' . $action)) {
                $controller->is_invalid_method_call = true;
                throw new \cn\eunionz\exception\MethodNotFoundException(ctx()->getI18n()->getLang('error_router_title'), ctx()->getI18n()->getLang('error_router_controller_miss_method', array($classes, '_' . $action)));
            }

            // get method
            $method = new \ReflectionMethod($controller, '_' . $action);

            // call
            $method->invokeArgs(
                $controller,
                array_pad($params, $method->getNumberOfParameters(), null)
            );

            ctx()->getSession()->saveSession();

            // fire hook event
            $this->loadCore('Hook')->call_hook('after_controller');

            // output content
            $this->loadCore('Hook')->call_hook('override_output');

            // fire hook event
            $this->loadCore('Hook')->call_hook('after_launch');

            if (ctx()->getResponse()->_is_ob_start) {
                if ($is_allow_cache_view) {
                    @unlink($view_cache_file);
                    @unlink($is_allow_cache_view);
                    if ($controller->_is_json_return) {
                        file_put_contents($view_cache_json_file, ctx()->getResponse()->ob_get_contents(), LOCK_EX);
                    } else {
                        file_put_contents($view_cache_file, ctx()->getResponse()->ob_get_contents(), LOCK_EX);
                    }
                }
                $timer = ctx()->execTimeElapsed('system_launch');
                $html = str_replace("__PAGE_EXECUTE_SECONDS__", $timer, ctx()->getResponse()->ob_get_contents());
                $html = str_replace("__PAGE_EXECUTE_QUERYS__", ctx()->getExecuteQuerys(), $html);
                ctx()->getResponse()->ob_end_clean();
                $trace_html = '';
                if (!$controller->_is_json_return) {
                    $trace_html = ctx()->outputTrace($controller, true);
                }
                ctx()->getResponse()->write($html . $trace_html);
            } else {
                if (!$controller->_is_json_return) {
                    $trace_html = ctx()->outputTrace($controller, true);
                    ctx()->getResponse()->write($trace_html);
                }
            }
        } catch (\Exception $err) {
            $error_code = '500';
            if (($err instanceof ControllerNotFoundException) || ($err instanceof MethodNotFoundException)) {
                $error_code = '404';
            }

            ctx()->getSession()->saveSession();
            @ob_end_clean();
            $client_type = 'web';
            if (ctx()->getSession()->session('CLIENT_TYPE')) $client_type = ctx()->getSession()->session('CLIENT_TYPE');
            //加载核心语言包
            ctx()->getI18n()->getCoreLang('core');
            //加载全局语言包
            ctx()->getI18n()->getGlobalLang("global");

            $this->cache()->clearCache();
            $errorObj = array();
            $errorObj['title'] = '';
            if (method_exists($err, 'getTitle')) {
                $errorObj['title'] = $err->getTitle();
            }
            $errorObj['message'] = $err->getMessage();
            $errorObj['code'] = $err->getCode();
            $errorObj['file'] = $err->getFile();
            $errorObj['line'] = $err->getLine();
            $errorObj['trace'] = $err->getTraceAsString();
            ctx()->setGlobalException($err);
            // fire hook event
            $this->loadCore('Hook')->call_hook('after_controller');

            // output content
            $this->loadCore('Hook')->call_hook('override_output');

            // fire hook event
            $this->loadCore('Hook')->call_hook('after_launch');

            $arr = array(
                'Exception' => $errorObj,
                'PLANTCSS' => 'platform',
                'core' => $controller ? $controller : $this
            );
            $html = $this->loadComponent('blade')->display('error/error_' . $client_type . '_' . $error_code, $arr, true);
            $trace_html = ctx()->outputTrace($this->loadComponent('blade'), true);
            ctx()->getResponse()->write($html . $trace_html);
        }
        ctx()->getResponse()->end();
        self::destoryContext();
    }

    /**
     * 当主服务器结束时触发该事件
     * 说明：
     *       仅主服务器可设置 onEnd 事件回调
     *       主要用于做主服务器结束前的一些处理
     */
    public function onEnd()
    {

    }

    /**
     * websocket服务器的 onMessage事件
     * @param $server  当前服务器对像
     * @param $frame   接收到的客户端数据帧
     * @param $cfg     服务器配置
     */
    public function onMessage($server, $frame, $cfg)
    {
        $websocket_return = [
            'status' => 0,
            'msg' => '',
            'header' => [],
        ];


        @libxml_disable_entity_loader(true);
        //初始化请求对像及响应对像，有状态对像必须为短暂生命周期对像，无状态对像可为单例生命周期对像
        self::setRequestFd($frame->fd);
        $controller = null;
        $ctx = new Context();
        $ctx->addTimeNode('system_launch');
        $ctx->setRequest(new Request(null, $cfg));
        $ctx->setI18n(new I18n());
        $ctx->setSession(new Session());
        self::setContext($ctx);
        $header = [];
        try {
            $data = json_decode($frame->data, true);
            $opcode = $frame->opcode;
            $finish = $frame->finish;


            $header = $data['header'] ?? [];
            $accept_data = $data['body'] ?? '';
            if (!$header) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get header，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }

            if (!$accept_data) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get body，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }


            //当前店铺ID，如果店铺为-1,则返回错误
            $shop_id = (isset($header['shopid']) && $header['shopid']) ? $header['shopid'] : -1;
            if ($shop_id < 0) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get header shopid，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }


            $url = (isset($header['url']) && $header['url']) ? $header['url'] : '';
            if (!$url) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get header url，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }
            $urls = parse_url($url);
            $url = isset($urls['path']) ? $urls['path'] : '';
            if (isset($urls['query']) && $urls['query']) {
                $querys = explode('&', $urls['query']);
                foreach ($querys as $value) {
                    $arr_values = explode('=', $value);
                    ctx()->getRequest()->get($arr_values[0], isset($arr_values[1]) ? $arr_values[1] : '');
                    ctx()->getRequest()->request($arr_values[0], isset($arr_values[1]) ? $arr_values[1] : '');
                }
            }


            //当前会话ID，如果为空则将生成新会话，否则基于会话ID连接已经存在的会话
            $session_id = (isset($header['sessionid']) && $header['sessionid']) ? $header['sessionid'] : '';
            if ($shop_id < 0) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get header sessionid，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }
            $session_name = (isset($header['sessionname']) && $header['sessionname']) ? $header['sessionname'] : '';
            if (empty($session_name)) {
                $websocket_return['status'] = 1;
                $websocket_return['msg'] = "not get header sessionname，call websocket service failure!";
                $websocket_return['header'] = $header;
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }


            ctx()->getSession()->session_id($session_id);
            ctx()->getSession()->session_name($session_name);
            ctx()->getSession()->rpcInitSession();


            ctx()->get('APP_SHOP_ID', $shop_id);

            //当前语言，如果为空则使用默认语言
            $appLanguage = (isset($header['applanguage']) && $header['applanguage']) ? $header['applanguage'] : ctx()->getDefaultLanguage();
            ctx()->get('APP_LANGUAGE', $appLanguage);


            //当前终端，如果为空则使用pc终端
            $clientType = (isset($header['clienttype']) && $header['clienttype']) ? $header['clienttype'] : 'pc';
            ctx()->get('APP_CLIENT_TYPE', $clientType);


            //当前客户端接口调用版本号，如果为空则使用0
            $clinetVersion = (isset($header['clientversion']) && $header['clientversion']) ? $header['clientversion'] : 0;
            ctx()->get('APP_CLIENT_VERSION', $clinetVersion);

            ctx()->setShopID($shop_id);

            $site_name = (empty($shop_id) ? getConfig('app', 'APP_SHOP_ID_ZERO_FOLDER_NAME') : $shop_id);
            ctx()->setSiteName($site_name);

            //当前站点临时文件夹物理路径
            ctx()->setAppStorageRealPath(APP_STORAGE_REAL_PATH . $site_name . APP_DS);

            //当前站点运行时文件夹物理路径，该文件夹位于 APP_CURRENT_SITE_TEMP_REAL_PATH 下
            ctx()->setAppRuntimeRealPath(ctx()->getAppStorageRealPath() . APP_RUNTIME_NAME . APP_DS);


            if (!is_dir(ctx()->getAppStorageRealPath())) {
                @mkdir(ctx()->getAppStorageRealPath(), 0x777);
                @mkdir(ctx()->getAppStorageRealPath() . 'view', 0x777);
            }

            //定义当前SHOP_ID对应的临时文件夹路径
            ctx()->setAppStoragePath(APP_PATH . APP_STORAGE_NAME . '/' . $site_name . '/');

            //初始化运行时文件夹
            if (!is_dir(ctx()->getAppRuntimeRealPath())) {
                @mkdir(ctx()->getAppRuntimeRealPath());
                @mkdir(ctx()->getAppRuntimeRealPath() . 'cache');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'html');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'logs');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'session');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'uploads');
                @mkdir(ctx()->getAppRuntimeRealPath() . 'templates_c');
            }


            //定义当前SHOP_ID运行时文件夹路径
            ctx()->setAppRuntimePath(ctx()->getAppStoragePath() . 'runtime/');

            //加载核心语言包
            ctx()->getI18n()->getCoreLang('core');


            //加载全局语言包
            ctx()->getI18n()->getGlobalLang("global");

            //检查是否cli模式
            if (strtolower(php_sapi_name()) == 'cli') {
                ctx()->setIsCli(true);
            }

            @ini_set("session.gc_maxlifetime", getConfig('app', 'APP_SESSION_LIFETIME_SECONDS'));

            // parse controller
            $route_datas = $this->loadCore('Router')->parse_controller($url);
            if (is_string($route_datas) && is_file($route_datas)) {
                //为渲染静态文件，直接结束本次请求
                $websocket_return['body'] = file_get_contents($route_datas);
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }

            ctx()->setRouter($route_datas);

            $service_class = $route_datas['controller'];
            $action = $route_datas['act'];
            $params = $route_datas['params'];

            if (!class_exists($service_class)) {
                $websocket_return['status'] = 4;
                $websocket_return['msg'] = $this->getLang('error_class_not_found', array($service_class));
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }
            // reflect call
            $ref_class = new \ReflectionClass($service_class);
            $comment = $ref_class->getDocComment();
            $docs = $this->loadComponent('docparser')->parse($comment);
            if (!isset($docs['WEBSOCKET_CLASS'])) {
                $websocket_return['status'] = 4;
                $websocket_return['msg'] = $this->getLang('error_websocket_class_not_found', array($service_class));
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }

            $controller = new $service_class;

            $curr_client_version = 0;
            $client_version_suffix = '';
            if ($clinetVersion > 0) {
                $client_version_suffixs = $this->cache('shop_0_shop_actions', array($service_class, $action, $clinetVersion));
                if (!is_array($client_version_suffixs)) {
                    $APP_SHOP_VERSION_LISTS = getConfig('version', 'APP_VERSION_LISTS');
                    if ($APP_SHOP_VERSION_LISTS) {
                        for ($i = count($APP_SHOP_VERSION_LISTS) - 1; $i >= 0; $i--) {
                            $version = $APP_SHOP_VERSION_LISTS[$i];
                            if ($version <= $clinetVersion) {
                                if (method_exists($controller, '_' . $action . '_' . str_ireplace('.', '_', $version))) {
                                    $client_version_suffix = '_' . str_ireplace('.', '_', $version);
                                    $curr_client_version = $version;
                                    break;
                                }
                            }
                        }
                        $client_version_suffixs = null;
                        $client_version_suffixs['client_version_suffix'] = $client_version_suffix;
                        $client_version_suffixs['curr_client_version'] = $curr_client_version;

                        $this->cache('shop_0_shop_actions', array($service_class, $action, $clinetVersion), $client_version_suffixs);
                    }
                }
                $client_version_suffix = $client_version_suffixs['client_version_suffix'];
                $curr_client_version = $client_version_suffixs['curr_client_version'];
            }

            $action = $action . $client_version_suffix;


            $ref_method = new \ReflectionMethod($service_class, '_' . $action);
            $comment = $ref_method->getDocComment();
            $docs = $this->loadComponent('docparser')->parse($comment);

            if (!isset($docs['WEBSOCKET_METHOD'])) {
                $websocket_return['status'] = 2;
                $websocket_return['msg'] = $this->getLang('error_websocket_class_method_not_found', array($service_class, '_' . $action));
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }

            //加载类语言包
            $this->getControllerLang($service_class);


            if (!method_exists($controller, '_' . $action)) {
                $websocket_return['status'] = 2;
                $websocket_return['msg'] = $this->getLang('error_class_method_not_found', array($service_class, '_' . $action));
                $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
                self::destoryContext();
                return;
            }
            $controller->accept_data = $accept_data;
            $controller->header = $header;
            $controller->url = $url;

            if (method_exists($controller, 'initialize')) {
                $method = new \ReflectionMethod($controller, 'initialize');
                $method->invokeArgs($controller, array());
            }



            // get method
            $method = new \ReflectionMethod($controller, '_' . $action);

            // call
            $rs = @$method->invokeArgs(
                $controller,
                array_pad($params, $method->getNumberOfParameters(), null)
            );
            $websocket_return['header'] = $header;
            $websocket_return['return'] = $rs;


            $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
            ctx()->getSession()->saveSession();
            self::destoryContext();
            return;

        } catch (\Exception $err) {
            $websocket_return['status'] = 1;
            $websocket_return['msg'] = $err->getMessage();
            $websocket_return['header'] = $header;
            $server->push($frame->fd, json_encode($websocket_return), $opcode, 1);
            self::destoryContext();
            return;
        }
        self::destoryContext();
        return;
    }

    /**
     * WebSocket服务器的 onOpen 事件 握手事件
     * @param $server
     * @param $request
     * @param $cfg
     */
    public function onOpen($server, $request, $cfg)
    {
//        consoleln($request->server['request_uri'] . ' onopen rid:' . self::getRequestUniqueId(), APP_INFO);
//        consoleln(print_r($request , true), APP_INFO);

    }


}
