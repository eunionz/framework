<?php
declare(strict_types=1);

namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');

/**
 * 请求对像
 * Class Request
 * @package cn\eunionz\core
 */
class Request extends Component
{
    private $http_request = null;
    private $cfg;

    private $SERVER = [];
    private $GET = [];
    private $POST = [];
    private $REQUEST = [];
    private $FILES = [];
    private $COOKIE = [];
    private $HEADER = [];


    public function __construct($http_request = null, $cfg = null)
    {
        $this->cfg = $cfg;
        if ($http_request) {
            $this->http_request = $http_request;
            $this->SERVER = $this->http_request->server;
            $this->SERVER['REQUEST_SCHEME'] = 'http';
            if (isset($cfg['is_https']) && $cfg['is_https']) {
                $this->http_request->header['https'] = 'on';
                $this->SERVER['REQUEST_SCHEME'] = 'https';
            }
            $this->SERVER['HTTP_ACCEPT'] = isset($this->http_request->header['accept']) ? $this->http_request->header['accept'] : '';
            $this->SERVER['HTTP_ACCEPT_CHARSET'] = isset($this->http_request->header['accept-charset']) ? $this->http_request->header['accept-charset'] : '';
            $this->SERVER['HTTP_ACCEPT_ENCODING'] = isset($this->http_request->header['accept-encoding']) ? $this->http_request->header['accept-encoding'] : '';
            $this->SERVER['HTTP_ACCEPT_LANGUAGE'] = isset($this->http_request->header['accept-language']) ? $this->http_request->header['accept-language'] : self::getConfig('app', 'APP_DEFAULT_LANGUAGE');
            $this->SERVER['HTTP_CONNECTION'] = isset($this->http_request->header['connection']) ? $this->http_request->header['connection'] : '';
            $this->SERVER['SERVER_NAME'] = isset($this->http_request->header['host']) ? $this->http_request->header['host'] : $cfg['host'];
            $this->SERVER['HTTP_HOST'] = isset($this->http_request->header['host']) ? $this->http_request->header['host'] : $cfg['host'];
            $this->SERVER['HTTP_REFERER'] = isset($this->http_request->header['referer']) ? $this->http_request->header['referer'] : '';
            $this->SERVER['HTTP_USER_AGENT'] = isset($this->http_request->header['user-agent']) ? $this->http_request->header['user-agent'] : '';
            $this->SERVER['HTTPS'] = isset($this->http_request->header['https']) ? $this->http_request->header['https'] : 'off';

            $this->SERVER['SERVER_PORT'] = isset($this->http_request->server['server_port']) ? $this->http_request->server['server_port'] : 80;
            $this->SERVER['SERVER_SIGNATURE'] = isset($this->http_request->server['SERVER_SIGNATURE']) ? $this->http_request->server['SERVER_SIGNATURE'] : '';
            $this->SERVER['REMOTE_ADDR'] = isset($this->http_request->server['remote_addr']) ? $this->http_request->server['remote_addr'] : '';
            $this->SERVER['REMOTE_HOST'] = isset($this->http_request->server['remote_addr']) ? $this->http_request->server['remote_addr'] : '';
            $this->SERVER['REMOTE_PORT'] = isset($this->http_request->server['remote_port']) ? $this->http_request->server['remote_port'] : '';
            $this->SERVER['HTTP_X_FORWARDED_FOR'] = isset($this->http_request->header['x-forwarded-for']) ? $this->http_request->header['x-forwarded-for'] : '';
            $this->SERVER['HTTP_X_PROXY_SERVER'] = isset($this->http_request->header['x-proxy-server']) ? $this->http_request->header['x-proxy-server'] : '';
            $this->SERVER['HTTP_X_PROXY_SCHEME'] = isset($this->http_request->header['x-proxy-scheme']) ? strtolower($this->http_request->header['x-proxy-scheme']) : 'http';
            $this->SERVER['HTTP_X_PROXY_SERVER_PORT'] = isset($this->http_request->header['x-proxy-server-port']) ? $this->http_request->header['x-proxy-server-port'] : '';

            if ($this->SERVER['HTTP_X_PROXY_SCHEME'] == 'http') {
                if ($this->SERVER['HTTP_X_PROXY_SERVER_PORT'] != '' && $this->SERVER['HTTP_X_PROXY_SERVER_PORT'] != 80) {
                    $this->SERVER['HTTP_HOST'] .= ':' . $this->SERVER['HTTP_X_PROXY_SERVER_PORT'];
                }
            }
            if ($this->SERVER['HTTP_X_PROXY_SCHEME'] == 'https') {
                if ($this->SERVER['HTTP_X_PROXY_SERVER_PORT'] != '' && $this->SERVER['HTTP_X_PROXY_SERVER_PORT'] != 443) {
                    $this->SERVER['HTTP_HOST'] .= ':' . $this->SERVER['HTTP_X_PROXY_SERVER_PORT'];
                }
            }


            $this->SERVER['PHP_SELF'] = 'index.php';
            $this->SERVER['DOCUMENT_ROOT'] = APP_REAL_PATH;
            $this->SERVER['SERVER_SOFTWARE'] = "eunionz framework 1.0(swoole 4.x)";
            $this->SERVER['GATEWAY_INTERFACE'] = "eunionz framework 1.0";

            $this->SERVER['SERVER_PROTOCOL'] = isset($this->http_request->server['server_protocol']) ? $this->http_request->server['server_protocol'] : '';
            $this->SERVER['REQUEST_SCHEME'] = "http";
            $this->SERVER['REQUEST_METHOD'] = isset($this->http_request->server['request_method']) ? $this->http_request->server['request_method'] : 'GET';
            $this->SERVER['QUERY_STRING'] = isset($this->http_request->server['query_string']) ? $this->http_request->server['query_string'] : '';
            $this->SERVER['REQUEST_URI'] = isset($this->http_request->server['request_uri']) ? $this->http_request->server['request_uri'] : '';
            $this->SERVER['PATH_INFO'] = $this->SERVER['REQUEST_URI'];
            if ($this->SERVER['QUERY_STRING']) {
                $this->SERVER['REQUEST_URI'] .= '?' . $this->SERVER['QUERY_STRING'];
            }

            $this->GET = (isset($this->http_request->get) && $this->http_request->get) ? $this->http_request->get : array();
            $this->COOKIE = (isset($this->http_request->cookie) && $this->http_request->cookie) ? $this->http_request->cookie : array();
            $this->FILES = (isset($this->http_request->files) && $this->http_request->files) ? $this->http_request->files : array();
            $this->POST = (isset($this->http_request->post) && $this->http_request->post) ? $this->http_request->post : array();
            $this->HEADER = (isset($this->http_request->header)) ? $this->http_request->header : array();
            $this->REQUEST = array();
            switch (strtolower(APP_PHP_REQUEST_ORDER)) {
                case 'gcp':
                    $this->REQUEST = array_merge($this->POST, $this->COOKIE, $this->GET);
                    break;
                case 'pgc':
                    $this->REQUEST = array_merge($this->COOKIE, $this->GET, $this->POST);
                    break;
                case 'pcg':
                    $this->REQUEST = array_merge($this->GET, $this->COOKIE, $this->POST);
                    break;
                case 'cgp':
                    $this->REQUEST = array_merge($this->POST, $this->GET, $this->COOKIE);
                    break;
                case 'cpg':
                    $this->REQUEST = array_merge($this->GET, $this->POST, $this->COOKIE);
                    break;
                default:
                    $this->REQUEST = array_merge($this->COOKIE, $this->POST, $this->GET);
                    break;
            }
        }
    }


