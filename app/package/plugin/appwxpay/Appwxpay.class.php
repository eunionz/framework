<?php
/**
 * EUnionZ PHP Framework Wxpay Plugin class
 * 微信支付第二版
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\appwxpay;


defined('APP_IN') or exit('Access Denied');

class Appwxpay extends \cn\eunionz\core\Plugin {

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


    function __construct() {

        $payment = $this->loadService('shop_payment')->get_payment('appwxpay');
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
            $this->SSLCERT_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
            $this->SSLKEY_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);
        }
    }

    /**
     * 生成支付代码
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order, $payment, $front_url = '') {
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


            $unifiedOrder->setParameter("body", $order['order_title']);//商品描述
            //自定义订单号，此处仅作举例
            //$unifiedOrder->setParameter("code","wxpay");
            //out_trade_no    String(32)  商户系统内部的订单号,32个字符内、可包含字母
            $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
            $unifiedOrder->setParameter("total_fee", ($order['order_order_amount'] * 100));//总金额
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

            return $pay_code;

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

            $unifiedOrder->setParameter("body", $order['order_title']);//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $out_trade_no = $order['order_sn'];
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($order['order_order_amount'] * 100));//总金额
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


            if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order')) {
                @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order');
            }

            $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order' . APP_DS . $order['order_id'] . '.png', false, 'L', true, 10, false);
            return array('result' => $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/order/' . $order['order_id'] . '.png');

        }


    }

    /**
     * 分销订单生成
     * */
    public function distributor_get_code($order, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;
        if ($_SESSION['is_weixin_browser']) {// Wap
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
            $unifiedOrder->setParameter("body", $order['order_title']);//商品描述
            //自定义订单号，此处仅作举例
            //$unifiedOrder->setParameter("code","wxpay");
            //out_trade_no    String(32)  商户系统内部的订单号,32个字符内、可包含字母
            $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
            $unifiedOrder->setParameter("total_fee", ($order['order_order_amount'] * 100));//总金额
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

            return $pay_code;

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

            $unifiedOrder->setParameter("body", $order['order_title']);//商品描述

            //自定义订单号，此处仅作举例
            $timeStamp = time();
            $out_trade_no = $order['order_sn'];
            $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
            $unifiedOrder->setParameter("total_fee", ($order['order_order_amount'] * 100));//总金额
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
            if (!file_exists(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'dist_order')) {
                @mkdir(APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'dist_order');
            }
            $this->loadPlugin('phpqrcode')->create($code_url, APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'dist_order' . APP_DS . $order['order_id'] . '.png', false, 'L', true, 10, false);
            return array('result' => $this->loadPlugin('common')->getDomain() . '/runtime/uploads/qrcode/dist_order/' . $order['order_id'] . '.png');
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
        $payment = $this->loadService('shop_payment')->get_payment('wxpay');
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond:mobile', 'appwxpay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];

            $this->loadService('order_info')->save_payment_notice_data('appwxpay', $order_sn, $arr);

            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }

            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_money($order_sn, $arr['total_fee'] / 100)) {
                return false;
            }

            /* 改变订单状态 */
            $this->loadService('order_info')->order_paid($order_sn);
            $this->loadService('order_info')->delete_payment_notice_data('appwxpay', $order_sn);
            return true;

        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc', 'appwxpay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('order_info')->save_payment_notice_data('appwxpay', $order_sn, $arr);

            $order_id = $this->loadService('order_info')->get_order_id_by_order_sn($order_sn);

            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }

            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_money($order_sn, $arr['total_fee'] / 100)) {
                return false;
            }

            /* 改变订单状态 */
            $this->loadService('order_info')->order_paid($order_sn);
            $this->loadService('order_info')->delete_payment_notice_data('appwxpay', $order_sn);

            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
            }
            return true;


        }
    }

    /**
     * 自动响应操作
     */
    function auto_respond($post) {
        $payment = $this->loadService('shop_payment')->get_payment('wxpay');
        $arr = $post;
        $order_sn = $arr['out_trade_no'];

        $order_id = $this->loadService('order_info')->get_order_id_by_order_sn($order_sn);

        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }

        /* 检查支付的金额是否相符 */
        if (!$this->loadService('order_info')->check_money($order_sn, $arr['total_fee'] / 100)) {
            return false;
        }

        /* 改变订单状态 */
        $this->loadService('order_info')->order_paid($order_sn);
        $this->loadService('order_info')->delete_payment_notice_data('appwxpay', $order_sn);

        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
        }
        return true;

    }

    /**
     * 响应操作
     */
    function distributor_respond($array_data) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay',$platform_shopid);
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond:mobile', 'appwxpay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('appwxpay', $order_sn, $arr);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100,$platform_shopid)) {
                return false;
            }
            /* 改变订单状态 */
            $this->loadService('distributor_buy_log')->order_paid($order_sn);
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('appwxpay', $order_sn);
            return true;
        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond:pc', 'appwxpay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('appwxpay', $order_sn, $arr);
            $order_id = $this->loadService('distributor_buy_log')->get_order_id_by_order_sn($order_sn,$platform_shopid);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100,$platform_shopid)) {
                return false;
            }
            /* 改变订单状态 */
            $this->loadService('distributor_buy_log')->order_paid($order_sn);
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('appwxpay', $order_sn);
            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html");
            }
            return true;
        }
    }

    /**
     * 自动响应操作
     */
    function distributor_auto_respond($post) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('wxpay');
        $arr = $post;
        $order_sn = $arr['out_trade_no'];
        $order_id = $this->loadService('distributor_buy_log')->get_order_id_by_order_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100,$platform_shopid)) {
            return false;
        }
        /* 改变订单状态 */
        $this->loadService('distributor_buy_log')->order_paid($order_sn);
        $this->loadService('distributor_buy_log')->delete_payment_notice_data('appwxpay', $order_sn);
        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html");
        }
        return true;
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
        $this->loadCore('log')->write(APP_ERROR, 'appwxpay: ' . print_r($refund, true), 'appwxpay');

        $payment = $this->loadService('shop_payment')->get_payment('appwxpay');
        require_once "wxlib/WxPay.Api.php";

        \WxPayConfig::$APPID = $payment['pay_config']['wx']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['wx']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['wx']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['wx']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['wx']['wx_ssl_key_files']);

        $remarket = "微信App支付退款成功";
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            //wap
            ini_set('date.timezone', 'Asia/Shanghai');
            error_reporting(E_ERROR);


            $out_trade_no = $order['order_sn'];

            $total_fee = $order["order_orig_money_paid"] * 100;
            $refund_fee = $refund["orefund_amount"] * 100;
            $input = new \WxPayRefund();

            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$MCHID);
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
            $this->loadCore('log')->write(APP_ERROR, "refund3:", 'appwxpay');

            //修改退款单号的状态
            $orefund_way = 0;// 0:线上退款 1：线下退款  
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $remarket, $refund['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'appwxpay');
            return $ret;
        } else {

            //pc
            //计算手续费
            //计算手续费
            $this->loadCore('Log')->write(APP_INFO, 'appwxpay refund1:', 'appwxpay');
            ini_set('date.timezone', 'Asia/Shanghai');
            error_reporting(E_ERROR);


            $out_trade_no = $order['order_sn'];
            $total_fee = $order["order_orig_money_paid"] * 100;
            $refund_fee = $refund["orefund_amount"] * 100;
            $input = new \WxPayRefund();

            $input->SetOut_trade_no($out_trade_no);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);
            $input->SetOut_refund_no(\WxPayConfig::$MCHID . date("YmdHis"));
            $input->SetOp_user_id(\WxPayConfig::$MCHID);
            $input->SetOp_user_id(\WxPayConfig::$MCHID);

            $rs = \WxPayApi::refund($input);
            $this->loadCore('Log')->write(APP_INFO, 'appwxpay refund2:' . print_r($rs, true), 'appwxpay');

            if ($rs['result_code'] == 'FAIL' || $rs['return_code'] == 'FAIL') {
                //echo "微信支付退款失败，失败原因：".$rs['return_msg'];
                //exit;
                $obj['status'] = false;
                $obj['msg'] = "微信支付退款失败，失败原因：" . $rs['return_msg'];
                return $obj;
            }

            //业务数据处理
            $this->loadCore('log')->write(APP_ERROR, "refund3:", 'appwxpay');

            //修改退款单号的状态
            $orefund_way = 0;// 0:线上退款 1：线下退款  
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $remarket, $refund['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'appwxpay');
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

    function get_platform_shopid() {
        return (isset($_SESSION['PLATFORM_SHOP_ID'])) ? $_SESSION['PLATFORM_SHOP_ID'] : $this->getConfig('shop', 'SHOP_ID');
    }

}