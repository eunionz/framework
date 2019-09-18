<?php
/**
 * EUnionZ PHP Framework Pinyin Plugin class
 * 银联在线支付/代付/退货/查询接口插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\upop;


defined('APP_IN') or exit('Access Denied');


class Upop extends \com\eunionz\core\Plugin {
    /**
     * 生成支付代码
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */

    static $api_url = array(0 => array('front_pay_url' => 'http://58.246.226.99/UpopWeb/api/Pay.action', 'back_pay_url' => 'http://58.246.226.99/UpopWeb/api/BSPay.action', 'query_url' => 'http://58.246.226.99/UpopWeb/api/Query.action',), 1 => array('front_pay_url' => 'http://www.epay.lxdns.com/UpopWeb/api/Pay.action', 'back_pay_url' => 'http://www.epay.lxdns.com/UpopWeb/api/BSPay.action', 'query_url' => 'http://www.epay.lxdns.com/UpopWeb/api/Query.action',), 2 => array('front_pay_url' => 'https://unionpaysecure.com/api/Pay.action', 'back_pay_url' => 'https://besvr.unionpaysecure.com/api/BSPay.action', 'query_url' => 'https://query.unionpaysecure.com/api/Query.action',),);

    public function get_code($order, $payment, $front_url = '') {
        $front_url = $front_url ? $front_url : $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        //$front_url=str_ireplace('/#/','/',$front_url);

        $payment = $this->loadService('shop_payment')->get_payment('upop');
        // 初始化变量
        //$upop_evn		= $payment['upop_evn'];		// 环境  2--生产环境
        $upop_evn = 2;
        header('Content-type:text/html;charset=utf-8');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        /**
         * 消费交易-前台
         */
        $this->loadCore('Log')->write(APP_INFO, \SDKConfig::$SDK_SIGN_CERT_PATH, 'upop123456');


        $this->loadCore('Log')->write(APP_INFO, '============处理前台请求开始===============');

        // 初始化日志
        $params = array('version' => '5.0.0',                //版本号
            'encoding' => 'utf-8',                //编码方式
            'certId' => getSignCertId(),            //证书ID
            'txnType' => '01',                //交易类型
            'txnSubType' => '01',                //交易子类
            'bizType' => '000201',                //业务类型
            'frontUrl' => $front_url,        //前台通知地址
            'backUrl' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop',        //后台通知地址
            'signMethod' => '01',        //签名方法
            'channelType' => '08',        //渠道类型，07-PC，08-手机
            'accessType' => '0',        //接入类型
            'merId' => $payment['pay_config']['wy']['merId'],                //商户代码，请改自己的测试商户号
            'orderId' => $order['order_sn'],    //商户订单号 AN8..32 最长32位 Anx..y 长度为x-y字节的变长字母或数字字符
            'txnTime' => date('YmdHis'),    //订单发送时间
            'txnAmt' => $order['order_order_amount'] * 100,        //交易金额，单位分
            'currencyCode' => '156',    //交易币种
            'defaultPayType' => '0001',    //默认支付方式
            //'orderDesc' => '订单描述',  //订单描述，网关支付和wap支付暂时不起作用
            'reqReserved' => $order['order_sn'], //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
        );


        // 签名
        sign($params);

        // 前台请求地址
        $front_uri = \SDKConfig::$SDK_FRONT_TRANS_URL;
        $this->loadCore('Log')->write(APP_INFO, "前台请求地址为>" . $front_uri);
        // 构造 自动提交的表单
        $html_form = create_html($params, $front_uri);

        $this->loadCore('Log')->write(APP_INFO, print_r($params, true), 'upop');
        $this->loadCore('Log')->write(APP_INFO, $html_form);
        $this->loadCore('Log')->write(APP_INFO, "-------前台交易自动提交表单>--end-------");
        $this->loadCore('Log')->write(APP_INFO, "============处理前台请求 结束===========");

        return $html_form;
    }

    /**
     * 分销订单生成
     * */
    public function distributor_get_code($order, $payment, $front_url = '') {
        $front_url = $front_url ? $front_url : $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        $payment = $this->loadService('shop_payment')->get_payment('upop');
        // 初始化变量
        //$upop_evn		= $payment['upop_evn'];		// 环境  2--生产环境
        $upop_evn = 2;
        header('Content-type:text/html;charset=utf-8');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        // 初始化日志
        $params = array('version' => '5.0.0',                //版本号
            'encoding' => 'utf-8',                //编码方式
            'certId' => getSignCertId(),            //证书ID
            'txnType' => '01',                //交易类型
            'txnSubType' => '01',                //交易子类
            'bizType' => '000201',                //业务类型
            'frontUrl' => $front_url,        //前台通知地址
            'backUrl' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop',        //后台通知地址
            'signMethod' => '01',        //签名方法
            'channelType' => '08',        //渠道类型，07-PC，08-手机
            'accessType' => '0',        //接入类型
            'merId' => $payment['pay_config']['wy']['merId'],                //商户代码，请改自己的测试商户号
            'orderId' => $order['order_sn'],    //商户订单号 AN8..32 最长32位 Anx..y 长度为x-y字节的变长字母或数字字符
            'txnTime' => date('YmdHis'),    //订单发送时间
            'txnAmt' => $order['order_order_amount'] * 100,        //交易金额，单位分
            'currencyCode' => '156',    //交易币种
            'defaultPayType' => '0001',    //默认支付方式
            //'orderDesc' => '订单描述',  //订单描述，网关支付和wap支付暂时不起作用
            'reqReserved' => $order['order_sn'], //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
        );
        // 签名
        sign($params);
        // 前台请求地址
        $front_uri = \SDKConfig::$SDK_FRONT_TRANS_URL;
        // 构造 自动提交的表单
        $html_form = create_html($params, $front_uri);
        $this->loadCore('Log')->write(APP_INFO, print_r($params, true), 'upop');
        $this->loadCore('Log')->write(APP_INFO, $html_form);
        return $html_form;
    }

    /**
     * 响应操作
     */
    function respond() {

        $payment = $this->loadService('shop_payment')->get_payment('upop');

        /**
         * Created by PhpStorm.
         * User: Administrator
         * Date: 2015/6/8
         * Time: 16:48
         */
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        if ($_POST) {
            $data = $_POST;
        } else {
            $data = $this->input();
            if (is_string($data)) {
                $data = coverStringToArray($data);
            }
        }

        $order_sn = $data['orderId'];
        $this->loadService('order_info')->save_payment_notice_data('upop', $order_sn, $data);

        $rs = verify($data);

        //验签成功
        if ($data['respCode'] == '00') {


            // 检查商户账号是否一致。
            if ($payment['pay_config']['wy']['merId'] != $data['merId']) {
                return false;
            }
            $action_note = $data['respCode'] . ':' . $data['respMsg'] . $GLOBALS['_LANG']['upop_txn_id'] . ':' . $data['queryId'];

            // 完成订单。
            $this->loadService('order_info')->order_paid($order_sn, $data['queryId']);
            $this->loadService('order_info')->delete_payment_notice_data('upop', $order_sn);
            //告诉用户交易完成
            return true;
        } else {
            return false;
        }

    }

    /**
     * 自动响应操作
     */
    function auto_respond($post) {

        $payment = $this->loadService('shop_payment')->get_payment('upop');

        /**
         * Created by PhpStorm.
         * User: Administrator
         * Date: 2015/6/8
         * Time: 16:48
         */
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        $data = $post;

        $order_sn = $data['orderId'];

        $rs = verify($data);

        //验签成功
        if ($data['respCode'] == '00') {


            // 检查商户账号是否一致。
            if ($payment['pay_config']['wy']['merId'] != $data['merId']) {
                return false;
            }
            $action_note = $data['respCode'] . ':' . $data['respMsg'] . $GLOBALS['_LANG']['upop_txn_id'] . ':' . $data['queryId'];

            // 完成订单。
            $this->loadService('order_info')->order_paid($order_sn, $data['queryId']);
            $this->loadService('order_info')->delete_payment_notice_data('upop', $order_sn);
            //告诉用户交易完成
            return true;
        } else {
            return false;
        }

    }


    /**
     * 响应操作
     */
    function distributor_respond() {
        $payment = $this->loadService('shop_payment')->get_payment('upop');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';
        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;
        if ($_POST) {
            $data = $_POST;
        } else {
            $data = $this->input();
            if (is_string($data)) {
                $data = coverStringToArray($data);
            }
        }
        $order_sn = $data['orderId'];
        $this->loadService('distributor_buy_log')->save_payment_notice_data('upop', $order_sn, $data);
        $rs = verify($data);
        //验签成功
        if ($data['respCode'] == '00') {
            // 检查商户账号是否一致。
            if ($payment['pay_config']['wy']['merId'] != $data['merId']) {
                return false;
            }
            $action_note = $data['respCode'] . ':' . $data['respMsg'] . $GLOBALS['_LANG']['upop_txn_id'] . ':' . $data['queryId'];
            // 完成订单。
            $this->loadService('distributor_buy_log')->order_paid($order_sn);
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('upop', $order_sn);
            //告诉用户交易完成
            return true;
        } else {
            return false;
        }
    }

    /**
     * 自动响应操作
     */
    function distributor_auto_respond($post) {
        $platform_shopid=$this->get_platform_shopid();
        $payment = $this->loadService('shop_payment')->get_payment('upop');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';
        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=distupop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;
        $data = $post;
        $order_sn = $data['orderId'];
        $rs = verify($data);
        //验签成功
        if ($data['respCode'] == '00') {
            // 检查商户账号是否一致。
            if ($payment['pay_config']['wy']['merId'] != $data['merId']) {
                return false;
            }
            $action_note = $data['respCode'] . ':' . $data['respMsg'] . $GLOBALS['_LANG']['upop_txn_id'] . ':' . $data['queryId'];
            // 完成订单。
            $this->loadService('distributor_buy_log')->order_paid($order_sn);
            $this->loadService('distributor_buy_log')->delete_payment_notice_data('upop', $order_sn);
            //告诉用户交易完成
            return true;
        } else {
            return false;
        }
    }




    /**
     * 格式订单号
     */
    function _formatSN($sn) {
        return str_repeat('0', 8 - strlen($sn)) . $sn;
    }

    /**
     * 银联在线退货功能
     *
     * @param array $order 订单数据   $data['money_paid'] --订单在线支付总金额，单位元
     * @param array $refund 退款单数据
     * @return bool true--成功  false--失败
     */
    public function refund($order, $refund) {
        //返回对象
        $retdata['status'] = true;
        $retdata['msg'] = "银联在线退款成功";

        $this->loadCore('Log')->write(APP_INFO, "=========upop refund==处理后台请求开始============" . print_r($order, true), 'upop');

        $payment = $this->loadService('shop_payment')->get_payment('upop');


        /**
         *    退货
         */
        //$upop_evn		= $payment['upop_evn'];		// 环境  2--生产环境
        $upop_evn = 2;
        header('Content-type:text/html;charset=utf-8');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/httpClient.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        $refund['refundlog_remark'] = empty($refund['refundlog_remark']) ? '银联退款成功' : $refund['refundlog_remark'];

        /**
         *    以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己需要，按照技术文档编写。该代码仅供参考
         */

        // 初始化日志

        $this->loadCore('Log')->write(APP_INFO, "========upop refund===处理后台请求开始============", 'upop');


        $params = array('version' => '5.0.0',        //版本号
            'encoding' => 'utf-8',        //编码方式
            'certId' => getSignCertId(),    //证书ID
            'signMethod' => '01',        //签名方法
            'txnType' => '04',        //交易类型
            'txnSubType' => '00',        //交易子类
            'bizType' => '000201',        //业务类型
            'accessType' => '0',        //接入类型
            'channelType' => '07',        //渠道类型
            'merId' => $payment['pay_config']['wy']['merId'],                //商户代码，请改自己的测试商户号
            'orderId' => $order['order_sn'] . '' . $this->_formatSN(rand(1, 10)),    //商户订单号
            'txnTime' => date('YmdHis', time() + 10),    //订单发送时间
            'txnAmt' => $refund['orefund_amount'] * 100,        //交易金额，单位分
            'origQryId' => $order['order_origQid'],    //原消费的queryId，可以从查询接口或者通知接口中获取
            'backUrl' => $this->loadPlugin('common')->getDomain() . '/service/payment/refund_notify.html?code=upop',        //后台通知地址
            'reqReserved' => $refund['orefund_id'] . "^" . $refund['refundlog_remark'], //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
        );
        $this->loadCore('Log')->write(APP_INFO, 'upop params:' . print_r($params, true), 'upop');
        try {

            // 签名
            sign($params);

            //echo "请求：" . getRequestParamString ( $params );
            //$log->LogInfo ( "后台请求地址为>" . \SDKConfig::$SDK_BACK_TRANS_URL );
            // 发送信息到后台

            $this->loadCore('log')->write(APP_ERROR, '修改订单状态: ' . \SDKConfig::$SDK_BACK_TRANS_URL, 'upop');

            $result = sendHttpRequest($params, \SDKConfig::$SDK_BACK_TRANS_URL);

            //$log->LogInfo ( "后台返回结果为>" . $result );

            //echo "应答：" . $result;

            //返回结果展示
            $result_arr = coverStringToArray($result);
            $this->loadCore('Log')->write(APP_INFO, 'upop refund1:' . print_r($result_arr, true), 'upop');
            if ($result_arr['respCode'] == '00') {
                //验签成功，进行处理
                $this->loadCore('log')->write(APP_ERROR, "refund:签名成功，进行处理", 'upop');
                //修改退款单号的状态
                $orefund_id = $refund['orefund_id'];//退款单号
                //查询退款订单-商品信息(包含订单信息)
                $order_refund_info = $this->loadService('order_refunds')->findByID($orefund_id, $this->getConfig('shop', 'SHOP_ID'));
                $orefund_way = 0;// 0:线上退款 1：线下退款  
                $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id} 信息：" . print_r($order_refund_info, true), 'upop');
                if ($order_refund_info['orefund_state'] == 4) {//防止重复修改
                    $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}已经退款成功，不再处理", 'upop');
                    echo "success";        //请不要修改或删除
                    return true;
                }

                //直接调用退款成功处理函数
                //修改退款单号的状态
                $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id},调用退款处理成功函数", 'upop');
                $ret = $this->loadService('order_info')->op_refund_by_order_sn($order['order_sn'], $refund['orefund_amount'], $refund['refundlog_remark'], $orefund_id);
                $this->loadCore('log')->write(APP_ERROR, "refund:" . $ret['msg'], 'upop');

                $retdata['status'] = true;
                $retdata['msg'] = "银联支付退款成功";
                return $retdata;
            } else {
                //验签失败
                throw new \Exception($result_arr['respMsg']);
            }
        } catch (\Exception $err) {
            $retdata['status'] = false;
            $retdata['msg'] = $err->getMessage();
            return $retdata;
        }
        $retdata['status'] = false;
        $retdata['msg'] = "银联支付退款失败";
        $this->loadCore('log')->write(APP_ERROR, "refund 银联支付退款失败", 'upop');
        return $retdata;
    }


    /**
     * 银联在线退货后台通知    无用    退款为即时接口不会有回调
     * @param array $config 在线支付配置数据
     * @param array $order 订单数据
     * @param array $refund_transfer 退款转帐申请数据
     */
    public function refund_notify() {

        $payment = $this->S('shop_payment')->get_payment('upop');

        /**
         * Created by PhpStorm.
         * User: Administrator
         * Date: 2015/6/8
         * Time: 16:48
         */
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;
        $reqReserved = $_POST['reqReserved'];//)?$_POST['reqReserved']:'银联退款成功';
        $data = explode('^', $reqReserved);
        $orefund_id = $data[0];//退款单号
        $refundlog_remark = (empty($data[1])) ? "银联退款成功" : $data[1];
        if (isset ($_POST ['signature'])) {
            echo verify($_POST) ? '验签成功' : '验签失败';
            $orderId = $_POST ['orderId']; //其他字段也可用类似方式获取
            if ($_POST['respCode'] == '00') {
                //验签成功，进行处理
                $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}  回调签名成功，进行处理", 'upop');
                //业务数据处理

                //修改退款单号的状态
                //查询退款订单-商品信息(包含订单信息)
                $order_refund_info = $this->loadService('order_refunds')->findByID($orefund_id, $this->getConfig('shop', 'SHOP_ID'));
                $orefund_way = 0;// 0:线上退款 1：线下退款  
                $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id} 信息：" . print_r($order_refund_info, true), 'upop');

                if ($order_refund_info['orefund_state'] == 4) {//防止重复修改
                    $this->loadCore('log')->write(APP_ERROR, "refund_id:{$orefund_id}已经退款成功，不再处理", 'upop');
                    echo "success";        //请不要修改或删除
                    return true;
                }

                //直接调用退款成功处理函数
                //修改退款单号的状态
                $this->loadCore('log')->write(APP_ERROR, "退款单号refund_id:{$orefund_id},调用退款处理成功函数", 'upop');
                $ret = $this->loadService('order_info')->op_refund_by_order_sn($order_refund_info['order_sn'], $order_refund_info['orefund_amount'], $order_refund_info['refundlog_remark'], $orefund_id);
                $this->loadCore('log')->write(APP_ERROR, "refund:" . $ret['msg'], 'upop');

                $retdata['status'] = true;
                $retdata['msg'] = "银联支付退款成功";
                return $retdata;
            }

        } else {
            echo '签名为空';
        }
        exit;
    }

    /**
     * 获取订单交易流水号
     * @param $order 订单信息
     */
    public function get_trade_no($order) {

        $payment = $this->loadService('shop_payment')->get_payment('upop');
        // 初始化变量
        //$upop_evn		= $payment['upop_evn'];		// 环境  2--生产环境
        $upop_evn = 2;
        header('Content-type:text/html;charset=utf-8');
        include_once 'upop/func/common.php';
        include_once 'upop/func/SDKConfig.php';
        include_once 'upop/func/secureUtil.php';
        include_once 'upop/func/log.class.php';
        include_once 'upop/func/httpClient.php';

        \SDKConfig::$SDK_SIGN_CERT_PWD = $payment['pay_config']['wy']['security_key'];
        \SDKConfig::$SDK_SIGN_CERT_PATH = str_ireplace('/runtime/', '/', str_ireplace('\\', '/', APP_RUNTIME_REAL_PATH . str_replace('/', APP_DS, $payment['pay_config']['wy']['sign_cert_files'])));
        \SDKConfig::$SDK_VERIFY_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'verify_cert.cer';
        \SDKConfig::$SDK_ENCRYPT_CERT_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop' . APP_DS . 'encrypt_cert.cer';
        \SDKConfig::$SDK_VERIFY_CERT_DIR = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_FRONT_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_BACK_NOTIFY_URL = $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop';
        \SDKConfig::$SDK_FILE_DOWN_PATH = get_package_real_path() . APP_DS . 'cert' . APP_DS . 'upop';
        \SDKConfig::$SDK_LOG_FILE_PATH = APP_RUNTIME_REAL_PATH;

        /**
         * 消费交易-前台
         */

        $this->loadCore('Log')->write(APP_INFO, '============处理前台请求开始===============', 'upop');

        // 初始化日志
        $params = array('version' => '5.0.0',                //版本号
            'encoding' => 'utf-8',                //编码方式
            'certId' => getSignCertId(),            //证书ID
            'txnType' => '01',                //交易类型
            'txnSubType' => '01',                //交易子类
            'bizType' => '000201',                //业务类型
            'frontUrl' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop',        //前台通知地址
            'backUrl' => $this->loadPlugin('common')->getDomain() . '/service/payment/respond.html?code=upop',        //后台通知地址
            'signMethod' => '01',        //签名方法
            'channelType' => '08',        //渠道类型，07-PC，08-手机
            'accessType' => '0',        //接入类型
            'merId' => $payment['pay_config']['wy']['merId'],                //商户代码，请改自己的测试商户号
            'orderId' => $order['order_sn'],    //商户订单号
            'txnTime' => date('YmdHis'),    //订单发送时间
            'txnAmt' => $order['order_order_amount'] * 100,        //交易金额，单位分
            'currencyCode' => '156',    //交易币种
            'defaultPayType' => '0001',    //默认支付方式
            //'orderDesc' => '订单描述',  //订单描述，网关支付和wap支付暂时不起作用
            'reqReserved' => $order['order_sn'], //请求方保留域，透传字段，查询、通知、对账文件中均会原样出现
        );

        // 签名
        sign($params);


        // 发送信息到后台
        $result = sendHttpRequest($params, \SDKConfig::$SDK_App_Request_Url);

        $result_arr = coverStringToArray($result);
        if ($result_arr['tn']) {
            return $result_arr['tn'];
        }
        return '';
    }

    function get_platform_shopid() {
        return (isset($_SESSION['PLATFORM_SHOP_ID'])) ? $_SESSION['PLATFORM_SHOP_ID'] : $this->getConfig('shop', 'SHOP_ID');
    }
}
