<?php
/**
 * EUnionZ PHP Framework weapppay Plugin class
 * 微信小程序支付第二版
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\weapppay;


defined('APP_IN') or exit('Access Denied');

class Weapppay extends \cn\eunionz\core\Plugin {

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

        $payment = $this->loadService('shop_payment')->get_payment('weapppay', $this->get_platform_shopid());
        if (isset($payment)) {
            $this->wxpay_app_id = $payment['pay_config']['weapp']['App_id'];
            $this->wxpay_app_secret = $payment['pay_config']['weapp']['Openid_JSP'];
            $this->wxpay_partnerid = $payment['pay_config']['weapp']['PartnerID'];
            $this->wxpay_partnerkey = $payment['pay_config']['weapp']['PaySignKey'];
            if (!isset($payment['pay_config']['weapp']['weapp_ssl_cert_files'])) {
                $payment['pay_config']['weapp']['weapp_ssl_cert_files'] = '';
            }
            if (!isset($payment['pay_config']['weapp']['weapp_ssl_key_files'])) {
                $payment['pay_config']['weapp']['weapp_ssl_key_files'] = '';
            }
//            $this->SSLCERT_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_cert_files']);
//            $this->SSLKEY_PATH = APP_REAL_PATH . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_key_files']);

            $this->SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_cert_files']);
            $this->SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_key_files']);
            if ($payment['pay_config']['weapp']['weapp_ssl_rootca_files']) {
                $this->SSLROOTCA_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_rootca_files']);
            }
        }
    }

    /**
     * 生成支付代码/微信支付时返回微信网页支付需要的参数
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order, $payment, $front_url = '') {
        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;
        $this->loadCore('log')->write(APP_DEBUG, 'get_code_start', 'weapppay');
        $charset = 'utf-8';
        $jsApi = new \JsApi_pub();
        //=========步骤1：网页授权获取用户openid============

        //通过code获得openid
        //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
        //通过code获得openid
        //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE

        $pay_code['status'] = true;
        if (!isset($_SESSION['weapp_openid']) || empty($_SESSION['weapp_openid'])) {
            $pay_code['error_desc'] = "session已过期，支付参数获取失败！";
            $pay_code['status'] = false;
            return $pay_code;
        }

        $openid = $_SESSION['weapp_openid'];

        //=========步骤2：使用统一支付接口，获取prepay_id============
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", "$openid");

        //添加支付单号记录
        $payment['payproducttype'] = 'weapppay';
        $payment['externalid'] = '';
        $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order);//外部订单号
        $out_trade_no = $payment['requestid'];
        $ret = $this->loadService('order_pay_base')->create_pay_no($order, $payment);
        if ($ret === false) {
            $pay_code['error_desc'] = '添加支付单号记录失败！';
            $pay_code['status'] = false;
            return $pay_code;
        }
        $opay_id = $ret;
        $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

        $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($payment['productname'], 0, 30, 'utf-8'));//商品描述
        //自定义订单号，此处仅作举例
        //$unifiedOrder->setParameter("code","weapppay");
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee", intval($payment['amount'] * 100));//总金额

        //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
        $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=weapppay');//通知地址  return_url(basename(__FILE__, '.php'))
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
        $this->loadCore('log')->write(APP_DEBUG, print_r($pay_code, true), 'weapppay');
        $this->loadCore('log')->write(APP_DEBUG, 'get_code_end', 'weapppay');
        return $pay_code;
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
        $this->loadCore('log')->write(APP_DEBUG, 'get_code_start', 'weapppay');
        $charset = 'utf-8';
        $jsApi = new \JsApi_pub();
        //=========步骤1：网页授权获取用户openid============

        //通过code获得openid
        //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE
        //通过code获得openid
        //http://demo3.s1.shop.iwanqi.cn/mobile/order.php?act=done&code=021072b846514da6f4bb71028b6910d-&state=STATE

        $pay_code['status'] = true;
        if (!isset($_SESSION['weapp_openid']) || empty($_SESSION['weapp_openid'])) {
            $pay_code['error_desc'] = "session已过期，支付参数获取失败！";
            $pay_code['status'] = false;
            return $pay_code;
        }

        $openid = $_SESSION['weapp_openid'];

        //=========步骤2：使用统一支付接口，获取prepay_id============
        $unifiedOrder = new \UnifiedOrder_pub();
        $unifiedOrder->setParameter("openid", "$openid");

        //添加支付单号记录
        $payment['payproducttype'] = 'wxpay';
        $payment['externalid'] = '';
        $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order);//外部订单号
        $out_trade_no = $payment['requestid'];
        $ret = $this->loadService('order_pay_base')->create_pay_no($order, $payment);
        if ($ret === false) {
            $pay_code['error_desc'] = '添加支付单号记录失败！';
            $pay_code['status'] = false;
            return $pay_code;
        }
        $opay_id = $ret;
        $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();


        $unifiedOrder->setParameter("body", $this->loadPlugin('common')->subString($payment['productname'], 0, 30, 'utf-8'));//商品描述
        //自定义订单号，此处仅作举例
        //$unifiedOrder->setParameter("code","weapppay");
        $unifiedOrder->setParameter("out_trade_no", $out_trade_no);//商户订单号
        $unifiedOrder->setParameter("total_fee", intval($payment['amount'] * 100));//总金额
        //$unifiedOrder->setParameter("notify_url",return_url(basename(__FILE__, '.php')));//通知地址
        $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distweapppay');//通知地址  return_url(basename(__FILE__, '.php'))
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
        $this->loadCore('log')->write(APP_DEBUG, 'get_code_end', 'weapppay');
        return $pay_code;
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
        $payment = $this->loadService('shop_payment')->get_payment('weapppay', $this->get_platform_shopid());
        $this->loadCore('log')->write(APP_DEBUG, 'respond_start', 'weapppay');
        $this->loadCore('log')->write(APP_DEBUG, print_r($array_data, true), 'weapppay');
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond：is_mobile_browser', 'weapppay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];

            $this->loadService('order_info')->save_payment_notice_data('weapppay', $order_sn, $arr);

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
                $this->loadService('order_info')->delete_payment_notice_data('weapppay', $order_sn);
            }
            return $ret['status'];

        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond：pc', 'weapppay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('order_info')->save_payment_notice_data('weapppay', $order_sn, $arr);
            $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                $this->loadCore('log')->write(APP_DEBUG, 'respond：trade_state大于0', 'weapppay');
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $arr['total_fee'] / 100)) {
                $this->loadCore('log')->write(APP_DEBUG, 'respond：支付的金额不相符', 'weapppay');
                return false;
            }

            /* 改变订单状态 */
            $ret = $this->loadService('order_info')->pay_success($order_sn, isset($array_data['transaction_id']) ? $array_data['transaction_id'] : '');
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('weapppay', $order_sn);
            }
            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
            }
            $this->loadCore('log')->write(APP_DEBUG, 'respond：success', 'weapppay');
            return $ret['status'];
        }
    }

    /**
     * 响应操作
     */
    function auto_respond($post) {
        $payment = $this->loadService('shop_payment')->get_payment('weapppay', $this->get_platform_shopid());
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
            $this->loadService('order_info')->delete_payment_notice_data('weapppay', $order_sn);
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
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('weapppay',$platform_shopid);
        $this->loadCore('log')->write(APP_DEBUG, print_r($array_data, true), 'weapppay');
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_DEBUG, 'respond：is_mobile_browser', 'weapppay');
            /*取返回参数*/
            $fields = 'bank_billno,bank_type,discount,fee_type,input_charset,notify_id,out_trade_no,partner,product_fee' . ',sign_type,time_end,total_fee,trade_mode,trade_state,transaction_id,transport_fee,result_code,return_code';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('weapppay', $order_sn, $arr);

            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100,$platform_shopid)) {
                return false;
            }
            /* 改变订单状态 */
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($array_data['transaction_id']) ? $array_data['transaction_id'] : ''), $platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('weapppay', $order_sn);
            }
            return $ret['status'];
        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'respond：pc', 'weapppay');
            /*取返回参数*/
            $fields = 'appid,bank_type,cash_fee,code,fee_type,is_subscribe,mch_id,nonce_str,openid' . ',out_trade_no,result_code,return_code,sign,time_end,total_fee,trade_type,transaction_id';
            $arr = null;
            foreach (explode(',', $fields) as $val) {
                if (isset($array_data[$val])) {
                    $arr[$val] = trim($array_data[$val]);
                }
            }
            $order_sn = $arr['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('weapppay', $order_sn, $arr);
            $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
            /* 如果trade_state大于0则表示支付失败 */
            if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
                $this->loadCore('log')->write(APP_DEBUG, 'respond：trade_state大于0', 'weapppay');
                return false;
            }
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee'] / 100,$platform_shopid)) {
                $this->loadCore('log')->write(APP_DEBUG, 'respond：支付的金额不相符', 'weapppay');
                return false;
            }
            /* 改变订单状态 */
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($array_data['transaction_id']) ? $array_data['transaction_id'] : ''), $platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('weapppay', $order_sn);
            }
            if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html")) {
                @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx_dist" . $order_id . "html");
            }
            $this->loadCore('log')->write(APP_DEBUG, 'respond：success', 'weapppay');
            return $ret['status'];
        }
    }

    /**
     * 响应操作
     */
    function distributor_auto_respond($post) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('weapppay',$platform_shopid);
        $arr = $post;
        $order_sn = $arr['out_trade_no'];
        $order_id = $this->loadService('order_info')->get_order_id_by_order_pay_sn($order_sn);
        /* 如果trade_state大于0则表示支付失败 */
        if ($arr['result_code'] != "SUCCESS" || $arr['return_code'] != "SUCCESS") {
            return false;
        }
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $arr['total_fee']/100,$platform_shopid)) {
            return false;
        }
        /* 改变订单状态 */
        $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, (isset($arr['transaction_id']) ? $arr['transaction_id'] : ''), $platform_shopid);
        if ($ret['status']) {
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('weapppay', $order_sn);
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
        $payment = $this->loadService('shop_payment')->get_payment('weapppay', $this->get_platform_shopid());
        require_once "wxlib/WxPay.Api.php";
        $this->loadCore('log')->write(APP_ERROR, "refund1", 'weapppay');

        \WxPayConfig::$APPID = $payment['pay_config']['weapp']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['weapp']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['weapp']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['weapp']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_key_files']);


        $remarket = "微信支付退款成功";
        if ($this->loadPlugin('common')->is_mobile_browser()) {
            $this->loadCore('log')->write(APP_ERROR, "退款途径：wap", 'weapppay');
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
            if ($rs['return_code'] == 'FAIL') {
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
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'weapppay');

            return $ret;
        } else {
            $this->loadCore('log')->write(APP_ERROR, "退款途径：PC", 'weapppay');
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
            $this->loadCore('log')->write(APP_ERROR, "refund2:" . print_r($rs, true), 'weapppay');
            if ($rs['return_code'] == 'FAIL') {
                //echo "微信支付退款失败，失败原因：".$rs['return_msg'];
                //exit;
                $obj['status'] = false;
                $obj['msg'] = "微信支付退款失败，失败原因：" . $rs['return_msg'];
                return $obj;
            }

            //业务数据处理
            $this->loadCore('log')->write(APP_ERROR, "refund3:", 'weapppay');


            //修改退款单号的状态
            $orefund_way = 0;// 0:线上退款 1：线下退款  
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $remarket, $refund['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'weapppay');

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
        $order['order_sn'] = "wt_sn_" . time();
        $order['order_order_amount'] = 0.01;

        include_once("wxpay/WxPayPubHelper.php");
        \WxPayConf_pub::$APPID = $this->wxpay_app_id;
        \WxPayConf_pub::$APPSECRET = $this->wxpay_app_secret;
        \WxPayConf_pub::$MCHID = $this->wxpay_partnerid;
        \WxPayConf_pub::$KEY = $this->wxpay_partnerkey;

        $jsApi = new \JsApi_pub();
        $unifiedOrder = new \UnifiedOrder_pub();

        $unifiedOrder->setParameter("openid", "oHHv60EgBWbfBi-110jrk1MComtI");
        $unifiedOrder->setParameter("body", $order['order_title']);//商品描述
        $unifiedOrder->setParameter("out_trade_no", $order['order_sn']);//商户订单号
        $unifiedOrder->setParameter("total_fee", intval($order['order_order_amount'] * 100));//总金额
        $unifiedOrder->setParameter("notify_url", $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=weapppay');//通知地址  return_url(basename(__FILE__, '.php'))
        $unifiedOrder->setParameter("trade_type", "JSAPI");//交易类型

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
    }

    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_order($order) {
        $obj = null;//new \stdClass();
        $payment = $this->loadService('shop_payment')->get_payment('weapppay', $this->get_platform_shopid());
        require_once "wxlib/WxPay.Api.php";
        $this->loadCore('log')->write(APP_ERROR, "refund1", 'weapppay');

        \WxPayConfig::$APPID = $payment['pay_config']['weapp']['App_id'];
        \WxPayConfig::$APPSECRET = $payment['pay_config']['weapp']['Openid_JSP'];
        \WxPayConfig::$MCHID = $payment['pay_config']['weapp']['PartnerID'];
        \WxPayConfig::$KEY = $payment['pay_config']['weapp']['PaySignKey'];

        \WxPayConfig::$SSLCERT_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_cert_files']);
        \WxPayConfig::$SSLKEY_PATH = APP_REAL_PATH . APP_SITE_TEMP_PATH . APP_DS . 'public_html' . APP_DS . str_replace("/", APP_DS, $payment['pay_config']['weapp']['weapp_ssl_key_files']);

        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $this->loadCore('log')->write(APP_ERROR, "退款途径：pc", 'weapppay');
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

}