<?php
/**
 * EUnionZ PHP Framework Xsmarty_insert Plugin class
 * Created by PhpStorm.
 * User: zhaosigui (1060656096@qq.com)
 * Date: 2016-04-05
 * Time: 15:33
 */

namespace package\plugin\version_limit;


defined('APP_IN') or exit('Access Denied');

/**
 * Xsmarty insert
 * Created by liulin
 **/

class Version_limit extends \cn\eunionz\core\Plugin
{
    public $message = '';


    public function alert($message,$back=''){
        header("Content-Type: text/html;charset=utf-8");
        if($back){
            $back= $back;
        }else if(!empty($_SERVER['HTTP_REFERER'])){
            $back= $_SERVER['HTTP_REFERER'];
        }
/*         echo <<<str

<!DOCTYPE html>
<html>
<head>
   <title>警告</title>
   <link href="/view/admin/assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
   <script src="/view/admin/assets/plugins/jquery/jquery-1.9.1.min.js"></script>
   <script src="/view/admin/assets/plugins/bootstrap/js/bootstrap.min.js"></script>
</head>
<body>



<!-- 模态框（Modal） -->
<div class="modal fade in" id="myModal" tabindex="1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false" style="display: block;">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                  ×
            </button>
            <h4 class="modal-title" id="myModalLabel">
   
            </h4>
         </div>
         <div class="modal-body">
            $message
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">关闭
            </button>
         </div>
      </div><!-- /.modal-content -->
</div><!-- /.modal -->
</div>
</body>
</html>       
str;
      */          

        $this->loadPlugin('common')->write_message("$message");
        
        return '';
    }

   /**
    * 后台用户数版本限制
    *  path title: 系统管理 ->用户管理
     * url: /admin/user/index.html
    */
    public function get_admin_user($version_config){
       $ret['status']=true;
       $ret['msg']="";
       
        $shop_id = $this->getConfig('shop','SHOP_ID');
        if($shop_id<1){
            return $ret;
        }
        //用户数
        $user_number  = $this->getConfig($version_config,'USER_NUMBER');
//         echo $user_number,'-',$version_config;
//         exit;
        //当前用户使用用户数
        $now_user_number_use_count = $this->loadService('user')->get_user_manager_count( );

        //用户数限制
        if( $user_number >0 &&  $now_user_number_use_count>=$user_number && $shop_id>0 ){
            $has_user_number = $user_number-$now_user_number_use_count;
            $has_user_number = $has_user_number<0 ? 0 : $has_user_number;
            $message ='您所购买的版本用户数'.$user_number.'已创建'.$now_user_number_use_count.'个，还能创建'.$has_user_number.'个！点击确定按钮返回上一级';
            $message = '当前后台版本只支持添加'.$user_number.'个用户';
            $ret['status']=false;
            $ret['msg']=$message;
            //$this->alert($message);
        }
        return $ret;
    }
    
