<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\cache;


use com\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');

/**
 * 通用缓存类，工具类
 * Class Cache
 */
class Cache extends Component
{

    /**
     * 是否启用缓存
     * @var bool|mixed
     */
    private $is_cache = true;//是否缓存

    /**
     * 缓存类型，目前仅支持 file 和 redis
     * @var mixed|string
     */
    private $cache_type = 'file';//缓存类型,'file','redis'

    /**
     * 缓存驱动数据
     * @var array|mixed
     * private $cache_driver_data=array('cache_dir'=>'');//文件缓存
     * private $cache_driver_data=array('isUseCluster'=>true,'server'=>'127.0.0.1','port'=>6379,'password'=>'123456','dbname'=>1,'add_servers'=>array(array('server' => '127.0.0.1','port' => '6380','password' => '123456',),array('server' => '127.0.0.1','port' => '6381','password' => '123456',),),);   //redis缓存
     */
    private $cache_driver_data = array('cache_dir' => '');//文件缓存

    /**
     * 默认所有缓存过期时间，0 表示永不过期
     * @var int|mixed
     */
    private $cache_life_seconds = 0;

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
     * 刷新可用从链接句柄数组的间隔时间，单位:秒
     * @var int
     */
    private $refresh_available_links_interval = 60;

    /**
     * 默认的hash名称
     * @var string
     */
    private $_default_preffix_name = 'default_hash';

    /**
     * 缓存构造函数
     * Cache constructor.
     * @param null $cfg 如果为空使用当前应用下的cache.config.php配置文件中的缓存配置
     */
    public function __construct($cfg = null)
    {
        $this->init($cfg);
    }

