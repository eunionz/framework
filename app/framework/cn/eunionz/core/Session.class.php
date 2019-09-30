<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Session class
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */

namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');

class Session extends Kernel
{
    /**
     * 基于会话ID的单例模式保存会话数据
     * @var array(session_id = > null)
     */
    private $SESSION = [];
    /**
     * Session 处理句柄
     * @var
     */
    private $_session_handler = null;


    public function getSessionHandler(): SessionHandler
    {
        if (!$this->_session_handler) {
            $mode = self::getConfig('app', 'APP_SESSION_MODE');
            $lefttime = self::getConfig('app', 'APP_SESSION_LIFETIME_SECONDS');
            $redis_config = self::getConfig('app', 'APP_SESSION_REDIS_CONFIG');
            $session_dir = self::getConfig('app', 'APP_SESSION_DIR');
            $session_table = self::getConfig('app', 'APP_SESSION_TABLE_NAME');
            $mysql_config = self::getConfig('app', 'APP_SESSION_MYSQL_CONFIG');
            $this->_session_handler = new SessionHandler($mode, $lefttime, $redis_config, $session_dir, $session_table, $mysql_config);
        }
        return $this->_session_handler;
    }

    /**
     * 获取/设置 SESSION数据
     * @param null $key
     * @param null $value
     * @return array|mixed
     */
    public function session($key = null, $value = null)
    {
        if ($key) {
            if ($value !== null) {
                $this->SESSION[$key] = $value;
                return $this->getSessionHandler()->write($this->session_id(), $this->SESSION);
            } else {
                $this->SESSION = $this->getSessionHandler()->read($this->session_id());
                return isset($this->SESSION[$key]) ? $this->SESSION[$key] : '';
            }
        }
        $this->SESSION = $this->getSessionHandler()->read($this->session_id());
        return $this->SESSION ?? [];
    }

    /**
     * 获得 当前环境的 HTTP 协议方式
     */
    public function getHttp()
    {
        return (strtolower(ctx()->getRequest()->server('HTTPS')) != 'off') ? 'https://' : 'http://';
    }