    /**
     * 重新初始化 Request
     */
    public function initialize(){
        $this->REQUEST = array();
        switch (strtolower(APP_PHP_REQUEST_ORDER)) {
            case 'gcp':
                $this->REQUEST = array_merge($this->POST, $this->COOKIE, $this->GET);
                break;
            case 'pgc':
                $this->REQUEST = array_merge($this->COOKIE, $this->GET, $this->POST);
                break;
            case 'pcg':
                $this->REQUEST = array_merge($this->GET, $this->COOKIE, $this->POST);
                break;
            case 'cgp':
                $this->REQUEST = array_merge($this->POST, $this->GET, $this->COOKIE);
                break;
            case 'cpg':
                $this->REQUEST = array_merge($this->GET, $this->POST, $this->COOKIE);
                break;
            default:
                $this->REQUEST = array_merge($this->COOKIE, $this->POST, $this->GET);
                break;
        }
    }


    /**
     * 获取所有头部信息或指定头部信息
     * @param null $header_name 如果为空则获取所有头部信息，否则为指定名称的头部信息
     * @return mixed
     */
    private function _getallheaders(string $header_name = null)
    {
        $headers = array();
        if (!function_exists('getallheaders')) {
            if ($header_name) {
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                    if ($header_name && strtolower($name) == strtolower($header_name)) {
                        return $value;
                    }
                }
                return '';
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        } else {
            $headers = getallheaders();
            if ($header_name) {
                foreach ($headers as $name => $value) {
                    if ($header_name && strtolower($name) == strtolower($header_name)) {
                        return $value;
                    }
                }
                return '';
            } else {
                return $headers;
            }
        }
        return '';
    }


