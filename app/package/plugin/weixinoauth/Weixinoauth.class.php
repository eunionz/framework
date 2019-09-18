<?php
/**
 * EUnionZ PHP Framework Weixin Plugin class
 * 微信插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\weixinoauth;


defined('APP_IN') or exit('Access Denied');


class Weixinoauth extends \com\eunionz\core\Plugin
{

    public $appid = '';//微信公众号APPID
    public $appsecret = '';//微信公众号安全码
    public $CURL_TIMEOUT = 3;

    public function init($appid,$appsecret){
        $this->appid = $appid;
        $this->appsecret = $appsecret;
        return $this;
    }

    /**
     * 	作用：生成可以获得code的url
     */
    function createOauthUrlForCode($redirectUrl,$scope="snsapi_userinfo")
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["redirect_uri"] = urlencode($redirectUrl);
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = $scope;
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    /**
     * 	作用：生成可以获得openid的url
     */
    function createOauthUrlForOpenid()
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["secret"] = $this->appsecret;
        $urlObj["code"] = $this->code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }
    /**
     * 获取用户信息
     * @param unknown $access_token
     * @param unknown $openid
     * @param unknown $lang
    */
    public function getUserInfo($access_token,$openid,$lang=''){
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = $lang ? $lang : "zh_CN";
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        $url = "https://api.weixin.qq.com/sns/userinfo?".$bizString;
        $context ['http'] = array (
            'timeout' => 3,
            'method' => 'GET',
        );
        $data = file_get_contents($url , false, stream_context_create ( $context ));
        $data = json_decode($data,true);
//         print_r($data);
//         exit;
//         echo $url;
//         exit;
        return $data;
    }
    
    /**
     * 	作用：通过curl向微信提交code，以获取openid
     */
    function getOpenid()
    {
        $url = $this->createOauthUrlForOpenid();
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->CURL_TIMEOUT);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res,true);
//         $this->openid = $data['openid'];
        return $data;
    }
    
    /**
     * 	作用：格式化参数，签名过程需要使用
     */
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }


    public function get_authorize_url($redirect_uri = '',$path='')
    {
    	$redirect_uri = urlencode($redirect_uri);
    	$path = urlencode($path);
    
    	header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state={$path}#wechat_redirect");
    }
    //获取access_token
    public function get_access_token($code = '')
    {
    	$token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->appid}&secret={$this->appsecret}&code={$code}&grant_type=authorization_code";
    	$token_data = $this->http($token_url);
    	return $token_data;
    }
    //获取用户信息函数
    public function get_user_info($access_token = '', $open_id = '')
    {
    	if($access_token && $open_id)
    	{
    		$info_url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
    		$info_data = $this->http($info_url);
    		return $info_data;
    	}
    }
    //校验access_token
    public function check_access_token($access_token = '', $open_id = '')
    {
    	if($access_token && $open_id)
    	{
    		$info_url = "https://api.weixin.qq.com/sns/auth?access_token={$access_token}&openid={$open_id}&lang=zh_CN";
    		$info_data = $this->http($info_url);
    		return json_decode($info_data[1], TRUE);
    	}
    }
    //http方法
    public function http($url)
    {
    	$ch = curl_init();
    	curl_setopt($ch,CURLOPT_URL,$url);
    	curl_setopt($ch, CURLOPT_HEADER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	$data = curl_exec($ch);
    	curl_close($ch);
    	return $data;
    }
    
}
