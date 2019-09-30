<?php
/**
 * EUnionZ PHP Framework Sms Plugin class
 * Sms 插件类
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\sms;


defined('APP_IN') or exit('Access Denied');


class Sms extends \cn\eunionz\core\Plugin
{

    private $default_signature='【万商云集】';

    /**
     * 执行短信发送
     * @param $Phones  目标手机号码
     * @param $Content  内容
     * @param $is_now_send  是否非验证码立即发送 0--不立即发送  1--立即发送
     * @param $is_yingxiao  是否营销类短信 0--否  1--是
     * @param $smslog_kms_msl_id  K+云商MAX后台创建的短信发送日志ID 0--不记录  >0记录，并在发送失败时同步回K+云商MAX后台
     * @param $smslog_id  KID的短信发送日志ID 0--不更新记录的发送状态  >0--更新该smslog_id对应的记录的发送状态
     * @return mixed array('success'=>1,'message'=>'')
     */
    public function sms_send($Phones,$Content,$is_now_send=0,$is_yingxiao=0,$smslog_kms_msl_id=0,$smslog_id=0){

        $params['success'] = 1;//0--成功   1--失败
        $APP_SMS_SERVICE_PROVIDERS=self::getConfig('sms','APP_SMS_SERVICE_PROVIDERS');
        $APP_SMS_SERVICE_PROVIDER_ENABLED_TYPES=self::getConfig('sms','APP_SMS_SERVICE_PROVIDER_ENABLED_TYPES');
        shuffle($APP_SMS_SERVICE_PROVIDER_ENABLED_TYPES);
        $available_sms_providers=$is_yingxiao?$APP_SMS_SERVICE_PROVIDERS['sale_category_apis']:$APP_SMS_SERVICE_PROVIDERS['verification_code_apis'];

        if(!$available_sms_providers){
            $params['message'] = '短信服务提供商配置列表为空(请检查sms配置文件)，发送短信失败';
            return $params;
        }
        if(!$APP_SMS_SERVICE_PROVIDER_ENABLED_TYPES){
            $params['message'] = '短信服务提供商启用列表为空(请检查sms配置文件)，发送短信失败';
            return $params;
        }

        foreach ($APP_SMS_SERVICE_PROVIDER_ENABLED_TYPES as $type_index){
            if(isset($available_sms_providers[$type_index])){
                $sms_api=$available_sms_providers[$type_index];

                //当前短信接口类别编号在启用的短信服务商启用列表中，允许发送
                $params['service_type']=$sms_api['type'];
                if(strtolower($sms_api['method'])=='get'){
                    $url=$sms_api['url'];
                    $url = str_replace('{Phones}',$Phones,$url);
                    $url = str_replace('{user}',$sms_api['user'],$url);
                    $url = str_replace('{password}',$sms_api['password'],$url);
                    $sms_api['charset']=strtolower($sms_api['charset']);
                    if($sms_api['charset']!='utf-8'){
                        if(preg_match("/【(.+?)】/",$Content,$arr)){
                            $Content=str_ireplace($arr[0],'',$Content).$arr[0];
                        }else{
                            $Content=$Content.$this->default_signature;
                        }
                        $url = str_replace('{Content}',urlencode(iconv('utf-8',$sms_api['charset'], $Content)),$url);
                    }else{
                        $url = str_replace('{Content}',urlencode($Content),$url);
                    }

                    if($is_now_send){
                        $result=$this->get_content($url);
                    }else{
                        $result=1;
                    }
                }else{
                    $url=$sms_api['url'];
                    $url = str_replace('{Phones}',$Phones,$url);
                    $url = str_replace('{user}',$sms_api['user'],$url);
                    $url = str_replace('{password}',$sms_api['password'],$url);
                    $sms_api['charset']=strtolower($sms_api['charset']);
                    if($sms_api['charset']!='utf-8'){
                        if(preg_match("/【(.+?)】/",$Content,$arr)){
                            $Content=str_ireplace($arr[0],'',$Content).$arr[0];
                        }else{
                            $Content=$Content.$this->default_signature;
                        }
                        $Content=iconv('utf-8',$sms_api['charset'], $Content);
                    }

                    $postArr = array (
                        'un' => $sms_api['user'],
                        'pw' => $sms_api['password'],
                        'msg' => $Content,
                        'phone' => $Phones,
                        'rd' => 1
                    );
                    if($is_now_send){
                        $result = $this->curlPost( $url , $postArr);
                        $result = $this->execResult($result);
                        $result=intval(isset($result[1])?$result[1]:1127);
                    }else{
                        $result=1;
                    }
                }


                if($sms_api['success']==0 && intval($result)==0){
                    $params['success'] = 0;
                    $params['message'] = '发送成功';
                    break;
                }elseif($sms_api['success']==-1 && intval($result)<0){
                    $params['success'] = 0;
                    $params['message'] = '发送成功';
                    break;
                }elseif($sms_api['success']==1 && intval($result)>=1){
                    $params['success'] = 0;
                    $params['message'] = '发送成功';
                    break;
                }else{
                    if(isset($sms_api['error_maps'][intval($result)])){
                        $params['message'] = $sms_api['error_maps'][intval($result)];
                    }elseif(intval($result)==-10001){
                        $params['message'] = '短信网关DNS解析错误';
                    }else{
                        $params['message'] = 'KID未知错误';
                    }
                }
            }
        }

        return $params;
    }

    /**
     * 发送
     * @param $kid  k+id
     * @param $Phones
     * @param $Content
     * @param $ip 客户端ip地址
     * @param $time 发送时间
     * @param $m 短信类型
     * @param $is_now_send  是否非验证码立即发送 0--不立即发送  1--立即发送
     * @param $is_yingxiao  是否营销类短信 0--否  1--是
     * @param $smslog_id  K+云商MAX后台创建的短信发送日志ID 0--不记录  >0记录，并在发送失败时同步回K+云商MAX后台
     * @return array('success'=>0,'message'=>'发送短信失败')
     */
    public function send($KID,$Phones,$Content,$ip,$time=0,$m='kshop',$is_now_send=0,$is_yingxiao=0,$smslog_id=0){

        $params=array(
            'Phones'=>$Phones,
            'Content'=>$Content,
            'sendTime'=>time(),
            'KID'=>$KID,
            'success'=>1,
            'message'=>'',
            'ip' => $ip,
            'is_now_send' => $is_now_send,
            'is_yingxiao' => $is_yingxiao,
            'smslog_id' => $smslog_id,
        );
        if(!preg_match('/【.+】/',$Content)){
            $Content .= $this->default_signature;
        }

        $sms_datas=$this->loadService('shop_base')->get_sms_count_by_shop_id($KID);
        if(!$sms_datas){
            return array('success'=>20001,'message'=>'商城【K+ID：' . $KID . '】不存在，发送短信失败');
        }
        if($sms_datas['shop_smscount']<=0){
            return array('success'=>20002,'message'=>'商城【K+ID：' . $KID . '】剩余短信条数为零，发送短信失败');
        }

        $arr=explode(',',$Phones);
        $params['count'] = count($arr)*$this->computeSMSCount($Content);
        if($sms_datas['shop_smscount']<$params['count']){
            return array('success'=>20002,'message'=>'商城【K+ID：' . $KID . '】短信条数 ' . $sms_datas['shop_smscount'] .' 不足以发送本次短信【本次发送短信数量为：' . $params['count'] .'】，发送短信失败');
        }
        if(strpos($time,'-')){
            $time=strtotime($time);
        }
        $params['time'] = time();
        $params['m'] = $m;

        try{
            //检查要发送的短信是否有效
//            $rs=$this->loadService('shop_base')->validate_sms_content($params['KID'],$params['Phones'],$params['Content'],$params['time']);
//            $this->loadCore('log')->log(APP_DEBUG,print_r($rs,true),'sms1');
//            if($rs['status']==-1){
//                //验证码类，拒绝发送
//                throw new \Exception($rs['msg']);
//            }
//            $params['time']=$rs['time'];

            $rs = $this->sms_send($Phones,$Content,$is_now_send,$is_yingxiao,$smslog_id);
            if($rs['success']==1){
                //发送失败，调用kms总平台接口将 kid以及该条记录的发送记录标识为发送失败
                $APP_KMS_SMS_LOG_STATUS_UPDATE_URL=self::getConfig('params','APP_KMS_SMS_LOG_STATUS_UPDATE_URL');
                if($smslog_id){
                    $sms_update_url=str_ireplace('@kid@',$KID,$APP_KMS_SMS_LOG_STATUS_UPDATE_URL);
                    $sms_update_url=str_ireplace('@msl_id@',$smslog_id,$sms_update_url);
                    $sms_update_url=str_ireplace('@msl_status@',1,$sms_update_url);
                    try{
                        file_get_contents($sms_update_url);
                    }catch (\Exception $err){

                    }
                }
                throw new \Exception($rs['message']);
            }else{
                $params['success']=$rs['success'];
                $params['smslog_fee_number']=$params['count'];
                $params['smslog_service_type']=$rs['service_type'];
                $params['message']=$rs['message'];
                $params['Content']=trim($params['Content']);
                if($params['Content']){
                    $this->loadService('shop_base')->consume_sms($params);
                }
            }
        }catch (\Exception $err){
            $params['message'] = $err->getMessage();
            $params['success']=0;
        }

        return array('success'=>$params['success'],'message'=>$params['message']);
    }


    /**
     * 获取url内容
     * @param $url  url
     * @return mixed|string
     */
    private function get_content($url)
    {
        $file_contents=-20000;
        try{
            if(function_exists('file_get_contents'))
            {
                $opts = array(
                    'http'=>array(
                        'method'=>'GET',
                        'timeout'=>5,
                    )
                );
                $context = stream_context_create($opts);

                $file_contents = file_get_contents($url, false, $context);
            }
            else
            {
                $ch = curl_init();
                $timeout = 5;
                curl_setopt ($ch, CURLOPT_URL, $url);
                curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
                $file_contents = curl_exec($ch);
                curl_close($ch);
            }

        }catch (\Exception $err){


            $urls=parse_url($url);
            if(!dns_get_record($urls['host'])){
                $file_contents=-10001;
            }
        }
        return $file_contents;
    }

    /**
     * 计算内容需要消费的短信条数
     * @param $content
     */
    public function computeSMSCount($content){
        $wordCount=70;
        $len=iconv_strlen($content,"utf-8");
        return ceil($len/$wordCount);
    }


    /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     */
    private function curlPost($url,$postFields){
        $postFields = http_build_query($postFields);
        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $postFields );
        $result = curl_exec ( $ch );
        curl_close ( $ch );
        return $result;
    }

    /**
     * 处理返回值
     *
     */
    private function execResult($result){
        $result=preg_split("/[,\r\n]/",$result);
        return $result;
    }


}