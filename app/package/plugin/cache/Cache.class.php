<?php
/**
 * EUnionZ PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\cache;


defined('APP_IN') or exit('Access Denied');

/**
 * 通用缓存类，工具类
 * Class Cache
 */
class Cache extends \cn\eunionz\core\Plugin
{

    private $is_cache=true;//是否缓存
    private $cache_type='file';//缓存类型,'db','memcached','redis'
    private $cache_driver_data=array('cache_dir'=>'');//文件缓存
    //private $cache_driver_data=array('server'=>'','user'=>'','password'=>'','db'=>'','table'=>'');//数据库缓存
    //表结构：  cache_key(varchar(255))   cache_table(varchar(255))   cache_data(longtext)    cache_time(int)

    //private $cache_driver_data=array('server'=>'','port'=>'','password'=>'','db_server'=>'','db_port'=>'','db_user'=>'','db_password'=>'','db_name'=>'','db_table'=>'');//memchaced缓存
    //private $cache_driver_data=array('server'=>'','port'=>'','password'=>'');//redis缓存

    private $cache_life_seconds=0;//缓存生命周期,如果为0，表示永不过期

    private $db_link_id = null;//memcached缓存模式会用的mysql数据库连接

    private $cache_link_id = null;//memcached缓存模式


    //redis缓存模式 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;

    //redis缓存模式 Slave 句柄标记
    private $_sn = 0;

    //redis缓存模式 服务器连接句柄
    private $_linkHandle = array(
        'master'=>null,// 只支持一台 Master
        'slave'=>array(),// 可以有多台 Slave
    );


    public function __construct(){
        $this->is_cache=$this->getConfig('db','APP_DB_DATA_QUERY_CACHE');
        $this->cache_type=$this->getConfig('db','APP_DB_DATA_CACHE_TYPE');
        $this->cache_life_seconds=$this->getConfig('db','APP_DB_DATA_QUERY_CACHE_EXPIRES');
        if($this->cache_type=='file'){
            $this->cache_driver_data['cache_dir']= APP_RUNTIME_REAL_PATH . $this->getConfig('db','APP_DB_DATA_QUERY_CACHE_TEMP_PATH');
            if(!file_exists($this->cache_driver_data['cache_dir'])){
                @mkdir($this->cache_driver_data['cache_dir'],0777,true);
            }
        }else if($this->cache_type=='memcached'){
            $this->cache_driver_data = $this->getConfig('db','APP_DB_DATA_CACHE_DRIVER');
            $this->cache_link_id = new \Memcache();
            if(!$this->cache_link_id->connect($this->cache_driver_data['server'],$this->cache_driver_data['port'])){
                die('Memchaced 连接失败，请检查Memchaed服务器【' . $this->cache_driver_data['server'].':' . $this->cache_driver_data['port'] .'】是否正常运行!');
            }
            if($this->cache_driver_data['add_servers'] && is_array($this->cache_driver_data['add_servers'])){
                foreach($this->cache_driver_data['add_servers'] as $server){
                    if($server && is_array($server)){
                        $this->cache_link_id -> addServer($server['server'],$server['port']);
                    }
                }
            }

            if($this->cache_driver_data['db_server']){
                $this->db_link_id = @mysql_connect($this->cache_driver_data['db_server'].':'. $this->cache_driver_data['db_port'], $this->cache_driver_data['db_user'],$this->cache_driver_data['db_password']);
                if(!$this->db_link_id){
                    die('Memchaced 关联MySQL数据库连接失败，请检查MySQL服务器【' . $this->cache_driver_data['db_server'].':' . $this->cache_driver_data['db_port'] .'】是否正常运行!');
                }
                @mysql_query("SET character_set_connection=utf8, character_set_results=utf8, character_set_client=binary", $this->db_link_id);
                if (@mysql_select_db($this->cache_driver_data['db_name'], $this->db_link_id) === false )
                {
                    die('Memchaced 关联MySQL数据库【' .  $this->cache_driver_data['db_name'] .'】不存在，请检查MySQL服务器【' . $this->cache_driver_data['db_server'].':' . $this->cache_driver_data['db_port'] .'】上是否存在【' . $this->cache_driver_data['db_name'] . '】数据库!');
                }
            }

        }else if($this->cache_type=='redis'){

            $this->cache_driver_data = $this->getConfig('db','APP_DB_DATA_CACHE_DRIVER');

            $this->_isUseCluster=$this->cache_driver_data['isUseCluster'];

            $this->_linkHandle['master'] = new \Redis();
            if(!$this->_linkHandle['master']->connect($this->cache_driver_data['server'],$this->cache_driver_data['port'])){
                die('主Redis 服务器连接失败，请检查Redis 服务器【' . $this->cache_driver_data['server'].':' . $this->cache_driver_data['port'] .'】是否正常运行!');
            }
            if($this->cache_driver_data['password']){
                $this->_linkHandle['master']->auth($this->cache_driver_data['password']) or die('主Redis 服务器【' . $this->cache_driver_data['server'].':' . $this->cache_driver_data['port'] .'】连接失败，请提供正确的密码!');
            }
            if(isset($this->cache_driver_data['dbname'])){
                $this->cache_driver_data['dbname']=intval($this->cache_driver_data['dbname']);
            }else{
                $this->cache_driver_data['dbname']=0;
            }
            $this->_linkHandle['master']->select($this->cache_driver_data['dbname']);

            if($this->cache_driver_data['add_servers'] && is_array($this->cache_driver_data['add_servers'])){
                foreach($this->cache_driver_data['add_servers'] as $server){
                    if($server && is_array($server)){
                        $this->_linkHandle['slave'][$this->_sn] = new \Redis();
                        $this->_linkHandle['slave'][$this->_sn]->connect($server['host'],$server['port']);
                        if($this->_linkHandle['slave'][$this->_sn] && $server['password']){
                            $this->_linkHandle['slave'][$this->_sn]->auth($server['password']);
                        }
                        if(isset($server['dbname'])){
                            $server['dbname']=intval($server['dbname']);
                        }else{
                            $server['dbname']=0;
                        }
                        $this->_linkHandle['slave'][$this->_sn]->select($server['dbname']);

                        ++$this->_sn;
                    }
                }
            }

        }else{
            die('不支持的缓存驱动模式');
        }
    }

