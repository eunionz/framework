<?php declare(strict_types=1);
/**
 * Eunionz PHP Framework Kernel class(will save config data with singleton mode,and supply some quick method to visit session,request,or other )
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\core;

use cn\eunionz\exception\ControllerNotFoundException;

defined('APP_IN') or exit('Access Denied');

class Kernel
{
    use KernelTrait;

    /**
     * 单例模式 Swoole 主服务器对像
     * @var null
     */
    protected static $_swoole_main_server = null;

    /**
     * 单例模式 Swoole 其它服务器对像集
     * @var null
     */
    protected static $_swoole_other_servers = [];


    /**
     * 单例模式全局核心对像
     * @var array
     */
    private static $_core_objects = array();


    /**
     * 单例模式组件对像
     * @var array
     */
    private static $_component_objects = array();


    /**
     * 单例模式服务对像
     * @var array
     */
    private static $_service_objects = array();


    /**
     * 单例模式插件对像
     * @var array
     */
    private static $_plugin_objects = array();


    /**
     * 单例模式模型对像
     * @var array
     */
    private static $_model_objects = array();


    /**
     * 基于当前请求FD保存的单例模式的上下文对像集合
     * @var array(RequestFD=>Context,...)
     */
    protected static $contexts = array();

    /**
     * 基于当前进程中唯一协程ID保持对应的请求FD集合，无论是http/https/websocket请求对将在该集合中保存协程ID与请求FD之间的关系
     * @var array
     */
    protected static $request_fds = array();

    /**
     * 分布式锁单例对像
     * @var null
     */
    private static $deploy_lock_object = null;

    /**
     * eunionz log
     */
    private const FONT_LOGO = "
    _ _ _ _                
   |  _ _ _|   _     _    _ _ _ _    _     ___     _ _ _ _    _ _ _ _   Version " . APP_FRAMEWORK_VERSION . "
   | |_ _ _   | |   | |  | | _ | |  |_|   / _ \   | | _ | |  |_ _ _  | 
   |  _ _ _|  | |   | |  | |   | |  | |  | ( ) |  | |   | |      /  /
   | |_ _ _   | | _ | |  | |   | |  | |  | (_) |  | |   | |    /  /_ 
   |_ _ _ _|   \ _ _ /   |_|   |_|  |_|   \___/   |_|   |_|  /_ _ _ _| 
