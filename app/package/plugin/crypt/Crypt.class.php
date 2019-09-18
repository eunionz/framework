<?php
/**
 * EUnionZ PHP Framework Crypt Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\crypt;


defined('APP_IN') or exit('Access Denied');
/**
 * Class Crypt 加密解密类
 */
class Crypt extends \cn\eunionz\core\Plugin{
    private $crypt_key='Ajd$29*34_16BkA';//密钥
    public function __construct(){
        $this->crypt_key= $this->getConfig('app',"APP_KEY");
    }

    public function init($crypt_key){
        $this->crypt_key= $crypt_key;
    }

    public function encrypt($txt){
        $encrypt_key=$this->getConfig('app','ENCRYPT_KEY');
        $ctr=0;
        $tmp='';
        for($i=0;$i<strlen($txt);$i++){
            $ctr=$ctr==strlen($encrypt_key)?0:$ctr;
            $tmp.=$encrypt_key[$ctr].($txt[$i]^$encrypt_key[$ctr++]);
        }
        return base64_encode(self::__key($tmp,$this->crypt_key));
    }

    public function decrypt($txt){
        $txt=self::__key(base64_decode($txt),$this->crypt_key);
        $tmp='';
        for($i=0;$i<strlen($txt);$i++){
            $md5=$txt[$i];
            $tmp.=$txt[++$i]^$md5;
        }
        return $tmp;
    }
    private function __key($txt,$encrypt_key){
        $encrypt_key=md5($encrypt_key);
        $ctr=0;
        $tmp='';
        for($i=0;$i<strlen($txt);$i++){
            $ctr=$ctr==strlen($encrypt_key)?0:$ctr;
            $tmp.=$txt[$i]^$encrypt_key[$ctr++];
        }
        return $tmp;
    }
    public function __destruct(){
        $this->crypt_key=NULL;
    }
}
