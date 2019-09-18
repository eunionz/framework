<?php
date_default_timezone_set('PRC');
require_once(__DIR__ . "/../inc/config.php");
/**
  * @请求易宝服务
  *
  */
class RequestService {
	
	//业务接口名称
	protected $bizName;

	//业务配置
	protected $bizConfig;
	
	//商户编号
	protected $customernumber;
	
	//hmac密钥
	protected $keyForHmac;
	
	//AES key
	protected $keyForAES;
	

	
	//存放原始请求数据
	protected $request = array();
	
	//存放请求数据
	protected $requestData = array();
	
	//存放原始返回数据
	protected $response;
	
	//存放解析后的返回数据
	protected $responseData = array();
	
	public function __construct($bizName) {
		global $infConfig;

		if ( !$bizName ) {
		
			throw new \ZGTException("bizName is null, [" . $bizName . "].");
		}
		
		if ( !isViaArray($infConfig) ) {
		
			throw new \ZGTException("infConfig is null.");
		}
		
		if ( !array_key_exists($bizName, $infConfig) ) {
			
			throw new \ZGTException("biz of infConfig is not found[" . $bizName . "].");
		}
		
		$this->bizName = $bizName;
		$this->customernumber = getCustomerNumber();
		$this->keyForHmac = getKeyValue();
		$this->keyForAES = getKeyForAes();
		$this->bizConfig = $infConfig[$bizName];
		
	}
	
	public function __destruct() {}
	
	public function sendRequest($queryData) {
		//global $sysConfig;
		//var_dump($sysConfig['customernumber']);die;
		$this->request = $queryData;

		/* echo "queryData:<br />";
		print_r($queryData);
		echo "<br /><hr />"; */
 
		if ( !$queryData || !is_array($queryData) ) {
			
			throw new \ZGTException("query is null or isn't array.");
		}
		
		//取得需要请求的URL
		$requestURL = $this->bizConfig["requestURL"];

		//检查必填项
		foreach ( $this->bizConfig["mustFillRequest"] as $fKey => $fValue ) {
			
			if ( !array_key_exists($fValue, $queryData) ) {
				
				throw new \ZGTException("queryData.[${fValue}] is must fill, but not found.");
			}
			
			if ( !$queryData[$fValue] ) {
				
				throw new \ZGTException("queryData.[${fValue}] is must fill.");
			}
		}
		//生成签名
  
		$hmacGenConfig = $this->bizConfig["needRequestHmac"];
		$hmacData = array();
		$hmacData["customernumber"] = $this->customernumber;
		foreach ( $hmacGenConfig as $hKey => $hValue ) {
			
			$v = "";
			//判断$queryData中是否存在此索引并且是否可访问
			if ( isViaArray($queryData, $hValue) && $queryData[$hValue] ) {
				
				$v = $queryData[$hValue];
			}
			
			//取得对应加密的明文的值
			$hmacData[$hValue] = $v;
		}
		//$hmacData = cn_url_encode($hmacData);
	
		/* echo "hmac:<br />";
		print_r($hmacData);
		echo "<br /><hr />"; */
	
		$hmac = getHmac($hmacData, $this->keyForHmac);
		
		//放到请求数组
		$requestDataConfig = $this->bizConfig["needRequest"];
		$dataMap = array();
		 if ($requestURL != getSysConfig("downloadURL")){
		$dataMap["customernumber"] = $this->customernumber;}
		else {		
			$dataMap["operator_no"] = $this->customernumber;
			}
		foreach ( $requestDataConfig as $rKey => $rValue ) {
			
			$v = "";
			//判断$queryData中是否存在此索引并且是否可访问
			if ( isViaArray($queryData, $rValue) && $queryData[$rValue] ) {
				
				$v = $queryData[$rValue];
			}
			
			//取得对应加密的明文的值
			$dataMap[$rValue] = $v;
		}
		$dataMap["hmac"] = $hmac;

		
		/* echo "dataMap<br />";
		print_r($dataMap);
		echo "<br /><hr />"; */
		
		//转换成json格式
		$dataJsonString = cn_json_encode($dataMap);
	
		/* echo "dataJsonString<br />";
		print_r($dataJsonString);
		echo "<br /><hr />"; */
	
	//	$dataJsonString = json_encode($dataMap);
		$dataJsonString = iconv(getLocaleCode(), getRemoteCode(),cn_json_encode($dataMap));
		
		
		
		//生成请求数据包
		$data = getAes($dataJsonString, $this->keyForAES);
		 if ($requestURL != getSysConfig("downloadURL")){
		$postfields = array("customernumber" => $this->customernumber, "data" => $data);}
		else{	
			$postfields = array("authorize_no" => $this->customernumber, "certify_token" => $data);}
		//print_r($postfields);
	 
		//保存请求数据
		$this->requestData["requestURL"] = $requestURL;
		$this->requestData["requestData"] = $postfields;
		
		if (empty($queryData["file"]["name"])){
			$this->response = post($requestURL, $postfields);
		}else{
			$this->response = post($requestURL, $postfields,array('file' => $queryData['file']['file_path']));			
		}
		return $this->response;
	}
	
