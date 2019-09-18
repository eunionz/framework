<?php
/**
 * EUnionZ PHP Framework Qqoauth Plugin class
 *
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\qqoauth;


defined('APP_IN') or exit('Access Denied');


class Qqoauth extends \cn\eunionz\core\Plugin
{
    private $qc;


    public function __construct()
    {
        require_once( "API/qqConnectAPI.php");
        $this->qc = new \QC();


    }

    public function login()
    {
        $this->qc->qq_login();

    }


    public function callback(){

        $acs = $this->qc->qq_callback();
        $oid = $this->qc->get_openid();
        $this->qc = new \QC($acs,$oid);
        $user_info = $this->qc->get_user_info();
        if($user_info){
            return array('uid'=>$oid,'nickname'=>$user_info['nickname'],'face'=>$user_info['figureurl_qq_2']);
        }
        return null;

    }


}
