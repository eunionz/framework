<?php
/**
 * EUnionZ PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\redis;


defined('APP_IN') or exit('Access Denied');

/**
 * Redis，工具类
 * Class Redis
 */
class Redis extends \com\eunionz\core\Plugin
{

    private $host='127.0.0.1';//redis主机
    private $port=6379;//redis端口
    private $password='123456';//redis密码
    private $db=0;//redis数据库

    private $handler=null;

    public function __construct(){

    }

    public function init($server_cfg=null){
        if(!$server_cfg){
            $server_cfg=$this->getConfig('params','APP_PROXY_REDIS_SERVER');
        }
        $this->host=$server_cfg['server'];
        $this->port=$server_cfg['port'];
        $this->password=$server_cfg['password'];
        $this->db=$server_cfg['dbname'];

        if(!$this->handler){
            $this->handler = new \Redis();
            if(!$this->handler->connect($this->host,$this->port)){
                die('Redis 服务器连接失败，请检查Redis 服务器【' . $this->host.':' . $this->port .'】是否正常运行!');
            }
            if($this->password){
                $this->handler->auth($this->password) or die('Redis 服务器【' . $this->host.':' . $this->port .'】连接失败，请提供正确的密码!');
            }
            $this->db=intval($this->db);
            $this->handler->select($this->db);
        }
        return $this;
    }


    /**
     * 关闭redis占用的资源
     *
     * @param int $flag 关闭选择 0:关闭
     * @return boolean
     */
    public function close(){
        $this->handler->quit();
        $this->handler=null;
        return true;
    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_hSet($key,$hash_key, $value){
        return $this->handler->hSet($key,$hash_key, $value);
    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_hSetNx($key,$hash_key, $value){
        return $this->handler->hSetNx($key,$hash_key, $value);
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hGet($key,$hash_key){
        if($this->handler->exists($key)){
            return $this->handler->hGet($key,$hash_key);
        }else{
            return '';
        }

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hGetAll($key){
        return $this->handler->hGetAll($key);
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hDel($key,$hash_key){
        return $this->handler->hDel($key,$hash_key);

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hLen($key){
        return $this->handler->hLen($key);

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hExists($key,$hash_key){
        return $this->handler->hExists($key,$hash_key);

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hKeys($key){
        return $this->handler->hKeys($key);

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hVals($key){
        return $this->handler->hVals($key);

    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_set($key, $value, $expire=0){
        // 永不超时
        if($expire == 0){
            $ret = $this->handler->set($key, $value);
        }else{

            $ret = $this->handler->setex($key, $expire, $value);
        }

        return $ret;
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_get($key){
        if($this->handler->exists($key)){
            return $this->handler->get($key);
        }else{
            return '';
        }

    }

    /**
     * 查找key
     *
     * @prefix string 前缀
     * @key string key
     * @return array
     */
    public function  redis_keys($prefix='',$key=''){
        // $key => "key1" || array('key1','key2')
        if($prefix){
            if($key){
                return $this->handler->keys($prefix.$key);
            }else{
                return $this->handler->keys($prefix.'*');
            }
        }else{
            if($key){
                return $this->handler->keys($key);
            }else{
                return $this->handler->keys('*');
            }
        }
    }


    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function  redis_delete($key){
        // $key => "key1" || array('key1','key2')
        return $this->handler->delete($key);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function  redis_incr($key,$default=1){
        if($default == 1){
            return $this->handler->incr($key);
        }else{
            return $this->handler->incrBy($key, $default);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function  redis_decr($key,$default=1){
        if($default == 1){
            return $this->handler->decr($key);
        }else{
            return $this->handler->decrBy($key, $default);
        }
    }

    /**
     * 添空所有缓存
     *
     * @return boolean
     */
    public function  clear(){
        return $this->handler->flushdb();
    }




}