    /**
     * 获取请求ID
     * @return int
     */
    public function getRequestId(): int
    {
        return $this->http_request->fd ?? 0;

    }

    /**
     * 获取工作进程ID
     * @return int
     */
    public function gettWorkerId(): int
    {
        return self::getCurrentWorkerId();
    }

    /**
     * 设置当前请求的$SERVER数组
     * @param array $server
     * @return bool
     */
    public function setServer(array $server): bool
    {
        if (is_array($server)) {
            $this->SERVER = $server;
            return true;
        }
        return false;
    }

    /**
     * 获取/设置 SERVER数据
     * @param string|null $key
     * @param string|null $value
     * @return array|mixed|string
     */
    public function server(string $key = null, string $value = null)
    {
        if ($key) {
            if ($value !== null) {
                return $this->SERVER[$key] = $value;
            } else {
                return (isset($this->SERVER[$key]) && $this->SERVER[$key]) ? $this->SERVER[$key] : '';
            }
        }
        return $this->SERVER ?? [];
    }


    /**
     * 设置当前请求的$GET数组
     * @param array $get
     * @return bool
     */
    public function setGet(array $get): bool
    {
        if (is_array($get)) {
            $this->GET = $get;
            return true;
        }
        return false;
    }

    /**
     * 获取/设置 GET数据
     * @param string $key
     * @param null $value
     * @return array|mixed
     */
    public function get(string $key = null, $value = null)
    {
        if ($key) {
            if ($value !== null) {
                return $this->GET[$key] = $value;
            } else {
                return (isset($this->GET[$key]) && $this->GET[$key]) ? $this->GET[$key] : '';
            }
        }
        return $this->GET ?? [];
    }


    /**
     * 设置当前请求的$POST数组
     * @param array $post
     * @return bool
     */
    public function setPost(array $post): bool
    {
        if (is_array($post)) {
            $this->POST = $post;
            return true;
        }
        return false;
    }

    /**
     * 获取/设置 POST数据
     * @param string $key
     * @param null $value
     * @return array|mixed
     */
    public function post(string $key = null, $value = null)
    {
        if ($key) {
            if ($value !== null) {
                return $this->POST[$key] = $value;
            } else {
                return (isset($this->POST[$key]) && $this->POST[$key]) ? $this->POST[$key] : '';
            }
        }
        return $this->POST ?? [];
    }


    /**
     * 设置当前请求的$COOKIE数组
     * @param array $cookie
     * @return bool
     */
    public function setCookie(array $cookie): bool
    {
        if (is_array($cookie)) {
            $this->COOKIE = $cookie;
            return true;
        }
        return false;
    }

