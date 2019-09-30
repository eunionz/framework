<?php

namespace package\application;

use cn\eunionz\core\Kernel;

/**
 * Tcp 客户端类
 * Class TcpClient
 * @package package\application
 */
class TcpClient extends Kernel
{

    /**
     * TCP服务器主机
     * @var string
     */
    private $tcp_host = "192.168.1.47";

    /**
     * TCP服务器端口
     * @var int
     */
    private $tcp_port = 9998;

    /**
     * 连接TCP服务器的超时时间，单位：秒
     * @var float
     */
    private $tcp_timeout = 0.5;

    /**
     * 协程 TCP 客户端对像
     * \Swoole\Coroutine\Client
     * @var null
     */
    private $tcp_client = null;

    /**
     * TCP客户端当前调用的TCP服务器配置名称（来自于rpc.config.php）
     * @var string
     */
    private $tcp_config_name = '';

    /**
     * RpcClient客户端构造函数
     * RpcClient constructor.
     * @param string $tcp_config_name rpc服务器配置名称，如果为空获取rpc.config.php中的 'tcp_service_default' 配置
     */
    public function __construct($tcp_config_name = '')
    {
        if (empty($tcp_config_name)) $tcp_config_name = 'tcp_service_default';
        $this->tcp_config_name = $tcp_config_name;
        $tcp_config = self::getConfig('tcp', $this->tcp_config_name);
        $this->tcp_host = isset($tcp_config['host']) ? $tcp_config['host'] : '127.0.0.1';
        $this->tcp_port = isset($tcp_config['port']) ? intval($tcp_config['port']) : 9998;
        $this->tcp_timeout = isset($tcp_config['timeout']) ? $tcp_config['timeout'] : 0.5;
        $this->tcp_client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if (!$this->tcp_client->connect($this->tcp_host, $this->tcp_port, $this->tcp_timeout)) {
            throw new \cn\eunionz\exception\BaseException($this->getLang('error_tcp_client_connect_fail', array($this->tcp_host . ':' . $this->tcp_port)), 50000);
        }
    }

    /**
     * TCP 数据发送方法
     * @param $data   发送数据
     */
    public function send($data)
    {
        $this->tcp_client->send($data);
        return $this->tcp_client->recv();
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        return @$this->tcp_client->close();
    }
}