<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Context class
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */

namespace cn\eunionz\core;


use cn\eunionz\component\grpc\Parser;

class Context extends Kernel
{

    private $is_grpc_response = false;

    /**
     * @return bool
     */
    public function isIsGrpcResponse(): bool
    {
        return $this->is_grpc_response;
    }

    /**
     * @param bool $is_grpc_response
     */
    public function setIsGrpcResponse(bool $is_grpc_response): void
    {
        $this->is_grpc_response = $is_grpc_response;
    }

    /**
     * 当前上下文请求对像
     * @var
     */
    private $request;

    /**
     * @return mixed
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }

    /**
     * 当前上下文响应对像
     * @var
     */
    private $response;

    /**
     * @return mixed
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }


    /**
     * 代码执行时间节点，用于对代码执行时间进行计算
     * @var array()
     */
    private $_execute_time_nodes = array();


    /**
     * 增加时间节点，用于统计代码执行时间
     * @param $node_name 节点名称
     */
    public final function addTimeNode($node_name)
    {
        $this->_execute_time_nodes[$node_name] = microtime();
    }

    /**
     * 用于计算两个时间节点之间的时间差
     * @param string $node1 时间节点1
     * @param string $node2 时间节点2
     * @param int $decimals 保留小数位数
     * @return string 返回以秒为单位的时间
     */
    public final function execTimeElapsed($node1 = '', $node2 = '', $decimals = 6): string
    {
        if ($node1 == '')
            return '';

        if (!isset($this->_execute_time_nodes[$node1]))
            return '';

        $end_times = microtime();
        if ($node2 && isset($this->_execute_time_nodes[$node2])) {
            $end_times = $this->_execute_time_nodes[$node2];
        }

        list($sm, $ss) = explode(' ', $this->_execute_time_nodes[$node1]);
        list($em, $es) = explode(' ', $end_times);

        return number_format(($em + $es) - ($sm + $ss), $decimals);
    }

    /**
     * 获取系统使用时间
     * @return mixed
     */
    public function getUseSeconds(): string
    {
        return $this->execTimeElapsed('system_launch');
    }

    /**
     * 当前上下文I18n对像
     */
    private $i18n;

    /**
     * @return mixed
     */
    public function getI18n(): I18n
    {
        return $this->i18n;
    }

    /**
     * @param mixed $i18n
     */
    public function setI18n($i18n): void
    {
        $this->i18n = $i18n;
    }

    /**
     * 当前上下文中站点名称，如果为0店铺则由app.config.php中APP_SHOP_ID_ZERO_FOLDER_NAME配置决定当前站点名称，否则直接使用SHOP_ID做为名称
     * 主要用于进行站点相关文件夹定位
     * @var
     */
    private $siteName;

    /**
     * @return mixed
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
     * @param mixed $siteName
     */
    public function setSiteName($siteName): void
    {
        $this->siteName = $siteName;
    }


    /**
     * 当前上下文中站点Storage文件夹物理路径
     * @var
     */
    private $appStorageRealPath;

    /**
     * @return mixed
     */
    public function getAppStorageRealPath()
    {
        return $this->appStorageRealPath;
    }

    /**
     * @param mixed $appStorageRealPath
     */
    public function setAppStorageRealPath($appStorageRealPath): void
    {
        $this->appStorageRealPath = $appStorageRealPath;
    }

    /**
     * 当前上下文中站点Storage文件夹Web路径
     * @var
     */
    private $appStoragePath;

    /**
     * @return mixed
     */
    public function getAppStoragePath()
    {
        return $this->appStoragePath;
    }

    /**
     * @param mixed $appStoragePath
     */
    public function setAppStoragePath($appStoragePath): void
    {
        $this->appStoragePath = $appStoragePath;
    }


    /**
     * 当前上下文中站点运行时物理路径，主要用于存储运行时相关文件包括上传文件，该文件夹位于 $appStorageRealPath 下
     * @var
     */
    private $appRuntimeRealPath;

