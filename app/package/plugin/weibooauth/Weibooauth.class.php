<?php
/**
 * EUnionZ PHP Framework Weibooauth Plugin class
 * Weibooauth 插件类
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\weibooauth;


defined('APP_IN') or exit('Access Denied');

class Weibooauth extends \cn\eunionz\core\Plugin
{
    private $weibo;

    public function __construct()
    {
        include_once( 'config.php' );
        include_once( 'saetv2.ex.class.php' );
        $this->weibo = new \SaeTOAuthV2( WB_AKEY , WB_SKEY );
    }

    public function login()
    {
        return $this->weibo->getAuthorizeURL( WB_CALLBACK_URL );
    }

    public function clear_login(){
        $SESSION = ctx()->session();
        if(isset($SESSION['token']) && isset($SESSION['token']['access_token']) ){
            include_once( 'config.php' );
            include_once( 'saetv2.ex.class.php' );
            $this->weibo = new \SaeTOAuthV2( WB_AKEY , WB_SKEY );
            $this->weibo->revokeoauth2($SESSION['token']['access_token']);
            ctx()->session('token' , null);
            setcookie( 'weibojs_'.$this->weibo->client_id, '');
        }
    }

    public function callback(){
        include_once( 'config.php' );
        include_once( 'saetv2.ex.class.php' );
        $this->weibo = new \SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $token="";
        $GET = ctx()->get();
        if (isset($GET['code'])) {
            $keys = array();
            $keys['code'] = $GET['code'];
            $keys['redirect_uri'] = WB_CALLBACK_URL;
            try {
                $token = $this->weibo->getAccessToken( 'code', $keys ) ;

            } catch (\OAuthException $e) {

            }
        }

        if ($token) {
            ctx()->session('token' , $token);
            //setcookie( 'weibojs_'.$this->weibo->client_id, http_build_query($token) );
            //授权完成
            $SESSION = ctx()->session();

            $c = new \SaeTClientV2( WB_AKEY , WB_SKEY , $SESSION['token']['access_token'] );
            $ms  = $c->home_timeline(); // done
            $uid_get = $c->get_uid();
            if(isset($uid_get['uid'])){
                $uid = $uid_get['uid'];

                $user_info = $c->show_user_by_id( $uid);//根据ID获取用户等基本信息
                return array('uid'=>$uid,'nickname'=>$user_info['screen_name'],'face'=>isset($user_info['profile_image_url'])?$user_info['profile_image_url']:'');
            }else{
                return null;
            }
        } else {
            //授权失败
            return null;
        }
    }


}
