<?php

namespace package\application;

use cn\eunionz\component\grpc\BaseStub;
use cn\eunionz\core\Kernel;

/**
 * GrpcClient 客户端类
 * Class GrpcClient
 * @package package\application
 */
class GrpcClient extends Kernel
{

    /**
     * RPC服务器主机
     * @var string
     */
    private $grpc_host = "127.0.0.1";

    /**
     * RPC服务器端口
     * @var int
     */
    private $grpc_port = 8888;

    /**
     * 连接RPC服务器的超时时间，单位：秒
     * @var float
     */
    private $grpc_timeout = 0.5;

    /**
     * 协程 RPC 客户端对像
     * \cn\eunionz\component\grpc\BaseStub
     * @var null
     */
    private $grpc_client = null;

    /**
     * RPC客户端当前调用的RPC服务器配置名称（来自于rpc.config.php）
     * @var string
     */
    private $grpc_config_name = '';

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
     * @param string $grpc_config_name rpc服务器配置名称，如果为空获取rpc.config.php中的 'grpc_service_default' 配置
     */
    public function __construct($grpc_host = '', $grpc_port = '', $opts = [])
    {
        if (empty($grpc_host) || empty($grpc_port)) {
            $grpc_config_name = 'grpc_service_default';
            $this->grpc_config_name = $grpc_config_name;
            $grpc_config = self::getConfig('rpc', $this->grpc_config_name);
            $this->grpc_host = isset($grpc_config['host']) ? $grpc_config['host'] : '127.0.0.1';
            $this->grpc_port = isset($grpc_config['port']) ? intval($grpc_config['port']) : 8888;
            $this->grpc_timeout = isset($grpc_config['timeout']) ? $grpc_config['timeout'] : 0.5;
        } else {
            $this->grpc_host = $grpc_host;
            $this->grpc_port = $grpc_port;
        }
        $this->grpc_client = new BaseStub($this->grpc_host . ':' . $this->grpc_port, $opts);
    }

    public function start()
    {
        if ($this->grpc_client) {
            $this->grpc_client->start();
        }
    }


    public function close()
    {
        if ($this->grpc_client) {
            $this->grpc_client->close();
        }
    }


    public function __call($method_name, $args)
    {
        if ($this->grpc_client) {
            return $this->grpc_client->_simpleRequest(rtrim($args[0], '/') . '/' . $method_name,
                $args[1],
                [$args[2], 'decode'],
                isset($args[3]) ? $args[3] : [], isset($args[4]) ? $args[4] : []);
        }

        return [];
    }


}