<?php
/**
 * Eunionz PHP Framework Validation component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\validation;

defined('APP_IN') or exit('Access Denied');
class Validation extends \cn\eunionz\core\Component {

    /**
     * 存在就验证
     */
    const VALIDATE_EXISTS = 0;

    /**
     * 必须验证
     */
    const VALIDATE_MUST  = 1;

    /**
     * 不为空字符串就验证
     */
    const VALIDATE_VALUE = 2;

    /**
     * 模型添加时验证
     */
    const MODEL_INSERT = 1;


    /**
     * 模型删除时验证
     */
    const MODEL_DELETE = 2;


    /**
     * 模型修改时验证
     */
    const MODEL_UPDATE = 4;


    /**
     * 模型修改或删除时验证
     */
    const MODEL_UPDATE_AND_DELETE = 6;

    /**
     * 模型修改或添加时验证
     */
    const MODEL_UPDATE_AND_INSERT = 3;

    /**
     * 模型删除或添加时验证
     */
    const MODEL_DELETE_AND_INSERT = 5;

    /**
     * 模型删除、修改或添加时验证
     */
    const MODEL_ALL = 7;

    /**
     * 客户端验证
     */
    const VALIDATE_CLIENT=1;

    /**
     * 服务器端验证
     */
    const VALIDATE_SERVER=2;


    /**
     * 客户端和服务器端验证
     */
    const VALIDATE_BOTH=3;

    /**
     * 验证远程url返回是否为true
     * @param $data
     * @param $data1
     *
     * @return bool
     */
    public function vRemote($data,$data1){
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;
        if(count($arr_data)==0) return true;
        $url=$arr_data[0];
        unset($arr_data[0]);

        if(trim($this->loadComponent('curl')->CurlPost($url,array('param'=>$data,'other_param'=>isset($arr_data[1])?$arr_data[1]:'')))=='true')
        {
            return true;
        }
        return false;
    }

    /**
     * 数据不能为空
     * @param $data
     */
    public function vRequired($data){
        if(!is_string($data)) $data .="";
        if(strlen(trim($data))>0) return true;
        return false;
    }

    /**
     * //验证邮箱
     * @param $email
     * @return bool
     */
    public function vEmail($email){
        $pregEmail = "/([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?/i";
        return preg_match($pregEmail,$email);
    }


    /**
     * 数据必须为url
     * @param $data
     */
    public function vUrl($data){
        $pattern = "/^(http|https|ftp|file){1}(:\/\/)?([\da-z-\.]+)\.([a-z]{2,6})([\/\w \.-?&%-=]*)*\/?$/i";
        return preg_match($pattern,$data);
    }

    /**
     * 数据必须为2014-10-28，格式日期
     * @param $data
     */
    public function vDateISO($data){
        $pattern = "/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/";
        return preg_match($pattern,$data);
    }


    /**
     * 数据必须为2014-10-28 14:23:56，格式日期时间
     * @param $data
     */
    public function vDatetime($data){
        $pattern = "/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        return preg_match($pattern,$data);
    }



    /**
     * 数据必须为14:23:56，格式时间
     * @param $data
     */
    public function vTime($data){
        $pattern = "/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/";
        return preg_match($pattern,$data);
    }


    /**
     * 数据必须为数字
     * @param $data
     */
    public function vNumber($data){
        return is_numeric($data);
    }

    /**
     * 数据必须为整数
     * @param $data
     */
    public function vDigits($data){
        $pattern = "/^[0-9]+$/";
        return preg_match($pattern,$data);
    }

    /**
     * 数据必须绝对相等
     * @param $data
     */
    public function vEqualTo($data,$data1){

        return $data==$data1;
    }


    /**
     * 数据必须以$data1为结束
     * @param $data
     */
    public function vExtension($data,$data1){
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode('|',$data1);
        if(!$arr_data) return true;

        $pattern = "/(";
        foreach($arr_data as $val){
            $pattern .="{$val}|";
        }
        $pattern=substr($pattern,0,strlen($pattern)-1);
        $pattern .= ")$/i";

        return preg_match($pattern,$data);
    }

    /**
     * 数据必须以$data1为结束
     * @param $data
     */
    public function vAccept($data,$data1){
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;

        $pattern = "/(";
        foreach($arr_data as $val){
            $pattern .="{$val}|";
        }
        $pattern=substr($pattern,0,strlen($pattern)-1);
        $pattern .= ")$/i";
        return preg_match($pattern,$data);
    }

    /**
     * 数据最大长度不能超过 $data1
     * @param $data
     */
    public function vMaxlength($data,$data1){
        $data1=intval($data1);
        return strlen($data)<=$data1;
    }

    /**
     * 数据最小长度不小于 $data1
     * @param $data
     */
    public function vMinlength($data,$data1){
        $data1=intval($data1);
        return strlen($data)>=$data1;
    }

    /**
     * 数据长度必须介于之间
     * @param $data
     */
    public function vRangelength($data,$data1){
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(count($arr_data)==0) return true;


        if(count($arr_data)==1) return strlen($data)>=intval($arr_data[0]);
        if(count($arr_data)>=2){
            if(empty($arr_data[0])){
                return strlen($data)<=intval($arr_data[1]);
            }
            if(empty($arr_data[1])){
                return strlen($data)>=intval($arr_data[0]);
            }
            return strlen($data)>=intval($arr_data[0]) && strlen($data)<=intval($arr_data[1]);
        }
    }

    /**
     * 数据必须介于之间
     * @param $data
     */
    public function vRange($data,$data1){
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(count($arr_data)==0) return true;

        if(count($arr_data)==1) return $data>=$arr_data[0];
        if(count($arr_data)>=2){
            if(empty($arr_data[0])){
                return $data<=$arr_data[1];
            }
            if(empty($arr_data[1])){
                return $data>=$arr_data[0];
            }
            return $data>=$arr_data[0] && $data<=$arr_data[1];
        }

    }



    /**
     * 数据必须小于等于最大值
     * @param $data
     */
    public function vMax($data,$data1){

        return $data <= $data1;
    }

    /**
     * 数据必须大于等于最小值
     * @param $data
     */
    public function vMin($data,$data1){

        return $data >= $data1;
    }


    /**
     * //验证身份证号
     */
    public function vIDNumber($id_card) {
        if (strlen($id_card) == 18) {
            return $this->idcard_checksum18($id_card);
        }
        elseif((strlen($id_card) == 15)) {
            $id_card = $this->idcard_15to18($id_card);
            return $this->idcard_checksum18($id_card);
        } else {
            return false;
        }
    }

    /**
     * 数据长度等于 $data1
     * @param $data
     */
    public function vLength($data,$data1){
        return strlen($data)==$data1;
    }


    /**
     * //手机号码验证
     * @param $mobile
     * @return bool
     */
    public function vMobile($mobile) {
        // 基础验证
        if($mobile == "" || is_null($mobile) || strlen($mobile)!=11){
            return false;
        }
        // 手机验证
        if(preg_match("/^13[0-9]{9}$|14[0-9]{9}|15[0-9]{9}$|17[0-9]{9}$|18[0-9]{9}$/",$mobile)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * //电话号码验证
     * @param $data
     * @return bool
     */
    public function vTelephone($data) {
        // 手机验证
        if($this->vMobile($data)){
            return true;
        }
        // 电话号码验证
        if(preg_match("/^(([0-9]{3}-?[0-9]{8})|([0-9]{4}-?[0-9]{7}))$/",$data)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 正则表达式验证
     * @param $data
     * @return bool
     */
    public function vRegex($data,$data1) {
        return preg_match("/" . $data1 ."/i",$data);
    }


    /**
     * 调用函数验证
     * @param $data
     * @return bool
     */
    public function vFunction($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!isset($arr_data[0])) return true;
        $method=$arr_data[0];
        unset($arr_data[0]);
        return call_user_func($method,$data,$arr_data);
    }

    /**
     * 调用模型方法验证
     * @param $data
     * @return bool
     */
    public function vCallback($data,$data1) {

        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!isset($arr_data[0])) return true;
        if(!isset($arr_data[1])) return true;
        $resource=$arr_data[0];
        $method=$arr_data[1];

        return call_user_func(array(is_string($resource)?$this->loadModel($resource):$resource,$method),$data,$arr_data);
    }

    /**
     * 数据绝对相等验证
     * @param $data
     * @return bool
     */
    public function vEqual($data,$data1) {
        return $data==$data1;
    }

    /**
     * 数据绝对不相等验证
     * @param $data
     * @return bool
     */
    public function vNotequal($data,$data1) {
        return $data!=$data1;
    }

    /**
     * 数据只能在值列表中
     * @param $data
     * @return bool
     */
    public function vIn($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;
        return in_array($data,$arr_data);
    }

    /**
     * 数据不能在值列表中
     * @param $data
     * @return bool
     */
    public function vNotin($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;
        return !in_array($data,$arr_data);
    }

    /**
     * 数据只能在之间
     * @param $data
     * @return bool
     */
    public function vBetween($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;

        if(count($arr_data)==0) return true;

        if(count($arr_data)==1) return $data>=$arr_data[0];

        if(count($arr_data)>=2) return $data>=$arr_data[0] && $data<=$arr_data[1];

    }

    /**
     * 数据不能在之间
     * @param $data
     * @return bool
     */
    public function vNotbetween($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!$arr_data) return true;

        if(count($arr_data)==0) return true;

        if(count($arr_data)==1) return $data<$arr_data[0];

        if(count($arr_data)>=2) return $data<$arr_data[0] || $data>$arr_data[1];

    }

    /**
     * 数据值必须唯一
     * @param $data
     * @return bool
     */
    public function vUnique($data,$data1) {
        if(is_array($data1))
            $arr_data=$data1;
        else
            $arr_data=explode(',',$data1);
        if(!isset($arr_data[0])) return true;
        if(!isset($arr_data[1])) return true;
        $resource=$arr_data[0];
        $method=$arr_data[1];

        return call_user_func(array(is_string($resource)?$this->loadModel($resource):$resource,$method),$data,$arr_data);

    }


    /**
     * //将15位身份证升级到18位
     * @param $idcard
     * @return bool|string
     */
    private function idcard_15to18($idcard) {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6).'18'.substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6).'19'.substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . $this->idcard_verify_number($idcard);
        return $idcard;
    }

    /**
     * //18位身份证校验码有效性检查
     * @param $idcard
     * @return bool
     */
    private function idcard_checksum18($idcard) {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        if ($this->idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * //计算身份证校验码，根据国家标准GB 11643-1999
     * @param $idcard_base
     * @return bool
     */
    private function idcard_verify_number($idcard_base) {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }


    /**
     * 根据默认验证规则获取jquery.validate.js的验证数据
     * @param $resource  资源名称，形如  model.user_address
     */
    public function get_validate_rules($resource){
        $validate_clients=array();
        //元素格式：
        //      array('field'=>array('title'=>'','rules'=>array('rule','')),),
        try{
            $model=$this->loadModel($resource);
            if($model->_is_validate){


                foreach($model->_validate_rules as $field=>$rules){
                    if(!is_array($rules)){
                        continue;
                    }
                    $field_text='';
                    $arr_rules=array();
                    foreach($rules  as  $rule){

                        if(is_string($rule)){
                            $field_text=$rule;
                        }else{
                            $field_validate_mode=isset($rule[0])?$rule[0]:self::VALIDATE_BOTH;
                            $field_rule=isset($rule[1])?$rule[1]:'';

                            $field_error=(isset($rule[2])?$rule[2]:'');

                            $field_error = str_replace('{0}',$field_text,$field_error);
                            $field_error = str_replace('{1}','',$field_error);

                            $field_validate_type=isset($rule[3])?$rule[3]:self::VALIDATE_MUST;
                            $field_ext_datas=isset($rule[4])?$rule[4]:array();
                            if(is_string($field_ext_datas)) $field_ext_datas=explode(',',$field_ext_datas);
                            $field_validate_action=isset($rule[5])?$rule[5]:self::MODEL_ALL;
                            if($field_rule=='equalTo'){
                                $field_ext_datas=$field_ext_datas[0];
                            }else if($field_rule=='maxlength' || $field_rule=='minlength' || $field_rule=='length' || $field_rule=='min' || $field_rule=='max' || $field_rule=='regex' || $field_rule=='equal' || $field_rule=='notequal'){
                                $field_ext_datas=$field_ext_datas[0];
                            }
                            $field_error = str_replace('{2}',is_array($field_ext_datas)?implode(',',$field_ext_datas):$field_ext_datas,$field_error);


                            if($field_validate_mode & self::VALIDATE_CLIENT){
                                //如果是客户端验证
                                $arr=array($field_rule,$field_error,$field_ext_datas);
                                $arr_rules[]=$arr;

                            }
                        }
                    }
                    $validate_clients[$field]=array('title'=>$field_text,'rules'=>$arr_rules);

                }
                return $validate_clients;
            }else{
                //没有验证规则
                return $validate_clients;
            }
        }catch (\Exception $err){
            throw new \cn\eunionz\exception\UploadException($this->getLang('error_validation_title'),$this->getLang('error_validation_exception',array($err->getMessage())));
            //没有验证规则
            return $validate_clients;
        }
    }

}
