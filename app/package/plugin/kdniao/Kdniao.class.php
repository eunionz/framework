<?php
/**
 * EUnionZ PHP Framework Pinyin Plugin class
 * 快递鸟接口插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\kdniao;


defined('APP_IN') or exit('Access Denied');
//电商ID
//defined('EBusinessID') or define('EBusinessID', '1256310');
//电商加密私钥，快递鸟提供，注意保管，不要泄漏
//defined('AppKey') or define('AppKey', 'e77ceced-e98b-460a-a26c-6731a6cd0ccf');
//请求url，正式环境地址：http://api.kdniao.cc/api/Eorderservice    测试环境地址：http://testapi.kdniao.cc:8081/api/EOrderService
//defined('ReqURL') or define('ReqURL', 'http://testapi.kdniao.cc:8081/api/Eorderservice');

/**
 * 快递鸟插件
 * Class Cod
 * @package plugin\cod
 */
class Kdniao extends \cn\eunionz\core\Plugin
{
    private $_EBussinessID;
    private $_AppKey;
    private $_ReqURL = 'http://api.kdniao.com/api/Eorderservice';//正式环境地址：http://api.kdniao.cc/api/Eorderservice
    private $_PrintApiUrl='http://www.kdniao.com/External/PrintOrder.aspx';

    public function __construct()
    {
        $SESSION = ctx()->session();
        $shop_id = isset($SESSION['PLATFORM_SHOP_ID'])?$SESSION['PLATFORM_SHOP_ID']:ctx()->getShopId();
        $this->_EBussinessID = $this->loadService('shop_params')->get_value_by_key('KD_USER_ID',$shop_id);
        $this->_AppKey = $this->loadService('shop_params')->get_value_by_key('KD_API_KEY',$shop_id);
    }
    /**
     * 快递鸟获取电子面单接口
     * @param $order 订单详情
     * @param $sender_msg 发件人信息
     * @param $recevier_msg 收件人信息
     * @return bool|mixed
     */
    public function get_eoder($order,$shipping_name,$sender_msg,$recevier_msg,$CustomerName='',$CustomerPwd='')
    {
        $eorder = [];
        /**
         * 快递编码
         */
        switch ($shipping_name){
            case '韵达快递':
                $eorder["ShipperCode"] = 'YD';
                break;
            case '中通快递':
                $eorder["ShipperCode"] = 'ZTO';
                break;
            case 'EMS':
                $eorder["ShipperCode"] = 'EMS';
                break;
            case '顺丰快递':
                $eorder["ShipperCode"] = 'SF';
                break;
            case '圆通快递':
                $eorder["ShipperCode"] = 'YTO';
                break;
            default:
                $eorder["ShipperCode"] = '';
                break;
        }
        $eorder["CustomerName"] = $CustomerName;
        $eorder["CustomerPwd"] = $CustomerPwd;



        if(empty($eorder['ShipperCode'])){
            return [
                'status'=> 1,
                'result'=>'暂未支持的快递方式！',
            ];
        }

        $eorder["OrderCode"] =$order['order_sn'];// "012657700399";订单编号
        $eorder["PayType"] = 1;//支付方式 默认现付
        $eorder["ExpType"] = 1;//快递类型。
        $eorder["IsReturnPrintTemplate"] = 1;//是否返回电子面单模板

        $sender = [];//组装发货信息
        $sender["Name"] = $sender_msg['warehouse_send_linkman'];//发货联系人
        $sender["Mobile"] = $sender_msg['warehouse_send_mobile'];//发货人电话
        $sender["ProvinceName"] = $sender_msg['warehouse_send_province'];//发货地址省份  需转为具体省份
        $sender["CityName"] = $sender_msg['warehouse_send_city'];//发货地址城市  需转为具体城市
        $sender["ExpAreaName"] = $sender_msg['warehouse_send_region'];//发货地址地区  需转为具体地区
        $sender["Address"] = $sender_msg['warehouse_send_address'];//发货详细地址
        $sender["PostCode"] = $sender_msg['warehouse_send_postcode'];//发货地址邮政编码

        $receiver = [];//组装收货信息
        $receiver["Name"] = $recevier_msg['delivery_consignee'];//收货人姓名
        $receiver["Mobile"] = $recevier_msg['delivery_mobile'];//收货人电话
        $receiver["ProvinceName"] = $recevier_msg['delivery_province'];//收货地址省份  需转为具体省份
        $receiver["CityName"] = $recevier_msg['delivery_citye'];//收货地址城市  需转为具体城市
        $receiver["ExpAreaName"] = $recevier_msg['delivery_region'];//收货地址地区  需转为具体地区
        $receiver["Address"] = $recevier_msg['delivery_address'];//收货详细地址
        $receiver["PostCode"] = $recevier_msg['delivery_zipcode'];//收货地址邮政编码

        $commodityOne = [];
        $commodityOne["GoodsName"] = $order['goods_name'];//商品名
        $commodity = [];
        $commodity[] = $commodityOne;

        $eorder["Sender"] = $sender;
        $eorder["Receiver"] = $receiver;
        $eorder["Commodity"] = $commodity;

        $jsonParam = json_encode($eorder, JSON_UNESCAPED_UNICODE);
        $jsonResult = $this->submitEOrder($jsonParam);
        //获取电子面单结果
        $result = json_decode($jsonResult, true);
        if(isset($result["ResultCode"]) && ($result["ResultCode"] == "100" || $result["ResultCode"] == "106")) {
            return [
                'status'=> 0,
                'result'=>$result,
            ];
        }else {
            if(isset($result["ResultCode"])){
                return [
                    'status'=> $result["ResultCode"],
                    'result'=>$result['Reason'],
                ];
            }else{
                return [
                    'status'=> 1,
                    'result'=>$result['Reason'],
                ];
            }
        }
    }

/*
 * 快递鸟面单打印接口
 * $order_infos  '[{"OrderCode":"012657700387","PortName":"DeliDL-888C"}]';//$orders;//
 * */
    public function do_print($order_infos){
        //OrderCode:需要打印的订单号，和调用快递鸟电子面单的订单号一致，PortName：本地打印机名称，请参考使用手册设置打印机名称。支持多打印机同时打印。
        $request_data =$order_infos;//
        $this->loadCore('log')->log(APP_ERROR,var_export($order_infos,true),'kdniao');
        $ip=$this->getip();
        $data_sign = $this->encrypt($ip.$request_data, $this->_AppKey);
        //是否预览，0-不预览 1-预览
        $is_priview = '0';
        //组装表单
        $returnarr['PostUrl']=$this->_PrintApiUrl;
        $returnarr['RequestData']=urlencode($request_data);
        $returnarr['EBusinessID']=$this->_EBussinessID;
        $returnarr['DataSign']=$data_sign;
        $returnarr['IsPriview']=$is_priview;
        return $returnarr;
//        $form = '<form id="form1" method="POST" action="'.$this->_PrintApiUrl.'">
//                <input type="text" name="RequestData" value=\''.$request_data.'\'/>
//                <input type="text" name="EBusinessID" value="'.$this->_EBussinessID.'"/>
//                <input type="text" name="DataSign" value="'.$data_sign.'"/>
//                <input type="text" name="IsPriview" value="'.$is_priview.'"/>
//                </form><script>form1.submit();</script>';
//        exit($form);
    }

