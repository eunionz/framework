<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\amqpPublisher;


use cn\eunionz\core\Component;
use mysql_xdevapi\Exception;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

defined('APP_IN') or exit('Access Denied');

/**
 * RabbitMq 消息队列类，工具类
 * Class AmqpPublisher
 */
class AmqpPublisher extends Component
{
    private $config = array();
    /**
     * RabbitMq连接主机
     * @var
     */
    private $host;

    /**
     * RabbitMq 连接端口
     * @var
     */
    private $port;

    /**
     * RabbitMq连接用户名
     * @var
     */
    private $user;

    /**
     * RabbitMq连接用户名密码
     * @var
     */
    private $password;

    /**
     * RabbitMq 虚拟主机
     * @var
     */
    private $vhost;

    /**
     * RabbitMq 交换机名称
     * @var
     */
    private $exchangeName;

    /**
     * @var 交换机类型
     */
    private $exchangeType = AMQP_EX_TYPE_DIRECT;

    /**
     * RabbitMq 路由键
     * @var
     */
    private $routeKey;

    /**
     * 队列名称
     * @var
     */
    private $queueName;

    /**
     * 是否持久化队列
     * @var bool
     */
    private $durable = true;

    /**
     * 队列是否镜像
     * @var bool
     */
    private $mirror = false;

    /**
     * 是否自动删除
     * @var bool
     */
    private $autodelete = false;

    private $connection = Null;
    private $channel = Null;
    private $exchange = Null;
    public $is_ready = False;

    /**
     * 创建连接，并指定交换机
     * @return void
     */
    public function __construct()
    {
        $this->config = self::getConfig('rabbitmq', 'default');
        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->user = $this->config['login'];
        $this->password = $this->config['password'];
        $this->vhost = $this->config['vhost'];
        $this->exchangeName = $this->config['exchangeName'];
        $this->routeKey = $this->config['routeKey'];
        $this->exchangeType = $this->config['exchangeType'];
        $this->durable = $this->config['durable'];
        $this->mirror = $this->config['mirror'];
        $this->autodelete = $this->config['autodelete'];

        $this->connection = new AMQPSocketConnection($this->host, $this->port, $this->user, $this->password, $this->vhost);
        if (!$this->connection) {
            throw new \Exception("Connect RabbitMQ Server[{$this->host}:{$this->port}] failure.");
        }
        $this->channel =  $this->connection->channel();
        if (!$this->channel) {
            throw new \Exception("Create RabbitMQ Server[{$this->host}:{$this->port}] channel failure.");
        }
        $this->channel->queue_declare($this->queueName, false, $this->durable, false, $this->autodelete);
        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, $this->durable, $this->autodelete);
        $this->channel->queue_bind($this->queueName, $this->exchangeName, $this->routeKey);
        $this->is_ready = true;
    }



    /**
     * 发送消息
     * @param mixed $body 消息体
     * @param array properties 消息头
     * @return int / False
     */
    public function send($body, $properties = [])
    {
        if (!$this->channel) return false;
        $AMQPMessage = new AMQPMessage(json_encode($body), $properties);
        $this->channel->basic_publish($AMQPMessage, $this->exchangeName, $this->routeKey);
        return true;
    }

    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }
    }
}
