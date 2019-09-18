<?php
/**
 * EUnionZ PHP Framework Alipay_quick_mobile Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\alipay_quick_mobile;


defined('APP_IN') or exit('Access Denied');

/**
 * 支付宝手机网页快速支付
 * Created by liulin
 * Alipay_quick_mobile
 **/

class Alipay_quick_mobile extends \cn\eunionz\core\Plugin
{

    private $alipay_partner="";                        //合作身份者ID，以 2088 开头由 16 位纯数字组成的字符串。请参考“7.1 如何获得PID与密钥”。
    private $alipay_key="";                             //安全检验码，以数字和字母组成的32位字符    如果签名方式设置为“MD5”时，请设置该参数
    private $alipay_private_key_path="";              //商户的私钥（后缀是.pen）文件相对路径  如果签名方式设置为“0001”时，请设置该参数
    private $alipay_ali_public_key_path="";           //支付宝公钥（后缀是.pen）文件相对路径  如果签名方式设置为“0001”时，请设置该参数
    private $alipay_sign_type="0001";                  //签名方式 不需修改
    private $alipay_input_charset="utf-8";            //字符编码格式 目前支持 gbk 或 utf-8
    private $alipay_cacert="";                          //ca证书路径地址，用于curl中ssl校验  请保证cacert.pem文件在当前文件夹目录中
    private $alipay_transport="http";                  //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http

    private $alipay_config=array();                     //支付宝支付参数配置数组




    public function __construct()
    {
        $this->alipay_private_key_path =  APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/key/rsa_private_key.pem';
        $this->alipay_ali_public_key_path =  APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/key/alipay_public_key.pem';
        $this->alipay_cacert =  APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/cacert.pem';
    }