    /**
     * @return mixed
     */
    public function getAppRuntimeRealPath()
    {
        return $this->appRuntimeRealPath;
    }

    /**
     * @param mixed $appRuntimeRealPath
     */
    public function setAppRuntimeRealPath($appRuntimeRealPath): void
    {
        $this->appRuntimeRealPath = $appRuntimeRealPath;
    }

    /**
     * 当前上下文中站点运行时 Web路径
     * @var
     */
    private $appRuntimePath;

    /**
     * @return mixed
     */
    public function getAppRuntimePath()
    {
        return $this->appRuntimePath;
    }

    /**
     * @param mixed $appRuntimePath
     */
    public function setAppRuntimePath($appRuntimePath): void
    {
        $this->appRuntimePath = $appRuntimePath;
    }


    /**
     * 当前店铺ID
     * @var int
     */
    private $shopId = 0;

    /**
     * @return mixed
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @param mixed $shopId
     */
    public function setShopId($shopId): void
    {
        $this->shopId = $shopId;
    }

    /**
     * 获取当前店铺ID
     */
    public function get_shop_id($domain = null)
    {
        if (!empty($domain)) {
            if ($domain == '192.168.1.194' || $domain == '192.168.1.194:8443') {
                return 10000006;//10006;//10000006;//$this->getConfig('app','SHOP_ID');
            }
            return 10006;//10006;//10000006;//$this->getConfig('app','SHOP_ID');
        } else {
            return $this->getShopId();
        }

    }


    /**
     * 是否RPC调用
     * @var bool
     */
    private $isRpcCall = false;

    /**
     * @return bool
     */
    public function isRpcCall(): bool
    {
        return $this->isRpcCall;
    }

    /**
     * @param bool $isRpcCall
     */
    public function setIsRpcCall(bool $isRpcCall): void
    {
        $this->isRpcCall = $isRpcCall;
    }

    /**
     * @return mixed
     */
    public function getPartitionName()
    {
        $route_datas = $this->getRouter();
        $controller = str_ireplace("\\package\\controller\\", "", $route_datas['controller']);
        $controllers = explode('\\', $controller);
        $partition_name = $controllers[0];
        $partitions = getConfig('app', 'APP_PARTITIONS');
        if (!array_key_exists($partition_name, $partitions)) {
            $partition_name = "";
        }
        return $partition_name;
    }

    /**
     * 根据分区名称获取SESSION 名称
     * @param string $partitionName 如果为空，则获取当前分区
     * @return 返回 SESSION NAME
     */
    public function getSessionNameByPartition($partitionName = '')
    {
        $partitionName = strtolower($partitionName);
        empty($partitionName) ? ($partitionName = strtolower($this->getPartitionName())) : '';
        $APP_DEFAULT_SESSION_NAME = getConfig('app', 'APP_DEFAULT_SESSION_NAME');
        if (empty($partitionName)) return $APP_DEFAULT_SESSION_NAME;

        $APP_SESSION_NAMES = getConfig('app', 'APP_SESSION_NAMES');
        if (isset($APP_SESSION_NAMES[$partitionName]) && $APP_SESSION_NAMES[$partitionName]) {
            return $APP_SESSION_NAMES[$partitionName];
        }
        return $APP_DEFAULT_SESSION_NAME;
    }

    /**
     * 是否运行在Cli中
     * @var bool
     */
    private $isCli = false;

    /**
     * @return bool
     */
    public function isCli(): bool
    {
        return $this->isCli;
    }

    /**
     * @param bool $isCli
     */
    public function setIsCli(bool $isCli): void
    {
        $this->isCli = $isCli;
    }

    private $session;

