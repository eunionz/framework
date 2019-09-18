<?php
/**
 * Eunionz PHP Framework ActiveMQ component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */


namespace cn\eunionz\component\activemq;

use cn\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');
// load pdo class
require_once('Stomp.php');

class Activemq extends Component  {
    private $activemq_url='tcp://localhost:61613';
    private $user='admin';
    private $password='admin';
    private $queue='/queue/test';
    private $readtimeout=3;

    /**
     * 构造函数
     * Activemq constructor.
     * @param null $activemq_url  ActiveMQ 消息队列tcp连接url
     * @param null $user 连接用户名
     * @param null $password  连接密码
     * @param null $queue  连接队列
     * @param null $readtimeout  读取队列超时时间，单位：秒
     */
    public function __construct($queue = null,$user=null,$password=null,$readtimeout=null,$activemq_url=null)
    {
        $this->init($queue,$user,$password,$readtimeout,$activemq_url);
    }

    /**
     * 初始化ActiveMQ消息队列
     * @param null $activemq_url  ActiveMQ 消息队列tcp连接url
     * @param null $user 连接用户名
     * @param null $password  连接密码
     * @param null $queue  连接队列
     * @param null $readtimeout  读取队列超时时间，单位：秒
     * @return $this
     */
    public function init($queue = null,$user=null,$password=null,$readtimeout=null,$activemq_url=null)
    {
        if($activemq_url){
            $this->activemq_url = $activemq_url;
        }
        if($user){
            $this->user = $user;
        }
        if($password){
            $this->password = $password;
        }
        if($queue){
            $this->queue = $queue;
        }
        if($readtimeout){
            $this->readtimeout = $readtimeout;
        }
        return $this;
    }

    /**
     * 发送消息到消息队列
     * @param $body 发送的消息体，可以为任意数据类型
     * @return bool true--成功  false--失败
     */
    public function send($body){
        // make a connection
        $con = new \Stomp($this->activemq_url);
        $con->sync = true;
        // connect
        $con->connect($this->user,$this->password);
        // send a message to the queue
        $rs = $con->send($this->queue, serialize($body));

        // disconnect
        $con->disconnect();
        return $rs;
    }


    /**
     * 消费消息队列
     * @return mixed|null  null--空的消息队列  mixed--消息体
     */
    public function consume(){
        // make a connection
        $con = new \Stomp($this->activemq_url);
        $con->setReadTimeout($this->readtimeout);
        $con->sync = true;
        // connect
        $con->connect($this->user,$this->password);
        // subscribe to the queue
        $con->subscribe($this->queue);
        // receive a message from the queue
        $msg = $con->readFrame();
        // do what you want with the message
        $body = null;
        if ( $msg != null) {
            $body = unserialize($msg->body);
            // mark the message as received in the queue
            $con->ack($msg);
        }
        //$con->unsubscribe("/queue/test");
        // disconnect
        $con->disconnect();
        return $body;
    }


// include a library
//header("Content-Type: text/html;charset=utf-8");
//for ($i=1;$i<10;$i++){
//    $arr=array('a'=>'张三'.$i,'b'=>'123456'.$i);
//    $rs = send($arr);
//    echo $rs . '<br/>';
//
//}
//
//$arr = consume();
//print_r($arr);

}