	//对账文件
   public function filedownResponse() {
			
			$content=iconv(getRemoteCode(), getLocaleCode(), $this->response);
		 // echo $notice;

          $path='/var/www/html/demo/zgt-bz/php/download/'.date("Y-m-d");  //配置文件夹绝对路径，以日期为文件夹名称
          if(!file_exists($path));  //检测变量中的文件夹是否存在
          {
           mkdir($path,0777,true);         //创建文件夹
          }
          $file = @"$path".'/'.time().".txt";    // 写入的文件
          file_put_contents($file,$content,FILE_APPEND);  // 最简单的快速的以追加的方式写入写入方法，
          return $file;
	}


    public function receviceResponse() {
		
		$responseJsonArray = json_decode($this->response, true);
		/* echo "responseJsonArray<br />";
		print_r($responseJsonArray);
		echo "<br /><hr />"; */
    
		if ( array_key_exists("code", $responseJsonArray)
				&& "1" != $responseJsonArray["code"] ) {

			throw new \ZGTException("response error, errmsg = ["
									 . iconv(getRemoteCode(), getLocaleCode(), $responseJsonArray["msg"])
									 . "], errcode = ["
									 . $responseJsonArray["code"]
									 . "]
									 . ", $responseJsonArray["code"]);
		}
		
		$responseData = getDeAes($responseJsonArray["data"], $this->keyForAES);
		$result = json_decode($responseData, true);
		//进行UTF-8->GBK转码
		$resultLocale = array();
		foreach ( $result as $rKey => $rValue ) {
			if (gettype($rValue) != "array")
			{
				$resultLocale[$rKey] = iconv(getRemoteCode(), getLocaleCode(), $rValue);
			}else
			{
				//处理rValue为数组的情况
				$resultLocale[$rKey]=iconv(getRemoteCode(), getLocaleCode(),cn_json_encode($rValue));
				$resultLocale2 = array();
				foreach ( $rValue[0] as $rKey2 => $rValue2 )
				{
					$resultLocale2[$rKey2] = iconv(getRemoteCode(), getLocaleCode(), $rValue2);
				}
  			$resultLocale[$rKey][0]=cn_json_encode($resultLocale2);
			}
					
		}
		$this->responseData = $resultLocale;
	
		/* echo "resultLocale<br />";
		print_r($resultLocale);
		echo "<br /><hr />"; */

	
	
		if (  "1" != $result["code"] ) {

			throw new \ZGTException("response error, errmsg = [" . $resultLocale["msg"] . "], errcode = [" . $resultLocale["code"] . "].", $result["code"]);
		}

		if ( $result["customernumber"] != $this->customernumber ) {
			
			throw new \ZGTException("customernumber not equals, request is [" . $this->customernumber . "], response is [" . $hmacData["customernumber"] . "].");
		}

		//验证返回签名
		$hmacGenConfig = $this->bizConfig["needResponseHmac"];
		$hmacData = array();
		foreach ( $hmacGenConfig as $hKey => $hValue ) {
			
			$v = "";
			//判断$queryData中是否存在此索引并且是否可访问
			if ( isViaArray($result, $hValue) && $result[$hValue] ) {
				
				$v = $result[$hValue];
			}
			
			//取得对应加密的明文的值
			//$hmacData[$hKey] = $v;
	        $hmacData[$hKey] = iconv(getRemoteCode(), getLocaleCode(), $v);
		}
		$hmac = getHmac($hmacData, $this->keyForHmac);
		
		if ( $hmac != $result["hmac"] ) {
			
			throw new \ZGTException("hmac not equals, response is [" . $result["hmac"] . "], gen is [" . $hmac . "].");
		}
		
		if ( array_key_exists("customError", $result)
		 		&& "" != $result["customError"] ) {
	
			throw new \ZGTException("response.customError error, errmsg = [" . $resultLocale["customError"] . "], errcode = [" . $resultLocale["code"] . "].", $result["code"]);
		}
		
		return $resultLocale;
	}
	
	public function getRequest() {
		
		return $this->request;
	}
	
	public function getRequestData() {
		
		return $this->requestData;
	}

	public function getResponse() {
		
		return $this->response;	
	}
	
	public function getResponseData() {
		
		return $this->responseData;
	}
}

?>