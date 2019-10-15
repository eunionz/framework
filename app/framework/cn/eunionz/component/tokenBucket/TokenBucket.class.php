<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\tokenBucket;


use cn\eunionz\component\redis\Redis;
use cn\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');

/**
 * 基于Redis(非协程)的令牌桶类，工具类
 * Class TokenBucket
 */
class TokenBucket extends Component
{

    private $_config; // redis设定
    private $_redis;  // redis对象
    private $_queue;  // 令牌桶
    private $_max;    // 最大令牌数

    /**
     * 初始化令牌桶
     * TokenBucket constructor.
     * @param $config  Redis配置数组
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
     * @param string $queue
     * @param float|int $max
     */
    public function __construct($config, $queue = 'default_TokenBucket', $max = 8 * 1024)
    {
        $this->_config = $config;
        $this->_queue = $queue;
        $this->_max = $max;
        $this->_redis = new Redis($this->_config);
    }

    /**
     * 加入令牌
     * @param Int $num 加入的令牌数量
     * @return Int 加入的数量
     */
    public function token_add($num = 0)
    {

        // 当前剩余令牌数
        $curnum = intval($this->_redis->redis_lsize($this->_queue));

        // 最大令牌数
        $maxnum = intval($this->_max);

        // 计算最大可加入的令牌数量，不能超过最大令牌数
        $num = $maxnum >= $curnum + $num ? $num : $maxnum - $curnum;

        // 加入令牌
        if ($num > 0) {
            $token = array_fill(0, $num, 1);
            $this->_redis->redis_lpush($this->_queue, ...$token);
            return $num;
        }

        return 0;

    }

    /**
     * 获取令牌
     * @return Boolean
     */
    public function token_get()
    {
        return $this->_redis->redis_lpop($this->_queue) ? true : false;
    }

    /**
     * 重设令牌桶，填满令牌
     */
    public function token_reset()
    {
        $this->_redis->redis_remove($this->_queue);
        $this->token_add($this->_max);
    }

}