<?php
/**
 * EUnionZ PHP Framework Curl Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\curl;


defined('APP_IN') or exit('Access Denied');

class Curl extends \cn\eunionz\core\Plugin
{

    protected $url;         // url参数
    protected $data;        // data参数
    // request
    protected $request_url = '';       // 请求地址
    protected $request_data = array();  // 请求参数
    protected $request_timeout = 30;       // 请求超时时间(单位秒)  0为无限等待（同步执行）  非0时，为异步执行方法


    //POST模拟提交
    public function CurlPost($url, $data = null,$headers=null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        if($headers) {
            $headerArr = array();
            foreach ($headers as $n => $v) {
                $headerArr[] = $n .':' . $v;
            }
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if($headerArr) curl_setopt ($curl, CURLOPT_HTTPHEADER , $headerArr );  //构造IP
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
        curl_setopt($curl, CURLOPT_TIMEOUT,$this->request_timeout);           // curl允许执行时间
        $result = curl_exec($curl);
        $aStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if (intval($aStatus) == 200) {
            return $result;
        }else{
            return false;
        }
    }
    
    //GET模拟提交
    public function CurlGet($url,$headers=null)
    {
    	$ch = curl_init();
    	
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    	if($headers) {
    		$headerArr = array();
    		foreach ($headers as $n => $v) {
    			$headerArr[] = $n .':' . $v;
    		}
    	}
    	if($headerArr) curl_setopt ($curl, CURLOPT_HTTPHEADER , $headerArr );  //构造IP
    	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
    	curl_setopt($curl, CURLOPT_TIMEOUT,$this->request_timeout);           // curl允许执行时间
    	
    	$result = curl_exec($ch);
    	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    	
    	$body = json_decode($result, TRUE);
    	if ($body === NULL) {
    		$body = $result;
    	}
    	
    	curl_close($ch);
    	return compact('status', 'body');
    }
    


    //POST模拟提交json
    public function http_post_data($url, $data_string, $headers=null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        if($headers) {
            $headerArr = array();
            foreach ($headers as $n => $v) {
                $headerArr[] = $n .':' . $v;
            }
            if(!empty($headerArr)) {
                curl_setopt ($ch, CURLOPT_HTTPHEADER , $headerArr );
            }
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);           // curl允许执行时间
        ob_start();
        $return_content = curl_exec($ch);
        ob_end_clean();
        $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (intval($return_code) == 200) {
            return $return_content;
        }else{
            $this->loadCore('log')->write(APP_DEBUG,$return_code . ':' . $return_content);
            return false;
        }
    }

    /**
     * @param $url
     */
    public function http_get($url, $headers=null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);           // curl允许执行时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($headers) {
            $headerArr = array();
            foreach ($headers as $n => $v) {
                $headerArr[] = $n .':' . $v;
            }
            if(!empty($headerArr)) {
                curl_setopt ($ch, CURLOPT_HTTPHEADER , $headerArr );
            }
        }
		$result = curl_exec($ch);
		$aStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//var_dump($result);die;
		curl_close($ch);
		if (intval($aStatus) == 200) {
			return $result;
		}else{
			return false;
		}
        ob_start();
        $return_content = curl_exec($ch);
        ob_get_contents();
        ob_end_clean();

        curl_close($ch);
        return $return_content;
    }


    /**
     * @name 请求地址
     * @param $url
     */
    public function url_data($url)
    {
        $this->url = $url;

        $parseUrl = parse_url($url);
        $this->request_url = '';
        $this->request_url .= $parseUrl['scheme'] == 'https' ? 'https://' : 'http://';
        $this->request_url .= $parseUrl['host'];
        $this->request_url .= isset($parseUrl['port']) ? ':' . $parseUrl['port'] : ':80';
        $this->request_url .= $parseUrl['path'];
        $parseStr = array();
        isset($parseUrl['query']) && parse_str($parseUrl['query'], $parseStr);
        $this->request_data = array_merge($this->request_data, $parseStr);

        return $this;
    }

    /**
     * @name 请求数据
     * @param $data 为数组
     */
    public function data($data)
    {
        $this->request_data = array_merge($this->request_data, $data);
        return $this;
    }


    /**
     * @name 请求超时时间  单位：秒
     * @param $timeout 超时， 当timeout 为0时,同步执行， 非零整数时，类为异步多线程执行
     */
    public function timeout($timeout)
    {
        // $this->request_timeout    = (int)$timeout==0 ? 1 : (int)$timeout;
        $this->request_timeout = (int)$timeout;
        return $this;
    }

    /**
     * @name get请求
     * @return mixed [status, data]
     **/
    public function get_method($url='')
    {
        $returnData = array();
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL
        if(empty($url)){
            $url = $this->request_url . '?' . http_build_query($this->request_data);
        }
        curl_setopt($ch, CURLOPT_HTTPGET, 1);           // 请求类型 get
        curl_setopt($ch, CURLOPT_URL, $url);            // 请求地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    // 将curl_exec()获取的信息以文件流的形式返回,不直接输出。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);           // curl允许执行时间

        // 3. 执行并获取返回内容
        $output = curl_exec($ch);
        if ($output === false) {
            $returnData['status'] = 0;
            $returnData['data'] = curl_error($ch);
        } else {
            $returnData['status'] = 1;
            $returnData['data'] = $output;
        }
        // 4. 释放curl句柄
        curl_close($ch);
        return $returnData;
    }

    /**
     * @name post请求
     * @return mixed [status, data]
     */
    public function post_method()
    {
        $returnData = array();
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL

        curl_setopt($ch, CURLOPT_POST, 1);                  // 请求类型 post
        curl_setopt($ch, CURLOPT_URL, $this->request_url);   // 请求地址
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request_data);   // 请求数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        // 将curl_exec()获取的信息以文件流的形式返回,不直接输出。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->request_timeout);    // 连接等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);           // curl允许执行时间

        // 3. 执行并获取返回内容
        $output = curl_exec($ch);
        if ($output === false) {
            $returnData['status'] = 0;
            $returnData['data'] = curl_error($ch);
        } else {
            $returnData['status'] = 1;
            $returnData['data'] = $output;
        }
        // 4. 释放curl句柄
        curl_close($ch);
        return $returnData;
    }

}