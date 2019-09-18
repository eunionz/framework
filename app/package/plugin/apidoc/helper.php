<?php
/*
 * $url 访问地址
 * $data 参数数组
 * 格式：array(
 * 		"a"=>"a",
 * 		"b"=>"b"
 * 		)
 * $headers 头信息数组
 * 格式：array(
 * 			"session_sid: 134153151353581asfdasef",
 * 			"client_type: wap"
 * 		)
 * $cookie 字符串
 */
function http_request($url, $data = null, $headers = array(),$cookie=null){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    if (count($headers)){
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    if (!empty($data)){
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    }
    if (!empty($cookie)){
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}