";

    private static $foreground_colors = [
        'black' => '0;30', 'dark_gray' => '1;30', 'blue' => '0;34', 'light_blue' => '1;34', 'green' => '0;32',
        'light_green' => '1;32', 'cyan' => '0;36', 'light_cyan' => '1;36', 'red' => '0;31', 'light_red' => '1;31',
        'purple' => '0;35', 'light_purple' => '1;35', 'brown' => '0;33', 'yellow' => '1;33', 'light_gray' => '0;37', 'white' => '1;37',
    ];
    private static $background_colors = ['black' => '40', 'red' => '41', 'green' => '42', 'yellow' => '43', 'blue' => '44', 'magenta' => '45', 'cyan' => '46', 'light_gray' => '47',];


    public static function showLogo(): void
    {
        self::console(self::FONT_LOGO);
    }


    // Returns colored string
    public static function getColoredString(string $string, string $foreground_color = 'light_green', string $background_color = null): string
    {
        $colored_string = "";

        // Check if given foreground color found
        if (isset(self::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(self::$background_colors[$background_color])) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }


    public function __construct()
    {

    }

    public static function setContext(Context $ctx): void
    {
        self::$contexts[self::getRequestFd()] = $ctx;
    }

    public static function getContext(): ?Context
    {
        if (isset(self::$contexts[self::getRequestFd()])) {
            return self::$contexts[self::getRequestFd()];
        }
        return null;
    }

    public static function destoryContext(): void
    {
        if (isset(self::$contexts[self::getRequestFd()])) {
            unset(self::$contexts[self::getRequestFd()]);
        }
        if (isset(self::$_app_config_settings[self::getRequestFd()])) {
            unset(self::$_app_config_settings[self::getRequestFd()]);
        }
        unset(self::$request_fds[self::getRequestUniqueId()]);
    }

    /**
     * 获取框架版本号
     * @return string
     */
    public function getVersion(): string
    {
        return APP_FRAMEWORK_VERSION;
    }

    /**
     * 加载核心类
     * @param $class  类名称，首字母小写,不包含类路径
     * @param bool $single 是否单例模式
     * @return mixed 返回对像
     */
    public final function loadCore(string $class, bool $single = true): ?Kernel
    {
        if (strpos($class, '\\') === false) {
            $class = "\\cn\\eunionz\\core\\" . ucfirst($class);
        }
        if ($single && isset(self::$_core_objects[$class])) {
            return self::$_core_objects[$class];
        }

        $obj = new $class;
        if (method_exists($obj, 'initialize')) {
            $obj->initialize();
        }
        if (!$single) {
            return $obj;
        }
        self::$_core_objects[$class] = $obj;
        return self::$_core_objects[$class];
    }

    /**
     * 加载组件
     * @param $class
     * @param bool $single
     * @return mixed
     */
    public function loadComponent(string $class, bool $single = true): ?Component
    {
        if (false === stripos($class, "\\")) {
            $class = "\\cn\\eunionz\\component\\" . strtolower($class) . '\\' . ucfirst($class);
        }

        if ($single && isset(self::$_component_objects[$class])) {
            return self::$_component_objects[$class];
        }
        $temps = explode("\\", $class);

        $obj = new $class;
        if (method_exists($obj, 'initialize')) {
            $obj->initialize($temps[count($temps) - 1]);
        }
        if (!$single)
            return $obj;

        self::$_component_objects[$class] = $obj;

        return self::$_component_objects[$class];
    }

    /**
     * 加载服务
     * @param $class
     * @param bool $single
     * @return Service
     */
    public function loadService(string $class, bool $single = true): ?Service
    {
        if (stripos($class, "\\") === false) {
            $class = "\\package\\service\\" . strtolower($class) . '\\' . ucfirst($class);
        }

        if ($single && isset(self::$_service_objects[$class]))
            return self::$_service_objects[$class];

        $temps = explode("\\", $class);
        // service base path

        $file = APP_PACKAGE_BASE_PATH . ltrim(str_replace("\\", APP_DS, $class), APP_DS) . '.class.php';


        if (is_file($file)) {
            $obj = new $class;
        } else {
            $obj = new Service();
        }

        if (method_exists($obj, 'initialize')) {
            $obj->initialize($temps[count($temps) - 1]);
        }
        if (!$single) {
            return $obj;
        }
        self::$_service_objects[$class] = $obj;
        return self::$_service_objects[$class];

    }

    /**
     * 加载插件
     * @param $class
     * @param bool $single
     * @return Plugin
     */
    public function loadPlugin(string $class, bool $single = true): ?Plugin
    {
        if (stripos($class, "\\") === false) {
            $class = "\\package\\plugin\\" . strtolower($class) . '\\' . ucfirst($class);
        }

        if ($single && isset(self::$_plugin_objects[$class]))
            return self::$_plugin_objects[$class];

        $temps = explode("\\", $class);

        // plugin base path
        $file = APP_PACKAGE_BASE_PATH . ltrim(str_replace("\\", APP_DS, $class), APP_DS) . '.class.php';
        if (is_file($file)) {
            $obj = new $class;
        } else {
            $obj = new Plugin();
        }

        if (method_exists($obj, 'initialize')) {
            $obj->initialize($temps[count($temps) - 1]);
        }

        if (!$single)
            return $obj;

        self::$_plugin_objects[$class] = $obj;

        return self::$_plugin_objects[$class];
    }

    /**
     * 加载模型
     * @param $class
     * @param bool $single
     * @param null $APP_ASSEMBLY_NAME
     * @return Model
     */
    public function loadModel(string $class, bool $single = true): ?Model
    {
        if (stripos($class, "\\") === false) {
            $class = "\\package\\model\\" . ucfirst($class);
        }

        if ($single && isset(self::$_model_objects[$class]))
            return self::$_model_objects[$class];

        $temps = explode("\\", $class);

        // model base path
        $file = APP_PACKAGE_BASE_PATH . ltrim(str_replace("\\", APP_DS, $class), APP_DS) . '.class.php';

        if (is_file($file)) {
            $obj = new $class;
        } else {
            $obj = new Model();
        }

        if (method_exists($obj, 'initialize')) {
            $obj->initialize(strtolower($temps[count($temps) - 1]));
        }
        if (!$single) {
            return $obj;
        }

        self::$_model_objects[$class] = $obj;
        return self::$_model_objects[$class];
    }


    /**
     * 加载Action控制器
     * @param $url
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $request
     * @return mixed
     * @throws \ReflectionException
     */
    public function loadActionByUrl(string $url, array $get = array(), array $post = array(), array $files = array(), array $request = array())
    {
        $old_request = ctx()->getRequest()->request();
        $old_get = ctx()->getRequest()->get();
        $old_post = ctx()->getRequest()->post();
        $old_files = ctx()->getRequest()->files();
        if ($request) ctx()->setRequest(array_merge($old_request, $request));
        if ($get) ctx()->setRequest(array_merge($old_get, $get));
        if ($post) ctx()->setRequest(array_merge($old_post, $post));
        if ($files) ctx()->setRequest(array_merge($old_files, $files));

        $route_datas = $this->loadCore('Router')->parse_controller($url);
        if (is_string($route_datas) && is_file($route_datas)) {
            return file_get_contents($route_datas);
        }
        ctx()->setRouter($route_datas);
        $classes = $route_datas['controller'];
        $action = $route_datas['act'];
        $params = $route_datas['params'];

        $old_trace_output_status = ctx()->getTraceOutput();

        // reflect call
        $controller = new $classes;
        // compress output
        ctx()->getResponse()->_is_ob_start = true;
        if (ctx()->getResponse()->_is_ob_start) {
            ctx()->getResponse()->ob_start();
        }
        ctx()->closeTraceOutput();

        $client_version_suffix = '';
        $clinetVersion = ctx()->getRequest()->getClinetVersion();

        $curr_client_version = 0;
        if ($clinetVersion > 0) {
            $client_version_suffixs = $this->cache('shop_0_shop_actions', array($classes, $action, $clinetVersion));
            if (!is_array($client_version_suffixs)) {
                $APP_SHOP_VERSION_LISTS = self::getConfig('version', 'APP_VERSION_LISTS');
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
        }

        $action = $action . $client_version_suffix;

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
        $return = $method->invokeArgs(
            $controller,
            array_pad($params, $method->getNumberOfParameters(), null)
        );

        if (ctx()->getResponse()->_is_ob_start) {
            $return = ctx()->getResponse()->ob_end_clean();
        }
        $controller->free();
        ctx()->getRequest()->setRequest($old_request);
        ctx()->getRequest()->setGet($old_get);
        ctx()->getRequest()->setPost($old_post);
        ctx()->getRequest()->setFiles($old_files);
        ctx()->setTraceOutput($old_trace_output_status);
        return $return;
    }


    /**
     * 加载Action控制器
     *
     *     'controller' => $ClassName,
     * 'act' => $action,
     * 'action' => '_' . $action,
     * 'params' => $uri,
     * 'path' => join('/', $classNameArr),
     * @param $url
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $request
     * @return mixed
     * @throws \ReflectionException
     */
    public function loadAction(string $classes, string $action, array $params = array(), array $get = array(), array $post = array(), array $files = array(), array $request = array())
    {
        if (!class_exists($classes)) {
            throw new ControllerNotFoundException(ctx()->getI18n()->getLang('error_router_title'), ctx()->getI18n()->getLang('error_router_controller', $classes));
        }
        if (!method_exists($classes, '_' . $action)) {
            throw new \cn\eunionz\exception\MethodNotFoundException(ctx()->getI18n()->getLang('error_router_title'), ctx()->getI18n()->getLang('error_router_controller_miss_method', array($classes, '_' . $action)));
        }
        $path = self::getRouterPathByControllerClass($classes);
        $url = "/" . $path . '/' . $action . '/' . trim(implode('/', $params), '/') . URL_HTML_SUFFIX;
        $url = str_replace("/" . URL_HTML_SUFFIX, URL_HTML_SUFFIX, $url);
        return $this->loadActionByUrl($url, $get, $post, $files, $request);
    }

    /**
     * 加载常量
     */
    public function loadConstrants(): void
    {
        $constants_path = APP_PACKAGE_REAL_PATH . 'constants';
        if (is_dir($constants_path)) {
            $dir = opendir($constants_path);
            if ($dir) {
                while ($filename = readdir($dir)) {
                    if ($filename != '.' && $filename != '..') {
                        if (endsWith(strtolower($filename), '.constants.php') && is_file($constants_path . APP_DS . $filename)) {
                            @require_once $constants_path . APP_DS . $filename;
                        }
                    }
                }
                closedir($dir);
            }

        }
    }


    /**
     * getServer 获取server对象
     * @param string $index
     * @return mixed|null
     */
    public static function getServer(string $index = 'main')
    {
        if (!APP_IS_IN_SWOOLE) return null;
        if (APP_IS_ONLY_ONE_SERVER) {
            if (self::$_swoole_main_server) {
                return self::$_swoole_main_server;
            }
        } else {
            if ($index == 'main') {
                if (self::$_swoole_main_server) {
                    return self::$_swoole_main_server;
                }
            } else {
                if (isset(self::$_swoole_other_servers[$index]) && self::$_swoole_other_servers[$index])
                    return self::$_swoole_other_servers[$index];
            }
        }
        return null;
    }


    /**
     * getConnections 获取指定服务器当前所有的连接
     * @param string $index
     * @return array
     */
    public static function getConnections(string $index = 'main')
    {
        if (self::getServer($index)) {
            return self::getServer($index)->connections;
        }
        return [];
    }

    /**
     * 获取当前worker的进程PID
     * @return int
     */
    public static function getCurrentWorkerPid(): int
    {
        if (APP_IS_IN_SWOOLE) {
            return posix_getpid();
        }
        return 1;
    }

    /**
     * 获取当前服务器主进程的PID
     * @return   int
     */
    public static function getMasterPid(): int
    {
        if (APP_IS_IN_SWOOLE) {
            return self::getServer()->master_pid;
        }
        return 1;
    }

    /**
     * 获取当前服务器管理进程的PID
     * @return   int
     */
    public static function getManagerPid(): int
    {
        if (APP_IS_IN_SWOOLE) {
            return self::getServer()->manager_pid;
        }
        return 1;

    }

    /**
     * 获取当前处理的worker_id
     * @return   int
     */
    public static function getCurrentWorkerId(): int
    {
        if (APP_IS_IN_SWOOLE) {
            return self::getServer()->worker_id;
        }
        return 1;
    }

    public static function getRequestUniqueId(): string
    {
        if (APP_IS_IN_SWOOLE) {
            return self::getCurrentWorkerPid() . '_' . \Swoole\Coroutine::getUid();
        } else {
            return "1";
        }

    }

    /**
     * 设置当前协程对应的请求FD
     * @param $fd
     */
    public static function setRequestFd(int $fd): void
    {
        self::$request_fds[self::getRequestUniqueId()] = $fd;
    }

    /**
     * 获取当前协程对应的请求FD
     */
    public static function getRequestFd(): int
    {
        if (isset(self::$request_fds[self::getRequestUniqueId()])) {
            return self::$request_fds[self::getRequestUniqueId()];
        } else {
            return 1;
        }
    }


    /**
     * 基于当前请求唯一PID保存的应用程序配置
     * @var array(getRequestUniqueId=>[])
     */
    private static $_app_config_settings = array();

    /**
     * 应用程序配置类型集
     * @var array
     */
    private static $_app_config_types = array();


    /**
     * 在内存中修改配置变量的值
     * @param $names 配置文件名
     * @param $key 配置变量名
     * @param $value 值
     */
    public static function setConfig(string $names, string $key, $value): void
    {
        self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names][$key] = $value;
    }

    /**
     * 加载/获取配置文件中配置项
     * 配置文件必须位于 /app/config文件夹(全局配置文件)  或/package/config(局部配置文件)，局部配置文件（优化级更高）将可能覆盖全局配置文件中配置项
     * @param string $name
     * @param string $key
     *
     * @return mixed
     */
    public static function getConfig(string $names, string $key = '')
    {
        $config_files = array();
        if (!isset(self::$_app_config_types[APP_APPLICATION_ENV][$names])) {
            //局部配置文件
            $local_config_file = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'config' . APP_DS . APP_APPLICATION_ENV . APP_DS . $names . '.config.php';
            if (!is_file($local_config_file)) {
                $local_config_file = '';
            }
            //全局配置文件
            $global_config_file = APP_PACKAGE_BASE_PATH . 'config' . APP_DS . APP_APPLICATION_ENV . APP_DS . $names . '.config.php';

            if (!is_file($global_config_file)) {
                $global_config_file = '';
            }

            if (!$local_config_file && !$global_config_file) {
                $i18n = new I18n(null);
                throw new \cn\eunionz\exception\FileNotFoundException($i18n->getLang('error_config_file_not_found', array($names)));
            }
            if ($global_config_file) {
                $config_files[] = $global_config_file;
            }
            if ($local_config_file) {
                $config_files[] = $local_config_file;
            }
            self::$_app_config_types[APP_APPLICATION_ENV][$names] = $config_files;
        } else {
            $config_files = self::$_app_config_types[APP_APPLICATION_ENV][$names];
        }

        if (!isset(self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names])) {
            $config_arrs = array();
            foreach ($config_files as $file) {
                if (is_file($file)) {
                    $tmp_arr = require($file);
                    $config_arrs = array_merge($config_arrs, $tmp_arr);
                }
            }
            self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names] = $config_arrs;
        }
        if (!$key)
            return self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names];

        if (!isset(self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names][$key]))
            return '';
        return self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names][$key];
    }


    /**
     * 重新加载配置文件获取配置项或值
     * 配置文件必须位于 /app/config文件夹(全局配置文件)  或/package/config(局部配置文件)，局部配置文件（优化级更高）将可能覆盖全局配置文件中配置项
     * @param string $name
     * @param string $key
     *
     * @return mixed
     */
    public static function reloadConfig(string $names, string $key = '')
    {
        unset(self::$_app_config_types[APP_APPLICATION_ENV][$names]);
        unset(self::$_app_config_settings[self::getRequestUniqueId()][APP_APPLICATION_ENV][$names]);
        return self::getConfig($names, $key);
    }

    /**
     * Write Log
     *
     * Write an log message to log file
     *
     * @param int $level 1--ERROR  2--WARNING  3--DEBUG  4--INFO   5--ALL
     * @param string $message
     *
     * @return bool
     */
    public function log(int $level = APP_ERROR, string $message, string $filename = ''): bool
    {
        return $this->loadCore('log')->log($level, $message, $filename);
    }


    /**
     * 向控制台输入调试信息不换行
     * @param int $level
     * @param $msg
     */
    public static function console(string $msg, int $level = 0, string $is_br = ''): string
    {
        if (self::getConfig('app', 'APP_LOG')) {
            switch ($level) {
                case APP_ERROR:
                    $title = '[ERROR  ]  ' . date('Y-m-d H:i:s') . ': ';
                    $color = 'light_red';
                    break;

                case APP_WARNING:
                    $title = '[WARNING]  ' . date('Y-m-d H:i:s') . ': ';
                    $color = 'yellow';
                    break;

                case APP_DEBUG:
                    $title = '[DEBUG  ]  ' . date('Y-m-d H:i:s') . ': ';
                    $color = 'light_cyan';
                    break;

                case APP_INFO:
                    $title = '[INFO   ]  ' . date('Y-m-d H:i:s') . ': ';
                    $color = '';
                    break;
                case 0:
                    $title = '';
                    $color = 'light_green';
                    break;
                default:
                    $title = '';
                    $color = '';
                    break;
            }
            if ($level > self::getConfig('app', 'APP_LOG_LEVEL'))
                return "";

            $str = self::getColoredString($title . $msg, $color);
            @file_put_contents("php://stdout", $str . $is_br);
            return $str . $is_br;
        }
        return "";
    }

    /**
     * 向控制台输入调试信息不换行
     * @param int $level
     * @param $msg
     */
    public static function consoleln(string $msg = '', int $level = 0): string
    {
        return self::console($msg, $level, PHP_EOL);
    }

    /**
     * 根据控制器完全限定类名获取路由路径
     * @param $controller_class
     * @return string
     */
    public static function getRouterPathByControllerClass(string $controller_class): string
    {
        $path = trim($controller_class, "\\");
        $path = str_replace("\\", "/", str_replace("package\\controller\\", "", $path));
        return strtolower($path);
    }


    /**
     * HTTP服务 get 调用
     * @param $serice_conf_name         HTTP服务器配置名称，http.config.php文件中的配置名称
     * @param $url                      HTTP服务器调用url
     * @param array $params HTTP服务器调用url的GET参数数组
     * @param bool $is_admin HTTP服务器调用url是否为后台URL
     * @param array $add_headers HTTP服务器调用url附加头部信息
     * @return array|mixed              返回HTTP服务器url输出结果
     * @throws \cn\eunionz\exception\BaseException
     */
    public function http_get_call(string $serice_conf_name, string $url, array $params = array(), bool $is_admin = false, array $add_headers = array())
    {
        $config = self::getConfig('http', $serice_conf_name);
        if(isset($config['host']) && isset($config['port']) && isset($config['is_ssl']) && isset($config['timeout'])){
            $httpclient = new \package\application\HttpClient($config['host'], $config['port'], $config['is_ssl'], $config['timeout']);
            return $httpclient->http_get($url, $params, $is_admin, $add_headers);
        }
        return "";
    }

    /**
     * HTTP服务 post 调用
     * @param $serice_conf_name         HTTP服务器配置名称，http.config.php文件中的配置名称
     * @param $url                      HTTP服务器调用url
     * @param array $params HTTP服务器调用url的POST参数数组
     * @param bool $is_admin HTTP服务器调用url是否为后台URL
     * @param array $add_headers HTTP服务器调用url附加头部信息
     * @param array $files 是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return array|mixed              返回HTTP服务器url输出结果
     * @throws \cn\eunionz\exception\BaseException
     */
    public function http_post_call(string $serice_conf_name, string $url, array $params = array(), bool $is_admin = false, array $add_headers = array(), array $files = array())
    {
        $config = self::getConfig('http', $serice_conf_name);
        if(isset($config['host']) && isset($config['port']) && isset($config['is_ssl']) && isset($config['timeout'])){
            $httpclient = new \package\application\HttpClient($config['host'], $config['port'], $config['is_ssl'], $config['timeout']);
            return $httpclient->http_post($url, $params, $is_admin, $add_headers, $files);
        }
        return "";
    }


    /**
     * 获取RPC客户端
     * @param $serice_conf_name  RPC服务器配置名称，rpc.config.php文件中的配置名称
     * @param $service_class     RPC服务器服务类完全限定名称
     * @return array|mixed       返回RPC服务类服务方法返回结果
     * @throws \cn\eunionz\exception\BaseException
     */
    public function rpc_client(string $serice_conf_name, string $service_class, array $add_headers = array())
    {
        $config = self::getConfig('rpc', $serice_conf_name);
        $rpcclient = new \package\application\RpcClient($config['host'], $config['port'], $config['timeout']);
        return $rpcclient->instance($service_class, $add_headers);
    }


    /**
     * RPC服务调用
     * @param $serice_conf_name  RPC服务器配置名称，rpc.config.php文件中的配置名称
     * @param $service_class     RPC服务器服务类完全限定名称
     * @param $service_method    RPC服务类服务方法名称
     * @param array $params RPC服务类服务方法参数数组
     * @param array $add_headers RPC服务类服务方法附加头部信息数组
     * @return array|mixed       返回RPC服务类服务方法返回结果
     * @throws \cn\eunionz\exception\BaseException
     */
    public function rpc_call(string $serice_conf_name, string $service_class, string $service_method, array $params = array(), array $add_headers = array())
    {
        $config = self::getConfig('rpc', $serice_conf_name);
        $rpcclient = new \package\application\RpcClient($config['host'], $config['port'], $config['timeout']);
        return $rpcclient->call($service_class, $service_method, $params, $add_headers);
    }


    /**
     * 检查指定进程名称进程是否存在
     * @param $process_name
     */
    public static function checkProcessExistsByName(string $process_name): bool
    {
        @exec("ps -ef|grep {$process_name}", $result);
        $sum = count($result);
        return $sum > 2 ? true : false;
    }

    /**
     * 加锁
     * @param [type] $name      锁的标识名
     * @param integer $timeout 循环获取锁的等待超时时间，在此时间内会一直尝试获取锁直到超时，为0表示失败后直接返回不等待
     * @param integer $expire 当前锁的最大生存时间(秒)，必须大于0，如果超过生存时间锁仍未被释放，则系统会自动强制释放
     * @param integer $waitIntervalUs 获取锁失败后挂起再试的时间间隔(微秒)
     * @return [type]         [description]
     */
    public function lock($name, $timeout = 10, $expire = 15, $waitIntervalUs = 100000)
    {
        if (!self::$deploy_lock_object) {
            self::$deploy_lock_object = new RedisLock();
        }
        return self::$deploy_lock_object->redis_lock($name, $timeout, $expire, $waitIntervalUs);
    }

    /**
     * 解锁
     * @param [type] $name [description]
     * @return [type]    [description]
     */

    public function unlock($name)
    {
        if (!self::$deploy_lock_object) {
            self::$deploy_lock_object = new RedisLock();
        }
        return self::$deploy_lock_object->redis_unlock($name);
    }
}
