<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\amqpConsumer;


use cn\eunionz\core\Component;
use mysql_xdevapi\Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;

defined('APP_IN') or exit('Access Denied');

/**
 * RabbitMq 消息队列类，工具类
 * Class AmqpConsumer
 */
class AmqpConsumer extends Component
{
    private $config;

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
    private $login;

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
     * RabbitMq 信道对像
     * @var
     */
    private $channel = null;

    /**
     * 连接对像
     * @var null
     */
    private $connection = null;

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

    /**
     * 消息队列对像
     * @var null
     */
    private $queue = null;

    /**
     * 构造函数
     * Cache constructor.
     */
    public function __construct()
    {
        $this->config = self::getConfig('rabbitmq', 'default');
        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->login = $this->config['login'];
        $this->password = $this->config['password'];
        $this->vhost = $this->config['vhost'];
        $this->exchangeName = $this->config['exchangeName'];
        $this->routeKey = $this->config['routeKey'];
        $this->exchangeType = $this->config['exchangeType'];
        $this->durable = $this->config['durable'];
        $this->mirror = $this->config['mirror'];
        $this->autodelete = $this->config['autodelete'];


        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->login, $this->password, $this->vhost);
        if (!$this->connection) {
            throw new \Exception("Connect RabbitMQ Server[{$this->host}:{$this->port}] failure.");
        }
        $this->channel = $this->connection->channel();
        if (!$this->channel) {
            throw new \Exception("Create RabbitMQ Server[{$this->host}:{$this->port}] channel failure.");
        }

//        $this->channel->exchange_declare($this->exchangeName, $this->exchangeType, false, $this->durable, $this->autodelete);

        $this->channel->queue_declare($this->queueName, false, $this->durable, false, $this->autodelete);

        $this->channel->queue_bind($this->queueName, $this->exchangeName, $this->routeKey);

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

    /**
     * 循环阻塞方式接收消息
     * @param callable $fun_name          回调函数定义格式：  function($msg, $queue){}
     *     格式1：   string $fun_name      传递全局回调函数名称
     *     格式2：   \Closure $fun_name    传递闭包
     *     格式3：   array(object,'method')  传递数组，描述对象及方法
     *     格式4：   array('fullclassname','method')  传递数组，描述类及静态方法
     * or array
     * @param bool $autoack 是否自动发送ACK应答，否则需要在自定义处理函数中手动发送
     * @return bool
     */
    public function run($fun_name, $autoack = true)
    {
        if (!$this->channel) return false;
        if(!$fun_name) return false;
        $this->channel->basic_consume($this->queueName, '', false, !$autoack, false, false, $fun_name);
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}
