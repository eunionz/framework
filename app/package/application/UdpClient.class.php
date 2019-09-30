<?php

namespace package\application;

use cn\eunionz\core\Kernel;

/**
 * Udp 客户端类
 * Class UdpClient
 * @package package\application
 */
class UdpClient extends Kernel
{

    /**
     * TCP服务器主机
     * @var string
     */
    private $udp_host = "192.168.1.47";

    /**
     * TCP服务器端口
     * @var int
     */
    private $udp_port = 9997;

    /**
     * 连接TCP服务器的超时时间，单位：秒
     * @var float
     */
    private $udp_timeout = 0.5;

    /**
     * 协程 TCP 客户端对像
     * \Swoole\Coroutine\Client
     * @var null
     */
    private $udp_client = null;

    /**
     * TCP客户端当前调用的TCP服务器配置名称（来自于rpc.config.php）
     * @var string
     */
    private $udp_config_name = '';

    /**
     * RpcClient客户端构造函数
     * RpcClient constructor.
     * @param string $udp_config_name rpc服务器配置名称，如果为空获取rpc.config.php中的 'udp_service_default' 配置
     */
    public function __construct($udp_config_name = '')
    {
        if (empty($udp_config_name)) $udp_config_name = 'udp_service_default';
        $this->udp_config_name = $udp_config_name;
        $udp_config = self::getConfig('udp', $this->udp_config_name);
        $this->udp_host = isset($udp_config['host']) ? $udp_config['host'] : '127.0.0.1';
        $this->udp_port = isset($udp_config['port']) ? intval($udp_config['port']) : 9997;
        $this->udp_timeout = isset($udp_config['timeout']) ? $udp_config['timeout'] : 0.5;
        $this->udp_client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_UDP);
        if (!$this->udp_client->connect($this->udp_host, $this->udp_port, $this->udp_timeout)) {
            throw new \cn\eunionz\exception\BaseException($this->getLang('error_udp_client_connect_fail', array($this->udp_host . ':' . $this->udp_port)), 50000);
        }
    }

    /**
     * TCP 数据发送方法
     * @param $data   发送数据
     */
    public function send($data)
    {
        $this->udp_client->send($data);
        return $this->udp_client->recv();
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        return @$this->udp_client->close();
    }
}