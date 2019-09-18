<?php
/**
 * EUnionZ PHP Framework alipay Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\alipay;


defined('APP_IN') or exit('Access Denied');

class Alipay extends \com\eunionz\core\Plugin {
    /**
     * 生成支付代码
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order_list, $payment, $front_url = '') {
        $front_url = $front_url ? $front_url : $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=alipay';
        $charset = 'utf-8';
        if ($this->loadPlugin('common')->is_mobile_browser()) {          // Wap
            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

            //            $gateway = 'http://wappaygw.alipay.com/service/rest.htm?';
            //            /************************功能开始**************************/

            //            //请求业务数据
            //            $req_data = '<direct_trade_create_req>'
            //                . '<subject>'. $payment['productname'] .'</subject>'
            //                . '<out_trade_no>'. $out_trade_no .'</out_trade_no>'
            //                . '<total_fee>'. $payment['amount'] .'</total_fee>'
            //                . '<seller_account_name>'. $payment['pay_config']['zfb']['alipay_account'] .'</seller_account_name>'
            //                . '<call_back_url>'. $front_url .'</call_back_url>'
            //                . '<notify_url>'.  $this->loadPlugin('common')->getDomain(). '/service/payment/respond.html?code=alipay'  .'</notify_url>'
            //                //. '<out_user>'. $order['consignee'] .'</out_user>'
            //                . '<merchant_url>'.  $this->loadPlugin('common')->getDomain() .'</merchant_url>'
            //                . '<pay_expire>3600</pay_expire>'
            //                . '</direct_trade_create_req>';
            //
            //            $parameter = array(
            //                'service'           => 'alipay.wap.trade.create.direct', //接口名称
            //                'format'            => 'xml', //请求参数格式
            //                'v'                 => '2.0', //接口版本号
            //                'partner'           => $payment['pay_config']['zfb']['alipay_partner'], //合作者身份ID
            //                'req_id'            => date('Ymdhis').rand(1000,9999), //请求号，唯一
            //                'sec_id'            => 'MD5', //签名方式
            //                'req_data'          => $req_data, //请求业务数据
            //                "_input_charset"	=> $charset
            //            );
            //
            //            ksort($parameter);
            //            reset($parameter);
            //
            //            $param = '';
            //            $sign  = '';
            //
            //            foreach ($parameter AS $key => $val)
            //            {
            //                $param .= "$key=" .urlencode($val). "&";
            //                $sign  .= "$key=$val&";
            //            }
            //
            //            $param = substr($param, 0, -1);
            //            $sign  = substr($sign, 0, -1). $payment['pay_config']['zfb']['key'];
            //
            //            /************************功能分割**************************/
            //
            //            //请求授权接口
            //            $result = $this->post_data($gateway, $param . '&sign='.md5($sign));
            //            $result = urldecode($result); //URL转码
            //            $result_array = explode('&', $result); //根据 & 符号拆分
            //            //重构数组
            //            $new_result_array = $temp_item = array();
            //            if(is_array($result_array)){
            //                foreach ($result_array as $vo){
            //                    $temp_item = explode('=', $vo, 2); //根据 & 符号拆分
            //                    $new_result_array[$temp_item[0]] = $temp_item[1];
            //                }
            //            }
            //
            //            /************************功能分割**************************/
            //
            //            $xml = simplexml_load_string($new_result_array['res_data']);
            //            $request_token = (array)$xml->request_token;
            //            //请求交易接口
            //            $parameter = array(
            //                'service'           => 'alipay.wap.auth.authAndExecute', //接口名称
            //                'format'            => 'xml', //请求参数格式
            //                'v'                 => $new_result_array['v'], //接口版本号
            //                'partner'           => $new_result_array['partner'], //合作者身份ID
            //                'sec_id'            => $new_result_array['sec_id'],
            //                'req_data'          => '<auth_and_execute_req><request_token>'. $request_token[0] .'</request_token></auth_and_execute_req>',
            //                'request_token'     => $request_token[0],
            //                '_input_charset'    => $charset
            //            );
            //
            //            ksort($parameter);
            //            reset($parameter);
            //
            //            $param = '';
            //            $sign  = '';
            //
            //            foreach ($parameter AS $key => $val)
            //            {
            //                $param .= "$key=" .urlencode($val). "&";
            //                $sign  .= "$key=$val&";
            //            }
            //
            //            $param = substr($param, 0, -1);
            //            $sign  = substr($sign, 0, -1). $payment['pay_config']['zfb']['key'];
            //
            //            //最新接口 end
            //            /************************生成支付链接**************************/
            //            $button = '<input type="button" class="pointer btn1" onclick="window.open(\''.$gateway.$param. '&sign='.md5($sign).'\')" value="立即支付" />';
            //            //echo $button;

            // 最新接口 start
            include_once('aop/AopClient.php');
            include_once 'aop/request/AlipayTradeWapPayRequest.php';
            $aop = new \AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';

            $aop->appId = $payment['pay_config']['zfb']['APP_ID'];
            $aop->rsaPrivateKey = $payment['pay_config']['zfb']['rsaPrivateKey'];
            $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];
            $aop->apiVersion = '1.0';
            $aop->postCharset = 'UTF-8';
            $aop->format = 'json';
            $aop->signType = 'RSA2';
            $request = new \AlipayTradeWapPayRequest();
            $request->setBizContent("{" . "\"body\":\"" . $out_trade_no . "\"," . "\"subject\":\"" . $payment['productname'] . "\"," . "\"out_trade_no\":\"" . $out_trade_no . "\"," . "\"timeout_express\":\"60m\"," . "\"total_amount\":" . $payment['amount'] . "," . "\"product_code\":\"QUICK_WAP_WAY\"" . "}");
            $request->setNotifyUrl($this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=alipay&client_type=wap');
            $request->setReturnUrl($front_url);
            $result = $aop->pageExecute($request, 'GET');
            $button['data'] = '<input type="button" class="pointer btn1" onclick="window.open(\'' . $result . '\')" value="立即支付" />';
            $button['datalink'] = $result;
            //最新接口 end

            return $button;

        } else {
            //pc

            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

            $real_method = $payment['pay_config']['zfb']['type'];


            switch ($real_method) {
                case '1':
                    $service = 'trade_create_by_buyer';
                    break;
                case '2':
                    $service = 'create_partner_trade_by_buyer';
                    break;
                case '3':
                    $service = 'create_direct_pay_by_user';
                    break;
            }

            $extend_param = 'isv^sh22';
            $parameter = array(
                'extend_param' => $extend_param, 'service' => $service, 'partner' => $payment['pay_config']['zfb']['alipay_partner'], //'partner'           => ALIPAY_ID,
                '_input_charset' => $charset,
                'notify_url' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=alipay', 'return_url' => $front_url, /* 业务参数 */
                'subject' => $payment['productname'], 'out_trade_no' => $out_trade_no, 'price' => $payment['amount'], 'quantity' => 1, 'payment_type' => 1, /* 物流参数 */
                'logistics_type' => 'EXPRESS', 'logistics_fee' => 0, 'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE', /* 买卖双方信息 */
                'seller_email' => $payment['pay_config']['zfb']['alipay_account'],
                'body' => $out_trade_no);
            ksort($parameter);
            reset($parameter);
            $param = '';
            $sign = '';
            foreach ($parameter AS $key => $val) {
                $param .= "$key=" . urlencode($val) . "&";
                $sign .= "$key=$val&";
            }
            $param = substr($param, 0, -1);
            $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
            //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;
            $button['data'] = '<input type="button" class="pointer pay_btn" onclick="window.open(\'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5\')" value="立即支付" />';
            $button['datalink'] = 'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5';
            return $button;
        }

    }

    /**
     * 分销订单生成
     * */
    public function distributor_get_code($order_list, $payment, $front_url = '') {
        $front_url = $front_url ? $front_url : $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distalipay';
        $charset = 'utf-8';
        if ($this->loadPlugin('common')->is_mobile_browser()) { // Wap
            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

            // 最新接口 start
            include_once('aop/AopClient.php');
            include_once 'aop/request/AlipayTradeWapPayRequest.php';
            $aop = new \AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';

            $aop->appId = $payment['pay_config']['zfb']['APP_ID'];
            $aop->rsaPrivateKey = $payment['pay_config']['zfb']['rsaPrivateKey'];
            $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];
            $aop->apiVersion = '1.0';
            $aop->postCharset = 'UTF-8';
            $aop->format = 'json';
            $aop->signType = 'RSA2';
            $request = new \AlipayTradeWapPayRequest();
            $request->setBizContent("{" . "\"body\":\"" . $out_trade_no . "\"," . "\"subject\":\"" . $payment['productname'] . "\"," . "\"out_trade_no\":\"" . $out_trade_no . "\"," . "\"timeout_express\":\"60m\"," . "\"total_amount\":" . $payment['amount'] . "," . "\"product_code\":\"QUICK_WAP_WAY\"" . "}");
            $request->setNotifyUrl($this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distalipay&client_type=wap');
            $request->setReturnUrl($front_url);
            $result = $aop->pageExecute($request, 'GET');
            $button['data'] = '<input type="button" class="pointer btn1" onclick="window.open(\'' . $result . '\')" value="立即支付" />';
            $button['datalink'] = $result;
            //最新接口 end
            return $button;
        } else {
            //pc
            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();
            $real_method = $payment['pay_config']['zfb']['type'];
            switch ($real_method) {
                case '1':
                    $service = 'trade_create_by_buyer';
                    break;
                case '2':
                    $service = 'create_partner_trade_by_buyer';
                    break;
                case '3':
                    $service = 'create_direct_pay_by_user';
                    break;
            }
            $extend_param = 'isv^sh22';
            $parameter = array(
                'extend_param' => $extend_param, 'service' => $service, 'partner' => $payment['pay_config']['zfb']['alipay_partner'], //'partner'           => ALIPAY_ID,
                '_input_charset' => $charset,
                'notify_url' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distalipay', 'return_url' => $front_url, /* 业务参数 */
                'subject' => $payment['productname'], 'out_trade_no' => $out_trade_no, 'price' => $payment['amount'], 'quantity' => 1, 'payment_type' => 1, /* 物流参数 */
                'logistics_type' => 'EXPRESS', 'logistics_fee' => 0, 'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE', /* 买卖双方信息 */
                'seller_email' => $payment['pay_config']['zfb']['alipay_account'],
                'body' => $out_trade_no);
            ksort($parameter);
            reset($parameter);
            $param = '';
            $sign = '';
            foreach ($parameter AS $key => $val) {
                $param .= "$key=" . urlencode($val) . "&";
                $sign .= "$key=$val&";
            }
            $param = substr($param, 0, -1);
            $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
            //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;
            $button['data'] = '<input type="button" class="pointer pay_btn" onclick="window.open(\'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5\')" value="立即支付" />';
            $button['datalink'] = 'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5';
            return $button;
        }
    }

    /**
     * 响应操作
     */
    function respond() {
        //        $alipay_ips=array('110.75.225.3');
        //        $ip= $this->loadPlugin('common')->get_ip();
        //        if(!in_array($ip,$alipay_ips)){
        //            //非支付宝的服务器ip，拒绝修改状态
        //            return false;
        //        }
        //        $this->loadCore('log')->write(APP_DEBUG,'alipay：ip'.$ip);
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());

        $this->loadCore('log')->write(APP_DEBUG, 'payment_input_REQUEST： ' . var_export($_REQUEST, true), 'alipay');

        if ($_REQUEST['client_type'] == 'wap') {
            // Wap
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 1', 'alipay');
            if (!empty($_POST)) {
                foreach ($_POST as $key => $data) {
                    $_GET[$key] = $data;
                }
            }

            $order_sn = $_POST['out_trade_no'];
            $this->loadService('order_info')->save_payment_notice_data('alipay', $order_sn, $_POST);

            $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap ' . $order_sn, 'alipay');


            include_once('aop/AopClient.php');
            $aop = new \AopClient();
            $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];
            $result = $aop->rsaCheckV1($_POST, $payment['pay_config']['zfb']['alipayrsaPublicKey'], 'RSA2');
            //            $this->loadCore('log')->write(APP_DEBUG,'alipay：Wap 21'.var_export($result,true));
            //            if (!$result){
            //                return false;
            //            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 22', 'alipay');
            /* 检查支付的金额是否相符 */
            $price = isset($_POST['total_amount']) ? $_POST['total_amount'] : 0;
            if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $price)) {
                return false;
            }

            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 23', 'alipay');
                $ret = $this->loadService('order_info')->pay_success($order_sn, $_POST['trade_no']);
                if ($ret['status']) {
                    $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } else {
                return false;
            }


        } else {
            //pc
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 1', 'alipay');
            if (!empty($_POST)) {
                foreach ($_POST as $key => $data) {
                    $_GET[$key] = $data;
                }
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay_get1： ' . print_r($_GET, true), 'alipay');


            $body = $_GET['body'];

            $seller_email = rawurldecode($_GET['seller_email']);

            $order_sn = $_GET['out_trade_no'];

            $this->loadService('order_info')->save_payment_notice_data('alipay', $order_sn, $_GET);

            // $order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
            /* 检查数字签名是否正确 */
            ksort($_GET);
            reset($_GET);

            $sign = '';
            foreach ($_GET AS $key => $val) {
                if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                    $sign .= "$key=$val&";
                }
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc ' . $order_sn, 'alipay');

            $price = isset($_GET['price']) ? $_GET['price'] : 0;


            $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
            //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;

            //            if (md5($sign) != $_GET['sign'])
            //            {
            //                return false;
            //            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21', 'alipay');


            /* 检查支付的金额是否相符 */
            if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $price)) {
                return false;
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');

            if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
                /* 改变订单状态 */
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23', 'alipay');
                $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
                if ($ret['status']) {
                    $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
                /* 改变订单状态 */
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24', 'alipay');
                $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
                if ($ret['status']) {
                    $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25', 'alipay');
                $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
                if ($ret['status']) {
                    $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } else {
                return false;
            }
        }

    }

    /**
     * 自动响应操作
     */
    function auto_respond($post) {
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $_GET = $post;
        $body = $_GET['body'];
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = $_GET['out_trade_no'];
        // $order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        $sign = '';
        foreach ($_GET AS $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                $sign .= "$key=$val&";
            }
        }

        $price = isset($_GET['price']) ? $_GET['price'] : 0;


        $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;

        //            if (md5($sign) != $_GET['sign'])
        //            {
        //                return false;
        //            }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21', 'alipay');


        /* 检查支付的金额是否相符 */
        if (!$this->loadService('order_info')->check_order_pay_money($order_sn, $price)) {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');

        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23', 'alipay');
            $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24', 'alipay');
            $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25', 'alipay');
            $ret = $this->loadService('order_info')->pay_success($order_sn, $_GET['trade_no']);
            if ($ret['status']) {
                $this->loadService('order_info')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } else {
            return false;
        }
    }

    /**
     * 响应操作
     */
    function distributor_respond() {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $platform_shopid);
        $this->loadCore('log')->write(APP_DEBUG, 'payment_input_REQUEST： ' . var_export($_REQUEST, true), 'alipay');
        if ($_REQUEST['client_type'] == 'wap') {
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 1', 'alipay');
            if (!empty($_GET) && empty($_POST) ) {
                foreach ($_GET as $key => $data) {
                    $_POST[$key] = $data;
                }
            }
            $order_sn = $_POST['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('alipay', $order_sn, $_POST);
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap ' . $order_sn, 'alipay');
            include_once('aop/AopClient.php');
            $aop = new \AopClient();
            $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];
            $result = $aop->rsaCheckV1($_POST, $payment['pay_config']['zfb']['alipayrsaPublicKey'], 'RSA2');
            $this->loadCore('log')->write(APP_DEBUG,'alipay：Wap 21'.var_export($result,true), 'alipay');
            /* 检查支付的金额是否相符 */
            $price = isset($_POST['total_amount']) ? $_POST['total_amount'] : 0;
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $price,$platform_shopid)) {
                return false;
            }
            if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 22:'.var_export($_POST,true), 'alipay');
                $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_POST['trade_no'],$platform_shopid);
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：Wap 23:'.var_export($ret,true), 'alipay');
                if ($ret['status']) {
                    $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } else {
                return false;
            }
        } else {//pc
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 1', 'alipay');
            if (!empty($_POST)) {
                foreach ($_POST as $key => $data) {
                    $_GET[$key] = $data;
                }
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay_get1： ' . print_r($_GET, true), 'alipay');
            $body = $_GET['body'];
            $seller_email = rawurldecode($_GET['seller_email']);
            $order_sn = $_GET['out_trade_no'];
            $this->loadService('distributor_buy_log')->save_payment_notice_data('alipay', $order_sn, $_GET);
            // $order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
            /* 检查数字签名是否正确 */
            ksort($_GET);
            reset($_GET);
            $sign = '';
            foreach ($_GET AS $key => $val) {
                if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                    $sign .= "$key=$val&";
                }
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc ' . $order_sn, 'alipay');
            $price = isset($_GET['price']) ? $_GET['price'] : 0;
            $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21'.$sign, 'alipay');
            /* 检查支付的金额是否相符 */
            if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $price)) {
                return false;
            }
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');
            if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
                /* 改变订单状态 */
                $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23'.var_export($ret,true), 'alipay');
                if ($ret['status']) {
                    $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
                /* 改变订单状态 */
                $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24'.var_export($ret,true), 'alipay');
                if ($ret['status']) {
                    $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
                $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25'.var_export($ret,true), 'alipay');
                if ($ret['status']) {
                    $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
                }
                return $ret['status'];
            } else {
                return false;
            }
        }

    }

    /**
     * 自动响应操作
     */
    function distributor_auto_respond($post) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $platform_shopid);
        $_GET = $post;
        $body = $_GET['body'];
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = $_GET['out_trade_no'];
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        $sign = '';
        foreach ($_GET AS $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                $sign .= "$key=$val&";
            }
        }
        $price = isset($_GET['price']) ? $_GET['price'] : 0;
        $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        // if (md5($sign) != $_GET['sign']){
        //   return false;
        // }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21', 'alipay');
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('distributor_buy_log')->check_order_pay_money($order_sn, $price)) {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');
        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23', 'alipay');
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24', 'alipay');
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25', 'alipay');
            $ret = $this->loadService('distributor_buy_log')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('distributor_buy_log')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } else {
            return false;
        }
    }

    //退款配置
    function alipay_config($payment) {
        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = $payment['pay_config']['zfb']['alipay_partner'];

        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];


        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = get_package_real_path() . APP_DS . 'plugin' . APP_DS . 'alipay' . APP_DS . 'alipay_lib' . APP_DS . 'cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';

        return $alipay_config;

    }

    /**
     * 生成退款代码
     * @param   array $order 订单信息
     * @param   array $refund 退款信息
     */
    function refund_old($order, $refund) {
        $payment = $this->loadService('shop_payment')->get_payment('alipay');

        $this->loadCore('log')->write(APP_ERROR, 'alipay: ' . print_r($refund, true), 'alipay');

        $alipay_config = $this->alipay_config($payment);

        require_once("alipay_lib/alipay_submit.class.php");

        /**************************请求参数**************************/


        //服务器异步通知页面路径 需http://格式的完整路径，不允许加?id=123这类自定义参数
        /* $arr = $this->loadService('shop_base')->get_domain_by_kid();
        if (!$arr) {
            $domain = 'http://' . $this->SHOP_ID . $this->getConfig('params', 'DEFAULT_MOBILE_DOMAIN_SUFFIX');
        } else {
            $domain = $arr['mobile_host'];
        } */
        $domain = $this->loadPlugin('common')->getDomain();
        $notify_url = $domain . '/service/payment/refund_notify/' . $refund['orefund_id'] . '.html?code=alipay';
        //卖家支付宝帐户
        $seller_email = $payment['pay_config']['zfb']['alipay_account'];
        //退款当天日期 必填，格式：年[4位]-月[2位]-日[2位] 小时[2位 24小时制]:分[2位]:秒[2位]，如：2007-10-01 13:13:13
        $refund_date = date('Y-m-d H:i:s');
        //批次号 必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001
        $batch_no = date('Ymd') . rand(1000, 99999);//str_pad($order['order_id'],12,'0',STR_PAD_LEFT);
        //退款笔数 必填，参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的数量999个）
        $batch_num = 1;

        //退款详细数据
        //计算手续费
        $refund_amount = $refund['orefund_amount'];
        $this->loadCore('log')->write(APP_DEBUG, '退款金额' . $refund_amount, 'alipay');
        $detail_data = $order['order_origQid'] . '^' . $refund_amount . '^' . "支付宝退款成功";//外部交易单号^退款金额^处理结果
        //必填，具体格式请参见接口技术文档
        //业务扩展参数--存储退款单号ID
        //$extend_param = $order['order_id'].'^'.$refund['orefund_id'];//订单id^退款单号
        $this->loadCore('log')->write(APP_DEBUG, '退款数据：' . $detail_data, 'alipay');

        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array("service" => "refund_fastpay_by_platform_pwd", "partner" => trim($payment['pay_config']['zfb']['alipay_partner']), "notify_url" => $notify_url, "seller_email" => $seller_email, "refund_date" => $refund_date, "batch_no" => $batch_no, "batch_num" => $batch_num, "detail_data" => $detail_data, "_input_charset" => trim(strtolower($alipay_config['input_charset'])));
        $this->loadCore('log')->write(APP_ERROR, "refund_parameter:" . print_r($parameter, true), 'alipay');

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");

        $retdata['status'] = true;
        $retdata['msg'] = "支付宝退款调用成功";
        $retdata['html'] = $html_text;

        return $retdata;
    }


    //退款响应操作
    function refund_notify_old($orefund_id) {
        $this->loadCore('log')->write(APP_ERROR, "refund7", 'alipay');

        $payment = $this->loadService('shop_payment')->get_payment('alipay');

        unset($_POST['code']);

        $log_data = serialize($_POST);
        $this->loadCore('log')->write(APP_ERROR, "refund8:" . $log_data, 'alipay');

        $alipay_config = $this->alipay_config($payment);
        $this->loadCore('log')->write(APP_ERROR, "refund9", 'alipay');

        require_once("alipay_lib/alipay_notify.class.php");
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        $this->loadCore('log')->write(APP_ERROR, "refund10:" . print_r($_POST, true), 'alipay');

        $this->loadCore('log')->write(APP_ERROR, "refund11:{$verify_result}", 'alipay');

        if ($verify_result) {
            //验证成功
            //批次号
            $this->loadCore('log')->write(APP_ERROR, "refund110:" . print_r($_POST, true), 'alipay');
            $batch_no = $_POST['batch_no'];

            //批量退款数据中转账成功的笔数

            $success_num = $_POST['success_num'];

            //批量退款数据中的详细信息
            //$_POST['result_details'] = '2015061800001000060055813807^0.01^789^';
            $result_details = $_POST['result_details'];
            $data = explode('^', $result_details);
            $this->loadCore('log')->write(APP_ERROR, "refund12:{$result_details}", 'alipay');

            //修改订单退款状态；
            $this->loadCore('log')->write(APP_ERROR, "refund120:", 'alipay');
            $info_where['where']['b.shop_id'] = $this->getConfig('shop', 'SHOP_ID');
            $info_where['where']['order_shop_id'] = $this->getConfig('shop', 'SHOP_ID');
            $info_where['where']['order_origQid'] = $data[0];
            /*根据条件搜索出对应的订单id,然后获取到日志文件中保存的留言日志 start*/
            $this->loadCore('log')->write(APP_ERROR, "refund121:" . print_r($info_where, true), 'alipay');

            $order_id = $this->loadService('order_info')->find_field($info_where, 'order_id', $this->getConfig('shop', 'SHOP_ID'));
            $this->loadCore('log')->write(APP_ERROR, "refund122:{$order_id}", 'alipay');
            $cache_dir = APP_RUNTIME_REAL_PATH . 'cache' . APP_DS . $order_id;
            if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                $remarket = file_get_contents($cache_dir . APP_DS . $order_id . '.log');
            } else {
                $remarket = "退款成功";
            }

            //修改退款单号的状态
            $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id}", 'alipay');
            //查询退款订单-商品信息(包含订单信息)
            $order_refund_info = $this->loadService('order_refunds')->findByID($orefund_id, $this->getConfig('shop', 'SHOP_ID'));

            if ($order_refund_info['orefund_state'] == 4) {//防止重复修改
                $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}已经退款成功，不再处理", 'alipay');
                echo "success";        //请不要修改或删除
                return true;
            }

            $orefund_way = 0;// 0:线上退款 1：线下退款
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($order_refund_info['order_sn'], $order_refund_info['orefund_amount'], $remarket, $order_refund_info['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund13:" . $ret['msg'], 'alipay');

            if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                @unlink($cache_dir . APP_DS . $order_id . '.log');
            }


            /*根据条件搜索出对应的订单id,然后获取到日志文件中保存的留言日志 end*/
            $this->loadCore('log')->write(APP_ERROR, "refund14:" . $this->C('db')->get_sql(), 'alipay');
            echo "success";        //请不要修改或删除

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            return true;

        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            $this->loadCore('log')->write(APP_ERROR, "返回参数校验错误", 'alipay');
            return false;
        }
    }

    /**
     * 生成退款代码
     * @param   array $order 订单信息
     * @param   array $refund 退款信息
     */
    function refund($order, $refund) {

        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $alipay_config = $this->alipay_config($payment);
        /* *
         * 功能：即时到账批量退款无密接口接入页
         * 版本：3.3
         * 修改日期：2012-07-23
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

         *************************注意*************************
         * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
         * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
         * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
         * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
         * 如果不想使用扩展功能请把扩展功能参数赋空值。
         */
        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = trim($payment['pay_config']['zfb']['alipay_partner']);
        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];
        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');
        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = PACKAGE_APP_ROOT . 'shop/package/plugin/alipay/lib/cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';

        require_once("lib/alipay_submit.class.php");
        /**************************请求参数**************************/
        //服务器异步通知页面路径
        $domain = $this->loadPlugin('common')->getDomain();
        $notify_url = $domain . '/service/payment/refund_notify/' . $refund['orefund_id'] . '.html?code=alipay';
        //需http://格式的完整路径，不允许加?id=123这类自定义参数
        //退款批次号 必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001
        $batch_no = date('Ymd') . mt_rand(100000, 999999);//str_pad($order['order_id'],12,'0',STR_PAD_LEFT);
        //必填，每进行一次即时到账批量退款，都需要提供一个批次号，必须保证唯一性
        //退款请求时间
        $refund_date = date('Y-m-d H:i:s');
        //必填，格式为：yyyy-MM-dd hh:mm:ss
        //退款总笔数
        $batch_num = 1;
        $refund_amount = $refund['orefund_amount'];
        $this->loadCore('log')->write(APP_DEBUG, '退款金额' . $refund_amount, 'alipay');
        $detail_data = $order['order_origQid'] . '^' . $refund_amount . '^' . "支付宝退款成功";//外部交易单号^退款金额^处理结果
        //必填，具体格式请参见接口技术文档
        //业务扩展参数--存储退款单号ID
        //$extend_param = $order['order_id'].'^'.$refund['orefund_id'];//订单id^退款单号
        $this->loadCore('log')->write(APP_DEBUG, '退款数据：' . $detail_data, 'alipay');

        /************************************************************/


        //构造要请求的参数数组，无需改动
        $parameter = array("service" => "refund_fastpay_by_platform_nopwd", "partner" => trim($payment['pay_config']['zfb']['alipay_partner']), "notify_url" => $notify_url, "batch_no" => $batch_no, "refund_date" => $refund_date, "batch_num" => $batch_num, "detail_data" => $detail_data, "_input_charset" => $alipay_config['input_charset'],);
        $this->loadCore('log')->write(APP_ERROR, "refund_parameter:" . print_r($parameter, true), 'alipay');

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);


        //解析XML
        //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
        $doc = new \DOMDocument();
        $doc->loadXML($html_text);
        //请在这里加上商户的业务逻辑程序代码
        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //解析XML
        if (!empty($doc->getElementsByTagName("alipay")->item(0)->nodeValue)) {
            $alipay = trim($doc->getElementsByTagName("alipay")->item(0)->nodeValue);
            if ($alipay == 'T') {
                //同步通知退款成功
                //业务数据处理
                //                $ret = $this->loadService('order_info')->do_cancel_by_order_sn($order);
                //                if($ret===false){
                //                    $retdata['msg'] = "退款申请失败！";
                //                    $retdata['status'] = false;
                //                    return $retdata;
                //                }

                $info_where['where']['b.shop_id'] = $this->getConfig('shop', 'SHOP_ID');
                $info_where['where']['order_shop_id'] = $this->getConfig('shop', 'SHOP_ID');
                $info_where['where']['order_origQid'] = $order['order_origQid'];
                /*根据条件搜索出对应的订单id,然后获取到日志文件中保存的留言日志 start*/
                $this->loadCore('log')->write(APP_ERROR, "refund121:" . print_r($info_where, true), 'alipay');

                $order_id = $this->loadService('order_info')->find_field($info_where, 'order_id', $this->getConfig('shop', 'SHOP_ID'));
                //修改订单退款状态；
                $this->loadCore('log')->write(APP_ERROR, "refund122:{$order_id}", 'alipay');
                $cache_dir = APP_RUNTIME_REAL_PATH . 'cache' . APP_DS . $order_id;
                if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                    $remarket = file_get_contents($cache_dir . APP_DS . $order_id . '.log');
                } else {
                    $remarket = "退款成功";
                }
                //修改退款单号的状态
                $orefund_id = $refund['orefund_id'];
                $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id}", 'alipay');
                //查询退款订单-商品信息(包含订单信息)
                $order_refund_info = $this->loadService('order_refunds')->findByID($orefund_id, $this->getConfig('shop', 'SHOP_ID'));

                if ($order_refund_info['orefund_state'] == 4) {//防止重复修改
                    $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}已经退款成功，不再处理", 'alipay');
                    $retdata['msg'] = "退款成功！";
                    $retdata['status'] = true;
                    return $retdata;
                }
                $orefund_way = 0;// 0:线上退款 1：线下退款
                //直接调用退款成功处理函数
                $ret = $this->loadService('order_info')->op_refund_by_order_sn($order_refund_info['order_sn'], $order_refund_info['orefund_amount'], $remarket, $order_refund_info['orefund_id'], $orefund_way);
                if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                    @unlink($cache_dir . APP_DS . $order_id . '.log');
                }
                $retdata['msg'] = "退款成功！";
                $retdata['status'] = true;
                return $retdata;
            }
        }
        $retdata['msg'] = "支付宝退款调用成功！";
        $retdata['status'] = true;
        //——退款失败
        return $retdata;
    }


    //退款响应操作
    function refund_notify($orefund_id) {
        $this->loadCore('log')->write(APP_ERROR, "refund7", 'alipay');

        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $alipay_config = $this->alipay_config($payment);
        $alipay_config['partner'] = trim($payment['pay_config']['zfb']['alipay_partner']);
        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];
        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');
        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = PACKAGE_APP_ROOT . 'shop/package/plugin/alipay/lib/cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';
        require_once("lib/alipay_submit.class.php");
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        $this->loadCore('log')->write(APP_ERROR, "refund10:" . print_r($_POST, true), 'alipay');

        $this->loadCore('log')->write(APP_ERROR, "refund11:{$verify_result}", 'alipay');

        if ($verify_result) {
            //验证成功
            //批次号
            $this->loadCore('log')->write(APP_ERROR, "refund110:" . print_r($_POST, true), 'alipay');
            $batch_no = $_POST['batch_no'];

            //批量退款数据中转账成功的笔数

            $success_num = $_POST['success_num'];

            //批量退款数据中的详细信息
            //$_POST['result_details'] = '2015061800001000060055813807^0.01^789^';
            $result_details = $_POST['result_details'];
            $data = explode('^', $result_details);
            $this->loadCore('log')->write(APP_ERROR, "refund12:{$result_details}", 'alipay');

            //修改订单退款状态；
            $this->loadCore('log')->write(APP_ERROR, "refund120:", 'alipay');
            $info_where['where']['b.shop_id'] = $this->getConfig('shop', 'SHOP_ID');
            $info_where['where']['order_shop_id'] = $this->getConfig('shop', 'SHOP_ID');
            $info_where['where']['order_origQid'] = $data[0];
            /*根据条件搜索出对应的订单id,然后获取到日志文件中保存的留言日志 start*/
            $this->loadCore('log')->write(APP_ERROR, "refund121:" . print_r($info_where, true), 'alipay');

            $order_id = $this->loadService('order_info')->find_field($info_where, 'order_id', $this->getConfig('shop', 'SHOP_ID'));
            $this->loadCore('log')->write(APP_ERROR, "refund122:{$order_id}", 'alipay');
            $cache_dir = APP_RUNTIME_REAL_PATH . 'cache' . APP_DS . $order_id;
            if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                $remarket = file_get_contents($cache_dir . APP_DS . $order_id . '.log');
            } else {
                $remarket = "退款成功";
            }

            //修改退款单号的状态
            $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id}", 'alipay');
            //查询退款订单-商品信息(包含订单信息)
            $order_refund_info = $this->loadService('order_refunds')->findByID($orefund_id, $this->getConfig('shop', 'SHOP_ID'));

            if ($order_refund_info['orefund_state'] == 4) {//防止重复修改
                $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}已经退款成功，不再处理", 'alipay');
                echo "success";        //请不要修改或删除
                return true;
            }

            $orefund_way = 0;// 0:线上退款 1：线下退款  
            //直接调用退款成功处理函数
            $ret = $this->loadService('order_info')->op_refund_by_order_sn($order_refund_info['order_sn'], $order_refund_info['orefund_amount'], $remarket, $order_refund_info['orefund_id'], $orefund_way);
            $this->loadCore('log')->write(APP_ERROR, "refund13:" . $ret['msg'], 'alipay');

            if (file_exists($cache_dir . APP_DS . $order_id . '.log')) {
                @unlink($cache_dir . APP_DS . $order_id . '.log');
            }


            /*根据条件搜索出对应的订单id,然后获取到日志文件中保存的留言日志 end*/
            $this->loadCore('log')->write(APP_ERROR, "refund14:" . $this->C('db')->get_sql(), 'alipay');
            echo "success";        //请不要修改或删除

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            return true;

        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            $this->loadCore('log')->write(APP_ERROR, "返回参数校验错误", 'alipay');
            return false;
        }
    }


    function post_data($url, $curlPost) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }


    /**
     * 转帐查询接口
     * @param $out_biz_no 查询外部订单号
     * @return  true -- 转帐成功   false--转帐失败
     */
    public function transfer_query($out_biz_no) {
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());

        include_once('aop/AopClient.php');
        include_once 'aop/request/AlipayFundTransOrderQueryRequest.php';

        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $payment['pay_config']['zfb']['APP_ID'];
        $aop->rsaPrivateKey = $payment['pay_config']['zfb']['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];

        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'UTF-8';
        $aop->format = 'json';
        $request = new \AlipayFundTransOrderQueryRequest();
        $request->setBizContent("{" . "\"out_biz_no\":\"" . $out_biz_no . "\"," . "\"order_id\":\"\"" . "}");
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if (!empty($resultCode) && $resultCode == 10000) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 转帐到支付宝帐号
     * @param $out_biz_no 外部订单号
     * @param $transfer_money  转帐金额 0.01
     * @param $to_account  收款方支付宝帐号
     * @param $to_realname  收款方支付宝实名用户真实姓名
     * @param $transfer_remark  转帐备注
     * @param $error_msg  失败时返回的错误信息
     * @return bool  true -- 转帐成功  false -- 转帐失败
     */
    public function transfer($out_biz_no, $transfer_money, $to_account, $to_realname, $transfer_remark, & $error_msg) {
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());

        include_once('aop/AopClient.php');
        include_once 'aop/request/AlipayFundTransToaccountTransferRequest.php';

        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $payment['pay_config']['zfb']['APP_ID'];
        $aop->rsaPrivateKey = $payment['pay_config']['zfb']['rsaPrivateKey'];
        $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];

        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'UTF-8';
        $aop->format = 'json';
        $request = new \AlipayFundTransToaccountTransferRequest();
        $out = $this->random(3, 1);
        $time = time();
        $out = $out . $time;
        $request->setBizContent("{" . "\"out_biz_no\":\"" . $out_biz_no . "\"," . "\"payee_type\":\"ALIPAY_LOGONID\"," . "\"payee_account\":\"" . $to_account . "\"," . "\"amount\":\"" . $transfer_money . "\"," . "\"payer_show_name\":\"转帐到" . $to_account . "\"," . "\"payee_real_name\":\"" . $to_realname . "\"," . "\"remark\":\"" . $transfer_remark . "\"" . "}");
        try {
            $result = $aop->execute($request);
            $this->loadCore('log')->write(APP_DEBUG, print_r($result, true), 'trans');
            $this->loadCore('log')->write(APP_DEBUG, $to_account, 'trans');
            $this->loadCore('log')->write(APP_DEBUG, $to_realname, 'trans');
            $respond = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$respond->code;
            if (!empty($resultCode) && $resultCode == 10000) {
                return true;
            } else {
                $error_msg = empty($result->$respond->sub_msg) ? "未知原因，转帐失败" : $result->$respond->sub_msg;
                return false;
            }
        } catch (\Exception $err) {
            $error_msg = $err->getMessage();
            return false;
        }
    }


    function random($length, $type = 0, $hash = '') {
        if ($type == 0) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        } else if ($type == 1) {
            $chars = '123456789';
        } else if ($type == 2) {
            $chars = 'abcdefghijklmnopqrstuvwxyz';
        }
        $max = strlen($chars) - 1;
        mt_srand((double)microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
        return $hash;
    }

    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_order($order) {
        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $alipay_config = $this->alipay_config($payment);
        /* *
         * 功能：即时到账批量退款无密接口接入页
         * 版本：3.3
         * 修改日期：2012-07-23
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

         *************************注意*************************
         * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
         * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
         * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
         * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
         * 如果不想使用扩展功能请把扩展功能参数赋空值。
         */


        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = trim($payment['pay_config']['zfb']['alipay_partner']);

        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = PACKAGE_APP_ROOT . 'shop/package/plugin/alipay/lib/cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';


        require_once("lib/alipay_submit.class.php");


        /**************************请求参数**************************/

        //服务器异步通知页面路径
        $domain = $this->loadPlugin('common')->getDomain();
        $notify_url = $domain . '/service/payment/refund_notify/' . $order['order_id'] . '_0.html?code=alipay';
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //退款批次号 必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001
        $batch_no = date('Ymd') . mt_rand(100000, 999999);//str_pad($order['order_id'],12,'0',STR_PAD_LEFT);
        //必填，每进行一次即时到账批量退款，都需要提供一个批次号，必须保证唯一性

        //退款请求时间
        $refund_date = date('Y-m-d H:i:s');
        //必填，格式为：yyyy-MM-dd hh:mm:ss

        //退款总笔数
        $batch_num = 1;
        //必填，即参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的最大数量999个）

        //单笔数据集
        //退款详细数据
        //计算手续费
        $refund_amount = $order['order_money_paid'];
        $this->loadCore('log')->write(APP_DEBUG, '退款金额' . $refund_amount, 'alipay');
        $detail_data = $order['order_origQid'] . '^' . $refund_amount . '^' . "支付宝退款成功";//外部交易单号^退款金额^处理结果
        //必填，具体格式请参见接口技术文档
        //业务扩展参数--存储退款单号ID
        //$extend_param = $order['order_id'].'^'.$refund['orefund_id'];//订单id^退款单号
        $this->loadCore('log')->write(APP_DEBUG, '退款数据：' . $detail_data, 'alipay');

        /************************************************************/

        //构造要请求的参数数组，无需改动
        //构造要请求的参数数组，无需改动
        $parameter = array("service" => "refund_fastpay_by_platform_nopwd", "partner" => trim($payment['pay_config']['zfb']['alipay_partner']), "notify_url" => $notify_url, "batch_no" => $batch_no, "refund_date" => $refund_date, "batch_num" => $batch_num, "detail_data" => $detail_data, "_input_charset" => $alipay_config['input_charset'],);
        $this->loadCore('log')->write(APP_ERROR, "refund_parameter:" . print_r($parameter, true), 'alipay');

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);

        //解析XML
        //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
        $doc = new \DOMDocument();
        $doc->loadXML($html_text);

        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //解析XML
        if (!empty($doc->getElementsByTagName("alipay")->item(0)->nodeValue)) {
            $alipay = trim($doc->getElementsByTagName("alipay")->item(0)->nodeValue);
            if ($alipay == 'T') {
                //同步通知退款成功
                //业务数据处理
                $ret = $this->loadService('order_info')->do_cancel_by_order_sn($order);
                if ($ret === false) {
                    $retdata['msg'] = "退款申请失败！";
                    $retdata['status'] = false;
                    return $retdata;
                }
                return $retdata;
            }
        }
        $retdata['msg'] = "退款申请失败！";
        $retdata['status'] = false;
        //——退款失败
        return $retdata;
    }

    //退款响应操作
    function cancel_order_notify($order_id) {
        $this->loadCore('log')->write(APP_ERROR, "refund7", 'alipay');

        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $alipay_config = $this->alipay_config($payment);
        /* *
         * 功能：即时到账批量退款无密接口接入页
         * 版本：3.3
         * 修改日期：2012-07-23
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

         *************************注意*************************
         * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
         * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
         * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
         * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
         * 如果不想使用扩展功能请把扩展功能参数赋空值。
         */


        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = trim($payment['pay_config']['zfb']['alipay_partner']);

        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = PACKAGE_APP_ROOT . 'shop/package/plugin/alipay/lib/cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';


        require_once("lib/alipay_submit.class.php");

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            $order_info = $this->loadService('order_info')->findByID($order_id);
            if (!$order_info) {
                //不存在的取消订单失败
                echo "fail";
                exit;
            }
            //业务数据处理
            $ret = $this->loadService('order_info')->do_cancel_by_order_sn($order_info);
            if ($ret === false) {
                //取消订单失败
                echo "fail";
                exit;
            }
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表

            //退款批次号
            $batch_no = $_POST['batch_no'];
            //必填


            //退款成功总数
            $success_num = $_POST['success_num'];
            //必填，0<= success_num<= batch_num


            //处理结果详情
            $result_details = $_POST['result_details'];
            //必填，详见“6.3 处理结果详情说明”


            //解冻结果明细
            $unfreezed_deta = $_POST['unfreezed_deta'];
            //格式：解冻结订单号^冻结订单号^解冻结金额^交易号^处理时间^状态^描述码


            //判断是否在商户网站中已经做过了这次通知返回的处理
            //如果没有做过处理，那么执行商户的业务程序
            //如果有做过处理，那么不执行商户的业务程序

            echo "success";        //请不要修改或删除

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    function get_platform_shopid() {
        return (isset($_SESSION['PLATFORM_SHOP_ID'])) ? $_SESSION['PLATFORM_SHOP_ID'] : $this->getConfig('shop', 'SHOP_ID');
    }

    /**
     * 店铺等级支付
     * */
    public function apply_get_code($order_list, $payment, $front_url = '') {
        $front_url = $front_url ? $front_url : $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=applyalipay';
        $charset = 'utf-8';
        if ($this->loadPlugin('common')->is_mobile_browser()) { // Wap
            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();

            // 最新接口 start
            include_once('aop/AopClient.php');
            include_once 'aop/request/AlipayTradeWapPayRequest.php';
            $aop = new \AopClient();
            $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';

            $aop->appId = $payment['pay_config']['zfb']['APP_ID'];
            $aop->rsaPrivateKey = $payment['pay_config']['zfb']['rsaPrivateKey'];
            $aop->alipayrsaPublicKey = $payment['pay_config']['zfb']['alipayrsaPublicKey'];
            $aop->apiVersion = '1.0';
            $aop->postCharset = 'UTF-8';
            $aop->format = 'json';
            $aop->signType = 'RSA2';
            $request = new \AlipayTradeWapPayRequest();
            $request->setBizContent("{" . "\"body\":\"" . $out_trade_no . "\"," . "\"subject\":\"" . $payment['productname'] . "\"," . "\"out_trade_no\":\"" . $out_trade_no . "\"," . "\"timeout_express\":\"60m\"," . "\"total_amount\":" . $payment['amount'] . "," . "\"product_code\":\"QUICK_WAP_WAY\"" . "}");
            $request->setNotifyUrl($this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=applyalipay&client_type=wap');
            $request->setReturnUrl($front_url);
            $result = $aop->pageExecute($request, 'GET');
            $button['data'] = '<input type="button" class="pointer btn1" onclick="window.open(\'' . $result . '\')" value="立即支付" />';
            $button['datalink'] = $result;
            //最新接口 end
            return $button;
        } else {
            //pc
            //添加支付单号记录
            $payment['payproducttype'] = 'alipay';
            $payment['externalid'] = '';
            $payment['requestid'] = $this->loadService('order_pay_base')->distributor_get_opay_no($order_list);//外部订单号
            $out_trade_no = $payment['requestid'];
            $ret = $this->loadService('order_pay_base')->distributor_create_pay_no($order_list, $payment);
            if ($ret === false) {
                return false;
            }
            $opay_id = $ret;
            $front_url .= (strpos($front_url, '?') ? '&' : '?') . "requestid={$out_trade_no}&amount={$payment['amount']}&pay_time=" . time();
            $real_method = $payment['pay_config']['zfb']['type'];
            switch ($real_method) {
                case '1':
                    $service = 'trade_create_by_buyer';
                    break;
                case '2':
                    $service = 'create_partner_trade_by_buyer';
                    break;
                case '3':
                    $service = 'create_direct_pay_by_user';
                    break;
            }
            $extend_param = 'isv^sh22';
            $parameter = array(
                'extend_param' => $extend_param, 'service' => $service, 'partner' => $payment['pay_config']['zfb']['alipay_partner'], //'partner'           => ALIPAY_ID,
                '_input_charset' => $charset,
                'notify_url' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=applyalipay', 'return_url' => $front_url, /* 业务参数 */
                'subject' => $payment['productname'], 'out_trade_no' => $out_trade_no, 'price' => $payment['amount'], 'quantity' => 1, 'payment_type' => 1, /* 物流参数 */
                'logistics_type' => 'EXPRESS', 'logistics_fee' => 0, 'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE', /* 买卖双方信息 */
                'seller_email' => $payment['pay_config']['zfb']['alipay_account'],
                'body' => $out_trade_no);
            ksort($parameter);
            reset($parameter);
            $param = '';
            $sign = '';
            foreach ($parameter AS $key => $val) {
                $param .= "$key=" . urlencode($val) . "&";
                $sign .= "$key=$val&";
            }
            $param = substr($param, 0, -1);
            $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
            //$sign  = substr($sign, 0, -1). ALIPAY_AUTH;
            $button['data'] = '<input type="button" class="pointer pay_btn" onclick="window.open(\'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5\')" value="立即支付" />';
            $button['datalink'] = 'https://mapi.alipay.com/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5';
            return $button;
        }
    }

    /**
     * 响应操作
     */
    function apply_respond()
    {
        $platform_shopid = $this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $platform_shopid);
        $this->loadCore('log')->write(APP_DEBUG, 'payment_input_REQUEST： ' . var_export($_REQUEST, true), 'alipay');
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 1', 'alipay');
        if (!empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay_get1： ' . print_r($_GET, true), 'alipay');
        $body = $_GET['body'];
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = $_GET['out_trade_no'];
        $this->loadService('shop_grade_apply')->save_payment_notice_data('alipay', $order_sn, $_GET);
        // $order_sn = str_replace($_GET['subject'], '', $_GET['out_trade_no']);
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        $sign = '';
        foreach ($_GET AS $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                $sign .= "$key=$val&";
            }
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc ' . $order_sn, 'alipay');
        $price = isset($_GET['total_fee']) ? $_GET['total_fee'] : 0;
        $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21' . $sign, 'alipay');
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('shop_grade_apply')->check_apply_pay_money($order_sn, $price)) {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');
        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
            /* 改变订单状态 */
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'], $platform_shopid);
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23' . var_export($ret, true), 'alipay');
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
            /* 改变订单状态 */
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'], $platform_shopid);
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24' . var_export($ret, true), 'alipay');
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            /* 改变订单状态 */
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'], $platform_shopid);
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25' . var_export($ret, true), 'alipay');
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } else {
            return false;
        }
    }

    /**
     * 自动响应操作
     */
    function apply_auto_respond($post) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('alipay', $platform_shopid);
        $_GET = $post;
        $body = $_GET['body'];
        $seller_email = rawurldecode($_GET['seller_email']);
        $order_sn = $_GET['out_trade_no'];
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        $sign = '';
        foreach ($_GET AS $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                $sign .= "$key=$val&";
            }
        }
        $price = isset($_GET['total_fee']) ? $_GET['total_fee'] : 0;
        $sign = substr($sign, 0, -1) . $payment['pay_config']['zfb']['key'];
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        // if (md5($sign) != $_GET['sign']){
        //   return false;
        // }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 21', 'alipay');
        /* 检查支付的金额是否相符 */
        if (!$this->loadService('shop_grade_apply')->check_apply_pay_money($order_sn, $price)) {
            return false;
        }
        $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 22 ' . $_GET['trade_status'], 'alipay');
        if ($_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 23', 'alipay');
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_FINISHED') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 24', 'alipay');
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } elseif ($_GET['trade_status'] == 'TRADE_SUCCESS') {
            /* 改变订单状态 */
            $this->loadCore('log')->write(APP_DEBUG, 'alipay：pc 25', 'alipay');
            $ret = $this->loadService('shop_grade_apply')->pay_success($order_sn, $_GET['trade_no'],$platform_shopid);
            if ($ret['status']) {
                $this->loadService('shop_grade_apply')->delete_payment_notice_data('alipay', $order_sn);
            }
            return $ret['status'];
        } else {
            return false;
        }
    }

    /**
     * 取消订单，退款功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @return bool true--成功  false--失败
     */
    public function refund_cancel_apply($order) {
        $retdata['msg'] = "退款申请成功！";
        $retdata['data'] = null;
        $retdata['status'] = true;

        $payment = $this->loadService('shop_payment')->get_payment('alipay', $this->get_platform_shopid());
        $alipay_config = $this->alipay_config($payment);
        /* *
         * 功能：即时到账批量退款无密接口接入页
         * 版本：3.3
         * 修改日期：2012-07-23
         * 说明：
         * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
         * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。

         *************************注意*************************
         * 如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
         * 1、商户服务中心（https://b.alipay.com/support/helperApply.htm?action=consultationApply），提交申请集成协助，我们会有专业的技术工程师主动联系您协助解决
         * 2、商户帮助中心（http://help.alipay.com/support/232511-16307/0-16307.htm?sh=Y&info_type=9）
         * 3、支付宝论坛（http://club.alipay.com/read-htm-tid-8681712.html）
         * 如果不想使用扩展功能请把扩展功能参数赋空值。
         */


        //↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        //合作身份者id，以2088开头的16位纯数字
        $alipay_config['partner'] = trim($payment['pay_config']['zfb']['alipay_partner']);

        //安全检验码，以数字和字母组成的32位字符
        $alipay_config['key'] = $payment['pay_config']['zfb']['key'];

        //↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        //签名方式 不需修改
        $alipay_config['sign_type'] = strtoupper('MD5');

        //字符编码格式 目前支持 gbk 或 utf-8
        $alipay_config['input_charset'] = strtolower('utf-8');

        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $alipay_config['cacert'] = PACKAGE_APP_ROOT . 'shop/package/plugin/alipay/lib/cacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $alipay_config['transport'] = 'http';


        require_once("lib/alipay_submit.class.php");


        /**************************请求参数**************************/

        //服务器异步通知页面路径
        $domain = $this->loadPlugin('common')->getDomain();
        $notify_url = $domain . '/service/payment/refund_notify/' . $order['apply_id'] . '_0.html?code=alipay';
        //需http://格式的完整路径，不允许加?id=123这类自定义参数

        //退款批次号 必填，格式：当天日期[8位]+序列号[3至24位]，如：201008010000001
        $batch_no = date('Ymd') . mt_rand(100000, 999999);//str_pad($order['order_id'],12,'0',STR_PAD_LEFT);
        //必填，每进行一次即时到账批量退款，都需要提供一个批次号，必须保证唯一性

        //退款请求时间
        $refund_date = date('Y-m-d H:i:s');
        //必填，格式为：yyyy-MM-dd hh:mm:ss

        //退款总笔数
        $batch_num = 1;
        //必填，即参数detail_data的值中，“#”字符出现的数量加1，最大支持1000笔（即“#”字符出现的最大数量999个）

        //单笔数据集
        //退款详细数据
        //计算手续费
        $refund_amount = $order['apply_pay_fee'];
        $this->loadCore('log')->write(APP_DEBUG, '退款金额' . $refund_amount, 'alipay');
        $detail_data = $order['apply_origQid'] . '^' . $refund_amount . '^' . "支付宝退款成功";//外部交易单号^退款金额^处理结果
        //必填，具体格式请参见接口技术文档
        //业务扩展参数--存储退款单号ID
        //$extend_param = $order['order_id'].'^'.$refund['orefund_id'];//订单id^退款单号
        $this->loadCore('log')->write(APP_DEBUG, '退款数据：' . $detail_data, 'alipay');

        /************************************************************/

        //构造要请求的参数数组，无需改动
        //构造要请求的参数数组，无需改动
        $parameter = array("service" => "refund_fastpay_by_platform_nopwd", "partner" => trim($payment['pay_config']['zfb']['alipay_partner']), "notify_url" => $notify_url, "batch_no" => $batch_no, "refund_date" => $refund_date, "batch_num" => $batch_num, "detail_data" => $detail_data, "_input_charset" => $alipay_config['input_charset'],);
        $this->loadCore('log')->write(APP_ERROR, "refund_parameter:" . print_r($parameter, true), 'alipay');

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);

        //解析XML
        //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
        $doc = new \DOMDocument();
        $doc->loadXML($html_text);
        $this->loadCore('log')->write(APP_ERROR, "html_text:" . $html_text, 'alipay');

        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //解析XML
        if (!empty($doc->getElementsByTagName("alipay")->item(0)->nodeValue)) {
            $alipay = trim($doc->getElementsByTagName("alipay")->item(0)->nodeValue);
            if ($alipay == 'T') {
                //同步通知退款成功
                //业务数据处理
                $ret = $this->loadService('shop_grade_apply')->do_cancel_apply($order);
                if ($ret === false) {
                    $retdata['msg'] = "退款申请失败！";
                    $retdata['status'] = false;
                    return $retdata;
                }
                return $retdata;
            }
        }
        $retdata['msg'] = "退款申请失败1！";
        $retdata['status'] = false;
        //——退款失败
        return $retdata;
    }
}