    /**
     * api注册用户限时
     * url: /service/user_vip/registe.html
     * @return 数字已限制,1邮箱，2手机，3用户|0不限制
     */
    public function get_service_User_vip_registe($version_config,$param){
        $this->message = '';
        $shop_id = $this->getConfig('shop','SHOP_ID');
        //基础版，多陪伴直接限制
        $email_mobile_versions = $this->get_email_mobile_versions();
        if($shop_id<1){
            return 0;
        }
        //用户注册类型
        $rigister_limit  = $this->getConfig($version_config,'REGISTER_LIMIT');
        $shop_version = str_replace('version', '', $version_config);
//         echo '<pre>';
//         echo $version_config,"\n";
        
//         echo $this->loadComponent('validation')->vEmail($param['name']);
//         print_r($rigister_limit);
        
//         $this->loadComponent('validation')->vEmail($param['name']);
        if(  $shop_id>0 && isset($rigister_limit['EMAIL']) && $rigister_limit['EMAIL']==0 && isset($param['name']) && $this->loadPlugin('validation')->vEmail($param['name'])){
            $this->message ='本商城已限制不能使用邮箱注册！';     
            return 1;
        }//不限制邮箱
        else if(  $shop_id>0 && isset($rigister_limit['EMAIL']) && $rigister_limit['EMAIL']==1 && isset($param['name']) && $this->loadPlugin('validation')->vEmail($param['name'])){
            //如果开启了邮箱开关
            if(!in_array($shop_version, $email_mobile_versions) && $this->loadService('shop_params')->get_value_by_key('SITE_IS_REGISTER_SEND_VALIDATE_MAIL', $shop_id)==1){
                return 0;
            }   
            $this->message ='本商城已限制不能使用邮箱注册！2='.$this->loadService('shop_params')->get_value_by_key('SITE_IS_REGISTER_SEND_VALIDATE_MAIL', $shop_id);
            return 1;
        }
        
        if(  $shop_id>0 && isset($rigister_limit['MOBILE']) && $rigister_limit['MOBILE']==0 && isset($param['name']) && $this->loadPlugin('validation')->vPhone($param['name'])){
            $this->message ='本商城已限制不能使用手机注册！';
            return 2;
        }//不限制手机注册
        else if(  $shop_id>0 && isset($rigister_limit['MOBILE']) && $rigister_limit['MOBILE']==1 && isset($param['name']) && $this->loadPlugin('validation')->vPhone($param['name'])){
            //如果开启了手机开关
            if(  !in_array($shop_version, $email_mobile_versions) && $this->loadService('shop_params')->get_value_by_key('SITE_IS_REGISTER_SEND_MOBILE_CHECKCODE', $shop_id)==1){
                return 0;
            }
            $this->message ='本商城已限制不能使用手机注册！2='.$this->loadService('shop_params')->get_value_by_key('SITE_IS_REGISTER_SEND_MOBILE_CHECKCODE', $shop_id);
            return 2;
        }
        
        if(  $shop_id>0 && isset($rigister_limit['USERNAME']) && $rigister_limit['USERNAME']==0 && isset($param['name']) && !($this->loadPlugin('validation')->vEmail($param['name'])) && !( $this->loadPlugin('validation')->vPhone($param['name']) ) ){
            $this->message ='本商城已限制不能使用用户名注册！';     
            return 3;
        }

        return 0;
    }
    
/**
     * api注册用户限时
     * url: /service/user_vip/registe.html
     * @return 数字已限制, // 3.密保问题找回   2.邮箱找回  1.手机号方式找回
     */
    public function get_service_Find_pwd_start($version_config,$type){
        $this->message = '';
        $shop_id = $this->getConfig('shop','SHOP_ID');
        if($shop_id<1){
            return 0;
        }
        //用户注册类型
//         $rigister_limit  = $this->getConfig($version_config,'REGISTER_LIMIT');
//         echo '<pre>';
//         echo $version_config,"\n";
//         echo $shop_id,"\n";
//         echo $rigister_limit['EMAIL'],"\n";
//         echo $type,"\n";

        
//         if(  $shop_id>0 && isset($rigister_limit['EMAIL']) && $rigister_limit['EMAIL']==0  && $type==2 ){
//             $this->message ='本商城已限制不能使用邮箱找回！';     
//             return 1;
//         }
        
//         if(  $shop_id>0 && isset($rigister_limit['MOBILE']) && $rigister_limit['MOBILE']==0 ){
//             $this->message ='本商城已限制不能使用手机找回！';
//             return 2;
//         }
        
//         if(  $shop_id>0 && isset($rigister_limit['USERNAME']) && $rigister_limit['USERNAME']==0 && isset($param['name']) && !($this->loadPlugin('validation')->vEmail($param['name'])) && !( $this->loadPlugin('validation')->vPhone($param['name']) ) ){
//             $this->message ='本商城已限制不能使用用户名注册！';     
//             return 3;
//         }

        return 0;
    }

    
    /**
     * 后台优惠卡券活动数量限制 0表示不限制 大于0表示限制的个数
     *  path title: 营销管理->优惠卡券
     * url: /admin/shopping_volumes/list.html
     */
    public function get_admin_shopping_volumes($version_config){
         
        $shop_id = $this->getConfig('shop','SHOP_ID');
        if($shop_id<1){
            return true;
        }
        //优惠卡券活动数量限制 0表示不限制 大于0表示限制的个数
        $discount_card  = $this->getConfig($version_config,'DISCOUNT_CARD');

        //当前开启活动数量
        $now_shopping_volumes_open_count = $this->loadService('shopping_volumes')->get_is_open_count();
    
        //用户数限制
        if( $discount_card >0 &&  $now_shopping_volumes_open_count>=$discount_card && $shop_id>0 ){
                        
            $message = '当前后台版本只支持开启'.$discount_card.'个优惠卡券活动';
            $this->message = $message;
            return true;
        }
        return false;
    }
    /**
     * 基础版，和多屏版限制
     * @return multitype:number
     */
    public function get_email_mobile_versions(){
        $version_arr = array(
            1,
            11,
            12,
            13,
            1101,
            1201,
            1301,
        );
        return $version_arr;
    }
}