    /**
     * 初始化函数
     * Cache constructor.
     * @param null $cfg 如果为空使用当前应用下的cache.config.php配置文件中的缓存配置
     */
    public function init($cfg = null)
    {
        if ($cfg && is_array($cfg)) {
            $this->is_cache = $cfg['is_cache'];
            $this->cache_type = $cfg['cache_type'];
            $this->cache_life_seconds = $cfg['cache_life_seconds'];
            $this->cache_driver_data = $cfg['cache_driver_data'];
        } else {
            $cfg = $this->getConfig('cache');
            $this->is_cache = $cfg['is_cache'];
            $this->cache_type = $cfg['cache_type'];
            $this->cache_life_seconds = $cfg['cache_life_seconds'];
            $this->cache_driver_data = $cfg['cache_driver_data'];
        }

        if ($this->cache_type == 'file') {
            if (stripos($this->cache_driver_data['cache_dir'], "%runtime%") !== false) {
                $this->cache_driver_data['cache_dir'] = str_ireplace("%runtime%", $this->getContext()->getAppRuntimeRealPath(), $this->cache_driver_data['cache_dir']);
            } else {
                $this->cache_driver_data['cache_dir'] = $this->getContext()->getAppStorageRealPath() . $this->cache_driver_data['cache_dir'];
            }
            if (!file_exists($this->cache_driver_data['cache_dir'])) {
                @mkdir($this->cache_driver_data['cache_dir'], 0777, true);
            }
        } else if ($this->cache_type == 'redis') {
            $this->available_link_ids = array();
            $this->isUseCluster = $this->cache_driver_data["redis_servers"]['isUseCluster'];
            $this->isPersistent = $this->cache_driver_data["redis_servers"]['isPersistent'];
            $connect_timeout = intval($this->cache_driver_data["redis_servers"]['connect_timeout']);

            $connect_func = $this->isPersistent ? "connect" : "pconnect";

//            $this->master_link_id = new \Swoole\Coroutine\Redis();
            $this->master_link_id = new \Redis();
            if (!$this->master_link_id->$connect_func($this->cache_driver_data["redis_servers"]['add_servers'][0]['server'], $this->cache_driver_data["redis_servers"]['add_servers'][0]['port'], $connect_timeout)) {
                die($this->getLang('error_main_redis_connect', $this->cache_driver_data["redis_servers"]['add_servers'][0]['server'], $this->cache_driver_data["redis_servers"]['add_servers'][0]['port']));
            }
            if ($this->cache_driver_data["redis_servers"]['add_servers'][0]['password']) {
                $this->master_link_id->auth($this->cache_driver_data["redis_servers"]['add_servers'][0]['password']) or die($this->getLang('error_main_redis_auth', $this->cache_driver_data["redis_servers"]['add_servers'][0]['server'], $this->cache_driver_data["redis_servers"]['add_servers'][0]['port']));
            }
            if (isset($this->cache_driver_data["redis_servers"]['dbname'])) {
                $this->cache_driver_data["redis_servers"]['dbname'] = intval($this->cache_driver_data["redis_servers"]['dbname']);
            } else {
                $this->cache_driver_data["redis_servers"]['dbname'] = 0;
            }
            $this->master_link_id->select($this->cache_driver_data["redis_servers"]['dbname']);
            if ($this->isUseCluster) {
                //启用Redis读写分离集群
                $this->available_link_ids[0] = $this->master_link_id;
                if ($this->cache_driver_data["redis_servers"]['add_servers'] && is_array($this->cache_driver_data["redis_servers"]['add_servers'])) {
                    for ($index = 1; $index < count($this->cache_driver_data["redis_servers"]['add_servers']); $index++) {
                        $server = $this->cache_driver_data["redis_servers"]['add_servers'][$index];
                        if ($server && is_array($server)) {
//                            $slave_redis =new \Swoole\Coroutine\Redis();
                            $slave_redis = new \Redis();
                            if ($slave_redis) {
                                if ($slave_redis->$connect_func($server['server'], $server['port'], $connect_timeout)) {
                                    if ($slave_redis && $server['password']) {
                                        if ($slave_redis->auth($server['password'])) {
                                            $slave_redis->select($this->cache_driver_data["redis_servers"]['dbname']);
                                            $this->available_link_ids[$index] = $slave_redis;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            die($this->getLang('error_cache_cache_type', $this->cache_type));
        }
        return $this;
    }


    public function setCacheByKey($prefix, $key, $data, $expires = null, $save_mode = 'serialize')
    {
        if (!$this->is_cache) {//没有开启缓存
            return false;
        }
        if ($expires === null) {
            $expires = $this->cache_life_seconds;
        } else {
            $expires = intval($expires);
        }

        switch ($this->cache_type) {
            case 'file':
                if ($prefix) {
                    if (!file_exists($this->cache_driver_data['cache_dir'] . APP_DS . $prefix)) @mkdir($this->cache_driver_data['cache_dir'] . APP_DS . $prefix, 0777);
                    $file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '.cache.php';
                    $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '_expires.cache.php';
                } else {
                    $file = $this->cache_driver_data['cache_dir'] . APP_DS . $key . '.cache.php';
                    $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $key . '_expires.cache.php';
                }
                if ($data) {
                    if (strtolower($save_mode) == 'serialize') {
                        file_put_contents($file, serialize($data));
                    } else {
                        file_put_contents($file, json_encode($data));
                    }
                    file_put_contents($expires_file, $expires);
                } else {
                    if (file_exists($file)) @unlink($file);
                    if (file_exists($expires_file)) @unlink($expires_file);
                }
                return true;
                break;
            case 'redis':
                if ($prefix) {
                    return $this->redis_hset($prefix, $key, $data, $expires, $save_mode);
                } else {
                    return $this->redis_hset($this->_default_preffix_name, $key, $data, $expires, $save_mode);
                }
                break;
            default://不支持的缓存驱动
                return false;
                break;
        }

        return false;

    }


    public function setCache($prefix, $keys, $data, $expires = null, $save_mode = 'serialize')
    {
        if (!$this->is_cache) {//没有开启缓存
            return false;
        }
        if (is_array($keys)) {
            $key = $this->array2string($keys);
        } else {
            $key = $keys;
        }
        $key = md5($key);

        return $this->setCacheByKey($prefix, $key, $data, $expires, $save_mode);

    }

    public function getCacheByKey($prefix, $key, $save_mode = 'serialize')
    {
        if (!$this->is_cache) {//没有开启缓存
            return null;
        }
        @ini_set("memory_limit", "1024M");
        switch ($this->cache_type) {
            case 'file':
                if ($prefix) {
                    $file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '.cache.php';
                    $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '_expires.cache.php';
                } else {
                    $file = $this->cache_driver_data['cache_dir'] . APP_DS . $key . '.cache.php';
                    $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '_expires.cache.php';
                }
                $expires = $this->cache_life_seconds;
                if (file_exists($expires_file)) {
                    $expires = intval(file_get_contents($expires_file));
                }
                if (file_exists($file)) {
                    if ($expires === 0 || ($expires > 0 && ((time() - filemtime($file)) <= $expires))) {
                        if (strtolower($save_mode) == 'serialize') {
                            return unserialize(file_get_contents($file));
                        } else {
                            return json_decode(file_get_contents($file), true);
                        }

                    }
                }
                break;
            case 'redis':
                if ($prefix) {
                    return $this->redis_hget($prefix, $key, $save_mode);
                } else {
                    return $this->redis_hget($this->_default_preffix_name, $key, $save_mode);
                }
                break;
            default://不支持的缓存驱动
                return false;
                break;
        }
        return null;

    }


    public function getCache($prefix, $keys, $save_mode = 'serialize')
    {
        if (!$this->is_cache) {//没有开启缓存
            return null;
        }

        if (is_array($keys)) {
            $key = $this->array2string($keys);
        } else {
            $key = $keys;
        }
        $key = md5($key);

        return $this->getCacheByKey($prefix, $key, $save_mode);
    }


    public function clearCacheByKey($prefix = '', $key = '')
    {
        switch ($this->cache_type) {
            case 'file':
                if ($prefix) {
                    if ($key) {
                        $file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '.cache.php';
                        $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key . '_expires.cache.php';
                        if (file_exists($file)) @unlink($file);
                        if (file_exists($expires_file)) @unlink($expires_file);
                        return true;
                    } else {
                        $dir = $this->cache_driver_data['cache_dir'] . APP_DS . $prefix;
                        if (file_exists($dir)) return $this->delFileUnderDir($dir);
                        return true;
                    }
                } else {
                    if ($key) {
                        $file = $this->cache_driver_data['cache_dir'] . APP_DS . $key . '.cache.php';
                        $expires_file = $this->cache_driver_data['cache_dir'] . APP_DS . $key . '_expires.cache.php';
                        if (file_exists($file)) @unlink($file);
                        if (file_exists($expires_file)) @unlink($expires_file);
                        return true;
                    } else {
                        $dir = $this->cache_driver_data['cache_dir'];
                        if (file_exists($dir)) return $this->delFileUnderDir($dir);
                        return true;
                    }
                }
                break;
            case 'redis':
                if ($prefix) {
                    //有前缀，检查该前缀与该key是否存在于关联表中，如果不存在，获取失败，并删除该前缀对应的key
                    if ($key) {
//                        return $this->redis_remove($prefix . '_' . $key);
                        return $this->redis_hdel($prefix, $key);
                    } else {
                        return $this->redis_remove($prefix);
                    }
                    return true;
                } else {
                    //没有前缀
                    if ($key) {
                        return $this->redis_hdel($this->_default_preffix_name, $key);
                    } else {
                        return $this->redis_clear();
                    }
                }
                break;
            default://不支持的缓存驱动
                return false;
                break;
        }
        return false;

    }


    public function clearCache($prefix = '', $keys = array())
    {
        $key = "";
        if ($keys) {
            if (is_array($keys)) {
                $key = $this->array2string($keys);
            } else {
                $key = $keys;
            }
            $key = md5($key);
        }

        return $this->clearCacheByKey($prefix, $key);
    }


    /**
     * 关闭缓存占用的资源
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2)
    {
        switch ($this->cache_type) {
            case 'file':
                return true;
                break;
            case 'redis':
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
                break;
            default://不支持的缓存驱动
                return false;
                break;
        }
        return false;
    }


    /* ===================redis 以下方法 =================== */

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @return redis object
     */
    public function getRedis($isMaster = true)
    {
        if ($isMaster) {
            return $this->master_link_id;
        }
        if (!$this->isUseCluster) {
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
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_set($key, $value, $expire = 0)
    {
        // 永不超时
        if ($expire == 0) {
            $ret = $this->getRedis()->set($key, serialize($value));
        } else {
            $ret = $this->getRedis()->setex($key, $expire, serialize($value));
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_get($key)
    {
        if ($this->getRedis(false)->exists($key)) {
            return unserialize($this->getRedis(false)->get($key));
        } else {
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
    public function redis_keys($prefix = '', $key = '')
    {
        if ($prefix) {
            if ($key) {
                return $this->getRedis(false)->keys($prefix . $key);
            } else {
                return $this->getRedis(false)->keys($prefix . '*');
            }
        } else {
            if ($key) {
                return $this->getRedis(false)->keys($key);
            } else {
                return $this->getRedis(false)->keys('*');
            }
        }
    }


    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function redis_remove($key)
    {
        return $this->getRedis()->delete($key);
    }


    /**
     * 写hash缓存
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间，单位：秒
     */
    public function redis_hset($prefix, $key, $value, $expire = 0, $save_mode = 'serialize')
    {
        // 永不超时
        if (strtolower($save_mode) == 'serialize') {
            $ret = $this->getRedis()->hset($prefix, $key, serialize($value));
        } else {
            $ret = $this->getRedis()->hset($prefix, $key, json_encode($value));
        }

        if ($expire > 0) {
            $ret = $this->getRedis()->expireAt($prefix . ':' . $key, time() + $expire);
        }
        return $ret;
    }

    /**
     * 读hash缓存
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     */
    public function redis_hget($prefix, $key, $save_mode = 'serialize')
    {
        $value = $this->getRedis(false)->hget($prefix, $key);
        if ($value) {
            if (strtolower($save_mode) == 'serialize') {
                return unserialize($value);
            } else {
                return json_decode($value, true);
            }
        } else {
            return '';
        }
    }

    /**
     * 获取hash中所有的key
     *
     * @param string $prefix hash名称
     */
    public function redis_hkeys($prefix)
    {
        return $this->getRedis(false)->hkeys($prefix);
    }


    /**
     * 随机顺序获取hash中所有的value
     *
     * @param string $prefix hash名称
     */
    public function redis_hvals($prefix)
    {
        return $this->getRedis(false)->hvals($prefix);
    }

    /**
     * 随机顺序获取hash中所有的key及value
     *
     * @param string $prefix hash名称
     */
    public function redis_hgetall($prefix)
    {
        return $this->getRedis(false)->hgetall($prefix);
    }

    /**
     * 获取hash中所有key的数量
     *
     * @param string $prefix hash名称
     */
    public function redis_hlen($prefix)
    {
        return $this->getRedis(false)->hlen($prefix);
    }

    /**
     * 获取hash中所有key的数量
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     */
    public function redis_hdel($prefix, $key)
    {
        return $this->getRedis()->hdel($prefix, $key);
    }


    /**
     * 判断hash中是否存在key
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     */
    public function redis_hexists($prefix, $key)
    {
        return $this->getRedis(false)->hexists($prefix, $key);
    }

    /**
     * 针对hash中某key增加一个整数值
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     * @param int $value 增加的增数
     */
    public function redis_hincrby($prefix, $key, $value)
    {
        return $this->getRedis()->hincrby($prefix, $key, $value);
    }

    /**
     * 针对hash中某key增加一个小数值
     *
     * @param string $prefix hash名称
     * @param string $key 缓存KEY
     * @param int $value 增加的小数值
     */
    public function redis_hincrbyfloat($prefix, $key, $value)
    {
        return $this->getRedis()->hincrbyfloat($prefix, $key, $value);
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
     * 添空所有缓存
     *
     * @return boolean
     */
    public function redis_clear()
    {
        return $this->getRedis()->flushdb();
    }

    /* ===================redis 以上私有方法 =================== */


    //循环目录下的所有文件
    public function delFileUnderDir($dirName)
    {
        if (file_exists($dirName) && $handle = opendir($dirName)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    if (is_dir("$dirName/$item")) {
                        $this->delFileUnderDir("$dirName/$item");
                        @rmdir("$dirName/$item");
                    } else {
                        @unlink("$dirName/$item");
                    }
                }
            }
            closedir($handle);
            return true;
        }
        return false;
    }

    public function array2string($keys)
    {

//        $s="";
//        foreach($keys  as $k=>$v){
//            $s .= $k;
//            if(is_array($v)){
//                $s .= $this->array2string($v);
//            }else{
//                $s .=$v;
//            }
//        }
        return serialize($keys);
    }


}