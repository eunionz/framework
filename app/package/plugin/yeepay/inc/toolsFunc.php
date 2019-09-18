<?php

/**
  * @检查一个数组是否是有效的
  * @$checkArray 数组
  * @$arrayKey 数组索引
  * @return boolean
  * 如果$arrayKey传参，则不止检查数组，
  * 而且检查索引是否存在于数组中。
  *
 */
function isViaArray($checkArray, $arrayKey = null) {
	
	if ( !$checkArray || empty($checkArray) ) {
		
		return false;
	}
	
	if ( !$arrayKey ) {
		
		return true;
	}
	
	return array_key_exists($arrayKey, $checkArray);
}

/**
  * @取得hmac签名
  * @$dataArray 明文数组或者字符串
  * @$key 密钥
  * @return string
  *
 */
function getHmac(array $dataArray, $key) {
	
	if ( !isViaArray($dataArray) ) {
	
		return null;	
	}
	
	if ( !$key || empty($key) ) {
		
		return null;
	}
	
	if ( is_array($dataArray) ) {
	
		$data = implode("", $dataArray);
	} else {
	
		$data = strval($dataArray);	
	}
	
	//print_r($data);
	
	if ( getLocaleCode() != "UTF-8" ) {
	
		$key = iconv(getLocaleCode(), "UTF-8", $key);
		$data = iconv(getLocaleCode(), "UTF-8", $data);	
	}
	

	$b = 64; // byte length for md5
	if (strlen($key) > $b) {
		
		$key = pack("H*",md5($key));
	}
	
	$key = str_pad($key, $b, chr(0x00));
	$ipad = str_pad('', $b, chr(0x36));
	$opad = str_pad('', $b, chr(0x5c));
	$k_ipad = $key ^ $ipad ;
	$k_opad = $key ^ $opad;

	return md5($k_opad . pack("H*",md5($k_ipad . $data)));
}

/**
  * @取得aes加密
  * @$dataArray 明文字符串
  * @$key 密钥
  * @return string
  *
 */
function getAes($data, $aesKey) {

	//print_r(mcrypt_list_algorithms());
	//print_r(mcrypt_list_modes());

	$aes = new \CryptAES();
	$aes->set_key($aesKey);
	$aes->require_pkcs5();
	$encrypted = strtoupper($aes->encrypt($data));
	
	return $encrypted;

}

/**
  * @取得aes解密
  * @$dataArray 密文字符串
  * @$key 密钥
  * @return string
  *
 */
function getDeAes($data, $aesKey) {

	$aes = new \CryptAES();
	$aes->set_key($aesKey);
	$aes->require_pkcs5();
	$text = $aes->decrypt($data);
	
	return $text;
}

/**
  * @发起http请求
  * @$url 请求的url
  * @$method POST 或者 GET
  * @$postfields 请求的参数
  * @return mixed
  */
function post($url, $postfields = array(),$uploadFile = array()) {
	$http_info = array();	
	$header = array(
		'Content-Type: multipart/form-data',
	);
	$ci = curl_init();
	curl_setopt($ci, CURLOPT_URL, $url);
	curl_setopt($ci, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ci, CURLOPT_BINARYTRANSFER,true); 
	curl_custom_postfields($ci, $postfields, $uploadFile);
	curl_setopt($ci, CURLOPT_USERAGENT, "Yeepay ZGT PHPSDK v1.1x");
	curl_setopt($ci, CURLOPT_TIMEOUT, 30);
	//curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ci, CURLOPT_HEADER, false);	
	curl_setopt($ci, CURLOPT_POST, true);
	
	
	$response = curl_exec($ci);
	$http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
	$http_info = array_merge($http_info, curl_getinfo($ci));
	//print_r($http_info);
	//echo "<br/>";
	curl_close ($ci);
	return $response;
}

/**
* 重写POST 参数body
* 
* @param resource $ch cURL resource
* @param array $assoc name => value
* @param array $files name => path
* @return bool
*/
function curl_custom_postfields($ch, array $assoc = array(), array $files = array()) {
    
    // invalid characters for "name" and "filename"
    static $disallow = array("\0", "\"", "\r", "\n");
    
    // build normal parameters
    foreach ($assoc as $k => $v) {
        $k = str_replace($disallow, "_", $k);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"",
            "",
            filter_var($v), 
        ));
    }
    
    // build file parameters
    foreach ($files as $k => $v) {    	
        switch (true) {
            case false === $v = realpath(filter_var($v)):
            case !is_file($v):
            case !is_readable($v):
                continue; // or return false, throw new InvalidArgumentException
        }
        $data = file_get_contents($v);        
        $v = call_user_func("end", explode(DIRECTORY_SEPARATOR, $v));
        $k = str_replace($disallow, "_", $k);
        $v = str_replace($disallow, "_", $v);
        $body[] = implode("\r\n", array(
            "Content-Disposition: form-data; name=\"{$k}\"; filename=\"{$v}\"",
            "Content-Type: application/octet-stream",
            "",
            $data, 
        ));
    }
    
    // generate safe boundary 
    do {
        $boundary = "---------------------" . md5(mt_rand() . microtime());
    } while (preg_grep("/{$boundary}/", $body));
    
    // add boundary for each parameters
    array_walk($body, function (&$part) use ($boundary) {
        $part = "--{$boundary}\r\n{$part}";
    });
    
    // add final boundary
    $body[] = "--{$boundary}--";
    $body[] = "";
    
    // set options
    return @curl_setopt_array($ch, array(
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => implode("\r\n", $body),
        CURLOPT_HTTPHEADER => array(
            "Expect: 100-continue",
            "Content-Type: multipart/form-data; boundary={$boundary}", // change Content-Type
        ),
    ));
}

/**
  * @使用特定function对数组中所有元素做处理
  * @&$array 要处理的字符串
  * @$function 要执行的函数
  * @$apply_to_keys_also 是否也应用到key上
  *
  */
function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
}

/**
  *
  * @将数组转换为JSON字符串（兼容中文）
  * @$array 要转换的数组
  * @return string 转换得到的json字符串
  *
  */
function cn_json_encode($array) {
    $array = cn_url_encode($array);
    $json = json_encode($array);
    return urldecode($json);
}

/**
  *
  * @将数组统一进行urlencode（兼容中文）
  * @$array 要转换的数组
  * @return array 转换后的数组
  *
  */
function cn_url_encode($array) {
    arrayRecursive($array, "urlencode", true);
	return $array;
}

?>