<?php
/**
 * EUnionZ PHP Framework Wxpay Plugin class
 * 易宝支付
 * Created by PhpStorm.
 * User: wt  (719863381@qq.com)
 * Date: 17-4-7
 * Time: 上午10:16
 */

namespace package\plugin\yeepay;

defined('APP_IN') or exit('Access Denied');

class Yeepay extends \cn\eunionz\core\Plugin {

    /**
     * 商户编号
     * @var string
     */
    public $customernumber = '';

    /**
     * 商户密钥
     * @var string
     */
    public $keyValue = '';


    function __construct() {
        $payment = $this->loadService('shop_payment')->get_payment('yeepay', $_SESSION['PLATFORM_SHOP_ID']);
        //先期固定
        //$payment['pay_config']['yeepay']['customernumber'] = '10000447996';
        //$payment['pay_config']['yeepay']['keyValue'] = 'jj3Q1h0H86FZ7CD46Z5Nr35p67L199WdkgETx85920n128vi2125T9KY2hzv';

        $sysConfig["customernumber"] = $payment['pay_config']['yeepay']['customernumber'];
        $sysConfig["keyValue"] = $payment['pay_config']['yeepay']['keyValue'];
        if ($_SESSION['is_debug']) {
            $sysConfig["customernumber"] = '10000447996';
            $sysConfig["keyValue"] = "jj3Q1h0H86FZ7CD46Z5Nr35p67L199WdkgETx85920n128vi2125T9KY2hzv";
        }
        $GLOBALS['sysConfig'] = $sysConfig;
        $GLOBALS['infConfig'] = array();

    }

