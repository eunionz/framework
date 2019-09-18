<?php
/**
 * EUnionZ PHP Framework Weixin Plugin class
 * 微信jssdk插件
 * Created by PhpStorm.
 * User: wangtao  (719863381@qq.com)
 * Date: 16-4-6
 * Time: 上午10:16
 */

namespace package\plugin\weixinjssdk;


defined('APP_IN') or exit('Access Denied');


class Weixinjssdk extends \cn\eunionz\core\Plugin
{

    private $appId;
    private $appSecret;
    private $SHOP_ID;

    //初始化函数
    public function init($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->SHOP_ID = $this->getConfig('shop', 'SHOP_ID');
        return $this;
    }

    public function getSignPackage($url = "")
    {
        $jsapiTicket = $this->getJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId" => $this->appId,
            "nonceStr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "string" => $string,
            "rawString" => $string
        );
        return $signPackage;
    }

    public function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function getJsApiTicket()
    {
        $now = time();
        $cache_token = $this->loadPlugin('cache')->getCache('shop_' . $this->SHOP_ID . '_jssdk_ticket', $this->appId . '_ticket');
        if (!isset($cache_token['ticket']) || $now > $cache_token['expires_in']) {
            $accessToken = $this->getAccessToken();
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url), true);
            if (isset($res['ticket'])) {
                $cache = array(
                    'ticket' => $res['ticket'],
                    'expires_in' => $now + intval($res['expires_in'])
                );
                $this->loadPlugin('cache')->setCache('shop_' . $this->SHOP_ID . '_jssdk_ticket', $this->appId . '_ticket', $cache);
                $ticket = $res['ticket'];
            }
        } else {
            $ticket = $cache_token['ticket'];
        }
        return $ticket;
    }

    public function getAccessToken()
    {
        $now = time();
        $cache_token = $this->loadPlugin('cache')->getCache('shop_' . $this->SHOP_ID . '_jssdk_token', $this->appId . '_token');
        if (!isset($cache_token['access_token']) || $now > $cache_token['expires_in']) {
            $url = APP_WEIXIN_ACCESS_TOKEN_AGENT_URL . urlencode("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret");
            //$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = json_decode($this->httpGet($url), true);
            if (isset($res['access_token'])) {
                $cache = array(
                    'access_token' => $res['access_token'],
                    'expires_in' => $now + intval($res['expires_in'])
                );
                $this->loadPlugin('cache')->setCache('shop_' . $this->SHOP_ID . '_jssdk_token', $this->appId . '_token', $cache);
                $access_token = $res['access_token'];
            }
        } else {
            $access_token = $cache_token['access_token'];
        }
        return $access_token;
    }

    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

    public function floder($floderpath)
    {
        if (!file_exists($floderpath)) {
            mkdir($floderpath);
        }
    }
}
