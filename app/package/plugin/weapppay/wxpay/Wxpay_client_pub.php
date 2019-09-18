<?php
include_once("Common_util_pub.php");
/**
 * 请求型接口的基类
 */ 
class Wxpay_client_pub extends Common_util_pub 
{
	var $parameters;//请求参数，类型为关联数组
	public $response;//微信返回的响应
	public $result;//返回参数，类型为关联数组
	var $url;//接口链接
	var $curl_timeout;//curl超时时间
	
	/**
	 * 	作用：设置请求参数
	 */
	function setParameter($parameter, $parameterValue)
	{
		$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}
	
	/**
	 * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
	 *  公众账号ID    appid	
	 *  商户号  mch_id
	 *  随机字符串 nonce_str
	 *  签名  sign
	 */
	function createXml($appid,$mch_id,$key)
	{		
	   	$this->parameters["appid"] = $appid;//公众账号ID
	   	$this->parameters["mch_id"] = $mch_id;//商户号
	    $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串	
		print_r($this->parameters);die;
	    $this->parameters["sign"] = $this->getSign($this->parameters,$key);//签名		
	    return  $this->arrayToXml($this->parameters);
	}
	
	/**
	 * 	作用：post请求xml
	 */
	function postXml($appid,$mch_id,$key)
	{		
	    $xml = $this->createXml($appid,$mch_id,$key);	
		$this->response = $this->postXmlCurl($xml,$this->url,$this->curl_timeout);		
		return $this->response;
	}
	
	/**
	 * 	作用：使用证书post请求xml
	 */
	function postXmlSSL($appid,$mch_id,$key)
	{	
	    $xml = $this->createXml($appid,$mch_id,$key);
		$this->response = $this->postXmlSSLCurl($xml,$this->url,$this->curl_timeout);
		return $this->response;
	}

	/**
	 * 	作用：获取结果，默认不使用证书
	 */
	function getResult($appid,$mch_id,$key)
	{		
		$this->postXml($appid,$mch_id,$key);
		$this->result = $this->xmlToArray($this->response);
		return $this->result;
	}
}
?>