    public function setCacheByKey($prefix,$key,$data,$expires=null){
        $shop_id = isset($_SESSION['PLATFORM_SHOP_ID'])?$_SESSION['PLATFORM_SHOP_ID']:$this->getConfig('shop','SHOP_ID');
        if(preg_match('/^(shop_)(\d+)(_.*)/',$prefix,$arr)){
            $prefix = $arr[1].$shop_id.$arr[3];
        }
        if(!$this->is_cache){//没有开启缓存
            return false;
        }
        if(isset($expires) && is_numeric($expires)){
            $this->cache_life_seconds=intval($expires);
        }

        switch($this->cache_type){
            case 'file':
                if($prefix){
                    if(!file_exists($this->cache_driver_data['cache_dir'] . APP_DS . $prefix)) @mkdir($this->cache_driver_data['cache_dir'] . APP_DS . $prefix,0777);
                    $file=$this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key .'.cache.php';
                }else{
                    $file=$this->cache_driver_data['cache_dir'] . APP_DS . $key .'.cache.php';
                }
                if($data){
                    file_put_contents($file,serialize($data));
                }else{
                    if(file_exists($file)) @unlink($file);
                }
                return true;
                break;
            case 'memcached':
                if($prefix){
                    //有前缀，检查该前缀与该key是否存在于关联表中，如果不存在，则添加一条记录，存在则不做任何操作
                    $exists=false;
                    $sql="SELECT COUNT(*) as num FROM `" . $this->cache_driver_data['db_table'] . "` WHERE `prefix`='" . str_replace("'","''",$prefix) . "' AND `key`='" . str_replace("'","''",$key) . "'";
                    $rs=mysql_query($sql,$this->db_link_id);
                    if($rs){
                        $row=mysql_fetch_array($rs);
                        if($row['num']>0) $exists=true;
                    }
                    if(!$exists){
                        $sql="INSERT INTO `" . $this->cache_driver_data['db_table'] . "` VALUES('" . str_replace("'","''",$prefix) ."','" . str_replace("'","''",$key) . "')";
                        $exists = mysql_query($sql,$this->db_link_id);
                    }
                    if($exists){
                        return $this->cache_link_id->set($prefix.$key,$data,MEMCACHE_COMPRESSED,$this->cache_life_seconds);
                    }
                }else{
                    //没有前缀
                    return $this->cache_link_id->set($key,$data,MEMCACHE_COMPRESSED,$this->cache_life_seconds);
                }
                break;
            case 'redis':

                if ($prefix) {
                    $b = $this->redis_set($prefix . $key, $data, $this->cache_life_seconds);
                    if ($b) {
                        $this->handle_prefix($prefix, $key);
                    }
                    return $b;
                } else {
                    return $this->redis_set($key, $data, $this->cache_life_seconds);
                }
                break;
            default://不支持的缓存驱动

                return false;
                break;
        }

        return false;

    }


