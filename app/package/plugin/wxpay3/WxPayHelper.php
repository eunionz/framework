<?php
/**
* 
*/
defined('APP_IN') or exit('Access Denied');
include_once("CommonUtil.php");
include_once("SDKRuntimeException.class.php");
include_once("MD5SignUtil.php");
class WxPayHelper
{
	var $parameters; //cft 参数


    var $APPID;  //微信公众服务平台  appid
    var $MCH_ID; //微信公众服务平台  商户号
    var $APIKEY;//微信公众服务平台  API密钥
    var $APPSERCERT; //通用加密签 通加密串

    public function init($appid,$appkey,$mch_id,$appsercert){
        $this->APPID=$appid;
        $this->APPKEY=$appkey;
        $this->MCH_ID=$mch_id;
        $this->APPSERCERT=$appsercert;
        return $this;
    }

    function __construct()
	{
		
	}
	function setParameter($parameter, $parameterValue) {
		$this->parameters[CommonUtil::trimString($parameter)] = CommonUtil::trimString($parameterValue);
	}
	function getParameter($parameter) {
		return $this->parameters[$parameter];
	}
	protected function create_noncestr( $length = 16 ) {  
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";  
		$str ="";  
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
			//$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];  
		}  
		return $str;  
	}
	function check_cft_parameters(){
		if($this->parameters["appid"] == null || $this->parameters["body"] == null || $this->parameters["mch_id"] == null ||
			$this->parameters["out_trade_no"] == null || $this->parameters["total_fee"] == null || $this->parameters["fee_type"] == null ||
			$this->parameters["notify_url"] == null || $this->parameters["spbill_create_ip"] == null || $this->parameters["openid"] == null
			)
		{
			return false;
		}
		return true;

	}

	public function get_prepayid(){
		 try {
		 	if($this->APPKEY == ""){
		 			throw new SDKRuntimeException("APIKEY为空！" . "<br>");
		 	}
		 	ksort($this->parameters);

            $commonUtil = new CommonUtil();
		 	$bizString = $commonUtil->formatBizQueryParaMap($this->parameters, false);
            $bizString .="&key=".$this->APPKEY;
            $this->setParameter("sign", strtoupper(md5($bizString)));

            $xml=$commonUtil->arrayToXml($this->parameters);

            $url="https://api.mch.weixin.qq.com/pay/unifiedorder";
            $data = $this->CurlPost($url,$xml);
            $data = (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
             if(!$data){
                 throw new SDKRuntimeException("获取微信预支付ID失败！" . "<br>");
             }
            if($data['return_code']=="SUCCESS"){
                if($data['result_code']=="FAIL"){
                    throw new SDKRuntimeException("获取微信预支付ID失败！错误原因：" . $data['err_code_des'] ."!!<br>");
                }
                return $data['prepay_id'];
            }
             throw new SDKRuntimeException("获取微信预支付ID失败！" . "<br>");

		 }catch (SDKRuntimeException $e)
		 {
			die($e->errorMessage());
		 }
	}

    protected function get_biz_sign($bizObj){
        try {
            if($this->APPKEY == ""){
                throw new SDKRuntimeException("APIKEY为空！" . "<br>");
            }
            $bizString="appId=".$bizObj['appId'];
            $bizString .="&nonceStr=".$bizObj['nonceStr'];
            $bizString .="&package=".$bizObj['package'];
            $bizString .="&signType=".$bizObj['signType'];
            $bizString .="&timeStamp=".$bizObj['timeStamp'];
            $bizString .="&key=".$this->APPKEY;
            return strtoupper(md5($bizString));
        }catch (SDKRuntimeException $e)
        {
            die($e->errorMessage());
        }
    }

    function create_biz_package($prepay_id){
        try {

            if($this->check_cft_parameters() == false) {
                throw new SDKRuntimeException("生成package参数缺失！" . "<br>");
            }
            $nativeObj["appId"] = $this->APPID;
            $nativeObj["package"] = "prepay_id=".$prepay_id;
            $nativeObj["timeStamp"] = time()."";
            $nativeObj["nonceStr"] = $this->create_noncestr();
            $nativeObj["signType"] = "MD5";
            $nativeObj["paySign"] = $this->get_biz_sign($nativeObj);
            file_put_contents(APP_REAL_PATH . "/data/1.txt",json_encode($nativeObj));
            return json_encode($nativeObj);

        }catch (SDKRuntimeException $e)
        {
            die($e->errorMessage());
        }

    }

    //POST模拟提交
    public function CurlPost($url,$data){
        $SERVER = ctx()->server();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_USERAGENT, isset($SERVER['HTTP_USER_AGENT'])?$SERVER['HTTP_USER_AGENT']:'');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $this->error_log(curl_error($curl));
        }
        curl_close($curl);
        return $result;
    }

}

?>