    /**
     * 获取客户端IP(非用户服务器IP)
     * @return 客户端IP
     */
    private function getip() {
        //获取客户端IP
        $SERVER = ctx()->server();

        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($SERVER['REMOTE_ADDR']) && $SERVER['REMOTE_ADDR'] && strcasecmp($SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $SERVER['REMOTE_ADDR'];
        }
        if($ip){
            $ip = str_replace('::ffff:','',$ip);
        }
        if(!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://www.kdniao.com/External/GetIp.aspx');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            return trim(substr($output,0,15));
        }else{
            return $ip;
        }
    }

    /**
     * Json方式 调用电子面单接口
     */
    private function submitEOrder($requestData){
        $datas = array(
            'EBusinessID' => $this->_EBussinessID,
            'RequestType' => '1007',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $this->encrypt($requestData, $this->_AppKey);
        $result=$this->sendPost($this->_ReqURL, $datas);

        //根据公司业务处理返回的信息......

        return $result;
    }

    /**
     *  post提交数据
     * @param  string $url 请求Url
     * @param  array $datas 提交的数据
     * @return url响应返回的html
     */
    private function sendPost($url, $datas) {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if(empty($url_info['port']))
        {
            $url_info['port']=80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader.= "Host:" . $url_info['host'] . "\r\n";
        $httpheader.= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader.= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader.= "Connection:close\r\n\r\n";
        $httpheader.= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets.= fread($fd, 128);
        }
        fclose($fd);
        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param data 内容
     * @param appkey Appkey
     * @return DataSign签名
     */
    private function encrypt($data, $appkey) {
        return urlencode(base64_encode(md5($data.$appkey)));
    }
    /**************************************************************
     *
     *  使用特定function对数组中所有元素做处理
     *  @param  string  &$array     要处理的字符串
     *  @param  string  $function   要执行的函数
     *  @return boolean $apply_to_keys_also     是否也应用到key上
     *  @access public
     *
     *************************************************************/
    private function arrayRecursive(&$array, $function, $apply_to_keys_also = false)
    {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }


    /**************************************************************
     *
     *  将数组转换为JSON字符串（兼容中文）
     *  @param  array   $array      要转换的数组
     *  @return string      转换得到的json字符串
     *  @access public
     *
     *************************************************************/
    private function JSON($array) {
        arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);
        return urldecode($json);
    }
}