    /**
     * 初始化支付宝接口参数
     */
    public function init($alipay_partner,$alipay_key,$private_key_path="",$ali_public_key_path="",$alipay_transport='http'){

        $this->alipay_partner =  $alipay_partner;
        $this->alipay_key =  $alipay_key;
        $this->alipay_transport =  $alipay_transport;


        //合作身份者id，以2088开头的16位纯数字
        $this->alipay_config['partner']		= $alipay_partner;

        //安全检验码，以数字和字母组成的32位字符
        //如果签名方式设置为“MD5”时，请设置该参数
        $this->alipay_config['key']			= $alipay_key;

        //商户的私钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        //$this->alipay_config['private_key_path']	= str_replace("\\","/", APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/key/rsa_private_key.pem');
        $this->alipay_config['private_key_path']	= '-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQDDJWsP+ytUcanqN2le+krC0cwv5gTQ6uL6YvOiybmywXwkiER6
qraS9nb6yn9xlzCBGEoo5wDMer4l+htwJe5pQZMOV1xX3Q7dvUHaJiJvdvxyTrjS
M68M6XjMP3ZVqrejsd3Jl0ZWQcwEkP8dAw5VrxlNSGuYODWOhTwpxHdDxQIDAQAB
AoGAFwUr+wejFeQOqj19JaIa/Z5oGuJkgQykV+siWHq5eDfQ4DJe+aV3wiBm6rDG
lezC4Qevf+G4O2bjVXGKmREwbW8vmEoCnaVhgwsLKFdlpQuTmHdDxZoAeIOS8Zl1
R58fUt69ylwH69RqMSnLc6cMvjui0exdDUCIAK08g5tLKCECQQD49Ayo7GU+r4R8
nH1BsnvF7hJDSd+BOiqadPSSVoFvwf9p6WWVMB4SVmyTYUSZhKeop1ZYEsuSzRGa
RCWypTWpAkEAyKt5f7emU0yPLQlA3CjPQm+gwME0zIwtxc+Vb6L9HLIXLrvDafRQ
4Huq3aRJpMPAW4MIWbb0IZM7A6HLL+U2vQJAKT/u1RKOWAunDSq0ymzze0FOP13/
LZ9grcURPSoeOlFPq9HjZgvZ/7nYNbdZMYK8cQKBGQjMOF/IOeJuyKH9OQJATWPs
YLg4GioNkcXe0MmgXTPl4FDjBvwM7xwGut42FaoNTMW3MJa71kd7dy1UBYpFFkXr
o4Xuk0BP4y+zyA3zVQJAH9pgDxdc+Dv0h/nucXofJwNJt2RYdUENGhaDqFNOGp7N
cC8fmng2zrGq05G1QL9Gg8/Hnq5DVoAASh6fSZ0PtA==
-----END RSA PRIVATE KEY-----';

        //支付宝公钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        //$this->alipay_config['ali_public_key_path']=  str_replace("\\","/",  APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/key/alipay_public_key.pem');
        $this->alipay_config['ali_public_key_path']=  '-----BEGIN PUBLIC KEY-----MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCmcVAiSFrbSjzBUDhhEQvw+ifVElzD3WKVdsQ3 vTGP0nN1RhM3Rl8CLJuDWcCCcB2xaAmCHocUAFFSjY5tJDu7n20dHc71FU76nnE8DSupTkwKgx8l NyPlKjoK8XRK2vQ5oHcPayMHDQfF8eosIWiAuGOXd9MNMYXiLPYyI1TI3wIDAQAB-----END PUBLIC KEY-----';


        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


        //签名方式 不需修改
        $this->alipay_config['sign_type']    = '0001';

        //字符编码格式 目前支持 gbk 或 utf-8
        $this->alipay_config['input_charset']= 'utf-8';

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $this->alipay_config['cacert']    =  str_replace("\\","/",  APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/cacert.pem');

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $this->alipay_config['transport']    = 'http';
        return $this;
    }


    /**
     * 消费交易
     */
    public function pay($seller_email,$out_trade_no,$subject,$total_fee,$notify_url,$call_back_url,$merchant_url){
        require_once(APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/lib/alipay_submit.class.php');

        /**************************调用授权接口alipay.wap.trade.create.direct获取授权码token**************************/

        //返回格式
        $format = "xml";
        //必填，不需要修改
        //返回格式
        $v = "2.0";
        //必填，不需要修改
        //请求号
        $req_id = date('Ymdhis');
        //必填，须保证每次请求都是唯一
        //**req_data详细信息**
        //服务器异步通知页面路径
        //$notify_url = "http://商户网关地址/WS_WAP_PAYWAP-PHP-UTF-8/notify_url.php";
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        //$call_back_url = "http://127.0.0.1:8800/WS_WAP_PAYWAP-PHP-UTF-8/call_back_url.php";
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //操作中断返回地址
        //$merchant_url = "http://127.0.0.1:8800/WS_WAP_PAYWAP-PHP-UTF-8/xxxx.php";
        //用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数

        //卖家支付宝帐户
        //$seller_email = $_POST['WIDseller_email'];
        //必填
        //商户订单号
        //$out_trade_no = $_POST['WIDout_trade_no'];
        //商户网站订单系统中唯一订单号，必填

        //订单名称
        //$subject = $_POST['WIDsubject'];
        //必填

        //付款金额
        //$total_fee = $_POST['WIDtotal_fee'];
        //必填

        //请求业务参数详细
        $req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
        //必填

        /************************************************************/

        //构造要请求的参数数组，无需改动
        $para_token = array(
            "service" => "alipay.wap.trade.create.direct",
            "partner" => trim($this->alipay_partner),
            "sec_id" => trim($this->alipay_sign_type),
            "format"	=> $format,
            "v"	=> $v,
            "req_id"	=> $req_id,
            "req_data"	=> $req_data,
            "_input_charset"	=> trim(strtolower($this->alipay_input_charset))
        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($this->alipay_config);

        $html_text = $alipaySubmit->buildRequestHttp($para_token);

        //URLDECODE返回的信息
        $html_text = urldecode($html_text);

        //解析远程模拟提交后返回的信息
        $para_html_text = $alipaySubmit->parseResponse($html_text);

        //获取request_token
        $request_token = $para_html_text['request_token'];


        /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

        //业务详细
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "alipay.wap.auth.authAndExecute",
            "partner" => trim($this->alipay_config['partner']),
            "sec_id" => trim($this->alipay_config['sign_type']),
            "format"	=> $format,
            "v"	=> $v,
            "req_id"	=> $req_id,
            "req_data"	=> $req_data,
            "_input_charset"	=> trim(strtolower($this->alipay_config['input_charset']))
        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($this->alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, 'get', '确认');
        return $html_text;

    }

    /**
     * 后台通知
     */
    public function notify(){
        require_once(APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/lib/alipay_notify.class.php');

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($this->alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //解析notify_data
            //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
            $doc = new DOMDocument();
            if ($this->alipay_config['sign_type'] == 'MD5') {
                $doc->loadXML($_POST['notify_data']);
            }

            if ($this->alipay_config['sign_type'] == '0001') {
                $doc->loadXML($alipayNotify->decrypt($_POST['notify_data']));
            }

            if( ! empty($doc->getElementsByTagName( "notify" )->item(0)->nodeValue) ) {
                //商户订单号
                $out_trade_no = $doc->getElementsByTagName( "out_trade_no" )->item(0)->nodeValue;
                //支付宝交易号
                $trade_no = $doc->getElementsByTagName( "trade_no" )->item(0)->nodeValue;
                //交易状态
                $trade_status = $doc->getElementsByTagName( "trade_status" )->item(0)->nodeValue;

                if($trade_status == 'TRADE_FINISHED') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序

                    //注意：
                    //该种交易状态只在两种情况下出现
                    //1、开通了普通即时到账，买家付款成功后。
                    //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

                    //调试用，写文本函数记录程序运行情况是否正常
                    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                    file_put_contents('/home/www/html/service.icloudmatrix.com/app/data/01.txt',$out_trade_no.'****'.$trade_no.'****'.$trade_status);

                    echo "success";		//请不要修改或删除
                }
                else if ($trade_status == 'TRADE_SUCCESS') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序

                    //注意：
                    //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

                    //调试用，写文本函数记录程序运行情况是否正常
                    //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                    file_put_contents('/home/www/html/service.icloudmatrix.com/app/data/02.txt',$out_trade_no.'****'.$trade_no.'****'.$trade_status);
                    echo "success";		//请不要修改或删除
                }
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";
            file_put_contents('/home/www/html/service.icloudmatrix.com/app/data/00.txt','fail');
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
        exit;
    }

    /**
     * 前端通知
     */
    public function front_notify(){
        require_once(APP_REAL_PATH . 'package/plugin/alipay_quick_mobile/lib/alipay_notify.class.php');

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($this->alipay_config);
        $verify_result = $alipayNotify->verifyReturn();
        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代码

            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

            //商户订单号
            $out_trade_no = $_GET['out_trade_no'];

            //支付宝交易号
            $trade_no = $_GET['trade_no'];

            //交易状态
            $result = $_GET['result'];


            //判断该笔订单是否在商户网站中已经做过处理
            //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            //如果有做过处理，不执行商户的业务程序
            file_put_contents('/home/www/html/service.icloudmatrix.com/app/data/11.txt',$out_trade_no.'****'.$trade_no.'****'.$result);
            echo "验证成功<br />";

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            file_put_contents('/home/www/html/service.icloudmatrix.com/app/data/10.txt','fail');
            echo "验证失败";
        }
        exit;
    }


    /**
     * 退款发起
     */
    public function back(){

    }


    /**
     * 退款通知
     */
    public function back_notify(){

    }

}