<?php
/**
 * Eunionz PHP Framework Security component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\session;


defined('APP_IN') or exit('Access Denied');

/**
 *
 * 安全
 *
 * 基本数据过滤、加密解密字符串、表单认证。

 */
class Session extends \cn\eunionz\core\Component implements \SessionHandlerInterface
{

    private $table= 'sessions.class';
    private $SESS_LIFE=180;

    private $conn;
    public static function getInstance(){
        static $instance=null;
        if($instance==null){
            $instance=new Session();
        }
        return $instance;
    }

    public function __destruct(){
        //
    }

    function __construct(){
        $this->table = $this->getConfig('app','APP_SESSION_TABLE_NAME');
        $this->SESS_LIFE=get_cfg_var("session.gc_maxlifetime");//得到session的最大有效期。
        session_set_save_handler(
            array($this,"open"),
            array($this,"close"),
            array($this,"read"),
            array($this,"write"),
            array($this,"destroy"),
            array($this,"gc")
        );
    }

    function open($save_path, $session_name)
    {
        return true;
    }
    function close()
    {
        return true;
    }
    function read($key)
    {
        try{
            $sql = "SELECT `sess_value` FROM `" . $this->table  . "` WHERE sess_shop_id=" . $this->getConfig('app','SHOP_ID') ." AND sess_key='" . str_replace("'","\\'",$key) ."' AND sess_expiry > ".time();
            $rs = $this->loadComponent('db')->query($sql);
            if($rs){
                //$sql="UPDATE `" . $this->table  . "` SET `sess_expiry`=" . time() ."  WHERE sess_shop_id=" . $this->getConfig('app','SHOP_ID') ." AND `sess_key`='{$key}'";
                //$this->loadComponent('db')->exec($sql);
                return $rs[0]['sess_value'];
            }else{
                return '';
            }
        }catch (\Exception $err){
            $this -> loadCore('log') -> write(APP_ERROR, $err -> getMessage());
            //throw $err;
        }

        return '';

    }
    function write($key, $value)
    {
        try{
            $expiry=time()+$this->SESS_LIFE;
            $sql="REPLACE INTO `" . $this->table  . "` SET `sess_key`='" . str_replace("'","\\'",$key) ."', `sess_shop_id`=" .  $this->getConfig('app','SHOP_ID') ." , `sess_expiry`={$expiry}, `sess_value`='" . str_replace("'","\\'",$value) ."'";
            return $this->loadComponent('db')->exec($sql);
        }catch (\Exception $err){
            $this -> loadCore('log') -> write(APP_ERROR, $err -> getMessage());
            //throw $err;
        }
        return false;
    }

    function destroy($key)
    {
        try{
            $sql = "DELETE FROM `" . $this->table  . "` WHERE `sess_key` ='" . str_replace("'","\\'",$key) ."' AND `sess_shop_id`=" .  $this->getConfig('app','SHOP_ID');
            return $this->loadComponent('db')->exec($sql);
        }catch (\Exception $err){
            $this -> loadCore('log') -> write(APP_ERROR, $err -> getMessage());
            //throw $err;
        }
        return false;
    }
    /*********************************************
     * WARNING - You will need to implement some *
     * sort of garbage collection routine here.  *
     *********************************************/
    function gc($maxlifetime)
    {
        try{
            $sql="DELETE FROM `" . $this->table  . "` WHERE `sess_expiry` < ".time() . ' AND `sess_shop_id`=' .  $this->getConfig('app','SHOP_ID');
            return $this->loadComponent('db')->exec($sql);
        }catch (\Exception $err){
            //throw $err;
        }
        return false;
    }


    public function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }
//session_set_save_handler("open", "close", "read", "write", "destroy", "gc");
//session_start();

}

?>