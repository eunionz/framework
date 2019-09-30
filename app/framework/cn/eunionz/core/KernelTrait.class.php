<?php declare(strict_types=1);
/**
 * Eunionz PHP Framework Kernel class(will save config data with singleton mode,and supply some quick method to visit session,request,or other )
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\core;


use cn\eunionz\component\cdb\Cdb;
use cn\eunionz\component\consul\Consul;
use cn\eunionz\component\db\Db;
use Grpc\HiReply;
use Grpc\HiUser;
use package\application\GrpcClient;
use package\application\RpcClient;

defined('APP_IN') or exit('Access Denied');

trait KernelTrait
{
    /**
     * 获取/设置Session数据
     * @param string|null $key
     * @param mixed|null $value
     * @return array|mixed
     */
    public function session(string $key = null, $value = null)
    {
        return ctx()->getSession()->session($key, $value);
    }


    /**
     * 获取/设置 SERVER数据
     * @param mixed|null $key
     * @param null $value
     * @return array|mixed
     */
    public function server(string $key = null,string $value = null)
    {
        return ctx()->getRequest()->server($key, $value);
    }

    /**
     * 获取/设置 GET数据
     * @param mixed|null $key
     * @param null $value
     * @return array|mixed
     */
    public function get(string $key = null, $value = null)
    {
        return ctx()->getRequest()->get($key, $value);
    }

    /**
     * 获取/设置 POST数据
     * @param mixed|null $key
     * @param null $value
     * @return array|mixed
     */
    public function post(string $key = null, $value = null)
    {
        return ctx()->getRequest()->post($key, $value);
    }

    /**
     * 获取/设置 COOKIE数据
     * @param mixed|null $key
     * @param null $value
     * @return array|mixed
     */
    public function cookie(string $key = null,string $value = null)
    {
        return ctx()->getRequest()->cookie($key, $value);
    }

    /**
     * 获取 REQUEST数据
     * @param mixed|null $key
     * @param null $value
     * @return array|mixed
     */
    public function request(string $key = null, $value = null)
    {
        return ctx()->getRequest()->request($key, $value);
    }

    /**
     * 获取 HEADER数据
     * @param string|null $key
     * @return array|mixed
     */
    public function header(string $key = null)
    {
        return ctx()->getRequest()->header($key);
    }

    /**
     * 获取 FILES数据
     * @return array|mixed
     */
    public function files()
    {
        return ctx()->getRequest()->files();
    }

    /**
     * 获取所有头部信息或指定头部信息
     * @param string|null $header_name 如果为空则获取所有头部信息，否则为指定名称的头部信息
     * @return mixed
     */
    public function getallheaders(string $header_name = null)
    {
        return ctx()->getRequest()->getallheaders($header_name);
    }

    /**
     * 获取当前应用客户端版本号，版本号格式为：0.01  0.21   1.01  依次类推
     */
    public function getClinetVersion(): float
    {
        return ctx()->getRequest()->getClinetVersion();
    }


    /**
     * 获取当前浏览器语言
     */
    public function getLanguage(): string
    {
        return ctx()->getI18n()->getLanguage();
    }

    /**
     * 获取默认语言
     * @return mixed
     */
    public function getDefaultLanguage(): string
    {
        return ctx()->getI18n()->getDefaultLanguage();
    }

    /**
     * 合并语言包数据
     * @param array $langs
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function mergeLang(array $langs): void
    {
        ctx()->getI18n()->mergeLang($langs);
    }


    /**
     * 获取框架语言文件
     * @param string $name 语言文件名
     * @param string $key
     * @return mixed|string
     */
    public function getCoreLang(string $name, string $key = '')
    {
        return ctx()->getI18n()->getCoreLang($name, $key);
    }

    /**
     * 获取全局语言文件
     * @param string $name 语言文件名
     * @param string $key
     * @return mixed|string
     */
    public function getGlobalLang(string $name, string $key = '')
    {
        return ctx()->getI18n()->getGlobalLang($name, $key);
    }

    /**
     * 获取控制器语言文件
     * @param string $name 语言文件名
     * @param string $key
     * @return mixed|string
     */
    public function getControllerLang(string $classname, string $key = '')
    {
        return ctx()->getI18n()->getControllerLang($classname, $key);
    }

    /**
     * 获取语言数据
     * @param string $key
     * @param null $args
     * @return array|mixed|string|string[]|null
     */
    public function getLang(string $key = '', $args = null)
    {
        return ctx()->getI18n()->getLang($key, $args);
    }

    /**
     * 获取当前上下文指定配置文件中指定集群对应的数据库对象
     * @param $db_cluster_name   数据库配置文件中定义的集群名称
     * @param string $db_config_name 数据库配置文件主文件名 例如 'db' 'db1' 如果为null，则由shop_id并根据 core.config.php中APP_KID_SPLIT_DATABASE_CONFIG_RULES配置规则自动决定使用哪一个数据库连接配置
     * @return pdo
     */
    public function db(string $db_cluster_name = 'default', string & $db_config_name = null): ?Db
    {
        return ctx()->db($db_cluster_name, $db_config_name);
    }

    /**
     * 获取当前上下文指定配置文件中指定集群对应的协程数据库对象
     * @param $db_cluster_name   数据库配置文件中定义的集群名称
     * @param string $db_config_name 数据库配置文件主文件名 例如 'db' 'db1' 如果为null，则由shop_id并根据 core.config.php中APP_KID_SPLIT_DATABASE_CONFIG_RULES配置规则自动决定使用哪一个数据库连接配置
     * @return pdo
     */
    public function cdb(string $db_cluster_name = 'default', string & $db_config_name = null): ?Cdb
    {
        return ctx()->cdb($db_cluster_name, $db_config_name);
    }

    /**
     * 核心缓存
     * @param null $prefix 缓存key前缀(或文件夹名称)，如果无值则返回核心缓存对象本身
     * @param null $key 缓存key，如果无值则返回核心缓存对象本身
     * @param null $data 缓存数据，如果无值则返回缓存数据，有值则缓存数据
     * @param null $expires 缓存过期时间，单位：秒，不传递使用默认配置
     * @return mixed
     */
    public function cache(string $prefix = null, $key = null, $data = null, int $expires = null)
    {
        return ctx()->cache($prefix, $key, $data, $expires);
    }

    /**
     * rpc 服务调用返回 rpc客户端
     * @param string $service_name 服务注册中心 rpc服务名称
     * @param string $rpc_service_class rpc服务类名完全限定类名
     * @param array $add_headers 附加头部信息
     */
    public function rpcClient(string $service_name, string $rpc_service_class, array $add_headers = []): ?RpcClient
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'rpc');
        if ($service) {
            $rpcclient = new \package\application\RpcClient($service['Address'], $service['Port'], 0.5);
            return $rpcclient->instance($rpc_service_class, $add_headers);
        }
        return null;
    }


    /**
     * rpc服务调用
     * @param $service_name         服务注册中心 rpc服务名称
     * @param $rpc_service_class    rpc服务类名完全限定类名
     * @param $rpc_service_method   rpc服务类服务方法
     * @param $params               rpc服务类服务方法参数
     * @param array $add_headers 附加头部信息
     */
    public function rpcCall(string $service_name, string $rpc_service_class, string $rpc_service_method, array $params = [], array $add_headers = [])
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'rpc');
        if ($service) {
            $rpcclient = new \package\application\RpcClient($service['Address'], $service['Port'], 0.5);
            return $rpcclient->call($rpc_service_class, $rpc_service_method, $params, $add_headers);
        }
        return null;
    }


    /**
     * grpc 服务调用
     * @param string $service_name 服务注册中心 grpc服务名称
     * @param string $grpc_service_url  grpc服务url
     * @param string $grpc_service_method grpc服务方法
     * @param $grpc_request_object grpc请求对象
     * @param string $grpc_response_class grpc响应类
     * @param array $opts  参数数组
     * @return array
     */
    public function grpcCall(string $service_name, string $grpc_service_url, string $grpc_service_method, $grpc_request_object, string $grpc_response_class, array $opts = []) : array
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'grpc');
        if ($service) {
            $grpcClient = new GrpcClient($service['Address'], $service['Port'], $opts);
            if ($grpcClient) {
                $grpcClient->start();
                list($reply, $status) = $grpcClient->$grpc_service_method($grpc_service_url, $grpc_request_object, $grpc_response_class);
                $grpcClient->close();
                return [$reply, $status];
            }
        }
        return [$service_name . ' not found', -1];
    }


    /**
     * grpc 服务调用返回grpc客户端
     * @param string $service_name  服务注册中心 grpc服务名称
     * @param array $opts  grpc服务 选项
     * @return GrpcClient|null
     */
    public function grpcClient(string $service_name, array $opts = []): ?GrpcClient
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'grpc');
        if ($service) {
            $grpcClient = new GrpcClient($service['Address'], $service['Port'], $opts);
            if ($grpcClient) {
                return $grpcClient;
            }
        }
        return null;
    }


    /**
     * http 服务调用
     * @param string $service_name      服务注册中心 http服务名称
     * @param string $http_service_url  http 服务url
     * @param array $params             http 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param string $http_method       http 服务方法，仅支持：  get  post
     * @param bool $is_admin            是否后端接口
     * @param array $add_headers        附加头部信息
     * @param array $files              post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed|null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpCall(string $service_name, string $http_service_url, array $params = [], string $http_method = 'get', bool $is_admin = false, array $add_headers = [], array $files = [])
    {
        $http_method = strtolower($http_method);
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'http');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], false);
            if ($http_method == 'get') {
                return $httpclient->http_get($http_service_url, $params, $is_admin, $add_headers);
            } elseif ($http_method == 'post') {
                return $httpclient->http_post($http_service_url, $params, $is_admin, $add_headers, $files);
            }
        }
        return null;
    }


    /**
     * http get 服务调用
     * @param string $service_name          服务注册中心 http服务名称
     * @param string $http_service_url      http 服务url
     * @param array $params                 http 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param bool $is_admin                是否后端接口
     * @param array $add_headers            附加头部信息
     * @param array $files                  post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed|null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpGetCall(string $service_name, string $http_service_url, array $params = [], bool $is_admin = false, array $add_headers = [], array $files = [])
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'http');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], false);
            return $httpclient->http_get($http_service_url, $params, $is_admin, $add_headers);
        }
        return null;
    }

    /**
     * http post 服务调用
     * @param string $service_name          服务注册中心 http服务名称
     * @param string $http_service_url      http 服务url
     * @param array $params                 http 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param bool $is_admin                是否后端接口
     * @param array $add_headers            附加头部信息
     * @param array $files                  post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed|null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpPostCall(string $service_name, string $http_service_url, array $params = [],bool $is_admin = false, array $add_headers = [], array $files = [])
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'http');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], false);
            return $httpclient->http_post($http_service_url, $params, $is_admin, $add_headers, $files);
        }
        return null;
    }

    /**
     * https 服务调用
     * @param string $service_name          服务注册中心 https服务名称
     * @param string $https_service_url     https 服务url
     * @param array $params                 https 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param string $http_method           https 服务方法，仅支持：  get  post
     * @param bool $is_admin                是否后端接口
     * @param array $add_headers            附加头部信息
     * @param array $files                  post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed|null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpsCall(string $service_name, string $https_service_url, array $params = [], string $http_method = 'get', bool $is_admin = false,array $add_headers = [], array $files = [])
    {
        $http_method = strtolower($http_method);
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'https');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], true);
            if ($http_method == 'get') {
                return $httpclient->http_get($https_service_url, $params, $is_admin, $add_headers);
            } elseif ($http_method == 'post') {
                return $httpclient->http_post($https_service_url, $params, $is_admin, $add_headers, $files);
            }
        }
        return null;

    }


    /**
     * https get 服务调用
     * @param string $service_name          服务注册中心 https服务名称
     * @param string $https_service_url     https 服务url
     * @param array $params                 https 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param bool $is_admin                是否后端接口
     * @param array $add_headers            附加头部信息
     * @param array $files                  post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return |null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpsGetCall(string $service_name, string $https_service_url, array $params = [], bool $is_admin = false,array $add_headers = [], array $files = [])
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'https');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], true);
            return $httpclient->http_get($https_service_url, $params, $is_admin, $add_headers);
        }
        return null;

    }


    /**
     * https post 服务调用
     * @param string $service_name          服务注册中心 https服务名称
     * @param string $https_service_url     https 服务url
     * @param array $params                 https 服务参数数组，如果为get请求则查询串参数，post请求则为提交参数
     * @param bool $is_admin                是否后端接口
     * @param array $add_headers            附加头部信息
     * @param array $files                  post请求有效，是否上传文件，格式：  array(array('name'=> 表单中文件域名称,'path'=>文件物理路径))
     * @return mixed|null
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function httpsPostCall(string $service_name, string $https_service_url, array $params = [], bool $is_admin = false,array $add_headers = [], array $files = [])
    {
        $consul = new Consul();
        $service = $consul->get_service($service_name, 'https');
        if ($service) {
            $httpclient = new \package\application\HttpClient($service['Address'], $service['Port'], true);
            return $httpclient->http_post($https_service_url, $params, $is_admin, $add_headers, $files);
        }
        return null;
    }

    /**
     * 返回csrf token并写入cookie中
     * @return string
     */
    public function csrftoken() :string
    {
        $_csrftoken = md5(uniqid() . time() . mt_rand(1, 1000000000));
        ctx()->getResponse()->setcookie("_csrftoken", $_csrftoken);
        return $_csrftoken;
    }
}
