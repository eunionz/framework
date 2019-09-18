<?php
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework rabbitmq config                        ///////
///////    All copyright at Eunionz.cn                                ///////
///////    Email : master@Eunionz.cn                                  ///////
///////    create at 2015-04-30  上午9:47                               ///////
///////////////////////////////////////////////////////////////////////////////

defined('APP_IN') or exit('Access Denied');
/*
 *
 * rabbitmq 配置
 */
return array(

    /**
     * 默认rabbitmq配置
     * //交换机类型：
     *         1、AMQP_EX_TYPE_DIRECT：处理路由键
     *             需要将一个队列绑定到交换机上，要求该消息与一个特定的路由键完全匹配。这是一个完整的匹配。如果一个队列绑定到该交换机上要求路由键 “dog”，则只有被标记为“dog”的消息才被转发，不会转发dog.puppy，也不会转发dog.guard，只会转发dog。
     *         2、AMQP_EX_TYPE_FANOUT：不处理路由键
     *             你只需要简单的将队列绑定到交换机上。一个发送到交换机的消息都会被转发到与该交换机绑定的所有队列上。很像子网广播，每台子网内的主机都获得了一份复制的消息。Fanout交换机转发消息是最快的。
     *         3、AMQP_EX_TYPE_TOPIC：将路由键和某模式进行匹配
     *             此时队列需要绑定要一个模式上。符号“#”匹配一个或多个词，符号“*”匹配不多不少一个词。因此“audit.#”能够匹配到“audit.irs.corporate”，但是“audit.*” 只会匹配到“audit.irs”
     *         4、AMQP_EX_TYPE_HEADERS： 基本不用
     * rabbitmq配置格式：
     *    'rabbitmq配置名称' => array(
     *        'host' => '主机',       //域名或IP
     *        'port' => 端口,         //整数
     *        'user' => '用户名',     //用户名
     *        'password' => '密码',   //密码
     *        'vhost' => '虚拟主机',  //虚拟主机
     *        'exchangeName'=>'test' ,//交换机名称
     *        'routeKey'=>'test' ,//路由键
     *        'queueName'=>'test' ,//队列名称
     *        'exchangeType'=> AMQP_EX_TYPE_DIRECT|AMQP_EX_TYPE_FANOUT|AMQP_EX_TYPE_TOPIC|AMQP_EX_TYPE_HEADERS,交换机类型
     *        'durable'=>true|false ,//队列是否持久化
     *        'mirror'=>true|false ,//队列是否镜像
     *        'autodelete'=>true|false ,//队列是否自动删除
     *    )
     */
    'default' => array(
        'host' => '192.168.1.135',
        'port' => 5672,
        'login' => 'admin',
        'password' => 'admin1234',
        'vhost' => 'vhost1',
        'exchangeName' => 'test',
        'routeKey' => 'test',
        'queueName' => 'mq_shop',
        'exchangeType'=> AMQP_EX_TYPE_DIRECT,
        'durable'=>true ,
        'mirror'=>false ,
        'autodelete'=>false,
    ),


);
