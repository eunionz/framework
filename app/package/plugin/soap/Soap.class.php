<?php
/**
 * EUnionZ PHP Framework Soap Plugin class
 * soap服务请求插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\soap;


defined('APP_IN') or exit('Access Denied');

class Soap extends \cn\eunionz\core\Plugin
{

//   //print_r($this->P('soap')->query('http://www.webxml.com.cn/WebServices/ChinaStockWebService.asmx','getStockInfoByCode',array('theStockCode'=>'sh000001')));

//print_r($this->P('soap')->query('http://www.webxml.com.cn/WebServices/MobileCodeWS.asmx','getMobileCodeInfo',array('mobileCode'=>'18628006328','userID'=>'')));
//exit;

    public function query($webservice_url,$method,$webservice_params,$charset='utf-8'){

        require_once "lib/nusoap.php";
        header("Content-Type:text/html;charset=utf-8");

//http://www.webxml.com.cn/WebServices/ChinaStockWebService.asmx  array('theStockCode'=>'sh000001')
        $client = new \Nusoap_Client($webservice_url . "?wsdl","wsdl");

        $client->soap_defencoding = $charset;
        $client->decode_utf8 = false;
        $client->xml_encoding = $charset;

        try {

           return $client->call($method,$webservice_params); // will cause a Soap Fault if divide by zero

        } catch(Exception $e) {
            print "Sorry an error was caught executing your request: {$e->getMessage()}";
        }
        return false;

    }


    /**
     * 静态方法,该方法输入数组并返回数组
     *
     * @param unknown_type $array 输入的数组
     * @param unknown_type $in 输入数组的编码
     * @param unknown_type $out 返回数组的编码
     * @return unknown 返回的数组
     */
    public function Conversion(&$array,$in,$out)
    {
        foreach ($array as $key=>$value)
        {

            if (!is_array($value)) {
                print_r($value ."<br/>");
                $value=iconv($in,$out,$value);
                $array[$key]=$value;
            }else {
                $this->Conversion($value,$in,$out);
            }
        }

    }

}
