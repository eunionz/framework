<?php
/**
 * EUnionZ PHP Framework Wxpay3 Plugin class
 * 微信支付第三版
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\wxpay3;


defined('APP_IN') or exit('Access Denied');

class Wxpay3 extends \com\eunionz\core\Plugin
{


    /**
     * @param $wxpays:$appid,$appkey,$mch_id,$appsercert
     * @param $order
     * @return string
     */
    public function create($wxpays,$order,$openid,$notify_url){

        include_once("WxPayHelper.php");
        $commonUtil = new \CommonUtil();
        $wxPayHelper = new \WxPayHelper();
        $wxPayHelper = $wxPayHelper->init($wxpays['appid'],$wxpays['appkey'],$wxpays['appsercert'],$wxpays['mch_id']);
        $wxPayHelper->setParameter("appid", $wxPayHelper->APPID);
        $wxPayHelper->setParameter("mch_id", $wxPayHelper->MCH_ID);//ymtx:1227203001
        $wxPayHelper->setParameter("device_info",$this->getIP());
        $wxPayHelper->setParameter("nonce_str",$commonUtil->create_noncestr());
        $wxPayHelper->setParameter("body", $order['name']);
        $wxPayHelper->setParameter("out_trade_no", $order['order_shop_id'] .'_' .$order['order_sn']);
        $wxPayHelper->setParameter("fee_type", "CNY");
        $wxPayHelper->setParameter("total_fee", $order['order_online_money']*100);
        $wxPayHelper->setParameter("spbill_create_ip", $this->getIP());
        $wxPayHelper->setParameter("notify_url", $notify_url);
        $wxPayHelper->setParameter("trade_type", "JSAPI");
        $wxPayHelper->setParameter("openid", $openid);
        $prepay_id = $wxPayHelper->get_prepayid($order,$openid);//获取预支付id
        if(!$prepay_id){
            die("Error: cannot get prepay_id!!");
        }

        return $wxPayHelper->create_biz_package($prepay_id);
    }


    /**
     * //获取客户端ip地址
     * @return string
     */
    public function getIP()
    {
        if (getenv("HTTP_CLIENT_IP"))
        {
            $ip = getenv("HTTP_CLIENT_IP");
        }
        else
        {
            if (getenv("HTTP_X_FORWARDED_FOR"))
            {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            }
            else
            {
                if (getenv("REMOTE_ADDR"))
                {
                    $ip = getenv("REMOTE_ADDR");
                }
                else
                {
                    $ip = "Unknow";
                }
            }
        }

        return $ip;
    }
}