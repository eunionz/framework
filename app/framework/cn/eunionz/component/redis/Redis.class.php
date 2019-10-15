<?php
/**
 * EUnionZ PHP Framework Cache Component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\redis;


use cn\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');

/**
 * Redis，工具类
 * Class Redis
 */
class Redis extends Component
{

    /**
     * 主链接句柄，仅 redis有效，当使用redis缓存模式时，主链接句柄必须可用
     * @var null
     */
    private $master_link_id = null;


    /**
     * redis缓存模式 是否使用 M/S 的读写集群方案
     * @var bool
     */
    private $isUseCluster = false;


    /**
     * 是否持久连接
     * @var bool
     */
    private $isPersistent = true;

    /**
     * 当前使用的链接句柄索引号
     * @var int
     */
    public $curr_link_index = 0;

    /**
     * 可用的从链接句柄数组(包括主链接句柄索引永远为0，所有可用的从链接句柄，如果从链接失败则将从可用的链接句柄中删除)
     * @var array
     */
    private $available_link_ids = array();//[0=>object 1=>object]

    /**
     * @var
     * array(
     *  'isUseCluster'=>true,              //是否启用主从配置(主用于读写，从仅读，并随机选择主从进行读)
     *  'isPersistent'=>true,              //是否启用持久链接
     *  'connect_timeout'=>5,              //链接超时时间，单位：秒
     *  'dbname'=>15,                         //主从redis服务器选择的数据库编号
     *  'add_servers'=>array(               //配置从redis服务器
     *      array(//主(写)服务器
     *          'server' => '127.0.0.1',              //从redis服务器地址或域名
     *          'port' => '6379',                //从redis服务器端口
     *          'password' => '123456',            //从redis密码
     *      ),
     *      array(
     *          'server' => '127.0.0.1',              //从redis服务器地址或域名
     *          'port' => '6380',                //从redis服务器端口
     *          'password' => '123456',            //从redis密码
     *      ),
     *      array(
     *          'server' => '127.0.0.1',              //从redis服务器地址或域名
     *          'port' => '6381',                //从redis服务器端口
     *          'password' => '123456',            //从redis密码
     *      ),
     *  )
     * )
     */
    private $config = array();

    /**
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     *
     * @param array $config Redis服务器配置
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($config = null)
    {
        if ($config) {
            $this->config = $config;
        }

        $this->available_link_ids = array();
        $this->isUseCluster = $this->config['isUseCluster'];
        $this->isPersistent = $this->config['isPersistent'];
        $connect_timeout = intval($this->config['connect_timeout']);

        $connect_func = $this->isPersistent ? "pconnect" : "connect";

//            $this->master_link_id = new \Swoole\Coroutine\Redis();
        $this->master_link_id = new \Redis();
        if (!$this->master_link_id->$connect_func($this->config['add_servers'][0]['server'], $this->config['add_servers'][0]['port'], $connect_timeout)) {
            die("Master redis server[" . $this->config['add_servers'][0]['server'] . ":" .  $this->config['add_servers'][0]['port'] . "] connect fial.");
        }
        if ($this->config['add_servers'][0]['password']) {
            $this->master_link_id->auth($this->config['add_servers'][0]['password']) or die("Master redis server[" . $this->config['add_servers'][0]['server'] . ":" .  $this->config['add_servers'][0]['port'] . "] auth fial.");
        }
        if (isset($this->config['dbname'])) {
            $this->config['dbname'] = intval($this->config['dbname']);
        } else {
            $this->config['dbname'] = 0;
        }
        $this->master_link_id->select($this->config['dbname']);
        if ($this->isUseCluster) {
            //启用Redis读写分离集群
            $this->available_link_ids[0] = $this->master_link_id;
            if ($this->config['add_servers'] && is_array($this->config['add_servers'])) {
                for ($index = 1; $index < count($this->config['add_servers']); $index++) {
                    $server = $this->config['add_servers'][$index];
                    if ($server && is_array($server)) {
//                            $slave_redis =new \Swoole\Coroutine\Redis();
                        $slave_redis = new \Redis();
                        if ($slave_redis) {
                            if ($slave_redis->$connect_func($server['server'], $server['port'], $connect_timeout)) {
                                if ($slave_redis && $server['password']) {
                                    if ($slave_redis->auth($server['password'])) {
                                        $slave_redis->select($this->config['dbname']);
                                        $this->available_link_ids[$index] = $slave_redis;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * 关闭连接
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function redis_close($flag = 2)
    {
        switch ($flag) {
            // 关闭 Master
            case 0:
                return $this->master_link_id->quit();
                break;
            // 关闭 Slave
            case 1:
                for ($i = 1; $i < count($this->available_link_ids); $i++) {
                    $this->available_link_ids[$i]->quit();
                }
                return true;
                break;
            // 关闭所有
            case 2:
                $this->master_link_id->quit();
                for ($i = 1; $i < count($this->available_link_ids); $i++) {
                    $this->available_link_ids[$i]->quit();
                }
                return true;
                break;
        }
        return true;
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @return redis object
     */
    public function getRedis($isMaster = true)
    {
        if ($isMaster || !$this->isUseCluster) {
            //单一Redis服务器，直接返回主Redis链接
            return $this->master_link_id;
        }
        $count = count($this->available_link_ids);
        $this->curr_link_index = intval(mt_rand(0, $count - 1));
        return $this->available_link_ids[$this->curr_link_index];
    }

