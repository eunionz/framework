<?php
/**
 * EUnionZ PHP Framework Pinyin Plugin class
 * 银联在线支付/代付/退货/查询接口插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\offline;


defined('APP_IN') or exit('Access Denied');

/**
 * 线下支付 支付方式
 * Class Offline
 * @package plugin\offline
 */
class Offline extends \cn\eunionz\core\Plugin {

    public function get_code($order, $payment) {
        return array('result' => true);
    }

    public function distributor_get_code($order, $payment) {
        return array('result' => true);
    }

    /**
     * 响应操作
     */
    function respond() {

    }


    public function refund($order_info, $order_refund_info) {
        $orefund_way = 1;// 0:线上退款 1：线下退款  
        //直接调用退款成功处理函数
        $ret = $this->loadService('order_info')->op_refund_by_order_sn($order_info['order_sn'], $order_refund_info['orefund_amount'], "线下退款成功", $order_refund_info['orefund_id'], $orefund_way);
        $this->loadCore('log')->log(APP_ERROR, "退款处理:" . $ret['msg'], 'offline');

        return $ret;
    }


    public function refund_notify() {

    }


}
