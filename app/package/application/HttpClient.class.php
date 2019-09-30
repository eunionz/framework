<?php

namespace package\application;

use cn\eunionz\core\Kernel;

/**
 * Http 客户端类
 * Class RpcClient
 * @package package\application
 */
class HttpClient extends Kernel
{

    /**
     * http服务器主机
     * @var string
     */
    private $http_host = "192.168.1.47";

    /**
     * http服务器端口
     * @var int
     */
    private $http_port = 8899;

    /**
     * 连接http服务器的超时时间，单位：秒
     * @var float
     */
    private $http_timeout = 0.5;

    /**
     * 是否支持 SSL 隧道协议
     * @var bool
     */
    private $http_is_ssl = false;

    /**
     * 协程 http 客户端对像
     * \Swoole\Coroutine\Http\Client
     * @var null
     */
    private $http_client = null;

    /**
     * http客户端当前调用的HTTP服务器配置名称（来自于http.config.php）
     * @var string
     */
    private $http_config_name = '';

    /**
     * HttpClient客户端构造函数
     * @param string $http_config_name http服务器配置名称，如果为空获取http.config.php中的 'http_service_default' 配置
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function __construct($http_host = '', $http_port = '', $is_ssl = false, $http_timeout = 0.5)
    {
        if (empty($http_host) || empty($http_port)) {
            $http_config_name = 'http_service_default';
            $this->http_config_name = $http_config_name;
            $http_config = self::getConfig('http', $this->http_config_name);
            $this->http_host = isset($http_config['host']) ? $http_config['host'] : '127.0.0.1';
            $this->http_port = isset($http_config['port']) ? intval($http_config['port']) : 80;
            $this->http_timeout = isset($http_config['timeout']) ? $http_config['timeout'] : 0.5;
            $this->is_ssl = isset($http_config['is_ssl']) ? $http_config['is_ssl'] : false;
        } else {
            $this->http_host = $http_host;
            $this->http_port = $http_port;
            $this->http_timeout = $http_timeout;
            $this->is_ssl = $is_ssl;
        }

        $this->http_client = new \Swoole\Coroutine\Http\Client($this->http_host, $this->http_port, $this->is_ssl);
        $this->http_client->set(['timeout' => $this->http_timeout]);
    }

    /**
     * 发起 http get 请求
     * @param $url 请求 url
     * @param array $params 请求查询串参数
     * @param bool $is_admin 是否后台http请求
     * @param array $add_headers 附加头部信息
     */
    public function http_get($url, $params = array(), $is_admin = false, $add_headers = array())
    {
        if ($is_admin) {
            $APP_SESSION_NAMES = self::getConfig('app', 'APP_SESSION_NAMES');
            $APP_MANAGE_PARTITIONS = self::getConfig('app', 'APP_MANAGE_PARTITIONS');
            $session_header_name = $APP_SESSION_NAMES[$APP_MANAGE_PARTITIONS[0]];
        } else {
            $session_header_name = self::getConfig('app', 'APP_DEFAULT_SESSION_NAME');
        }
        if ($params) {
            $param_str = '';
            foreach ($params as $key => $val) {
                $param_str .= $key . '=' . urlencode($val) . '&';
            }
            if ($param_str) {
                $param_str = trim($param_str, '&');
                if (strpos($url, '?') === false) {
                    $url .= '?' . $param_str;
                } else {
                    $url .= $param_str;
                }
            }
        }
        $headers = array(
            'Host' => $this->http_host,
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
            $session_header_name => ctx()->getSession()->session_id(),
            'accept-language' => ctx()->getLanguage(),
            'clientversion' => ctx()->getClinetVersion(),
            'clienttype' => ctx()->getClinetType(),
        );
        $headers = array_merge($headers, $add_headers);

        $this->http_client->setHeaders($headers);
        $this->http_client->get($url);
        $return = $this->http_client->body;

        ctx()->getSession()->initSession();
        return $return;
    }


    /**
     * 发起 http post 请求
     * @param $url 请求 url
     * @param array $params POST参数数组
     * @param bool $is_admin 是否后台http请求
     * @param array $add_headers 附加头部信息
     * @param array $files 是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function http_post($url, $params = array(), $is_admin = false, $add_headers = array(), $files = array())
    {
        if ($is_admin) {
            $APP_SESSION_NAMES = self::getConfig('app', 'APP_SESSION_NAMES');
            $APP_MANAGE_PARTITIONS = self::getConfig('app', 'APP_MANAGE_PARTITIONS');
            $session_header_name = $APP_SESSION_NAMES[$APP_MANAGE_PARTITIONS[0]];
        } else {
            $session_header_name = self::getConfig('app', 'APP_DEFAULT_SESSION_NAME');
        }
        $headers = array(
            'Host' => $this->http_host,
            "User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
            $session_header_name => ctx()->getSession()->session_id(),
            'accept-language' => ctx()->getLanguage(),
            'clientversion' => ctx()->getClinetVersion(),
            'clienttype' => ctx()->getClinetType(),
        );
        $headers = array_merge($headers, $add_headers);
        $this->http_client->setHeaders($headers);
        if (is_array($files)) {
            foreach ($files as $file) {
                if (isset($file['name']) && $file['name'] && isset($file['path']) && is_file($file['path'])) {
                    $this->http_client->addFile($file['path'], $file['name']);
                }
            }
        }

        $this->http_client->post($url, $params);
        $return = $this->http_client->body;
        ctx()->getSession()->initSession();
        return $return;
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        return @$this->http_client->close();
    }
}