    /**
     * 生成支付代码/微信支付时返回微信网页支付需要的参数
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order_list, $pay_info, $front_url = '') {
        $method = "pay";
        include_once("inc/config.php");

        $pay_code['error_desc'] = "获取支付参数成功！";
        $pay_code['status'] = true;

        //根据支付的类型（网银，一键支付，账号支付，微信支付，无卡直连，将必填参数配置扩展）
        if (!array_key_exists("payproducttype", $_REQUEST)) {
            throw new \ZGTException("payproducttype of request is not found.");
            $pay_code['error_desc'] = 'payproducttype of request is not found.';
            $pay_code['status'] = false;
            return $pay_code;
        }
        global $infConfig;
        $infConfig[$method]["mustFillRequest"] = array_merge($infConfig[$method]["mustFillRequest"], $infConfig[$method]["mustFillRequest_" . strtoupper($_REQUEST["payproducttype"])]);

        //生成支付单号

        $pay_info['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list, true); //'yee' . date("YmdHis") . rand(10000, 99999);


        $pay_info['callbackurl'] = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=yeepay';//通知地址  return_url(basename(__FILE__, '.php'))
        $pay_info['ip'] = $this->loadPlugin('common')->get_ip();//客户端IP
        //在支付成功回调的页面链接中添加支付单号，金额，支付时间
        //拼接符号
        $str = $this->isInString("?", $pay_info['webcallbackurl']) ? "&" : "?";
        $pay_info['webcallbackurl'] = $pay_info['webcallbackurl'] . trim($str) . "requestid={$pay_info['requestid']}&amount={$pay_info['amount']}&pay_time=" . time();
        if ($_SESSION['is_weixin_browser']) {
            // Wap
            $this->loadCore('log')->write(APP_DEBUG, 'wx_browser:start', 'yeepay');
            if (!isset($_SESSION['weixin_openid']) || empty($_SESSION['weixin_openid'])) {
                $pay_code['error_desc'] = '用户信息已过期';
                $pay_code['status'] = false;
                return $pay_code;
            }

            //$pay_info['payproducttype'] = 'WECHATU';
            $pay_info['userno'] = $_SESSION['weixin_openid'];

            $req = new \RequestService($method);
            try {
                $req->sendRequest($pay_info);
                $req->receviceResponse();
            } catch (\Exception $err) {
                $pay_code['status'] = false;
                $pay_code['error_desc'] = "请检查支付接口配置";//$err->getMessage();
                $this->loadCore('log')->write(APP_DEBUG, $err->getMessage(), 'yeepay');
                return $pay_code;
            }
            $request = $req->getRequest();
            $response = $req->getResponseData();
            $pay_code['result'] = $response;

            //return $pay_code;
        } else {
            // PC
            $this->loadCore('log')->write(APP_DEBUG, 'pc:start', 'yeepay');


            $req = new \RequestService($method);
            try {
                $req->sendRequest($pay_info);
                $req->receviceResponse();
            } catch (\Exception $err) {
                $pay_code['status'] = false;
                $pay_code['error_desc'] = "请检查支付接口配置";//$err->getMessage();
                $this->loadCore('log')->write(APP_DEBUG, $err->getMessage(), 'yeepay');
                return $pay_code;
            }
            $request = $req->getRequest();
            $response = $req->getResponseData();
            $pay_code['result'] = $response;
            if ($request["payproducttype"] == "WECHATU") {
                $img = hex2byte($response["payurl"]);
                $path = APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order';
                $filename = @"$path" . '/' . $pay_info['requestid'] . '.png';    // 写入的文件   // 写入的文件
                $file = fopen($filename, "w");//打开文件准备写入
                fwrite($file, $img);//写入
                fclose($file);//关闭
                $pay_code['result']['payurl'] = $this->loadPlugin('common')->getImageUrl('/runtime/uploads/qrcode/order/' . $pay_info['requestid'] . '.png') . '?' . time();
            }

        }
        $pay_info['externalid'] = $pay_code['result']['externalid'];
        //添加支付单号记录
        $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $pay_info);
        if ($ret === false) {
            $pay_code['error_desc'] = '添加支付单号记录失败！';
            $pay_code['status'] = false;
            return $pay_code;
        }
        $pay_code['pay_info'] = $pay_info;
        return $pay_code;
    }

    /**
     * 分销订单生成
     * */
    public function distributor_get_code($order_list, $payment, $front_url = '') {
        $method = "pay";
        include_once("inc/config.php");
        $pay_code['error_desc'] = "获取支付参数成功！";
        $pay_code['status'] = true;
        //根据支付的类型（网银，一键支付，账号支付，微信支付，无卡直连，将必填参数配置扩展）
        if (!array_key_exists("payproducttype", $_REQUEST)) {
            throw new \ZGTException("payproducttype of request is not found.");
            $pay_code['error_desc'] = 'payproducttype of request is not found.';
            $pay_code['status'] = false;
            return $pay_code;
        }
        global $infConfig;
        $infConfig[$method]["mustFillRequest"] = array_merge($infConfig[$method]["mustFillRequest"], $infConfig[$method]["mustFillRequest_" . strtoupper($_REQUEST["payproducttype"])]);
        //生成支付单号
        $pay_info['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list, true); //'yee' . date("YmdHis") . rand(10000, 99999);
        $pay_info['callbackurl'] = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distyeepay';//通知地址  return_url(basename(__FILE__, '.php'))
        $pay_info['ip'] = $this->loadPlugin('common')->get_ip();//客户端IP
        //在支付成功回调的页面链接中添加支付单号，金额，支付时间
        //拼接符号
        $str = $this->isInString("?", $pay_info['webcallbackurl']) ? "&" : "?";
        $pay_info['webcallbackurl'] = $pay_info['webcallbackurl'] . trim($str) . "requestid={$pay_info['requestid']}&amount={$pay_info['amount']}&pay_time=" . time();
        if ($_SESSION['is_weixin_browser']) {
            // Wap
            $this->loadCore('log')->write(APP_DEBUG, 'wx_browser:start', 'yeepay');
            if (!isset($_SESSION['weixin_openid']) || empty($_SESSION['weixin_openid'])) {
                $pay_code['error_desc'] = '用户信息已过期';
                $pay_code['status'] = false;
                return $pay_code;
            }
            //$pay_info['payproducttype'] = 'WECHATU';
            $pay_info['userno'] = $_SESSION['weixin_openid'];
            $req = new \RequestService($method);
            try {
                $req->sendRequest($pay_info);
                $req->receviceResponse();
            } catch (\Exception $err) {
                $pay_code['status'] = false;
                $pay_code['error_desc'] = "请检查支付接口配置";//$err->getMessage();
                $this->loadCore('log')->write(APP_DEBUG, $err->getMessage(), 'yeepay');
                return $pay_code;
            }
            $request = $req->getRequest();
            $response = $req->getResponseData();
            $pay_code['result'] = $response;

            //return $pay_code;
        } else {
            // PC
            $this->loadCore('log')->write(APP_DEBUG, 'pc:start', 'yeepay');
            $req = new \RequestService($method);
            try {
                $req->sendRequest($pay_info);
                $req->receviceResponse();
            } catch (\Exception $err) {
                $pay_code['status'] = false;
                $pay_code['error_desc'] = "请检查支付接口配置";//$err->getMessage();
                $this->loadCore('log')->write(APP_DEBUG, $err->getMessage(), 'yeepay');
                return $pay_code;
            }
            $request = $req->getRequest();
            $response = $req->getResponseData();
            $pay_code['result'] = $response;
            if ($request["payproducttype"] == "WECHATU") {
                $img = hex2byte($response["payurl"]);
                $path = APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'qrcode' . APP_DS . 'order_dist';
                $filename = @"$path" . '/' . $pay_info['requestid'] . '.png';    // 写入的文件   // 写入的文件
                $file = fopen($filename, "w");//打开文件准备写入
                fwrite($file, $img);//写入
                fclose($file);//关闭
                $pay_code['result']['payurl'] = $this->loadPlugin('common')->getImageUrl('/runtime/uploads/qrcode/order_dist/' . $pay_info['requestid'] . '.png') . '?' . time();
            }

        }
        $pay_info['externalid'] = $pay_code['result']['externalid'];
        //添加支付单号记录
        $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $pay_info);
        if ($ret === false) {
            $pay_code['error_desc'] = '添加支付单号记录失败！';
            $pay_code['status'] = false;
            return $pay_code;
        }
        $pay_code['pay_info'] = $pay_info;
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
        $method = "pay";
        include_once("inc/config.php");

        if (!isViaArray($_REQUEST, "data")) {
            $this->loadCore('log')->write(APP_DEBUG, 'callback param data is null.', 'yeepay');
            return false;
        }
        $data = $_REQUEST["data"];
        //解析返回值
        if (!isViaArray($infConfig)) {
            $this->loadCore('log')->write(APP_DEBUG, 'infConfig is null.', 'yeepay');
            return false;
        }
        if (!array_key_exists($method, $infConfig)) {
            $this->loadCore('log')->write(APP_DEBUG, "biz of infConfig is not found[" . $method . "].", 'yeepay');
            return false;
        }

        $customernumber = getCustomerNumber();
        $keyForHmac = getKeyValue();
        $keyForAES = getKeyForAes();
        $bizConfig = $infConfig[$method];

        $responseData = getDeAes($data, $keyForAES);
        $result = json_decode($responseData, true);
        $this->loadCore('log')->write(APP_DEBUG, "result:" . print_r($result, true), 'yeepay');
        //进行UTF-8->GBK转码
        /* $resultLocale = array();
        foreach ( $result as $rKey => $rValue ) {

            $resultLocale[$rKey] = iconv(getRemoteCode(), getLocaleCode(), $rValue);
        } */

        if ("1" != $result["code"]) {
            $this->loadCore('log')->write(APP_DEBUG, "response error, errmsg = [" . $resultLocale["msg"] . "], errcode = [" . $resultLocale["code"] . "].", 'yeepay');
            return false;
        }
        if (array_key_exists("customError", $result) && "" != $result["customError"]) {
            $this->loadCore('log')->write(APP_DEBUG, "response.customError error, errmsg = [" . $resultLocale["customError"] . "], errcode = [" . $resultLocale["code"] . "].", 'yeepay');
            return false;
        }
        if ($result["customernumber"] != $customernumber) {
            $this->loadCore('log')->write(APP_DEBUG, "customernumber not equals, request is [" . $customernumber . "], response is [" . $hmacData["customernumber"] . "].", 'yeepay');
            return false;
        }
        //验证返回签名
        $hmacGenConfig = $bizConfig["needCallbackHmac"];
        $hmacData = array();
        foreach ($hmacGenConfig as $hKey => $hValue) {
            $v = "";
            //判断$queryData中是否存在此索引并且是否可访问
            if (isViaArray($result, $hValue) && $result[$hValue]) {
                $v = $result[$hValue];
            }
            //取得对应加密的明文的值
            $hmacData[$hKey] = $v;
        }
        $hmac = getHmac($hmacData, $keyForHmac);
        if ($hmac != $result["hmac"]) {
            $this->loadCore('log')->write(APP_DEBUG, "hmac not equals, response is [" . $result["hmac"] . "], gen is [" . $hmac . "].", 'yeepay');
            return false;
        }
        if ("SERVER" == $result["notifytype"]) {
            /* 改变订单状态 */
            $ret = $this->loadService('order_info')->pay_success($result["requestid"]);
            return $ret['status'];
        }
    }