    /**
     * 写缓存
     *
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_set($key, $value, $expire = 0)
    {
        // 永不超时
        if ($expire == 0) {
            $ret = $this->getRedis()->set($key, $value);
        } else {
            $ret = $this->getRedis()->setex($key, $expire, $value);
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_get($key)
    {
        // 是否一次取多个值
        $func = is_array($key) ? 'mGet' : 'get';
        return $this->getRedis(false)->{$func}($key);
    }


    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function redis_setnx($key, $value)
    {
        return $this->getRedis()->setnx($key, $value);
    }

    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function redis_remove($key)
    {
        // $key => "key1" || array('key1','key2')
        return $this->getRedis()->delete($key);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function redis_incr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->incr($key);
        } else {
            return $this->getRedis()->incrBy($key, $default);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function redis_decr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->decr($key);
        } else {
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    /**
     * 添空当前数据库
     *
     * @return boolean
     */
    public function redis_clear()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id, $m = 10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for ($i = 0; $i < $l; $i++) {
            //相加模式HASH
            $h += substr($b, $i * 2, 2);
        }
        $hash = ($h * 1) % $m;
        return $hash;
    }

    /**
     *    lpush
     */
    public function redis_lpush($key, ...$value)
    {
        return $this->getRedis()->lpush($key, ...$value);
    }

    /**
     *    add lpop
     */
    public function redis_lpop($key)
    {
        return $this->getRedis()->lpop($key);
    }

    /**
     * lsize
     */
    public function redis_lsize($key)
    {
        return $this->getRedis(false)->lSize($key);
    }


    /**
     * lrange
     */
    public function redis_lrange($key, $start, $end)
    {
        return $this->getRedis(false)->lrange($key, $start, $end);
    }

    /**
     *    set hash opeation
     */
    public function redis_hset($name, $key, $value)
    {
        return $this->getRedis()->hset($name, $key, $value);
    }

    /**
     *    get hash opeation
     */
    public function redis_hget($name, $key = null)
    {
        if ($key) {
            return $this->getRedis(false)->hget($name, $key);
        }
        return $this->getRedis(false)->hgetAll($name);
    }

    /**
     *    get hash opeation
     */
    public function redis_hgetAll($name)
    {
        return $this->getRedis(false)->hgetAll($name);
    }

    /**
     *    delete hash opeation
     */
    public function redis_hdel($name, $key = null)
    {
        if ($key) {
            return $this->getRedis()->hdel($name, $key);
        }
        return $this->getRedis()->hdel($name);
    }

    /**
     * Transaction start
     */
    public function redis_multi()
    {
        return $this->getRedis()->multi();
    }

    /**
     * Transaction send
     */

    public function redis_exec()
    {
        return $this->getRedis()->exec();
    }

}