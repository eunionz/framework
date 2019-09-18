<?php
/**
 * EUnionZ PHP Framework Wxpay Plugin class
 * 微信支付第二版
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\wxpay;


defined('APP_IN') or exit('Access Denied');

class Wxpay extends \com\eunionz\core\Plugin {

    /**
     * 微信支付APP_ID
     * @var string
     */
    public $wxpay_app_id = '';

    /**
     * 微信支付APP_SECRET
     * @var string
     */
    public $wxpay_app_secret = '';

    /**
     * 微信支付 PARTNER ID
     * @var string
     */
    public $wxpay_partnerid = '';

    /**
     * 微信支付密钥
     * @var string
     */
    public $wxpay_partnerkey = '';

    /**
     * 微信支付 一个证书路径
     * 证书路径,注意应该填写绝对路径
     * @var string
     */
    private $SSLCERT_PATH = "";

    /**
     * 微信支付 一个证书路径
     * 证书路径,注意应该填写绝对路径
     * @var string
     */
    private $SSLKEY_PATH = "";

    /**
     * 微信支付 一个证书路径
     * 证书路径,注意应该填写绝对路径
     * @var string
     */
    private $SSLROOTCA_PATH = "";

    function __construct() {

        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        if (isset($payment)) {
            $this->wxpay_app_id = $payment['pay_config']['wx']['App_id'];
            $this->wxpay_app_secret = $payment['pay_config']['wx']['Openid_JSP'];
            $this->wxpay_partnerid = $payment['pay_config']['wx']['PartnerID'];
            $this->wxpay_partnerkey = $payment['pay_config']['wx']['PaySignKey'];
            if (!isset($payment['pay_config']['wx']['wx_ssl_cert_files'])) {
                $payment['pay_config']['wx']['wx_ssl_cert_files'] = '';
            }
            if (!isset($payment['pay_config']['wx']['wx_ssl_key_files'])) {
                $payment['pay_config']['wx']['wx_ssl_key_files'] = '';
            }

            $this->SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
            $this->SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);
            if ($payment['pay_config']['wx']['wx_ssl_rootca_files']) {
                $this->SSLROOTCA_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_rootca_files']);
            }
        }
    }

    /**
     * 生成支付代码/微信支付时返回微信网页支付需要的参数
     * @param   array $order_list 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order_list, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;
        if ($_SESSION['is_weixin_browser']) {   // Wap
            $charset = 'utf-8';
            $jsApi = new \JsApi_pub();
            //=========步骤1：网页授权获取用户openid============

            //通过code获得openid
            //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
            //通过code获得openid
            //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
            if (!isset($_SESSION['weixin_openid']) || empty($_SESSION['weixin_openid'])) {
                if (!isset($_SESSION['code'])) {
                    $__url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    //触发微信返回code码
                    $url = $jsApi->createOauthUrlForCode($__url, $this->wxpay_app_id);
                    //return "<button style='width:300px; height:44px; background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;' type='button' onClick='callpay()' >立即支付</button>";

                    header("Location: $url");

                } else {
                    //获取code码，以获取openid
                    $code = $_SESSION['code'];
                    $jsApi->setCode($code);
                    $openid = $jsApi->getOpenId($this->wxpay_app_id, $this->wxpay_app_secret);
                    if (!is_null($openid)) {
                        $_SESSION['openid'] = $openid;
                        $_SESSION['weixin_openid'] = $openid;
                    }
                }
            } else {
                $_SESSION['openid'] = $_SESSION['weixin_openid'];
            }

            $openid = (isset($_SESSION['openid'])) ? $_SESSION['openid'] : ((isset($openid)) ? $openid : '');

            //=========步骤2：使用统一支付接口，获取prepay_id============
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", "$openid");
            $this->loadCore('log')->write(APP_DEBUG, 'unifiedOrder' . var_export($unifiedOrder, true), 'yeepay');


            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败！';
                $pay_code['status'] = false;
                return $pay_code;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

            $unifiedOrder->setParameter("body", $payment['productname']);//商品描述

            //自定义订单号，此处仅作举例
            //$unifiedOrder->setParameter("code","wxpay");
            $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=wxpay');//通知地址  return_url(basename(__FILE__, '.php'))
            // $unifiedOrder->setParameter("notify_url","http://".$_SERVER['SERVER_NAME']."/mobile/respond.php");//通知地址
            // file_put_contents("notify_url.txt",return_url(basename(__FILE__, '.php')));
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            $retdata = $unifiedOrder->getPrepayId($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            $this->loadCore('log')->write(APP_DEBUG, 'unifiedOrder' . var_export($retdata, true), 'yeepay');
            $prepay_id = $retdata['prepay_id'];
            $pay_code['status'] = true;
            if (!isset($prepay_id) || empty($prepay_id)) {
                /*可能原因
                 * 1、openid和appid不匹配
                 * 2、缺少统一支付参数   out_trade_no  body  total_fee notify_url
                 * trade_type openid
                 * 3、微信号参数设置错误（该店铺的appid、secretid、key、商户号等设置与WxPay.config.
                 * php中设置的不一致）
                 *  */
                $pay_code['error_desc'] = $retdata['ret_msg'] . $retdata['err_code_des'];//"prepay_id 获取失败！";
                $pay_code['status'] = false;
            }
            //=========步骤3：使用jsapi调起支付============
            $jsApi->setPrepayId($prepay_id);

            $jsApiParameters = $jsApi->getParameters($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            if (!isset($jsApiParameters) || empty($jsApiParameters)) {
                $pay_code['error_desc'] = "支付参数  获取失败！";
                $pay_code['status'] = false;
            }
            $pay_code['result'] = json_decode($jsApiParameters, true);
            $pay_code['requestid'] = $out_trade_no;
            return $pay_code;

        } elseif ($_SESSION['client_type'] == 'wap') {
            $charset = 'utf-8';
            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败！';
                $pay_code['status'] = false;
                return $pay_code;
            }

            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
            $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写
            //sign已填,商户无需重复填写

            $unifiedOrder->setParameter("body", $payment['productname']);//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=wxpay");//通知地址  return_url(basename(__FILE__, '.php'))


            $unifiedOrder->setParameter("trade_type", "MWEB");//交易类型
            $unifiedOrder->setParameter("spbill_create_ip", $payment['ip']);//终端ip
            $shop_base_arr = $this->loadService('shop_base')->get_domain_by_kid($this->get_platform_shopid());
            $h5_info = ['h5_info' => [//h5支付固定传"h5_info"
                'type' => 'Wap',//场景类型
                'wap_url' => $shop_base_arr['mobile_host'],//WAP网站URL地址
                'wap_name' => $this->loadService('shop_params')->get_value_by_key('SITE_NAME', $this->get_platform_shopid())//WAP 网站名
            ]];
            $unifiedOrder->setParameter("scene_info", json_encode($h5_info));//网站信息

            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            $code_url = "";
            $error_desc = '';
            $pay_code['status'] = true;
            $pay_code['error_desc'] = '';
            $pay_code['requestid'] = $out_trade_no;
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            } else {
                $pay_code['result'] = $unifiedOrderResult['mweb_url'] ? $unifiedOrderResult['mweb_url'] : $unifiedOrderResult['code_url'];
                $pay_code['datalink'] = $unifiedOrderResult['mweb_url'] ? $unifiedOrderResult['mweb_url'] : $unifiedOrderResult['code_url'];
                $pay_code['status'] = true;
            }
            return $pay_code;
        } else {
            // PC
            $charset = 'utf-8';
            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败！';
                $pay_code['status'] = false;
                return $pay_code;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();


            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
            $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写
            //sign已填,商户无需重复填写

            $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($payment['productname'], 0, 30, 'utf-8'));//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=wxpay");//通知地址  return_url(basename(__FILE__, '.php'))


            $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            $code_url = "";
            $error_desc = '';
            $pay_code['status'] = true;
            $pay_code['error_desc'] = '';
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            }
            if ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            }
            if ($unifiedOrderResult["code_url"] != NULL) {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
            if ($error_desc) {
                return array('result' => false, 'error_desc' => '支付参数错误,请联系管理员!', 'status' => $pay_code['status']);
            }


            if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay')) {
                @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay');
            }

            $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay' . APP_DS . $opay_id . '.png', false, 'L', true, 10, false);
            //$pay_code['result'] = $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/order/'.$order['order_id'].'.png';
            $pay_code['result'] = $this->loadPlugin('common')->getImageUrl('/runtime/uploads/qrcode/order_pay/' . $opay_id . '.png', '', false, false) . '?' . time();
            $pay_code['requestid'] = $out_trade_no;
            return $pay_code;
        }
    }

    /**
     * 分销订单生成
     * */
    public function distributor_get_code($order_list, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;
        if ($_SESSION['is_weixin_browser']) {   // Wap
            $charset = 'utf-8';
            $jsApi = new \JsApi_pub();
            //=========步骤1：网页授权获取用户openid============
            if (!isset($_SESSION['weixin_openid']) || empty($_SESSION['weixin_openid'])) {
                if (!isset($_SESSION['code'])) {
                    $__url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    //触发微信返回code码
                    $url = $jsApi->createOauthUrlForCode($__url, $this->wxpay_app_id);
                    header("Location: $url");
                } else {
                    //获取code码，以获取openid
                    $code = $_SESSION['code'];
                    $jsApi->setCode($code);
                    $openid = $jsApi->getOpenId($this->wxpay_app_id, $this->wxpay_app_secret);
                    if (!is_null($openid)) {
                        $_SESSION['openid'] = $openid;
                        $_SESSION['weixin_openid'] = $openid;
                    }
                }
            } else {
                $_SESSION['openid'] = $_SESSION['weixin_openid'];
            }

            $openid = (isset($_SESSION['openid'])) ? $_SESSION['openid'] : ((isset($openid)) ? $openid : '');

            //=========步骤2：使用统一支付接口，获取prepay_id============
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", "$openid");
            $this->loadCore('log')->write(APP_DEBUG, 'unifiedOrder' . var_export($unifiedOrder, true), 'yeepay');
            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败3！';
                $pay_code['status'] = false;
                return $pay_code;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();
            $unifiedOrder->setParameter("body", $payment['productname']);//商品描述

            //自定义订单号，此处仅作举例
            //$unifiedOrder->setParameter("code","wxpay");
            $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distwxpay');//通知地址  return_url(basename(__FILE__, '.php'))
            // $unifiedOrder->setParameter("notify_url","http://".$_SERVER['SERVER_NAME']."/mobile/respond.php");//通知地址
            // file_put_contents("notify_url.txt",return_url(basename(__FILE__, '.php')));
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            $retdata = $unifiedOrder->getPrepayId($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            $this->loadCore('log')->write(APP_DEBUG, 'unifiedOrder' . var_export($retdata, true), 'yeepay');
            $prepay_id = $retdata['prepay_id'];
            $pay_code['status'] = true;
            if (!isset($prepay_id) || empty($prepay_id)) {
                /*可能原因
                 * 1、openid和appid不匹配
                 * 2、缺少统一支付参数   out_trade_no  body  total_fee notify_url
                 * trade_type openid
                 * 3、微信号参数设置错误（该店铺的appid、secretid、key、商户号等设置与WxPay.config.
                 * php中设置的不一致）
                 *  */
                $pay_code['error_desc'] = $retdata['ret_msg'] . $retdata['err_code_des'];//"prepay_id 获取失败！";
                $pay_code['status'] = false;
            }
            //=========步骤3：使用jsapi调起支付============
            $jsApi->setPrepayId($prepay_id);

            $jsApiParameters = $jsApi->getParameters($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            if (!isset($jsApiParameters) || empty($jsApiParameters)) {
                $pay_code['error_desc'] = "支付参数  获取失败！";
                $pay_code['status'] = false;
            }
            $pay_code['result'] = json_decode($jsApiParameters, true);
            $pay_code['requestid'] = $out_trade_no;
            return $pay_code;

        } elseif ($_SESSION['client_type'] == 'wap') {
            $charset = 'utf-8';
            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败2！';
                $pay_code['status'] = false;
                return $pay_code;
            }

            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
            $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写
            //sign已填,商户无需重复填写

            $unifiedOrder->setParameter("body", $payment['productname']);//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=distwxpay");//通知地址  return_url(basename(__FILE__, '.php'))


            $unifiedOrder->setParameter("trade_type", "MWEB");//交易类型
            $unifiedOrder->setParameter("spbill_create_ip", $payment['ip']);//终端ip
            $shop_base_arr = $this->loadService('shop_base')->get_domain_by_kid($this->get_platform_shopid());
            $h5_info = ['h5_info' => [//h5支付固定传"h5_info"
                'type' => 'Wap',//场景类型
                'wap_url' => $shop_base_arr['mobile_host'],//WAP网站URL地址
                'wap_name' => $this->loadService('shop_params')->get_value_by_key('SITE_NAME', $this->get_platform_shopid())//WAP 网站名
            ]];
            $unifiedOrder->setParameter("scene_info", json_encode($h5_info));//网站信息

            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            $code_url = "";
            $error_desc = '';
            $pay_code['status'] = true;
            $pay_code['error_desc'] = '';
            $pay_code['requestid'] = $out_trade_no;
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            } elseif ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            } else {
                $pay_code['result'] = $unifiedOrderResult['mweb_url'] ? $unifiedOrderResult['mweb_url'] : $unifiedOrderResult['code_url'];
                $pay_code['datalink'] = $unifiedOrderResult['mweb_url'] ? $unifiedOrderResult['mweb_url'] : $unifiedOrderResult['code_url'];
                $pay_code['status'] = true;
            }
            return $pay_code;
        } else {
            // PC
            $charset = 'utf-8';
            //添加支付单号记录
            $payment['payproducttype'] = 'wxpay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                $pay_code['error_desc'] = '添加支付单号记录失败2！';
                $pay_code['status'] = false;
                return $pay_code;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();


            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
            $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写
            //sign已填,商户无需重复填写

            $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($payment['productname'], 0, 30, 'utf-8'));//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=distwxpay");//通知地址  return_url(basename(__FILE__, '.php'))


            $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            $code_url = "";
            $error_desc = '';
            $pay_code['status'] = true;
            $pay_code['error_desc'] = '';
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            }
            if ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
                $pay_code['error_desc'] = $error_desc;
                $pay_code['status'] = false;
            }
            if ($unifiedOrderResult["code_url"] != NULL) {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
            if ($error_desc) {
                return array('result' => false, 'error_desc' => '支付参数错误,请联系管理员!', 'status' => $pay_code['status']);
            }


            if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_dist')) {
                @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_dist');
            }

            $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_dist' . APP_DS . $opay_id . '.png', false, 'L', true, 10, false);
            //$pay_code['result'] = $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/order/'.$order['order_id'].'.png';
            $pay_code['result'] = $this->loadPlugin('common')->getImageUrl('/runtime/uploads/qrcode/order_pay_dist/' . $opay_id . '.png', '', false, false) . '?' . time();
            $pay_code['requestid'] = $out_trade_no;
            return $pay_code;
        }
    }

    /**
     * 生成支付代码   已过时
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    function get_code_old($order, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;

        if ($_SESSION['is_weixin_browser']) {
            // Wap

            $charset = 'utf-8';
            $jsApi = new \JsApi_pub();
            //=========步骤1：网页授权获取用户openid============

            //通过code获得openid
            //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
            //通过code获得openid
            //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
            if (!isset($_SESSION['weixin_openid']) || empty($_SESSION['weixin_openid'])) {
                if (!isset($_SESSION['code'])) {
                    $__url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    //触发微信返回code码
                    $url = $jsApi->createOauthUrlForCode($__url, $this->wxpay_app_id);
                    //return "<button style='width:300px; height:44px; background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;' type='button' onClick='callpay()' >立即支付</button>";

                    header("Location: $url");

                } else {
                    //获取code码，以获取openid
                    $code = $_SESSION['code'];
                    $jsApi->setCode($code);
                    $openid = $jsApi->getOpenId($this->wxpay_app_id, $this->wxpay_app_secret);
                    if (!is_null($openid)) {
                        $_SESSION['openid'] = $openid;
                        $_SESSION['weixin_openid'] = $openid;
                    }
                }
            } else {
                $_SESSION['openid'] = $_SESSION['weixin_openid'];
            }

            $openid = (isset($_SESSION['openid'])) ? $_SESSION['openid'] : ((isset($openid)) ? $openid : '');

            //=========步骤2：使用统一支付接口，获取prepay_id============
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("openid", "$openid");


            $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($order['order_title'], 0, 30, 'utf-8'));
            //自定义订单号，此处仅作举例
            //$unifiedOrder->setParameter("code","wxpay");
            $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
            $unifiedOrder->setParameter("total_fee", intval($order['order_order_amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->P('common')->getDomain() . '/service/payment/respond.html?code=wxpay');//通知地址  return_url(basename(__FILE__, '.php'))
            // $unifiedOrder->setParameter("notify_url","http://".$_SERVER['SERVER_NAME']."/mobile/respond.php");//通知地址
            // file_put_contents("notify_url.txt",return_url(basename(__FILE__, '.php')));
            $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            $prepay_id = $unifiedOrder->getPrepayId($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            //=========步骤3：使用jsapi调起支付============
            $jsApi->setPrepayId($prepay_id);

            $jsApiParameters = $jsApi->getParameters($this->wxpay_app_id, $this->wxpay_partnerid, $this->wxpay_partnerkey);
            // file_put_contents("wxtext.txt",$jsApiParameters);
            // echo $jsApiParameters;	die;

            $button = <<<EOT
		<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <title>微信安全支付</title>
	<script type="text/javascript">
		//调用微信JS api 支付
		function jsApiCall()
		{
			WeixinJSBridge.invoke(
				'getBrandWCPayRequest',
				{$jsApiParameters},
				function(res){
					WeixinJSBridge.log(res.err_msg);
					window.location.reload();
				}
			);
		}

		function callpay()
		{
			if (typeof WeixinJSBridge == "undefined"){
			    if( document.addEventListener ){
			        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
			    }else if (document.attachEvent){
			        document.attachEvent('WeixinJSBridgeReady', jsApiCall);
			        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
			    }
			}else{
			    jsApiCall();
			}
		}
	</script>
</head>
<body>
	<div align="center">
		<button style="width:300px; height:44px; background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onClick="callpay()" >立即支付</button>
	</div>
</body>
</html>
EOT;
            return array('result' => $button);


        } else {
            // PC

            $charset = 'utf-8';

            //使用统一支付接口
            $unifiedOrder = new \UnifiedOrder_pub();
            $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
            $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

            //设置统一支付接口参数
            //设置必填参数
            //appid已填,商户无需重复填写
            //mch_id已填,商户无需重复填写
            //noncestr已填,商户无需重复填写
            //spbill_create_ip已填,商户无需重复填写
            //sign已填,商户无需重复填写

            // $unifiedOrder->setParameter("body",  $order['order_title']);//商品描述
            $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($order['order_title'], 0, 30, 'utf-8'));//商品描述


            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $out_trade_no = $order['order_sn'];
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", intval($order['order_order_amount'] * 100));//总金额
            //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
            $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=wxpay");//通知地址  return_url(basename(__FILE__, '.php'))


            $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型
            //非必填参数，商户可根据实际情况选填
            //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
            //$unifiedOrder->setParameter("device_info","XXXX");//设备号
            //$unifiedOrder->setParameter("attach","XXXX");//附加数据
            //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
            //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
            //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
            //$unifiedOrder->setParameter("openid","XXXX");//用户标识
            //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

            //获取统一支付接口结果
            $unifiedOrderResult = $unifiedOrder->getResult();

            $code_url = "";
            $error_desc = '';
            $order['order_order_amount'] = sprintf('%.2f', $order['order_order_amount']);
            //商户根据实际情况设置相应的处理流程
            if ($unifiedOrderResult["return_code"] == "FAIL") {
                //商户自行增加处理流程
                $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
            }
            if ($unifiedOrderResult["result_code"] == "FAIL") {
                //商户自行增加处理流程
                //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
                //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
                $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
            }
            if ($unifiedOrderResult["code_url"] != NULL) {
                //从统一支付接口获取到code_url
                $code_url = $unifiedOrderResult["code_url"];
                //商户自行增加处理流程
                //......
            }
            if ($error_desc) {
                return array('result' => false, 'error_desc' => $error_desc);
            }


            /*
            $part_url = $this->loadPlugin('common')->getDomain() .'/runtime';
            $button = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <meta name="format-detection" content="telephone=no" />
	<title>微信支付</title>
	<STYLE type="text/css">
	html,body {
		width: 100%;
		font-size: 14px;
		margin: 0px;
		padding: 0px;
		text-align: center;
		background: #cfd1d2;
	}
	.top{
		height:54px;
		width: 100%;
		line-height: 54px;
		background: #363a42;
		color:#cbcbcd;
		position: relative;
		min-width:831px;
	}
	.top img{
		width:34px;
		height: 28px;
		vertical-align: middle;
		_margin:10px 0;
	}
	.content{
		margin:0 auto;
		padding:0px;
		text-align: center;
		width:831px;
		height: 581px;
		position: relative;
		background: url("' . APP_PATH .'images/pay_bg.png");
		margin-top: 20px;
	}
	.detail{
		margin:0 auto;
		padding:0px;
		text-align: center;
		width:233px;
		height: 580px;
		position: relative;
	}
	.detail div.qrcode{
		width:233px;
		height: 233px;
		margin-top:80px;
		position: relative;
	}
	.pay_botton{
		width:237px;
		height: 79px;
		background: url("' . APP_PATH .'images/pay.png");
		margin-top: 22px;
		position: relative;
	}
	.detail .much{
		width:237px;
		height: 79px;
		margin-top: 85px;
		position: relative;
		color:#cc0001;
		font-size: 3.5em;
		font-weight: bold;
	}
	#qrcode div img{
		width:233px;
		height: 233px;
	}
	.pay_success_botton{
		width:237px;
		height: 79px;
		background: url("' . APP_PATH .'images/pay_botton.png");
		margin-top: 22px;
		position: relative;
	}
	</STYLE>
</head>
<body>
	<div class="top">
		<img src="' . APP_PATH .'images/wei.png" />
		<span>微信支付</span>
	</div>
	<div class="content">
		<div class="detail" style="padding-top: 3px;">
			<div id="qrcode" class="qrcode" >

			</div>
			' . $error_desc .'
			<div id="notify_button" class="pay_botton"></div>
			<div class="much">¥'.  $order['order_order_amount'].'</div>
		</div>
	</div>
	<script src="' . APP_PATH .'js/qrcode.js"></script>
	<script src="' . APP_PATH .'js/jquery-1.11.1.min.js"></script>
	<script>
		window.onload = function(){
			var url = \''.$code_url.'\';
			var qr = qrcode(10, \'M\');
			qr.addData(url);
			qr.make();
			var code=document.createElement(\'DIV\');
			code.innerHTML = qr.createImgTag();
			var element=document.getElementById("qrcode");
			element.appendChild(code);
			var timer = setInterval(function(){
				$.get("' . APP_PATH .'/service/flow/get_order_status.html?order_id='.$order['order_id'].'",function(data){
					var data = JSON.parse(data);
					if(parseInt(data.status)==0){
						clearInterval(timer);
						$("#notify_button").removeClass("pay_botton");
						$("#notify_button").addClass("pay_success_botton");
						setTimeout("CloseWebPage()",3000);
						window.location.href="http://"+window.location.host;
					}
				});
			},5000);
		};

		function CloseWebPage(){
		 if (navigator.userAgent.indexOf("MSIE") > 0) {
		  if (navigator.userAgent.indexOf("MSIE 6.0") > 0) {
		   window.opener = null;
		   window.close();
		  } else {
		   window.open("", "_top");
		   window.top.close();
		  }
		 }
		 else if (navigator.userAgent.indexOf("Firefox") > 0) {
		  window.location.href = "about:blank ";
		 } else {
		  window.opener = null;
		  window.open("", "_self", "");
		  window.close();
		 }
		}
	</script>
</body>
</html>';

            if(!file_exists(APP_RUNTIME_REAL_PATH ."data/wxhtml")){
                @make_dir(APP_RUNTIME_REAL_PATH."data/wxhtml",0777);
                print_r(APP_RUNTIME_REAL_PATH ."data/wxhtml");exit;
            }
            $wxpage = "wx".$order['order_id'].".html";
            $filename = APP_RUNTIME_REAL_PATH."data/wxhtml/".$wxpage;
            @file_put_contents($filename, $button);
            $button = '<input type="button" class="pointer btn1" onclick="window.open(\''.$part_url.'/data/wxhtml/'.$wxpage.'\');" value="立即支付"/>';
// echo $button;die;
            */
            if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order')) {
                @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order');
            }

            $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order' . APP_DS . $order['order_id'] . '.png', false, 'L', true, 10, false);
            return array('result' => $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/order/' . $order['order_id'] . '.png');

        }


    }

    //获取url后面字符
    function getParameter($url, $keys) {
        $arr = array();
        $arrvalue = array();
        $url = substr($url, strpos($url, "?") + 1, strlen($url));
        $arr = explode("&", str_replace("&amp;", "&", $url));

        foreach ($arr as $key => $value) {
            $arrvalue = explode("=", $value);
            if ($arrvalue[0] == $keys) {
                return $arrvalue[1];
            }
        }
    }


    /**
     * 响应操作
     */
    function respond($array_data) {
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond:mobile', 'wxpay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];

            $this->loadService('order_info')->save_payment_notice_data('wxpay', $order_sn, $arr);

            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }

            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $arr['total_fee'] / 100)) {
                return false;
            }
            /* 改变订单状态 */
            $ret = $this->loadService('order_info')->pay_success($order_sn, isset($array_data['transaction_id']) ? $array_data['transaction_id'] : '');
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('wxpay', $order_sn);
            }
            return $ret['status'];
        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc', 'wxpay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('order_info')->save_payment_notice_data('wxpay', $order_sn, $arr);

            $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);

            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }

            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc ' . $order_sn, 'wxpay');

            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $arr['total_fee'] / 100)) {
                return false;
            }

            $this->loadCore('log')->write(APP_DEBUG, print_r($arr, true), 'wxpay');

            /* 改变订单状态 */
            $ret = $this->loadService('order_info')->pay_success($order_sn, isset($array_data['transaction_id']) ? $array_data['transaction_id'] : '');
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('wxpay', $order_sn);
            }
            $this->loadCore('log')->write(APP_DEBUG, print_r($ret, true), 'wxpay');

            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
            }
            return $ret['status'];
        }
    }

    /**
     * 响应操作
     */
    function auto_respond($post) {
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        $arr = $post;
        $order_sn = $arr['out_trade_no'];
        $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $arr['total_fee'] / 100)) {
            return false;
        }
        /* 改变订单状态 */
        $ret = $this->loadService('order_info')->pay_success($order_sn, isset($arr['transaction_id']) ? $arr['transaction_id'] : '');
        if ($ret['status']) {
            $this->loadService('order_info')->delete_payment_notice_data('wxpay', $order_sn);
        }
        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
        }
        return $ret['status'];
    }


    /**
     * 响应操作
     */
    function distributor_respond($array_data) {
        $platform_shopid = $this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $platform_shopid);
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond:mobile', 'wxpay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('wxpay', $order_sn, $arr);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100, $platform_shopid)) {
                return false;
            }
            /* 改变订单状态 */
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($array_data['transaction_id']) ? $array_data['transaction_id'] : ''), $platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('wxpay', $order_sn);
            }
            return $ret['status'];
        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc', 'wxpay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('wxpay', $order_sn, $arr);
            $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }
            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc ' . $order_sn, 'wxpay');
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100, $platform_shopid)) {
                return false;
            }
            $this->loadCore('log')->write(APP_DEBUG, print_r($arr, true), 'wxpay');
            /* 改变订单状态 */
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($array_data['transaction_id']) ? $array_data['transaction_id'] : ''), $platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('wxpay', $order_sn);
            }
            $this->loadCore('log')->write(APP_DEBUG, print_r($ret, true), 'wxpay');

            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html");
            }
            return $ret['status'];
        }
    }

    /**
     * 响应操作
     */
    function distributor_auto_respond($post) {
        $platform_shopid = $this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $platform_shopid);
        $arr = $post;
        $order_sn = $arr['out_trade_no'];
        $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100, $platform_shopid)) {
            return false;
        }
        /* 改变订单状态 */
        $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($arr['transaction_id']) ? $arr['transaction_id'] : ''), $platform_shopid);
        if ($ret['status']) {
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('wxpay', $order_sn);
        }
        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html");
        }
        return $ret['status'];
    }


    /**
     * 微信支付在线退货功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @param array $refund 退款单数据
     * @return bool true--成功  false--失败
     */
    public function refund($order, $refund) {
        $obj = null;//new \stdClass();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        require_once "wxlib/WxPay.Api.php";
        $this->loadCore('log')->write(APP_ERROR, "refund1", 'wxpay');

        \WxPayConfig::$APPID = $payment['pay_config']['wx']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['wx']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['wx']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['wx']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);


        $remarket = "微信支付退款成功";
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_ERROR, "退款途径：wap", 'wxpay');
            //wap
            ini_set('date.timezone', 'Asia/Shanghai');
            error_reporting(E_ERROR);


            $out_trade_no = $order['order_pay_no'];

            $total_fee = $order["order_orig_money_paid"] * 100;
            $refund_fee = $refund["orefund_amount"] * 100;
            $input = new \WxPayRefund();

            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$MCHID);

            $rs = \WxPayApi::refund($input);
            if ($rs['result_code'] == 'FAIL' || $rs['return_code'] == 'FAIL') {
                //                echo "微信支付退款失败，失败原因：".$rs['return_msg'];
                //                exit;
                $obj['status'] = false;
                $obj['msg'] = "微信支付退款失败，失败原因：" . $rs['return_msg'];
                return $obj;
            }

            //业务数据处理
            //修改退款单号的状态
            $orefund_way = 0;// 0:线上退款 1：线下退款
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $remarket, $refund['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'wxpay');

            return $ret;
        } else {
            $this->loadCore('log')->write(APP_ERROR, "退款途径：PC", 'wxpay');
            //pc
            ini_set('date.timezone', 'Asia/Shanghai');
            error_reporting(E_ERROR);


            $out_trade_no = $order['order_pay_no'];
            $total_fee = $order["order_orig_money_paid"] * 100;
            $refund_fee = $refund["orefund_amount"] * 100;
            $input = new \WxPayRefund();

            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$MCHID);

            $rs = \WxPayApi::refund($input);
            $this->loadCore('log')->write(APP_ERROR, "refund2:" . print_r($rs, true), 'wxpay');
            if ($rs['result_code'] == 'FAIL' || $rs['return_code'] == 'FAIL') {
                //echo "微信支付退款失败，失败原因：".$rs['return_msg'];
                //exit;
                $obj['status'] = false;
                $obj['msg'] = "微信支付退款失败，失败原因：" . (isset($rs['err_code_des']) ? $rs['err_code_des'] : $rs['return_msg']);
                return $obj;
            }

            //业务数据处理
            $this->loadCore('log')->write(APP_ERROR, "refund3:", 'wxpay');


            //修改退款单号的状态
            $orefund_way = 0;// 0:线上退款 1：线下退款
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $remarket, $refund['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'wxpay');

            return $ret;
        }

    }


    /**
     * 微信支付在线退款后台通知，无通知
     * @param array $config 在线支付配置数据
     * @param array $order 订单数据
     * @param array $refund_transfer 退款转帐申请数据
     */
    public function refund_notify() {
        return true;
    }


    /**
     * 创建sign
     * @return string
     */
    public function create_sign($arr) {
        $para = $this->parafilter($arr);
        $para = $this->argsort($para);
        $signValue = $this->createlinkstring($para);
        $signValue = $signValue . "&key=" . $this->partnerKey;
        $signValue = strtoupper(md5($signValue));
        return $signValue;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function createlinkstring($para) {
        $arg = "";
        foreach ($para as $key => $val) {
            $arg .= strtolower($key) . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */

    public function parafilter($para) {
        $para_filter = array();
        foreach ($para as $key => $val) {
            if ($key == "sign_method" || $key == "sign" || $val == "")
                continue; else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    public function argsort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 从xml中获取数组
     * @return array
     */
    public function getXmlArray() {
        $postStr = @$this->input();
        if ($postStr) {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!is_object($postObj)) {
                return false;
            }
            $array = json_decode(json_encode($postObj), true); // xml对象转数组
            return array_change_key_case($array, CASE_LOWER); // 所有键小写
        } else {
            return false;
        }
    }


    public function testpay() {
        $order['order_title'] = "测试支付";
        $order['order_sn'] = "sn1234567890";
        $order['order_order_amount'] = 0.01;

        include_once("wxpay/WxPayPubHelper.php");
        $jsApi = new \JsApi_pub();
        $unifiedOrder = new \UnifiedOrder_pub();

        $unifiedOrder->setParameter("openid", "o0Wqswjs92RX2UfUG7n1pwVOTCgA");
        $unifiedOrder->setParameter("body", $order['order_title']);//商品描述
        $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
        $unifiedOrder->setParameter("total_fee", intval($order['order_order_amount'] * 100));//总金额
        $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=wxpay');//通知地址  return_url(basename(__FILE__, '.php'))
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型
        //非必填参数，商户可根据实际情况选填
        //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        //$unifiedOrder->setParameter("device_info","XXXX");//设备号
        //$unifiedOrder->setParameter("attach","XXXX");//附加数据
        //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
        //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        //$unifiedOrder->setParameter("openid","XXXX");//用户标识
        //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

        //$prepay_id = $unifiedOrder->getPrepayId($this->wxpay_app_id,$this->wxpay_partnerid,$this->wxpay_partnerkey);

        $key = \WxPayConf_pub::$KEY;
        var_dump($key);
        $prepay_id = $unifiedOrder->getPrepayId();
        //=========步骤3：使用jsapi调起支付============
        $jsApi->setPrepayId($prepay_id);

        //$jsApiParameters = $jsApi->getParameters($this->wxpay_app_id,$this->wxpay_partnerid,$this->wxpay_partnerkey);
        $jsApiParameters = $jsApi->getParameters();
        $this->ajax();


    }

    /**
     * 转帐到零钱
     * @param $out_biz_no 外部订单号
     * @param $transfer_money  转帐金额 0.01
     * @param $to_account  收款方微信openid
     * @param $to_realname  收款方实名用户真实姓名
     * @param $transfer_remark  转帐备注
     * @param $error_msg  失败时返回的错误信息
     * @return bool  true -- 转帐成功  false -- 转帐失败
     */
    public function transfer($out_biz_no, $transfer_money, $to_account, $to_realname, $transfer_remark, & $error_msg) {
        header("Content-Type: text/html;charset=utf-8");
        require_once "WxTransfers.Api.php";

        //绑定支付的APPID（必须配置，开户邮件中可查看）
        $config['wxappid'] = $this->wxpay_app_id;
        //MCHID：商户号（必须配置，开户邮件中可查看）
        $config['mch_id'] = $this->wxpay_partnerid;
        //KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        $config['key'] = $this->wxpay_partnerkey;
        $config['PARTNERKEY'] = $this->wxpay_partnerkey;

        $config['api_cert'] = $this->SSLCERT_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/apiclient_cert.pem';
        $config['api_key'] = $this->SSLKEY_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/apiclient_key.pem';
        if ($this->SSLROOTCA_PATH) {
            $config['rootca'] = $this->SSLROOTCA_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/rootca.pem';
        }

        $wxtran = new \WxTransfers($config);

        //转账
        $data = array('openid' => $to_account,//'o0F0Nt8gZ2Kzxjaka2lDP3pBCoF0',//openid
            'partner_trade_no' => $out_biz_no,//外部订单号
            'check_name' => 'NO_CHECK',//是否验证真实姓名参数
            're_user_name' => $to_realname,//'刘林',//姓名
            'amount' => $transfer_money * 100,//100,//最小1元 也就是100
            'desc' => $transfer_remark,//'企业转账测试',//描述
            'spbill_create_ip' => $wxtran->getServerIp(),//服务器IP地址
        );
        if ($wxtran->transfers($data)) {
            return true;
        }
        $error_msg = $wxtran->error;
        return false;
    }

    /**
     * 转帐查询接口
     * @param $out_biz_no 查询外部订单号
     * @return  true -- 转帐成功   false--转帐失败
     */
    public function transfer_query($out_biz_no) {
        require_once "WxTransfers.Api.php";

        //绑定支付的APPID（必须配置，开户邮件中可查看）
        $config['wxappid'] = $this->wxpay_app_id;
        //MCHID：商户号（必须配置，开户邮件中可查看）
        $config['mch_id'] = $this->wxpay_partnerid;
        //KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
        $config['key'] = $this->wxpay_partnerkey;
        $config['PARTNERKEY'] = $this->wxpay_partnerkey;

        $config['api_cert'] = $this->SSLCERT_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/apiclient_cert.pem';
        $config['api_key'] = $this->SSLKEY_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/apiclient_key.pem';
        if ($this->SSLROOTCA_PATH) {
            $config['rootca'] = $this->SSLROOTCA_PATH;//APP_REAL_PATH . '../shop/package/plugin/weixinsdk/cert/rootca.pem';
        }

        $wxtran = new \WxTransfers($config);
        //获取转账信息
        if ($wxtran->getInfo($out_biz_no)) {
            //转帐成功
            return true;
        }
        //转帐失败
        return false;

    }

    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_order($order) {
        $obj = null;//new \stdClass();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        require_once "wxlib/WxPay.Api.php";
        $this->loadCore('log')->write(APP_ERROR, "refund1", 'wxpay');

        \WxPayConfig::$APPID = $payment['pay_config']['wx']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['wx']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['wx']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['wx']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);

        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $this->loadCore('log')->write(APP_ERROR, "退款途径：wap", 'wxpay');
        //wap
        ini_set('date.timezone', 'Asia/Shanghai');
        error_reporting(E_ERROR);

        $out_trade_no = $order['order_pay_no'];

        $refund_fee = $order["order_money_paid"] * 100;
        $input = new \WxPayRefund();

        $opts['where']['opay_no'] = $order['order_pay_no'];
        $opts['where']['opay_status'] = 1;
        $order_pay_base = $this->loadService('order_pay_base')->find_one($opts);
        if (!$order_pay_base) {
            $obj['status'] = false;
            $obj['msg'] = "不存在的支付单，退款失败";
            return $obj;
        }


        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($order_pay_base['opay_amount'] * 100);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
        $input->SetOp_user_id(\WxPayConfig::$MCHID);
        try {
            $rs = \WxPayApi::refund($input);
        } catch (\Exception $err) {
            $obj['status'] = false;
            $obj['msg'] = "微信支付退款失败，失败原因：" . $err->getMessage();
            return $obj;
        }

        if ($rs['result_code'] == 'FAIL' || $rs['return_code'] == 'FAIL') {
            //                echo "微信支付退款失败，失败原因：".$rs['return_msg'];
            //                exit;
            $obj['status'] = false;
            $obj['msg'] = "微信支付退款失败，失败原因：" . $rs['return_msg'];
            return $obj;
        }

        //业务数据处理
        $ret = $this->loadService('order_info')->do_cancel_by_order_sn($order);
        $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret, 'orderinfo_refund');
        if ($ret === false) {
            $retdata['msg'] = "退款申请失败！";
            $retdata['status'] = false;
        }

        return $retdata;
    }

    function get_platform_shopid() {
        return (isset($_SESSION['PLATFORM_SHOP_ID'])) ? $_SESSION['PLATFORM_SHOP_ID'] : $this->getConfig('shop', 'SHOP_ID');
    }

    /**
     * 分销订单生成
     * */
    public function apply_get_code($order_list, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;
        // PC
        $charset = 'utf-8';
        //添加支付单号记录
        $payment['payproducttype'] = 'wxpay';
        $payment['externalid'] = '';
        $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
        $out_trade_no = $payment['requestid'];
        $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
        if ($ret === false) {
            $pay_code['error_desc'] = '添加支付单号记录失败2！';
            $pay_code['status'] = false;
            return $pay_code;
        }
        $opay_id = $ret;
        $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();


        //使用统一支付接口
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("appid", \WxPayConf_pub::$APPID);//公众账号ID
        $unifiedOrder->setParameter("mch_id", \WxPayConf_pub::$MCHID);//商户号

        //设置统一支付接口参数
        //设置必填参数
        //appid已填,商户无需重复填写
        //mch_id已填,商户无需重复填写
        //noncestr已填,商户无需重复填写
        //spbill_create_ip已填,商户无需重复填写
        //sign已填,商户无需重复填写

        $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($payment['productname'], 0, 30, 'utf-8'));//商品描述

        //自定义订单号，此处仅作举例
        $timeStamp = time();
        $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
        $unifiedOrder->setParameter("total_fee", ($payment['amount'] * 100));//总金额
        //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
        $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . "/service/payment/respond.html?code=applywxpay");//通知地址  return_url(basename(__FILE__, '.php'))


        $unifiedOrder->setParameter("trade_type", "NATIVE");//交易类型
        //非必填参数，商户可根据实际情况选填
        //$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号
        //$unifiedOrder->setParameter("device_info","XXXX");//设备号
        //$unifiedOrder->setParameter("attach","XXXX");//附加数据
        //$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
        //$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间
        //$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记
        //$unifiedOrder->setParameter("openid","XXXX");//用户标识
        //$unifiedOrder->setParameter("product_id","XXXX");//商品ID

        //获取统一支付接口结果
        $unifiedOrderResult = $unifiedOrder->getResult();

        $code_url = "";
        $error_desc = '';
        $pay_code['status'] = true;
        $pay_code['error_desc'] = '';
        //商户根据实际情况设置相应的处理流程
        if ($unifiedOrderResult["return_code"] == "FAIL") {
            //商户自行增加处理流程
            $error_desc = "通信出错：" . $unifiedOrderResult['return_msg'];
            $pay_code['error_desc'] = $error_desc;
            $pay_code['status'] = false;
        }
        if ($unifiedOrderResult["result_code"] == "FAIL") {
            //商户自行增加处理流程
            //echo "错误代码：".$unifiedOrderResult['err_code']."<br>";
            //echo "错误代码描述：".$unifiedOrderResult['err_code_des']."<br>";
            $error_desc = "通信出错：" . $unifiedOrderResult['err_code_des'];
            $pay_code['error_desc'] = $error_desc;
            $pay_code['status'] = false;
        }
        if ($unifiedOrderResult["code_url"] != NULL) {
            //从统一支付接口获取到code_url
            $code_url = $unifiedOrderResult["code_url"];
            //商户自行增加处理流程
            //......
        }
        if ($error_desc) {
            return array('result' => false, 'error_desc' => '支付参数错误,请联系管理员!', 'status' => $pay_code['status']);
        }


        if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_apply')) {
            @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_apply');
        }

        $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_pay_apply' . APP_DS . $opay_id . '.png', false, 'L', true, 10, false);
        //$pay_code['result'] = $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/order/'.$order['order_id'].'.png';
        $pay_code['result'] = $this->loadPlugin('common')->getImageUrl('/runtime/uploads/qrcode/order_pay_apply/' . $opay_id . '.png', '', false, false) . '?' . time();
        $pay_code['requestid'] = $out_trade_no;
        return $pay_code;
    }

    /**
     * 响应操作
     */
    function apply_respond($array_data)
    {
        $platform_shopid = $this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $platform_shopid);
        //pc
        $this->loadCore('log')->write(APP_DEBUG, 'respond:pc', 'wxpay');
        /*取返回参数*/
        $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
        $arr = null;
        foreach (explode(',', $fields) as $val) {
            if (isset($array_data[$val])) {
                $arr[$val] = trim($array_data[$val]);
            }
        }
        $order_sn = $arr['out_trade_no'];
        $this->loadService('shop_grade_apply')->save_payment_notice_data('wxpay', $order_sn, $arr);
        $order_id = $this->loadService('shop_grade_apply')->get_apply_id_by_apply_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, 'respond:pc ' . $order_sn, 'wxpay');
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('shop_grade_apply')->check_apply_pay_money($order_sn, $arr['total_fee'] / 100, $platform_shopid)) {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, print_r($arr, true), 'wxpay');
        /* 改变订单状态 */
        $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, (isset($array_data['transaction_id']) ? $array_data['transaction_id'] : ''), $platform_shopid);
        if ($ret['status']) {
            $this->loadService('shop_grade_apply')->delete_payment_notice_data('wxpay', $order_sn);
        }
        $this->loadCore('log')->write(APP_DEBUG, print_r($ret, true), 'wxpay');

        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_apply" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_apply" . $order_id . "html");
        }
        return $ret['status'];
    }

    /**
     * 响应操作
     */
    function apply_auto_respond($post) {
        $platform_shopid = $this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $platform_shopid);
        $arr = $post;
        $order_sn = $arr['out_trade_no'];
        $order_id = $this->loadService('shop_grade_apply')->get_apply_id_by_apply_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('shop_grade_apply')->check_apply_pay_money($order_sn, $arr['total_fee'] / 100, $platform_shopid)) {
            return false;
        }
        /* 改变订单状态 */
        $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, (isset($arr['transaction_id']) ? $arr['transaction_id'] : ''), $platform_shopid);
        if ($ret['status']) {
            $this->loadService('shop_grade_apply')->delete_payment_notice_data('wxpay', $order_sn);
        }
        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_apply" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_apply" . $order_id . "html");
        }
        return $ret['status'];
    }


    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_apply($order) {
        $obj = null;//new \stdClass();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay', $this->get_platform_shopid());
        require_once "wxlib/WxPay.Api.php";
        $this->loadCore('log')->write(APP_ERROR, "refund1", 'wxpay');

        \WxPayConfig::$APPID = $payment['pay_config']['wx']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['wx']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['wx']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['wx']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);

        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $this->loadCore('log')->write(APP_ERROR, "退款途径：wap", 'wxpay');
        //wap
        ini_set('date.timezone', 'Asia/Shanghai');
        error_reporting(E_ERROR);

        $out_trade_no = $order['apply_pay_no'];

        $refund_fee = $order["apply_pay_fee"] * 100;
        $input = new \WxPayRefund();

        $opts['where']['opay_no'] = $order['apply_pay_no'];
        $opts['where']['opay_status'] = 1;
        $order_pay_base = $this->loadService('order_pay_base')->find_one($opts);
        if (!$order_pay_base) {
            $obj['status'] = false;
            $obj['msg'] = "不存在的支付单，退款失败";
            return $obj;
        }


        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($order_pay_base['opay_amount'] * 100);
        $input->SetRefund_fee($refund_fee);
        $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
        $input->SetOp_user_id(\WxPayConfig::$MCHID);
        try {
            $rs = \WxPayApi::refund($input);
        } catch (\Exception $err) {
            $obj['status'] = false;
            $obj['msg'] = "微信支付退款失败，失败原因：" . $err->getMessage();
            return $obj;
        }

        if ($rs['result_code'] == 'FAIL' || $rs['return_code'] == 'FAIL') {
            //                echo "微信支付退款失败，失败原因：".$rs['return_msg'];
            //                exit;
            $obj['status'] = false;
            $obj['msg'] = "微信支付退款失败，失败原因：" . $rs['return_msg'];
            return $obj;
        }

        //业务数据处理
        $ret = $this->loadService('shop_grade_apply')->do_cancel_apply($order);
        $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret, 'orderinfo_refund');
        if ($ret === false) {
            $retdata['msg'] = "退款申请失败！";
            $retdata['status'] = false;
        }

        return $retdata;
    }
}