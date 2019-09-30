<?php
/**
 * EUnionZ PHP Framework Crypt Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\cryptaes;


defined('APP_IN') or exit('Access Denied');
/**
 * Class CryptAES 128 - CBC 加密解密类
 */
class Cryptaes extends \cn\eunionz\core\Plugin{
    protected $key = '';
    protected $iv = '';
    public function __construct(){
        $this->iv = substr(self::getConfig('app','ENCRYPT_KEY'),0,16);
        $this->key =substr(self::getConfig('app',"APP_KEY"),0,16);
    }

    public function init($secret_key , $iv){
        $this->iv = $iv;
        $this->key = $secret_key;
    }

    public static function _encrypt($input, $key, $iv) {
        $localIV = $iv;
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $key, $localIV);
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $input = Cryptaes::pkcs5_pad($input, $size);
        $data = mcrypt_generic($module, $input);

        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        $data = base64_encode($data);
        return $data;
    }

    private static function pkcs5_pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    public static function _decrypt($sStr, $key, $iv) {
        $localIV = $iv;
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $key, $localIV);
        $encryptedData = base64_decode($sStr);
        $encryptedData = mdecrypt_generic($module, $encryptedData);

        $dec_s = strlen($encryptedData);
        $padding = ord($encryptedData[$dec_s-1]);
        $decrypted = substr($encryptedData, 0, -$padding);

        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        if(!$decrypted){
            throw new \Exception("Decrypt Error,Please Check SecretKey");
        }
        return $decrypted;
    }

    /**
     * @param $str
     * @return string
     */
    public function encrypt($str)
    {
        if(version_compare(PHP_VERSION, '7.1', '<')){
            return self::_encrypt($str,$this->key,$this->iv);
        }else{
            if(function_exists('openssl_encrypt')){
                return base64_encode(openssl_encrypt($str, 'aes-128-cbc', $this->key, OPENSSL_RAW_DATA, $this->iv));
            }else{
                die("Please load openssl extension.");
            }
        }
    }

    public function decrypt($str)
    {
        if(version_compare(PHP_VERSION, '7.1', '<')){
            return self::_decrypt($str,$this->key,$this->iv);
        }else{
            if(function_exists('openssl_decrypt')){
                return openssl_decrypt(base64_decode($str), 'aes-128-cbc', $this->key, OPENSSL_RAW_DATA, $this->iv);
            }else{
                die("Please load openssl extension.");
            }
        }
    }

}
