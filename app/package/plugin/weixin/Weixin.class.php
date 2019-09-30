<?php
/**
 * EUnionZ PHP Framework Weixin Plugin class
 * 微信插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\weixin;


defined('APP_IN') or exit('Access Denied');


class Weixin extends \cn\eunionz\core\Plugin
{

    public $token = '';//token,访问令牌
    public $debug =  false;//是否debug的状态标示，方便我们在调试的时候记录一些中间数据
    public $setFlag = false;
    public $msgtype = 'text';   //('text','image','location')
    public $msg = array();

    public function init($token,$debug)
    {
        $this->token=$token;
        $this->debug = $debug;
        return $this;
    }

    public function __construct()
    {

    }

    //获得用户发过来的消息（消息内容和消息类型  ）
    public function getMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if ($this->debug) {
            $this->write_log($postStr);
        }
        if (!empty($postStr)) {
            $this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->msgtype = strtolower($this->msg['MsgType']);
        }
    }


    //回复文本消息
    public function makeText($text='')
    {
        $CreateTime = time();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $textTpl = "<xml><ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName><FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName><CreateTime>{$CreateTime}</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
        return sprintf($textTpl,$text);
    }

    //根据数组参数回复图文消息
    public function makeNews($newsData=array())
    {
        $CreateTime = time();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $newTplHeader = "<xml><ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName><FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName><CreateTime>{$CreateTime}</CreateTime><MsgType><![CDATA[news]]></MsgType><ArticleCount>%s</ArticleCount><Articles>";
        $newTplItem = "<item><Title><![CDATA[%s]]></Title><Description><![CDATA[%s]]></Description><PicUrl><![CDATA[%s]]></PicUrl><Url><![CDATA[%s]]></Url></item>";
        $newTplFoot = "</Articles></xml>";
        $Content = '';
        $itemsCount = count($newsData['items']);
        $itemsCount = $itemsCount < 10 ? $itemsCount : 10;//微信公众平台图文回复的消息一次最多10条
        if ($itemsCount) {
            foreach ($newsData['items'] as $key => $item) {
                if ($key<=9) {
                    $Content .= sprintf($newTplItem,$item['title'],$item['description'],$item['picurl'],$item['url']);
                }
            }
        }
        $header = sprintf($newTplHeader,$itemsCount);
        $footer = $newTplFoot;
        return $header . $Content . $footer;
    }


    public function reply($data)
    {
        if ($this->debug) {
            $this->write_log($data);
        }
        echo $data;
    }


    public function write_log($log){
        if($this->debug){
            $filename = ctx()->getAppRuntimeRealPath() .date('Y_m_d').'.log.php';
            file_put_contents($filename , date('Y-m-d H:i:s') .'：' .  $log .PHP_EOL, FILE_APPEND);
        }
    }


    //验证token
    public function checkSignature($token)
    {
        $GET = ctx()->get();

        $signature = $GET["signature"];
        $timestamp = $GET["timestamp"];
        $nonce = $GET["nonce"];

        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
}