    /**
     * 响应操作
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
        $this->loadService('order_info')->order_paid($order_sn, isset($array_data['transaction_id']) ? $array_data['transaction_id'] : '');
        $this->loadService('order_info')->delete_payment_notice_data('wxpay', $order_sn);

        if (file_exists(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html")) {
            @unlink(APP_RUNTIME_REAL_PATH . "data/wxhtml/wx" . $order_id . "html");
        }
        return true;
    }


    /**
     * 响应操作
     */
    function distributor_respond($array_data) {
        $method = "pay";
        include_once("inc/config.php");
        if (!isViaArray($_REQUEST, "data")) {
            $this->loadCore('log')->write(APP_DEBUG, 'callback param data is null.', 'yeepay');
            return false;
        }
        $data = $_REQUEST["data"];
        //解析返回值
        if (!isViaArray($infConfig)) {
            $this->loadCore('log')->write(APP_DEBUG, 'infConfig is null.', 'yeepay');
            return false;
        }
        if (!array_key_exists($method, $infConfig)) {
            $this->loadCore('log')->write(APP_DEBUG, "biz of infConfig is not found[" . $method . "].", 'yeepay');
            return false;
        }

        $customernumber = getCustomerNumber();
        $keyForHmac = getKeyValue();
        $keyForAES = getKeyForAes();
        $bizConfig = $infConfig[$method];

        $responseData = getDeAes($data, $keyForAES);
        $result = json_decode($responseData, true);
        $this->loadCore('log')->write(APP_DEBUG, "result:" . print_r($result, true), 'yeepay');
        //进行UTF-8->GBK转码
        /* $resultLocale = array();
        foreach ( $result as $rKey => $rValue ) {

            $resultLocale[$rKey] = iconv(getRemoteCode(), getLocaleCode(), $rValue);
        } */

        if ("1" != $result["code"]) {
            $this->loadCore('log')->write(APP_DEBUG, "response error, errmsg = [" . $resultLocale["msg"] . "], errcode = [" . $resultLocale["code"] . "].", 'yeepay');
            return false;
        }
        if (array_key_exists("customError", $result) && "" != $result["customError"]) {
            $this->loadCore('log')->write(APP_DEBUG, "response.customError error, errmsg = [" . $resultLocale["customError"] . "], errcode = [" . $resultLocale["code"] . "].", 'yeepay');
            return false;
        }
        if ($result["customernumber"] != $customernumber) {
            $this->loadCore('log')->write(APP_DEBUG, "customernumber not equals, request is [" . $customernumber . "], response is [" . $hmacData["customernumber"] . "].", 'yeepay');
            return false;
        }
        //验证返回签名
        $hmacGenConfig = $bizConfig["needCallbackHmac"];
        $hmacData = array();
        foreach ($hmacGenConfig as $hKey => $hValue) {
            $v = "";
            //判断$queryData中是否存在此索引并且是否可访问
            if (isViaArray($result, $hValue) && $result[$hValue]) {
                $v = $result[$hValue];
            }
            //取得对应加密的明文的值
            $hmacData[$hKey] = $v;
        }
        $hmac = getHmac($hmacData, $keyForHmac);
        if ($hmac != $result["hmac"]) {
            $this->loadCore('log')->write(APP_DEBUG, "hmac not equals, response is [" . $result["hmac"] . "], gen is [" . $hmac . "].", 'yeepay');
            return false;
        }
        if ("SERVER" == $result["notifytype"]) {
            /* 改变订单状态 */
            $ret = $this->loadService('distributor_buy_log')->pay_success($result["requestid"]);
            return $ret['status'];
        }
    }