    /**
     * 获取当前主机
     * @return string
     */
    public function getHost()
    {
        if (ctx()->isCli() && !APP_IS_IN_SWOOLE) return "127.0.0.1";
        /* 协议 */
        $protocol = $this->getHttp();
        $host = '127.0.0.1';
        /* 域名或IP地址 */
        if (ctx()->getRequest()->server('HTTP_X_FORWARDED_HOST')) {
            $host = ctx()->getRequest()->server('HTTP_X_FORWARDED_HOST');
        } elseif (ctx()->getRequest()->server('HTTP_HOST')) {
            $host = ctx()->getRequest()->server('HTTP_HOST');
        } else {
            /* 端口 */
            if (ctx()->getRequest()->server('SERVER_PORT')) {
                $port = ':' . ctx()->getRequest()->server('SERVER_PORT');

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (ctx()->getRequest()->server('SERVER_NAME')) {
                $host = ctx()->getRequest()->server('SERVER_NAME') . $port;
            } elseif (ctx()->getRequest()->server('SERVER_ADDR')) {
                $host = ctx()->getRequest()->server('SERVER_ADDR') . $port;
            }
        }
        return $host;
    }

    /**
     * 获取IP
     * @return mixed|string
     */
    function get_ip()
    {
        $ip = "";
        $ips = array();
        if (ctx()->getRequest()->server("HTTP_CLIENT_IP")) {
            $ip = ctx()->getRequest()->server("HTTP_CLIENT_IP");
        }
        if (ctx()->getRequest()->server('HTTP_X_FORWARDED_FOR')) { //获取代理ip
            $ips = explode(',', ctx()->getRequest()->server('HTTP_X_FORWARDED_FOR'));
        }
        if ($ip != "") {
            $ips = array_unshift($ips, $ip);
        }

        $count = count($ips);
        for ($i = 0; $i < $count; $i++) {
            if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {//排除局域网ip
                $ip = $ips[$i];
                break;
            }
        }
        if (ctx()->isCli()) $ip = "127.0.0.1";
        $tip = (empty($ip)) ? (empty(ctx()->getRequest()->server('REMOTE_ADDR')) ? '127.0.0.1' : ctx()->getRequest()->server('REMOTE_ADDR')) : $ip;
        if ($tip == "127.0.0.1") { //获得本地真实IP
            return $tip;
        } else {
            return $tip;
        }
    }


    /**
     * 获取全局GUID
     * @return string
     */
    public function guid()
    {
        $host = $this->getHost(); //获取当前访问url中主机
        $ip = $this->get_ip();  //当前的ip地址
        $uuid = '';
        if (function_exists('com_create_guid')) {
            $uuid = com_create_guid();
        } else {
            mt_srand(intval(microtime(true) * 10000));//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(strval(rand()), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12)
                . chr(125);// "}"
        }
        $strtime = microtime();  //当前的时间
        mt_srand(intval(microtime(true) * 10000));//optional for php 4.2.0 and up.
        $uuid = ctx()->getShopId() . self::getRequestUniqueId() . ctx()->getRequest()->getRequestId() . $host . $ip . $uuid . $strtime . rand();
        return md5($uuid);
    }

    /**
     * 生成session_id
     * @param string $session_id
     * @return bool|mixed|string
     */
    public function makeSessionId($session_id = '')
    {
        if (empty($session_id)) {

            if (!ctx()->isRpcCall()) {
                $header_session_id = ctx()->getRequest()->header($this->session_name());
                $get_session_id = ctx()->getRequest()->get($this->session_name()) ?? '';
                $cookie_session_id = ctx()->getRequest()->cookie($this->session_name()) ?? '';
                if (empty($header_session_id) && empty($get_session_id) && empty($cookie_session_id)) {
                    try {
                        $session_id = md5($this->guid());
                        $this->session_id($session_id);
                        ctx()->getResponse()->setcookie($this->session_name(), $session_id, 0, '/', '', (ctx()->getRequest()->server('REQUEST_SCHEME') == 'https' ? true : false));
                        return $session_id;
                    } catch (\Exception $ex) {
                        throw $ex;
                    }
                } else {
                    if ($header_session_id) {
                        $this->session_id($header_session_id);
                        return $header_session_id;
                    } elseif ($get_session_id) {
                        $this->session_id($get_session_id);
                        return $get_session_id;
                    } else {
                        $this->session_id($cookie_session_id);
                        return $cookie_session_id;
                    }
                }
            } else {
                $session_id = md5($this->guid());
                $this->session_id($session_id);
                return $session_id;
            }
        } else {
            $this->session_id($session_id);
            if (!ctx()->isRpcCall()) {
                ctx()->getResponse()->setcookie($this->session_name(), $session_id, 0, '/', '', (ctx()->getRequest()->server('REQUEST_SCHEME') == 'https' ? true : false));
            }
            return $session_id;
        }
    }

    /**
     * 初始化会话
     * @param string $partitionName
     * @param string $session_id 如果为空基于header/get/cookie中的相应session_id进行会话初始化，否则直接基于$session_id进行会话初始化
     * @throws \com\hanlintx\exception\FileNotFoundException
     */
    public final function initSession($partitionName = '', $session_id = '')
    {

        empty($partitionName) ? ($partitionName = ctx()->getPartitionName()) : '';
        $this->session_name(ctx()->getSessionNameByPartition($partitionName));
        if (empty($session_id)) {
            $this->SESSION = $this->getSessionHandler()->read($this->makeSessionId());
        } else {
            $this->SESSION = $this->getSessionHandler()->read($this->session_id($session_id));
        }
    }

    /**
     * 基于当前会话ID保存会话数据
     * @throws \com\hanlintx\exception\FileNotFoundException
     */
    public final function saveSession()
    {
        return $this->getSessionHandler()->write($this->session_id(), $this->SESSION);
    }

    /**
     * 基于当前会话ID从会话中删除数据或销毁会话
     * @param null $key
     */
    public final function delSession($key = null)
    {
        if (empty($key)) {
            $this->SESSION = [];
            return $this->getSessionHandler()->write($this->session_id(), $this->SESSION);
        }

        if (isset($this->SESSION[$key])) {
            unset($this->SESSION[$key]);
            return self::getSessionHandler()->write($this->session_id(), $this->SESSION);
        }
    }

    private $session_name = 'PHPSESSIONID';

    /**
     * 设置或获取session_name
     * @param null $name
     */
    public final function session_name($name = null)
    {
        if (empty($name)) {
            return $this->session_name;
        } else {
            return $this->session_name = $name;
        }
    }


    private $session_id = '';


    /**
     * 设置或获取 session_id
     * @param null $session_id
     */
    public final function session_id($session_id = null)
    {
        if (empty($session_id)) {
            if(empty($this->session_id)){
                $this->session_id = $this->makeSessionId();
            }
            return $this->session_id;
        } else {
            return $this->session_id = $session_id;
        }
    }

    /**
     * Rpc初始化会话
     * @param string $session_name
     * @param string $session_id
     * @throws \com\hanlintx\exception\FileNotFoundException
     */
    public final function rpcInitSession()
    {
        $this->SESSION = $this->getSessionHandler()->read($this->session_id());
    }

}