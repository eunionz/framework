<?php
/**
 * EUnionZ PHP Framework Token Plugin class
 * Created by PhpStorm.
 * User: liuqiang
 * Date: 16-4-27
 * Time: 上午11:56
 */
namespace package\plugin\token;

defined('APP_IN') or exit('Access Denied');

class Token extends \cn\eunionz\core\Plugin
{

    /**
     * 设置默认的加密key
     * @var str
     */
    private $key = "ilanhai~4`-*(#65";

    /**
     * 设置默认加密向量
     * @var str
     */
    private $iv = 'ilanhai~4`-*(#65';

    /**
     * 设置加密算法
     * @var str
     */
    private $cipher = MCRYPT_RIJNDAEL_128;

    /**
     * 设置加密模式
     * @var str
     */
    private $mode = MCRYPT_MODE_CBC;

    /**
     * 对内容加密，注意此加密方法中先对内容使用padding pkcs7，然后再加密。
     * @param str $content    需要加密的内容
     * @return str 加密后的密文
     */
    private function encrypt($content){
        if(empty($content)){
            return null;
        }
        $srcdata = $content;
        $block_size = mcrypt_get_block_size($this->cipher, $this->mode);
        $padding_char = $block_size - (strlen($content) % $block_size);
        $srcdata .= str_repeat(chr($padding_char),$padding_char);
        return mcrypt_encrypt($this->cipher, $this->key, $srcdata, $this->mode, $this->iv);
    }

    /**
     * 对内容解密，注意此加密方法中先对内容解密。再对解密的内容使用padding pkcs7去除特殊字符。
     * @param String $content    需要解密的内容
     * @return String 解密后的内容
     */
    private function decrypt($content){
        if(empty($content)){
            return null;
        }
        $content = mcrypt_decrypt($this->cipher, $this->key, $content, $this->mode, $this->iv);
        $block = mcrypt_get_block_size($this->cipher, $this->mode);
        $pad = ord($content[($len = strlen($content)) - 1]);
        return substr($content, 0, strlen($content) - $pad);
    }

    /**
     * 创建token
     * @param int $shop_id  店铺id
     * @param string $uid  用户id
     * @return mixed   false--失败   varchar 生成的token
     */
    public function get_token($data)
    {
        return base64_encode($this->encrypt($data));
    }

    /**
     * 创建token
     * @param int $sid  店铺id
     * @return mixed   false--失败   varchar 生成的token
     */
    public function check_token($data)
    {
        return $this->decrypt(base64_decode($data));
    }
}