    /**
     * 响应操作
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
        $this->loadService('distributor_buy_log')->delete_payment_notice_data('yeepay', $order_sn);
        return true;
    }



    /**
     * 在线退货功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @param array $refund 退款单数据
     * @return bool true--成功  false--失败
     */
    public function refund($order, $refund) {
        $method = "refund";
        include_once("inc/config.php");
        global $sysConfig;

        $data['customernumber'] = $sysConfig['customernumber'];
        $data['requestid'] = $refund['orefund_id'] . date("YmdHis") . rand(100, 999);
        $data['orderrequestid'] = $order['order_pay_no'];//支付单号-易宝商户订单号
        $data['amount'] = $refund['orefund_amount'];
        $data['divideinfo'] = '';
        $data['confirm'] = 1;
        $data['memo'] = $order['order_sn'] . '^' . $refund['orefund_amount'] . '^' . "易宝退款成功";

        $retdata['smg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['msg'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($request["requestid"] != $response["requestid"]) {
            $retdata['msg'] = '请求参数不一致，退款失败';
            $retdata['status'] = false;
            return $retdata;
        }
        //失败
        if ($response['code'] != 1) {
            $retdata['msg'] = $response['msg'];
            $retdata['status'] = false;
            $retdata['code'] = $response['code'];
            $this->loadCore('log')->write(APP_ERROR, print_r($retdata, true), 'yeepay');
            return $retdata;
        }
        /* //修改退款单号的状态
        $orefund_way = 0;// 0:线上退款 1：线下退款
        //直接调用退款成功处理函数
        $ret = $this->loadService('order_info')->op_refund_by_order_sn($refund['order_sn'], $refund['orefund_amount'], $data['memo'], $refund['orefund_id'], $orefund_way, $order['order_shop_id']);
        $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'orderinfo_refund'); */
        $ret = $this->loadService('order_refunds')->do_refund_apply_success($refund['orefund_id'], $refund['orefund_shop_id'], $request["requestid"]);
        if ($ret === false) {
            $retdata['msg'] = "退款申请失败！";
            $retdata['status'] = false;
        }
        return $retdata;
    }

    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_order($order) {
        $method = "refund";
        include_once("inc/config.php");
        global $sysConfig;

        $data['customernumber'] = $sysConfig['customernumber'];
        $data['requestid'] = $order['order_sn'];
        $data['orderrequestid'] = $order['order_pay_no'];//支付单号-易宝商户订单号
        $data['amount'] = $order['order_money_paid'];
        $data['divideinfo'] = '';
        $data['confirm'] = 1;
        $data['memo'] = $order['order_sn'] . '^' . $order['orefund_amount'] . '^' . "易宝退款成功";

        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $this->loadCore('log')->write(APP_ERROR, $err->getMessage(), 'yeepay');
            $retdata['msg'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($request["requestid"] != $response["requestid"]) {
            $retdata['msg'] = '请求参数不一致，退款失败';
            $retdata['status'] = false;
            return $retdata;
        }
        //失败
        if ($response['code'] != 1) {
            $retdata['msg'] = $response['msg'];
            $retdata['status'] = false;
            $retdata['code'] = $response['code'];
            $this->loadCore('log')->write(APP_ERROR, print_r($retdata, true), 'yeepay');
            return $retdata;
        }
        //修改退款单号的状态
        $orefund_way = 0;// 0:线上退款 1：线下退款
        //直接调用退款成功处理函数
        $ret = $this->loadService('order_info')->do_cancel_by_order_sn($order);
        $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret, 'yeepay');
        if ($ret === false) {
            $retdata['msg'] = "退款申请失败！";
            $retdata['status'] = false;
        }
        return $retdata;
    }