    public function setCache($prefix,$keys,$data,$expires=null){
        if(!$this->is_cache){//没有开启缓存
            return false;
        }
        if(isset($expires) && is_numeric($expires)){
            $this->cache_life_seconds=intval($expires);
        }

        if(is_array($keys)){
            $key=$this->array2string($keys);
        }else{
            $key=$keys;
        }
        $key=md5($key);
        return $this->setCacheByKey($prefix,$key,$data,$this->cache_life_seconds);

    }

    public function getCacheByKey($prefix,$key,$expires=null){
        $shop_id = isset($_SESSION['PLATFORM_SHOP_ID'])?$_SESSION['PLATFORM_SHOP_ID']:$this->getConfig('shop','SHOP_ID');
        if(preg_match('/^(shop_)(\d+)(_.*)/',$prefix,$arr)){
            $prefix = $arr[1].$shop_id.$arr[3];
        }
        if(!$this->is_cache){//没有开启缓存
            return null;
        }
        if(isset($expires) && is_numeric($expires)){
            $this->cache_life_seconds=intval($expires);
        }
        switch($this->cache_type){
            case 'file':
                if($prefix){
                    $file=$this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key .'.cache.php';
                }else{
                    $file=$this->cache_driver_data['cache_dir'] . APP_DS . $key .'.cache.php';
                }
                if(file_exists($file)){
                    if($this->cache_life_seconds===0 || ($this->cache_life_seconds>0 && ((time()-filemtime($file))<=$this->cache_life_seconds))){
                        return unserialize(file_get_contents($file));
                    }
                }
                break;
            case 'memcached':
                if($prefix){
                    //有前缀，检查该前缀与该key是否存在于关联表中，如果不存在，获取失败，并删除该前缀对应的key
                    $exists=false;
                    $sql="SELECT COUNT(*) as num FROM `" . $this->cache_driver_data['db_table'] . "` WHERE `prefix`='" . str_replace("'","''",$prefix) . "' AND `key`='" . str_replace("'","''",$key) . "'";
                    $rs=mysql_query($sql,$this->db_link_id);
                    if($rs){
                        $row=mysql_fetch_array($rs);
                        if($row['num']>0) $exists=true;
                    }
                    if($exists){
                        return $this->cache_link_id->get($prefix.$key);
                    }else{
                        $this->cache_link_id->delete($prefix.$key);
                    }
                }else{
                    //没有前缀
                    return $this->cache_link_id->get($key);
                }
                break;
            case 'redis':
                if($prefix){
                    return  $this->redis_get($prefix . $key);
                }else{
                    return  $this->redis_get($key);
                }
                break;
            default://不支持的缓存驱动
                return false;
                break;
        }
        return null;

    }



