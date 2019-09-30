<?php

namespace package\application;

use cn\eunionz\core\Kernel;

/**
 * Rpc 客户端类
 * Class RpcClient
 * @package package\application
 */
class RpcClient extends Kernel
{

    /**
     * RPC服务器主机
     * @var string
     */
    private $rpc_host = "192.168.1.47";

    /**
     * RPC服务器端口
     * @var int
     */
    private $rpc_port = 8899;

    /**
     * 连接RPC服务器的超时时间，单位：秒
     * @var float
     */
    private $rpc_timeout = 0.5;

    /**
     * 协程 RPC 客户端对像
     * \Swoole\Coroutine\Client
     * @var null
     */
    private $rpc_client = null;

    /**
     * RPC客户端当前调用的RPC服务器配置名称（来自于rpc.config.php）
     * @var string
     */
    private $rpc_config_name = '';

    /**
     * RPC服务类
     * @var
     */
    private $service_class;

    /**
     * RPC 附加头部信息
     * @var
     */
    private $add_headers = [];

    /**
     * RpcClient客户端构造函数
     * RpcClient constructor.
     * @param string $rpc_config_name rpc服务器配置名称，如果为空获取rpc.config.php中的 'rpc_service_default' 配置
     */
    public function __construct($rpc_host = '' , $rpc_port = '' , $rpc_timeout = 0.5)
    {
        if (empty($rpc_host) || empty($rpc_port)){
            $rpc_config_name = 'rpc_service_default';
            $this->rpc_config_name = $rpc_config_name;
            $rpc_config = self::getConfig('rpc', $this->rpc_config_name);
            $this->rpc_host = isset($rpc_config['host']) ? $rpc_config['host'] : '127.0.0.1';
            $this->rpc_port = isset($rpc_config['port']) ? intval($rpc_config['port']) : 8899;
            $this->rpc_timeout = isset($rpc_config['timeout']) ? $rpc_config['timeout'] : 0.5;
        }else{
            $this->rpc_host = $rpc_host;
            $this->rpc_port = $rpc_port;
            $this->rpc_timeout = $rpc_timeout;
        }
        $this->rpc_client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if (!$this->rpc_client->connect($this->rpc_host, $this->rpc_port, $this->rpc_timeout)) {
            throw new \cn\eunionz\exception\BaseException($this->getLang('error_rpc_client_connect_fail', array($this->rpc_host . ':' . $this->rpc_port)), 50000);
        }
    }

    public function instance($service_class, $add_headers = array()){
        $this->service_class = $service_class;
        $this->add_headers = $add_headers;
        return $this;
    }



    public function __call($method_name, $args){
        return $this->call($this->service_class,$method_name,$args, $this->add_headers);
    }

    /**
     * RPC远程过程调用方法
     * @param $service_class      远程服务器完全限定类名
     * @param $service_method     类中服务方法
     * @param array $params 传递给服务方法的实参数组
     * @param array $add_headers 头部信息，如果头部信息中有session_id、shop_id、app_language、client_version、client_type将覆盖系统自动配置的四个头部信息值
     */
    public function call($service_class, $service_method, $params = array(), $add_headers = array())
    {
        $header = array(
            'shopid' => ctx()->getShopID(),
            'sessionid' => ctx()->getSession()->session_id(),
            'sessionname' => ctx()->getSession()->session_name(),
            'applanguage' => ctx()->getLanguage(),
            'clientversion' => ctx()->getClinetVersion(),
            'clienttype' => ctx()->getClinetType(),
        );
        $header = array_merge($header, $add_headers);
        $send_datas = array(
            'service' => $service_class,
            'action' => $service_method,
            'header' => $header,
            'params' => $params,
        );
        $this->rpc_client->send(serialize($send_datas));
        $return_json = $this->rpc_client->recv();
        ctx()->getSession()->session();
        if($return_json){
            $return_json = unserialize($return_json);
            return $return_json;
        }else{
            return array(
                'status' => 1000,
                'header' => $header,
                'msg' => $this->getLang('error_rpc_server_no_response' , array($this->rpc_host  . ':' . $this->rpc_port , $service_class , $service_method)),
            );
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        return @$this->rpc_client->close();
    }
}