    /**
     * @return mixed
     */
    public function getSession(): Session
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session): void
    {
        $this->session = $session;
    }


    /**
     * 当前上下文中sql查询总次数
     * @var int
     */
    private $_execute_querys = 0;

    /**
     * @return mixed
     */
    public function getExecuteQuerys(): int
    {
        return $this->_execute_querys;
    }

    /**
     * @param mixed $execute_querys
     */
    public function setExecuteQuerys(int $execute_querys): void
    {
        $this->_execute_querys = $execute_querys;
    }


    /**
     * 输出当前控制器应用跟踪数据
     * @param $controller
     */
    public function outputTrace($controller, bool $return = false)
    {
        if (getConfig('app', 'APP_DEVENV') && getConfig('app', 'APP_DEV_TRACE_OUTPUT')) {
            $tpl = "<div id='parent_page_trace_output' style='background-color: #efefef'><input type='button' value='" . $this->getI18n()->getLang('app_output_trace_close_btn_txt') . "' onclick='document.getElementById(\"parent_page_trace_output\").style.display=\"none\";' /><input type='button' value='" . $this->getI18n()->getLang('app_output_trace_display_btn_txt') . "' onclick='document.getElementById(\"page_trace_output\").style.display=\"\";' /><input type='button' value='" . $this->getI18n()->getLang('app_output_trace_hidden_btn_txt') . "' onclick='document.getElementById(\"page_trace_output\").style.display=\"none\";'/><div id='page_trace_output'>";
            $tpl .= "<table width='100%' border='1'><tr><th>" . $this->getI18n()->getLang('app_output_trace_var_name') . "</th><th>" . $this->getI18n()->getLang('app_output_trace_var_value') . "</th></tr>";
            $vars = isset($controller->viewData) ? $controller->viewData : array();
            if (empty($vars)) $vars = isset($controller->_view_vars) ? $controller->_view_vars : array();
            foreach ($vars as $key => $value) {
                if ($key == 'core') continue;
                if (is_array($value)) {
                    foreach ($value as $kkk => $vvv) {
                        if (is_array($vvv)) {
                            foreach ($vvv as $kk => $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $k => $v) {
                                        $tpl .= "<tr><td>{$key}[{$kkk}][{$kk}][{$k}]</td><td>" . $v . "</td></tr>";
                                    }
                                } elseif (is_object($vv)) {
                                    foreach (get_object_vars($vv) as $k => $v) {
                                        $tpl .= "<tr><td>{$key}[{$kkk}][{$kk}]->{$k}</td><td>" . $v . "</td></tr>";
                                    }
                                } else {
                                    $tpl .= "<tr><td>{$key}[{$kkk}][{$kk}]</td><td>" . $vv . "</td></tr>";
                                }
                            }
                        } elseif (is_object($vvv)) {
                            foreach (get_object_vars($vvv) as $kk => $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $k => $v) {
                                        $tpl .= "<tr><td>{$key}[{$kkk}]->{$kk}[{$k}]</td><td>" . $v . "</td></tr>";
                                    }
                                } elseif (is_object($vv)) {
                                    foreach (get_object_vars($vv) as $k => $v) {
                                        $tpl .= "<tr><td>{$key}[{$kkk}]->{$kk}->{$k}</td><td>" . $v . "</td></tr>";
                                    }
                                } else {
                                    $tpl .= "<tr><td>{$key}[{$kkk}]->{$kk}</td><td>" . $vv . "</td></tr>";
                                }
                            }
                        } else {
                            $tpl .= "<tr><td>{$key}[{$kkk}]</td><td>" . $vvv . "</td></tr>";
                        }
                    }

                } elseif (is_object($value)) {
                    foreach (get_object_vars($value) as $kkk => $vvv) {
                        if (is_array($vvv)) {
                            foreach ($vvv as $kk => $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $k => $v) {
                                        $tpl .= "<tr><td>{$key}->{$kkk}[{$kk}][{$k}]</td><td>" . $v . "</td></tr>";
                                    }
                                } elseif (is_object($vv)) {
                                    foreach (get_object_vars($vv) as $k => $v) {
                                        $tpl .= "<tr><td>{$key}->{$kkk}[{$kk}]->{$k}</td><td>" . $v . "</td></tr>";
                                    }
                                } else {
                                    $tpl .= "<tr><td>{$key}->{$kkk}[{$kk}]</td><td>" . $vv . "</td></tr>";
                                }
                            }
                        } elseif (is_object($vvv)) {
                            foreach (get_object_vars($vvv) as $kk => $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $k => $v) {
                                        $tpl .= "<tr><td>{$key}->{$kkk}->{$kk}[{$k}]</td><td>" . $v . "</td></tr>";
                                    }
                                } elseif (is_object($vv)) {
                                    foreach (get_object_vars($vv) as $k => $v) {
                                        $tpl .= "<tr><td>{$key}->{$kkk}->{$kk}->{$k}</td><td>" . $v . "</td></tr>";
                                    }
                                } else {
                                    $tpl .= "<tr><td>{$key}->{$kkk}->{$kk}</td><td>" . $vv . "</td></tr>";
                                }
                            }
                        } else {
                            $tpl .= "<tr><td>{$key}->{$kkk}</td><td>" . $vvv . "</td></tr>";
                        }
                    }
                } else {
                    $tpl .= "<tr><td>{$key}</td><td>" . $value . "</td></tr>";
                }
            }
            $tpl .= "</table></div></div>";
            if ($return) {
                return $tpl;
            } else {
                echo $tpl;
            }

        }
    }

    /**
     * 获取跟踪输出状态
     */
    public function getTraceOutput()
    {
        return self::getConfig('app', 'APP_DEV_TRACE_OUTPUT');
    }

    /**
     * 修改跟踪输出状态
     */
    public function setTraceOutput($status)
    {
        return self::setConfig('app', 'APP_DEV_TRACE_OUTPUT', $status ? true : false);
    }


    /**
     * 关闭跟踪输出
     */
    public function closeTraceOutput()
    {
        self::setConfig('app', 'APP_DEV_TRACE_OUTPUT', false);
    }

    /**
     * 打开跟踪输出
     */
    public function openTraceOutput()
    {
        self::setConfig('app', 'APP_DEV_TRACE_OUTPUT', true);
    }

    /**
     * 最近一次全局异常对像
     * @var null
     */
    private $globalException = null;

    /**
     * @return null
     */
    public function getGlobalException()
    {
        return $this->globalException;
    }

    /**
     * @param null $globalException
     */
    public function setGlobalException($globalException): void
    {
        $this->globalException = $globalException;
    }


    /**
     * 获取主题名称
     * @return mixed|string
     * @throws \com\hanlintx\exception\FileNotFoundException
     */
    public final function getTheme()
    {
        $partition_name = $this->getPartitionName();
        $partitions = getConfig('app', 'APP_PARTITIONS');
        if (in_array(strtolower($partition_name), getConfig('app', 'APP_MANAGE_PARTITIONS'))) {
            $theme_name = 'APP_THEME_manage';
        } else {
            $theme_name = 'APP_THEME' . ($partition_name ? ('_' . $partition_name) : '');
        }

        if ($this->get($theme_name)) {
            $theme = $this->get($theme_name);
        } else {
            if ($this->cookie($theme_name)) {
                $theme = $this->cookie($theme_name);
            } else {
                if ($this->session($theme_name)) {
                    $theme = $this->session($theme_name);
                } else {
                    if ($partition_name && isset($partitions[$partition_name])) {
                        $theme = $partitions[$partition_name];
                    } else {
                        $theme = getConfig('app', 'APP_DEFAULT_THEME');
                    }
                }
            }
        }

        return $theme;
    }

    /**
     * 设置主题名称
     * @param string $theme
     * @throws \com\hanlintx\exception\FileNotFoundException
     */
    public final function setTheme($theme = "default")
    {
        $partition_name = $this->getPartitionName();
        if (in_array(strtolower($partition_name), getConfig('app', 'APP_MANAGE_PARTITIONS'))) {
            $theme_name = 'APP_THEME_manage';
        } else {
            $theme_name = 'APP_THEME' . ($partition_name ? ('_' . $partition_name) : '');
        }
        $this->getResponse()->setcookie($theme_name, $theme, null, '/');
        $this->session($theme_name, $theme);
    }

    /**
     * 基于当前进程ID保存路由信息
     * @var array(currWorkerPid=>[])
     */
    private static $_router;

    /**
     * 基于当前请求ID设置路由数据到路由堆栈中
     * @param $datas
     */
    public final function setRouter($datas)
    {
        if (!isset(self::$_router[self::getCurrentWorkerPid()])) {
            self::$_router[self::getCurrentWorkerPid()] = [];
        }
        array_push(self::$_router[self::getCurrentWorkerPid()], $datas);
    }

    /**
     * 基于当前请求ID从路由堆栈中获取路由数据
     * @param bool $is_delete 是否删除  true--是   false--否
     * @return mixed
     */
    final public function getRouter($is_delete = false)
    {
        $route = null;
        if (isset(self::$_router[self::getCurrentWorkerPid()]) && is_array(self::$_router[self::getCurrentWorkerPid()])) {
            $route = array_pop(self::$_router[self::getCurrentWorkerPid()]);
        }
        if (!$is_delete && $route) {
            array_push(self::$_router[self::getCurrentWorkerPid()], $route);
        }
        if (empty($route)) {
            array('controller' => '', 'act' => '', 'action' => '', 'params' => '', 'path' => '');
        }
        return $route;
    }

    /**
     * 获取当前控制器
     */
    public function getControllerClass()
    {
        $route_datas = $this->getRouter();
        return $route_datas['controller'];
    }

    /**
     * 获取当前控制器
     */
    public function getController()
    {
        $route_datas = $this->getRouter();
        return $route_datas['path'];
    }

    /**
     * 获取当前控制器当前action方法
     */
    public function getAction()
    {
        $route_datas = $this->getRouter();
        return $route_datas['act'];
    }

    /**
     * 获取当前请求中客户端类型 'pc|wap|wx|andriod|ios|weapp|baiduweapp|aliweapp',
     * @return string
     */
    public function getClinetType()
    {
        return $this->getRequest()->getClinetType();
    }


    /**
     * 当前上下文数据库对像
     * 基于数据库配置及集群名称保存数据库对像
     * @var
     */
    private $dbs = [];

    /**
     * 当前上下文协程数据库对像
     * 基于数据库配置及集群名称保存数据库对像
     * @var
     */
    private $cdbs = [];

    /**
     * 根据KID获取分库配置
     */
    public function get_split_database_config()
    {
        $shop_id = $this->getShopID();

        if (!isset($GLOBALS['APP_KID_SPLIT_DATABASE_CONFIG_RULES']) || empty($GLOBALS['APP_KID_SPLIT_DATABASE_CONFIG_RULES'])) {
            return 'db';
        }
        $APP_KID_SPLIT_DATABASE_CONFIG_RULES = $GLOBALS['APP_KID_SPLIT_DATABASE_CONFIG_RULES'];
        $db_index = 0;
        foreach ($APP_KID_SPLIT_DATABASE_CONFIG_RULES as $index => $arr) {
            if ($shop_id >= $arr[0] && $shop_id < $arr[1]) {
                $db_index = $index;
                break;
            }
        }
        if ($db_index <= 0) return 'db';
        return 'db' . $db_index;
    }

    /**
     * 获取指定配置文件中指定集群对应的数据库pdo对象
     * @param $db_cluster_name   数据库配置文件中定义的集群名称
     * @param string $db_config_name 数据库配置文件主文件名 例如 'db' 'db1' 如果为null，则由shop_id并根据 core.config.php中APP_KID_SPLIT_DATABASE_CONFIG_RULES配置规则自动决定使用哪一个数据库连接配置
     * @return pdo
     */
    public function db($db_cluster_name = 'default', & $db_config_name = null)
    {

        if ($db_config_name === null) {
            $db_config_name = $this->get_split_database_config();
        }

        if (empty($db_config_name)) $db_config_name = 'db';
        if (!isset($this->dbs[$db_config_name . $db_cluster_name]) || !$this->dbs[$db_config_name . $db_cluster_name]) {
            $pdo = new \cn\eunionz\component\db\Db($db_config_name, $db_cluster_name);
//            $pdo = new \cn\eunionz\component\cdb\Cdb($db_config_name, $db_cluster_name);
            if ($pdo) {
                if (method_exists($pdo, "initialize")) {
                    $pdo->initialize();
                }
                $this->dbs[$db_config_name . $db_cluster_name] = $pdo;
            }
        }
        return $this->dbs[$db_config_name . $db_cluster_name];
    }

    /**
     * 获取指定配置文件中指定集群对应的数据库pdo对象
     * @param $db_cluster_name   数据库配置文件中定义的集群名称
     * @param string $db_config_name 数据库配置文件主文件名 例如 'db' 'db1' 如果为null，则由shop_id并根据 core.config.php中APP_KID_SPLIT_DATABASE_CONFIG_RULES配置规则自动决定使用哪一个数据库连接配置
     * @return pdo
     */
    public function cdb($db_cluster_name = 'default', & $db_config_name = null)
    {

        if ($db_config_name === null) {
            $db_config_name = $this->get_split_database_config();
        }

        if (empty($db_config_name)) $db_config_name = 'db';
        if (!isset($this->cdbs[$db_config_name . $db_cluster_name]) || !$this->cdbs[$db_config_name . $db_cluster_name]) {
            $pdo = new \cn\eunionz\component\cdb\Cdb($db_config_name, $db_cluster_name);
            if ($pdo) {
                if (method_exists($pdo, "initialize")) {
                    $pdo->initialize();
                }
                $this->cdbs[$db_config_name . $db_cluster_name] = $pdo;
            }
        }
        return $this->cdbs[$db_config_name . $db_cluster_name];
    }

    private $_cache_object = null;

    /**
     * 核心缓存
     * @param null $prefix 缓存key前缀(或文件夹名称)，如果无值则返回核心缓存对象本身
     * @param null $key 缓存key，如果无值则返回核心缓存对象本身
     * @param null $data 缓存数据，如果无值则返回缓存数据，有值则缓存数据
     * @param null $expires 缓存过期时间，单位：秒，不传递使用默认配置
     * @return mixed
     */
    public function cache($prefix = null, $key = null, $data = null, $expires = null)
    {
        $CORE_CACHE_CONFIG = $this->getConfig('app', 'CORE_CACHE_CONFIG');
        if (!$CORE_CACHE_CONFIG) {
            die($this->getLang('error_cache_CORE_CACHE_CONFIG'));
        }

        if (isset($CORE_CACHE_CONFIG['is_cache']) && $CORE_CACHE_CONFIG['is_cache']) {
            if (!$this->_cache_object) {
                $this->_cache_object = new \cn\eunionz\component\cache\Cache($CORE_CACHE_CONFIG);
            }
            if ($prefix === null || $key === null) {
                return $this->_cache_object;
            } elseif ($data === null) {
                return $this->_cache_object->getCache($prefix, $key);
            } else {
                return $this->_cache_object->setCache($prefix, $key, $data, $expires);
            }
        } else {
            return null;
        }
    }


    public function getRequestObjectByGrpc()
    {
        return Parser::deserializeMessage([\Grpc\HiUser::class, null], $this->request->rawContent());
    }

    public function responseGrpc($res_object)
    {
        $this->is_grpc_response = true;
        $this->getResponse()->addHeader('content-type', 'application/grpc');
        $this->getResponse()->addHeader('trailer', 'grpc-status, grpc-message');
        $trailer = [
            "grpc-status" => "0",
            "grpc-message" => ""
        ];
        foreach ($trailer as $trailer_name => $trailer_value) {
            ctx()->getResponse()->trailer($trailer_name, $trailer_value);
        }
        ctx()->getResponse()->get_HttpResponse()->end(Parser::serializeMessage($res_object));
    }

}