    public function getCache($prefix,$keys,$expires=null){
        if(!$this->is_cache){//没有开启缓存
            return null;
        }
        if(isset($expires) && is_numeric($expires)){
            $this->cache_life_seconds=intval($expires);
        }
        if(is_array($keys)){
            $key=$this->array2string($keys);
        }else{
            $key=$keys;
        }
        $key=md5($key);
        return $this->getCacheByKey($prefix,$key,$this->cache_life_seconds);
    }


    public function clearCacheByKey($prefix='',$key=''){
        $shop_id = isset($_SESSION['PLATFORM_SHOP_ID'])?$_SESSION['PLATFORM_SHOP_ID']:$this->getConfig('shop','SHOP_ID');
        if(preg_match('/^(shop_)(\d+)(_.*)/',$prefix,$arr)){
            $prefix = $arr[1].$shop_id.$arr[3];
        }
        switch($this->cache_type){
            case 'file':
                if($prefix){
                    if($key){
                        $file=$this->cache_driver_data['cache_dir'] . APP_DS . $prefix . APP_DS . $key .'.cache.php';
                        if(file_exists($file)) return @unlink($file);
                        return true;
                    }else{
                        $dir=$this->cache_driver_data['cache_dir'] . APP_DS . $prefix;
                        if(file_exists($dir))  return $this->delFileUnderDir($dir);
                        return true;
                    }
                }else{
                    if($key){
                        $file=$this->cache_driver_data['cache_dir'] . APP_DS . $key .'.cache.php';
                        if(file_exists($file)) return @unlink($file);
                        return true;
                    }else{
                        $dir=$this->cache_driver_data['cache_dir'];
                        if(file_exists($dir))  return $this->delFileUnderDir($dir);
                        return true;
                    }
                }
                break;
            case 'memcached':
                if($prefix){
                    //有前缀，检查该前缀与该key是否存在于关联表中，如果不存在，获取失败，并删除该前缀对应的key
                    if($key){
                        $sql="SELECT * FROM `" . $this->cache_driver_data['db_table'] . "` WHERE `prefix` LIKE '" . str_replace("'","''",$prefix) . "%' AND `key`='" .  str_replace("'","''",$key) . "'";
                        $rs=mysql_query($sql,$this->db_link_id);
                        $b = false;
                        if($rs){
                            if($row=mysql_fetch_array($rs)){
                                $b = $this->cache_link_id->delete($prefix.$row['key']);
                            }
                        }else{
                            $b = true;
                        }
                        mysql_free_result($rs);
                        return $b;

                    }else{
                        $sql="SELECT * FROM `" . $this->cache_driver_data['db_table'] . "` WHERE `prefix` LIKE '" . str_replace("'","''",$prefix) . "'";
                        $rs=mysql_query($sql,$this->db_link_id);
                        $b = true;
                        if($rs){
                            while($row=mysql_fetch_array($rs)){
                                $this->cache_link_id->delete($prefix.$row['key']);
                            }
                        }
                        mysql_free_result($rs);
                        return $b;
                    }
                }else{
                    //没有前缀
                    if($key){
                        return $this->cache_link_id->delete($key);
                    }else{
                        return $this->cache_link_id->flush();
                    }
                }
                break;
            case 'redis':
                if($prefix){
                    //有前缀，检查该前缀与该key是否存在于关联表中，如果不存在，获取失败，并删除该前缀对应的key
                    if ($key) {
                        return $this->redis_remove($prefix . $key);
                    } else {
                        $this->clear_prefix($prefix);
                    }
                    return true;
                }else{
                    //没有前缀
                    if($key){
                        return $this->redis_remove($key);
                    }else{
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



    public function clearCache($prefix='',$keys=array()){
        $key="";
        if($keys){
            if(is_array($keys)){
                $key=$this->array2string($keys);
            }else{
                $key=$keys;
            }
            $key=md5($key);
        }

        return $this->clearCacheByKey($prefix,$key);
    }

    /**
     * 基于基准文件夹以及前缀删除文件夹
     * @param $base_dir
     * @param $prefix
     */
    private function del_dir_by_prefix($base_dir,$prefix){
        $dh=@opendir($base_dir);
        if($dh){
            while ($file=readdir($dh)) {
                if($file!="." && $file!="..") {
                    if(stripos($file,$prefix)===0){
                        $fullpath=$base_dir."/".$file;
                        $this->loadPlugin('common')->delDir($fullpath);
                    }
                }
            }
            @closedir($dh);
        }
        return true;
    }

    public function clear($shop_id=null){
        if($shop_id==null) $shop_id = $this->getConfig('shop','SHOP_ID');
        switch($this->cache_type) {
            case 'file':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_0');
                    return $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_'.$shop_id);
                }
                break;
            case 'memcached':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
            case 'redis':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
        }
        return true;
    }

    /**
     * 关闭缓存占用的资源
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag=2){
        switch($this->cache_type){
            case 'file':
                return true;
                break;
            case 'memcached':
                if($this->db_link_id){
                    @mysql_close($this->db_link_id);
                }
                if($this->cache_link_id){
                    return $this->cache_link_id->quit();
                }

                break;
            case 'redis':
                switch($flag){
                    // 关闭 Master
                    case 0:
                        return $this->_linkHandle['master']->quit();
                        break;
                    // 关闭 Slave
                    case 1:
                        for($i=0; $i<$this->_sn; ++$i){
                            $this->_linkHandle['slave'][$i]->quit();
                        }
                        return true;
                        break;
                    // 关闭所有
                    case 2:
                        $this->_linkHandle['master']->quit();
                        for($i=0; $i<$this->_sn; ++$i){
                            $this->_linkHandle['slave'][$i]->quit();
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
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster=true,$slaveOne=true){
        // 只返回 Master
        if($isMaster){
            return $this->_linkHandle['master'];
        }else{
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
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
            $ret = $this->getRedis()->set($key, serialize($value));
        }else{

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
    public function redis_get($key){
        // 是否一次取多个值
        // 没有使用M/S
        if(! $this->_isUseCluster){
            if($this->getRedis()->exists($key)){
                return unserialize($this->getRedis()->get($key));
            }else{
                return '';
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if($redis->exists($key)){
            return unserialize($redis->get($key));
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
        if ($prefix) {
            if ($key) {
                return array($prefix . $key);
            } else {
                return $this->redis_sMembers($prefix);
            }
        } else {
            if ($key) {
                return array($key);
            } else {
                return array();//$this->getRedis()->keys('*');
            }
        }
    }


    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function  redis_remove($key){
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
    public function  redis_incr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->incr($key);
        }else{
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
    public function  redis_decr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->decr($key);
        }else{
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_hSet($key, $hash_key, $value)
    {
        return $this->getRedis()->hSet($key, $hash_key, $value);
    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_hSetNx($key, $hash_key, $value)
    {
        return $this->getRedis()->hSetNx($key, $hash_key, $value);
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hGet($key, $hash_key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hGet($key, $hash_key);
            } else {
                return '';
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hGet($key, $hash_key);
        } else {
            return '';
        }
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hGetAll($key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hGetAll($key);
            } else {
                return array();
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hGetAll($key);
        } else {
            return array();
        }
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hDel($key, $hash_key)
    {
        if ($this->getRedis()->exists($key)) {
            return $this->getRedis()->hDel($key, $hash_key);
        } else {
            return false;
        }
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hLen($key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hLen($key);
            } else {
                return false;
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hLen($key);
        } else {
            return false;
        }

    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hExists($key, $hash_key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hExists($key, $hash_key);
            } else {
                return false;
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hExists($key, $hash_key);
        } else {
            return false;
        }
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hKeys($key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hKeys($key);
            } else {
                return array();
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hKeys($key);
        } else {
            return array();
        }
    }

    /**
     * 读redis
     *
     * @param string $key 缓存KEY,不支持一次取多个
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function redis_hVals($key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->hVals($key);
            } else {
                return array();
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->hVals($key);
        } else {
            return array();
        }

    }


    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     */
    public function redis_sAdd($key, $value)
    {
        return $this->getRedis()->sAdd($key, $value);
    }

    /**
     * 写缓存
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function redis_sRem($key, $value)
    {
        return $this->getRedis()->sRem($key, $value);
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY
     */
    public function redis_sMembers($key)
    {
        // 是否一次取多个值
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            if ($this->getRedis()->exists($key)) {
                return $this->getRedis()->sMembers($key);
            } else {
                return array();
            }
        }
        // 使用了 M/S
        $redis = $this->_getSlaveRedis();
        if ($redis->exists($key)) {
            return $redis->sMembers($key);
        } else {
            return array();
        }
    }

    /**
     * 添空所有缓存
     *
     * @return boolean
     */
    public function  redis_clear($is_clear=false){
        if($this->getConfig('shop','SHOP_ID')<=0 || $is_clear){
            return $this->getRedis()->flushdb();
        }
        return 1;

    }

    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     *
     * @return redis object
     */
    private function _getSlaveRedis(){
        // 就一台 Slave 机直接返回
        if($this->_sn <= 1){
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id,$m=10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for($i=0;$i<$l;$i++)
        {
            //相加模式HASH
            $h += substr($b,$i*2,2);
        }
        $hash = ($h*1)%$m;
        return $hash;
    }

    /* ===================redis 以上私有方法 =================== */


    //循环目录下的所有文件
    public function delFileUnderDir($dirName)
    {
        if (file_exists($dirName) &&  $handle = opendir($dirName) ) {
            while ( false !== ( $item = readdir( $handle ) ) ) {
                if ( $item != "." && $item != ".." ) {
                    if ( is_dir( "$dirName/$item" ) ) {
                        $this->delFileUnderDir( "$dirName/$item" );
                        @rmdir("$dirName/$item" );
                    } else {
                        @unlink( "$dirName/$item" );
                    }
                }
            }
            closedir( $handle );
            return true;
        }
        return false;
    }

    public function array2string($keys){

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

    /**
     * 处理前缀和key并建立前缀与key之间的关联关系
     * @param $prefix  前缀
     * @param $key key
     * @return bool
     */
    private function handle_prefix($prefix, $key)
    {
        if (preg_match("/^shop_[0-9]+/", $prefix, $arr)) {
            $kid_prefix = $arr[0];
            $b1 = $this->redis_sAdd($kid_prefix, $prefix . $key);
            $b2 = $this->redis_sAdd($kid_prefix, $prefix);
            $b3 = $this->redis_sAdd($prefix, $prefix . $key);
            return $b1 || $b2 || $b3;
        }
        return false;
    }

    /**
     * 清除前缀对应的缓存
     * @param $prefix  前缀
     * @return bool
     */
    private function clear_prefix($prefix)
    {
        $keys = $this->redis_sMembers($prefix);
        foreach ($keys as $key){
            $this->redis_remove($key);
        }
        $this->redis_remove($prefix);
        return true;
    }

    public function clearAll(){
        $shop_id = $this->getConfig('shop','SHOP_ID');
        switch($this->cache_type) {
            case 'file':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_0');
                    return $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_'.$shop_id);
                }
                break;
            case 'memcached':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
            case 'redis':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
        }
        return true;
    }

    /*
     * 升级专用
     * */
    public function upgradeClearAll(){
        $shop_id = 0;
        switch($this->cache_type) {
            case 'file':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_0');
                    return $this->del_dir_by_prefix($this->cache_driver_data['cache_dir'],'shop_'.$shop_id);
                }
                break;
            case 'memcached':
                if($shop_id<=0){
                    return $this->clearCache();
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
            case 'redis':
                if($shop_id<=0){
                    return $this->redis_clear(true);
                }else{
                    $this->clearCache('shop_0');
                    return $this->clearCache('shop_'.$shop_id);
                }
                break;
        }
        return true;
    }

}