    /**
     * 退款查询功能
     *
     * @param array $refundrequestid 退款请求号-订单表的order_sn
     * @param array $orderrequestid 商户订单号-订单表的order_pay_no
     * @return bool true--成功  false--失败
     */
    public function queryRefund($refundrequestid, $orderrequestid) {
        $method = "refundQuery";
        include_once("inc/config.php");
        global $sysConfig;

        $data['customernumber'] = $sysConfig['customernumber'];
        $data['refundrequestid'] = $refundrequestid;
        $data['orderrequestid'] = $orderrequestid;

        $retdata['smg'] = "退款状态查询成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['msg'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        $retdata['data'] = $response;
        //验证请求的requestid和返回的requestid是否一致
        if ($request["orderrequestid"] != $response["orderrequestid"]) {
            $retdata['msg'] = '请求参数不一致，查询退款状态失败';
            $retdata['status'] = false;
            return $retdata;
        }
        //失败
        if ($response['code'] != 1) {
            $retdata['msg'] = $response['msg'];
            $retdata['status'] = false;
            $retdata['code'] = $response['code'];
            $this->loadCore('log')->write(APP_ERROR, print_r($retdata, true), 'yeepay');
            return $retdata;
        }

        $this->loadCore('log')->write(APP_ERROR, "refund4:" . $ret['msg'], 'yeepay');
        return $retdata;
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

    function hex2byte($str) {
        $sbin = "";
        $len = strlen($str);
        for ($i = 0; $i < $len; $i += 2) {
            $sbin .= pack("H*", substr($str, $i, 2));
        }
        return $sbin;
    }


    /**
     * 分账账户注册
     */
    public function sendRegister($data) {
        $method = "register";
        include_once("inc/config.php");

        $retdata['error_desc'] = "分账账户注册成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        $retdata['data'] = $response;
        if ($request["requestid"] != $response["requestid"]) {
            $retdata['error_desc'] = "requestid not equals, response is [" . $response["requestid"] . "], requestid is [" . $request["requestid"] . "].";
            $retdata['status'] = false;
            return $retdata;
        }
        return $retdata;
    }


    /**
     * 分账方资质审核前上传本地
     */
    public function uploadLedgerQualifications_verify($data) {
        //        $method = "upload";
        include_once("inc/config.php");
        global $sysConfig;
        $data['customernumber'] = $sysConfig['customernumber'];
        //var_dump($data);die;


        $retdata['error_desc'] = "分账方资质上传成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $path = APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'yeepay' . APP_DS . 'ledger';
        if (!is_dir($path))
            @mkdir($path, 0777, true);

        if (!in_array($data["file"]["type"], array('image/gif', 'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png'))) {
            $retdata['error_desc'] = "文件格式错误，仅支持gif,jpeg,jpg,pjpeg,png格式文件。";
            $retdata['status'] = false;
        }
        if (intval($data["file"]["size"]) >= 512000) {
            $retdata['error_desc'] = "文件大小不能超过512K。";
            $retdata['status'] = false;
        }
        if (!$retdata['status'])
            return $retdata;

        if ($data["file"]["error"] > 0) {
            $retdata['error_desc'] = "Return Code: " . $data["file"]["error"];
            $retdata['status'] = false;
        } else {
            $file_path = $path . "/" . MD5($data["file"]["name"] . time()) . ".png";
            move_uploaded_file($data["file"]["tmp_name"], $file_path);
            $data['file']['file_path'] = $file_path;
            $retdata['file_path'] = "runtime/uploads/yeepay/ledger/" . MD5($data["file"]["name"] . time()) . ".png";
        }

        $data['file_path'] = $retdata['file_path'];
        $retdata['data'] = array('customernumber' => $data['customernumber'], 'file_path' => $data['file_path'], 'ledgerno' => $data['ledgerno'], 'filetype' => $data['filetype'],);
        return $retdata;
    }


    /**
     * 分账方资质仅上传
     */
    public function uploadLedgerQualifications_only($data) {
        $method = "upload";
        include_once("inc/config.php");
        global $sysConfig;
        $data['customernumber'] = $sysConfig['customernumber'];
        //var_dump($data);die;

        $retdata['error_desc'] = "分账方资质上传成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $path = APP_RUNTIME_REAL_PATH . str_ireplace('runtime/', '', $data['file_path']);
        $data['file']['name'] = basename($path);
        $data['file']['file_path'] = $path;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "上传资质失败！";
            $retdata['status'] = false;
            return $retdata;
        }
        $retdata['data'] = $response;
        return $retdata;
    }


