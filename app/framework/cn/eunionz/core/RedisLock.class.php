<?php
declare(strict_types=1);

namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');


/**
 * 基于 redis 实现分布式锁
 * Class RedisLock
 * @package cn\eunionz\core
 */
class RedisLock extends Kernel
{
    /**
     * Redis锁连接对像(单点)
     * @var null
     */
    private $redis = null;

    /**
     * Redis连接配置
     * @var string
     */
    private $config_name = '';

    /**
     * 分布式锁列表
     * @var array
     */
    private $lockedNames = [];

    /**
     * Redis连接服务器
     * @var string
     */
    private $server = '127.0.0.1';

    /**
     * Redis连接端口
     * @var int
     */
    private $port = 6379;

    /**
     * Redis 连接密码
     * @var string
     */
    private $auth = '';

    /**
     * Redis 连接数据库编号
     * @var int
     */
    private $dbname = 15;


    public function __construct(string $redislock_config_name = 'redislock' ) {
        $this->redis = new \Redis();
        if(empty($redislock_config_name)){
            $this->config_name = 'redislock';
        }else{
            $this->config_name = $redislock_config_name;
        }
        $this->server = self::getConfig($this->config_name , 'server');
        $this->port = self::getConfig($this->config_name , 'port');
        $this->auth = self::getConfig($this->config_name , 'password');
        $this->dbname = self::getConfig($this->config_name , 'dbname');

        if (false === $this->redis->connect($this->server, (int)$this->port)) {
            throw new \Exception("RedisLock(redis:" . $this->server . ":" . $this->port .") connect fail. RedisLock start fail.");
        }
        if ("" !== $this->auth) {
            if (!$this->redis->auth($this->auth)) {
                throw new \Exception("RedisLock(redis:" . $this->server . ":" . $this->port .") auth fail. RedisLock start fail.");
            }
        }
        if (!$this->redis->select($this->dbname)) {
            throw new \Exception("RedisLock(redis:" . $this->server . ":" . $this->port .") select db " .  $this->dbname . " fail. RedisLock start fail.");
        }
    }



    /**
     * 加锁
     * @param [type] $name      锁的标识名
     * @param integer $timeout    循环获取锁的等待超时时间，在此时间内会一直尝试获取锁直到超时，为0表示失败后直接返回不等待
     * @param integer $expire     当前锁的最大生存时间(秒)，必须大于0，如果超过生存时间锁仍未被释放，则系统会自动强制释放
     * @param integer $waitIntervalUs 获取锁失败后挂起再试的时间间隔(微秒)
     * @return [type]         [description]
     */
    public function redis_lock($name, $timeout = 10, $expire = 15, $waitIntervalUs = 100000) {
        if ($name == null) return false;
        //取得当前时间
        $now = time();
        //获取锁失败时的等待超时时刻
        $timeoutAt = $now + $timeout;
        //锁的最大生存时间
        $expireAt = $expire;
        $redisKey = "Lock:{$name}";
        while (true) {
            //将rediskey的最大生存时刻存到redis里，过了这个时刻该锁会被自动释放
            $result = $this->redis->setnx($redisKey, $expireAt);
            if ($result != false) {
                //设置key的失效时间
                $this->redis->expire($redisKey, $expireAt);
                //将锁标志放到lockedNames数组里
                $this->lockedNames[$name] = $expireAt;
                return true;
            }
            //以秒为单位，返回给定key的剩余生存时间
            if($this->redis->exists($redisKey)){
                //如果key存在
                $ttl = $this->redis->ttl($redisKey);
                //ttl小于0 表示key上没有设置生存时间（key是不会不存在的，因为前面setnx会自动创建）
                //如果出现这种状况，那就是进程的某个实例setnx成功后 crash 导致紧跟着的expire没有被调用
                //这时可以直接设置expire并把锁纳为己用
                if ($ttl < 0) {
                    $this->redis->set($redisKey, $expireAt);
                    $this->lockedNames[$name] = $expireAt;
                    return true;
                }
            }
            /*****循环请求锁部分*****/
            //如果没设置锁失败的等待时间 或者 已超过最大等待时间了，那就退出
            if ($timeout <= 0 || $timeoutAt < microtime(true)) break;
            //隔 $waitIntervalUs 后继续 请求
            usleep($waitIntervalUs);
        }
        return false;
    }



    /**

     * 解锁
     * @param [type] $name [description]
     * @return [type]    [description]
     */

    public function redis_unlock($name) {
        //先判断是否存在此锁
        if ($this->redis_isLocking($name)) {
            //删除锁
            if ($this->redis->delete("Lock:$name")) {
                //清掉lockedNames里的锁标志
                unset($this->lockedNames[$name]);
                return true;
            }
        }
        return false;
    }



    /**
     * 释放当前锁有获得的锁
     * @return [type] [description]
     */
    public function redis_unlockAll() {
        //此标志是用来标志是否释放锁有锁成功
        $allSuccess = true;
        foreach ($this->lockedNames as $name => $expireAt) {
            if (false === $this->unlock($name)) {
                $allSuccess = false;
            }
        }
        return $allSuccess;
    }



    /**
     * 给当前锁增加指定生存时间，必须大于0
     * @param [type] $name [description]
     * @return [type]    [description]
     */
    public function redis_expire($name, $expire) {
        //先判断是否存在该锁
        if ($this->redis_isLocking($name)) {
            //锁指定的生存时间必须大于0
            $expire = max($expire, 1);
            //增加锁生存时间
            if ($this->redis->expire("Lock:$name", $expire)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断当前是否拥有指定名字的锁
     * @param [type] $name [description]
     * @return boolean    [description]
     */
    public function redis_isLocking($name) {
        //先看lonkedName[$name]是否存在该锁标志名
        if (isset($this->lockedNames[$name])) {
            //从redis返回该锁的生存时间
            return (string)$this->lockedNames[$name] = (string)$this->redis->get("Lock:$name");
        }
        return false;
    }


}