    /**
     * 获取/设置 COOKIE数据
     * @param string $key
     * @param string $value
     * @return array|mixed
     */
    public function cookie(string $key = null, string $value = null)
    {
        if ($key) {
            if ($value !== null) {
                return $this->COOKIE[$key] = $value;
            } else {
                return (isset($this->COOKIE[$key]) && $this->COOKIE[$key]) ? $this->COOKIE[$key] : '';
            }
        }
        return $this->COOKIE ?? [];
    }


    /**
     * 设置当前请求的$REQUEST数组
     * @param array $request
     * @return bool
     */
    public function setRequest(array $request): bool
    {
        if (is_array($request)) {
            $this->REQUEST = $request;
            return true;
        }
        return false;
    }

    /**
     * 获取 REQUEST数据
     * @param string $key
     * @param null $value
     * @return array|mixed
     */
    public function request(string $key = null, $value = null)
    {
        if ($key) {
            if ($value !== null) {
                return $this->REQUEST[$key] = $value;
            } else {
                return (isset($this->REQUEST[$key]) && $this->REQUEST[$key]) ? $this->REQUEST[$key] : '';
            }
        }
        return $this->REQUEST ?? [];
    }

    /**
     * 设置当前请求的$HEADER数组
     * @param array $header
     * @return bool
     */
    public function setHeader(array $header): bool
    {
        if (is_array($header)) {
            $this->HEADER = $header;
            return true;
        }
        return false;
    }

    /**
     * 获取 HEADER数据
     * @param string $key
     * @return array|mixed
     */
    public function header(string $key = null)
    {
        if ($key) {
            return $this->HEADER[$key] ?? '';
        }
        return $this->HEADER ?? [];
    }


    /**
     * 设置当前请求的$FILES数组
     * @param array $files
     * @return bool
     */
    public function setFiles(array $files): bool
    {
        if (is_array($files)) {
            $this->FILES = $files;
            return true;
        }
        return false;
    }

    /**
     * 获取 FILES数据
     * @return array
     */
    public function files(): array
    {
        return $this->FILES ?? [];
    }

    /**
     * 获取所有头部信息或指定头部信息
     * @param string|null $header_name 如果为空则获取所有头部信息，否则为指定名称的头部信息
     * @return mixed
     */
    public function getallheaders(string $header_name = null)
    {
        $headers = $this->header();
        if ($header_name) {
            foreach ($headers as $name => $value) {
                if ($header_name && strtolower($name) == strtolower($header_name)) {
                    return $value;
                }
            }
            return '';
        } else {
            return $headers;
        }
        return '';
    }

    /**
     * 获取当前应用客户端版本号，版本号格式为：0.01  0.21   1.01  依次类推
     * @return float
     */
    public function getClinetVersion(): float
    {
        $clientversion = $this->header('clientversion');
        if (!empty($clientversion)) return $clientversion + 0;
        if ($this->get('clientversion')) return $this->get('clientversion') + 0;
        if ($this->cookie('clientversion')) return $this->cookie('clientversion') + 0;
        return 0;
    }

    /**
     * 获取当前请求中客户端类型 pc--PC  wap--WAP  wx-WXP  app- weapp
     * @return string
     */
    public function getClinetType(): string
    {
        $clienttype = 'pc';
        if ($this->header('clienttype')) {
            $clienttype = $this->header('clienttype');
        } elseif ($this->get('clienttype')) {
            $clienttype = $this->get('clienttype');
        } elseif ($this->cookie('clienttype')) {
            $clienttype = $this->cookie('clienttype');
        }
        return $clienttype;
    }

    /**
     * 获取 rawContent
     * @return |null
     */
    public function rawContent()
    {
        if ($this->http_request) {
            return $this->http_request->rawContent();
        }
        return null;
    }

    /**
     * 获取 http_request
     * @return |null
     */
    public function getHttpRequest()
    {
        if ($this->http_request) {
            return $this->http_request;
        }
        return null;
    }
}