    /**
     * 分账方资质上传
     */
    public function uploadLedgerQualifications($data) {
        $method = "upload";
        include_once("inc/config.php");
        global $sysConfig;
        $data['customernumber'] = $sysConfig['customernumber'];
        //var_dump($data);die;

        $retdata['error_desc'] = "分账方资质上传成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $path = APP_RUNTIME_REAL_PATH . 'uploads' . APP_DS . 'yeepay' . APP_DS . 'ledger';
        if (!is_dir($path))
            @mkdir($path, 0777, true);

        if (!in_array($data["file"]["type"], array('image/gif', 'image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png'))) {
            $retdata['error_desc'] = "文件格式错误，仅支持gif,jpeg,jpg,pjpeg,png格式文件。";
            $retdata['status'] = false;
        }
        if (intval($data["file"]["size"]) >= 512000) {
            $retdata['error_desc'] = "文件大小不能超过512K。";
            $retdata['status'] = false;
        }
        if (!$retdata['status'])
            return $retdata;

        if ($data["file"]["error"] > 0) {
            $retdata['error_desc'] = "Return Code: " . $data["file"]["error"];
            $retdata['status'] = false;
        } else {
            $file_path = $path . "/" . MD5($data["file"]["name"] . time()) . ".png";
            move_uploaded_file($data["file"]["tmp_name"], $file_path);
            $data['file']['file_path'] = $file_path;
            $retdata['file_path'] = "runtime/uploads/yeepay/ledger/" . MD5($data["file"]["name"] . time()) . ".png";
        }

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "上传资质失败！";
            $retdata['status'] = false;
            return $retdata;
        }
        $response['file_path'] = $retdata['file_path'];
        $retdata['data'] = $response;
        return $retdata;
    }


    /**
     * 分账方资质审核状态查询接口
     */
    public function queryCheckRecord($ledgerno) {
        $method = "checkRecordQuery";
        include_once("inc/config.php");
        global $sysConfig;
        $customernumber = $sysConfig['customernumber'];

        $data['customernumber'] = $customernumber;
        $data['ledgerno'] = $ledgerno;

        $retdata['error_desc'] = "分账方审核结果查询成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        $retdata['data'] = $response;
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "分账方审核结果查询失败！";
            $retdata['status'] = false;
            return $retdata;
        }
        return $retdata;
    }


    /**
     * 账户余额查询接口
     * 1. 当$ledgerno 为空时，将会查询主账户的余额。
     * 2. 当$ledgerno 有值时，查询子账号的余额；可同时查询多个子账户的余额，此时ledgerno 的输入格式为：ledgerno1,ledgerno2,ledgerno3。
     */
    public function queryBalance($ledgerno = null) {
        $method = "balanceQuery";
        include_once("inc/config.php");
        global $sysConfig;
        $customernumber = $sysConfig['customernumber'];

        $data['customernumber'] = $customernumber;
        $data['ledgerno'] = $ledgerno;

        $retdata['error_desc'] = "余额查询成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "余额查询失败！";
            $retdata['status'] = false;
            return $retdata;
        }
        $retdata['data'] = $response;
        return $retdata;
    }


    /**
     * 转帐接口
     * $requestid 结算单号：转账请求号转账请求号，在该商编下唯一
     * $ledgerno 子账户商户编号
     * $amount  转帐金额
     */
    public function transfer($requestid = '', $ledgerno = '', $amount = 0) {
        $method = "transfer";
        include_once("inc/config.php");
        global $sysConfig;
        $customernumber = $sysConfig['customernumber'];
        $data = null;
        $data['customernumber'] = $customernumber;
        $data['requestid'] = $requestid;
        $data['ledgerno'] = $ledgerno;
        $data['amount'] = $amount;

        $retdata['error_desc'] = "转帐请求成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;
        $retdata['data_s'] = $data;
        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "转帐请求失败！错误码：[{$response['code']}]";
            $retdata['status'] = false;
            return $retdata;
        }
        //验证请求的requestid和返回的requestid是否一致
        if ($data["requestid"] != $response["requestid"]) {
            $retdata['error_desc'] = "requestid not equals, response is [" . $response["requestid"] . "], requestid is [" . $request["requestid"] . "].";
            $retdata['status'] = false;
            return $retdata;
        }
        $retdata['data'] = $response;
        return $retdata;
    }

    /**
     * 转帐查询接口
     *$requestid  结算单号
     *  返回状态status为以下两种状态时，对数据进行操作，否则不操作
     *  FAIL：转账失败
     * COMPLETE：转账成功
     */
    public function transferQuery($requestid = '') {
        $method = "transferQuery";
        include_once("inc/config.php");
        global $sysConfig;
        $customernumber = $sysConfig['transferQuery'];

        $data['customernumber'] = $customernumber;
        $data['requestid'] = $requestid;

        $retdata['error_desc'] = "转账查询成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $req = new \RequestService($method);
        try {
            $req->sendRequest($data);
            $req->receviceResponse();
        } catch (\Exception $err) {
            $retdata['error_desc'] = $err->getMessage();
            $retdata['status'] = false;
            return $retdata;
        }
        $request = $req->getRequest();
        $response = $req->getResponseData();
        //验证请求的requestid和返回的requestid是否一致
        if ($response['code'] != 1) {
            $retdata['error_desc'] = "转账查询失败！错误码：[{$response['code']}]";
            $retdata['status'] = false;
            return $retdata;
        }
        $retdata['data'] = $response;
        return $retdata;
    }

    //查询某个字符串是否存在
    public function isInString($key, $str) {
        $array = explode($key, $str);
        return count($array) > 1;
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

    function get_platform_shopid() {
        return (isset($_SESSION['PLATFORM_SHOP_ID'])) ? $_SESSION['PLATFORM_SHOP_ID'] : $this->getConfig('shop', 'SHOP_ID');
    }
}