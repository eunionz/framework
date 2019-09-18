<?php
/**
 * Eunionz PHP Framework Pdo component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\mdb;

defined('APP_IN') or exit('Access Denied');

/**
 * PDO 数据库操作抽象类
 */
abstract class Pdo extends \cn\eunionz\core\Component
{
    /**
     * us-en:current query sql string
     * zh-cn:当前执行的sql语句
     * @var null
     */
    static protected $_query_str = null;

    /**
     * us-en:total query times
     * zh-cn:累计查询次数
     * @var int
     */
    static protected $_query_times = 0;


    /**
     * us-en:current read/write database connection object(master connection)
     * zh-cn:当前读写数据库链接描述符(主链接)
     * @var null
     */
    protected $rw_link=null;

    /**
     * us-en:current read only database connection object(slave connection)
     * zh-cn:当前只读数据库链接描述符(从链接)，仅用于执行 select show  desc
     * @var null
     */
    protected $ro_link=null;


    /**
     * us-en:database master server config
     * zh-cn:数据库主服务器连接配置
     * format(格式):array(
     *      "TYPE"=>'mysql|oci',        //connect type(数据库链接类型)
     *      "HOST"=>'',                 //host(链接主机)
     *      "PORT"=>3306,               //port(链接端口)
     *      "NAME"=>'',                 //db name(链接数据库名称)
     *      "USER"=>'',                 //user name(链接用户名)
     *      "PASS"=>'',                 //password(链接密码)
     *      "CHARSET"=>'utf-8',         //charset(链接字符集)，默认utf-8
     *      "PERSISTENT"=>true|false,   //persistent connection(持久链接)
     *      "CACHE"=>true|false,        //enable cache (缓存启用)
     * )
     * @var null
     */
    protected $master=null;

    /**
     * us-en:current database slave server config index
     * zh-cn:当前数据库从服务器连接配置索引号
     * @var 0
     */
    protected $slave_index=0;

    /**
     * us-en:database slave server configs
     * zh-cn:数据库从服务器连接配置
     * @var null
    */
    protected $slaves=null;


    /**
     * us-en:slave database links
     * zh-cn:所有从数据库连接描述符集合
     * @var null
     */
    protected $slave_links=null;


    /**
     * us-en:is disable slave server
     * zh-cn:是否禁用从服务器，如果禁用则仅使用主服务器
     * @var null
     */
    protected $is_disabled_slave=false;


    /**
     * us-en:cache settings(config),if is null then use config in cache.config.php
     * zh-cn:缓存配置，如果为空则使用cache.config.php中缓存配置
     * @var null
     */
    protected $cache_settings=null;


    /**
     * us-en:curr pdo statement object
     * zh-cn:当前 pdo statement 对象
     * @var null
     */
    private $_PDOStatement=null;

    /**
     * us-en: current database tables
     * zh-cn:当前数据库表集合
     * @var array
     */
    private $_tables;

    /**
     * us-en: current database all table fields
     * zh-cn:当前数据库所有表字段集合
     * @var array
     */
     private $_fields;




    /**
     * us-en:set cache settings(array)
     * zh-cn:设置缓存配置(数组)
     * @param array $cache_settings
     * array(
     *      'APP_DB_DATA_QUERY_CACHE' => true|false,                                            //使用数据查询缓存
     *      'APP_DB_DATA_QUERY_CACHE_TEMP_PATH' => 'cache' . APP_DS . 'db' . APP_DS . 'data',   //数据库缓存的存储路径（相对于 APP_RUNTIME_REAL_PATH目录下）
     *      'APP_DB_DATA_QUERY_CACHE_EXPIRES' => 3600,                                          // 使用数据查询缓存的默认过期时间，单位：秒
     *      'APP_DB_DATA_CACHE_TYPE' => 'redis',                                                // 缓存模式（file|memcached|redis）
                                                                                                // file 模式下需要 FILE 组件支持
                                                                                                // memcached 模式下需要 MEMCACHE 组件支持
                                                                                                // redis 模式下需要 REDIS 组件支持
     *      'APP_DB_DATA_CACHE_DRIVER'=>array(),            //缓存模式对应的驱动
                                                            //file模式 为空数组，file将使用 APP_REAL_PATH 及 APP_DB_DATA_QUERY_CACHE_TEMP_PATH 两个配置变量的组合路径构成缓存路径
                                                            //'APP_DB_DATA_CACHE_DRIVER'=>array(),

                                                            //memcached模式必须配置为： array('server'=>'','port'=>'11211','password'=>'','db_server'=>'','db_port'=>'','db_user'=>'','db_password'=>'','db_name'=>'','db_table'=>'','add_servers'=>array(array('server'=>'',port=>''),));
                                                            //memchaced缓存服务器及密码，如果不支持密码，密码留空，key及前缀保存的数据库连接信息及数据表，其中该表必须具有  prefix字段  key字段，这两个字段构成组合键(主键)
                                                            //将使用mysql扩展进行该表操作
                                                            //    'APP_DB_DATA_CACHE_DRIVER'=>array(
                                                            //        'server'=>'127.0.0.1',
                                                            //        'port'=>11211,
                                                            //        'password'=>'',
                                                            //        'db_server'=>'localhost',
                                                            //        'db_port'=>'3306',
                                                            //        'db_user'=>'root',
                                                            //        'db_password'=>'123',
                                                            //        'db_name'=>'wmwj',
                                                            //        'db_table'=>'memcached_keys',
                                                            //        'add_servers'=>array(),
                                                            //    ),

                                                            //redis模式必须配置为： array('isUseCluster'=>true|false,'server'=>'','port'=>'','password'=>'','dbname'=>1,'add_servers'=>array(array('server'=>'','port'=>'','dbname'=>1,'password'=>'')));//redis缓存服务器及密码，如果没有密码，密码留空
                                                                'APP_DB_DATA_CACHE_DRIVER'=>array(
                                                                    'isUseCluster'=>false,
                                                                    'server'=>'192.168.1.125',//主服务器配置
                                                                    'port'=>6379,//主服务器配置
                                                                    'password'=>'',//主服务器配置
                                                                    'dbname'=>1,//主服务器选择的数据库编号
                                                                    'add_servers'=>array(),//从服务器配置
                                                                ),
     * )
     */
    public function setCacheSettings($cache_settings)
    {
        $this->cache_settings = $cache_settings;
    }

    /**
     * us-en:get cache settings(array)
     * zh-cn:获取缓存配置(数组)
     * @return array $cache_settings
     */
    public function getCacheSettings()
    {
        return $this->cache_settings;
    }


    /**
     * us-en:get disable slave server status
     * zh-cn:获取是否禁用从服务器状态
     * @return bool true-disabled(禁用从服务器)  false--enabled(启用从服务器)
     */
    public function getIsDisabledSlave()
    {
        return $this->is_disabled_slave;
    }

    /**
     * us-en:set disable slave server status
     * zh-cn:设置是否禁用从服务器状态
     * @param bool $is_disabled_slave  true-disabled(禁用从服务器)  false--enabled(启用从服务器)
     */
    public function setIsDisabledSlave($is_disabled_slave)
    {
        $this->is_disabled_slave = $is_disabled_slave;
    }


    /**
     * us-en:get read/write(master) connection object(pdo object)
     * zh-cn:获取读写数据库链接对象(PDO对象)
     * @return object read/write(master) connection object(读写即主数据库链接对象)
     */
    public function getRwLink()
    {
        if(!$this->rw_link){
            //init $this->rw_link
            //初始化读写数据库链接对象

        }
        return $this->rw_link;
    }

    /**
     * us-en:set read/write(master) connection object(pdo object)
     * zh-cn:设置读写数据库链接对象(PDO对象)
     * @param object $rw_link read/write(master) connection object(读写即主数据库链接对象)
     */
    public function setRwLink($rw_link)
    {
        $this->rw_link = $rw_link;
    }


    /**
     * us-en:get read only(slave) connection object(pdo object)
     * zh-cn:获取只读数据库(从数据库)链接对象(PDO对象)
     * @return object readonly(slave) connection object(获取只读数据库(从数据库)链接对象(PDO对象))
     */
    public function getRoLink()
    {
        //if disable slave config then return read/write connection object(pdo)
        //如果已经禁用从服务器，则返回读写链接
        if($this->is_disabled_slave){
            return $this->getRwLink();
        }else{
            if(!$this->ro_link){
                //init $this->ro_link
                //初始化只读数据库链接对象
            }
            return $this->ro_link;
        }

    }


    /**
     * us-en:set read only(slave) connection object(pdo object)
     * zh-cn:设置只读(从)数据库链接对象(PDO对象)
     * @param object $ro_link readonly(slave) connection object(只读数据库(从数据库)链接对象(PDO对象))
     */
    public function setRoLink($ro_link)
    {
        $this->ro_link = $ro_link;

    }

    /**
     * us-en:get master connection config(array)
     * zh-cn:获取主数据库链接配置(数组)
     * @return array master connection config(array)(获取主数据库链接配置(数组))
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * us-en:set master connection configs(array(array))
     * zh-cn:设置主数据库链接配置数组(一维数组)
     * @param array $master master connection configs(array(array))(从数据库链接配置数组(一维数组))
     */
    public function setMaster($master)
    {
        $this->master = $master;
    }




    /**
     * us-en:get current slave connection config(array)
     * zh-cn:获取当前从数据库链接配置(数组)
     * @return array current slave connection config(array)(获取当前从数据库链接配置(数组))
     */
    public function getSlave()
    {
        if(isset($this->slaves[$this->slave_index]) && $this->slaves[$this->slave_index]){
            return $this->slaves[$this->slave_index];
        }
        return null;
    }


    /**
     * us-en:get slaves connection configs(array(array))
     * zh-cn:获取从数据库链接配置数组(二维数组)
     * @return array slaves connection configs(array(array))(获取从数据库链接配置数组(二维数组))
     */
    public function getSlaves()
    {
        return $this->slaves;
    }

    /**
     * us-en:set slaves connection configs(array(array))
     * zh-cn:设置从数据库链接配置数组(二维数组)
     * @param array $slaves slaves connection configs(array(array))(从数据库链接配置数组(二维数组))
     */
    public function setSlaves($slaves)
    {
        $this->slaves = $slaves;
    }


    /**
     * us-en:set slaves connection pdo objects(array(pdo object))
     * zh-cn:设置从数据库链接对象数组(pdo对象一维数组)
     * @param array $slave_links slaves connection pdo objects(array(pdo object))(设置从数据库链接对象数组(pdo对象一维数组))
     */
    public function setSlaveLinks($slave_links)
    {
        $this->slave_links = $slave_links;
    }

    /**
     * us-en:get slaves connection pdo objects(array(pdo object))
     * zh-cn:获取从数据库链接对象数组(pdo对象一维数组)
     * @return array slaves connection pdo objects(array(pdo object))(设置从数据库链接对象数组(pdo对象一维数组))
     */
    public function getSlaveLinks()
    {
        return $this->slave_links;
    }


    /**
     * us-en:init connect database server,and save connection object to $this(or $this->rw_link or $this->ro_link or $this->ro_link)
     * zh-cn:初始化链接到指定的数据库服务器
     * @param bool $is_master  is master(是否主数据库链接(读写数据库链接))  true--master(主即读写)   false--slave(从即只读)
     * @param array $config
     * format(格式):array(
     *      "TYPE"=>'mysql|oci',        //connect type(数据库链接类型)
     *      "HOST"=>'',                 //host(链接主机)
     *      "PORT"=>3306,               //port(链接端口)
     *      "NAME"=>'',                 //db name(链接数据库名称)
     *      "USER"=>'',                 //user name(链接用户名)
     *      "PASS"=>'',                 //password(链接密码)
     *      "CHARSET"=>'utf-8',         //charset(链接字符集)，默认utf-8
     *      "PERSISTENT"=>true|false,   //persistent connection(持久链接)
     *      "CACHE"=>true|false,        //enable cache (缓存启用)
     * )
     * @return mixed null--connect fail(链接失败)  pdo object--connect success(链接成功)
     * @throws \cn\eunionz\exception\DBException
     */
    public function init_connect($is_master,$config){
        if($this->is_disabled_slave || $is_master){
            //master
            if(empty($this->master) || $config!=$this->master){
                $this->rw_link=null;
            }
            if(!$this->rw_link){
                $this->rw_link=$this->connect($config);
                if($this->rw_link) $this->master = $config;
            }
            return $this->rw_link;
        }else{
            //slave
            if(empty($this->slaves)){
                $this->ro_link=$this->connect($config);
                if($this->ro_link){
                    $this->slaves[$this->slave_index]=$config;
                    $this->slave_links[$this->slave_index]=$this->ro_link;
                }
            }else{
                $is_find_slave=false;
                foreach ($this->slaves as $index => $conf){
                    if($config==$conf){
                        $this->slave_index=$index;
                        $is_find_slave=true;
                        break;
                    }
                }
                if(!$is_find_slave){
                    $this->slave_index=count($this->slaves);
                }
                if(isset($this->slave_links[$this->slave_index]) && $this->slave_links[$this->slave_index]){
                    $this->ro_link=$this->slave_links[$this->slave_index];
                }else{
                    $this->ro_link=$this->connect($config);
                    if($this->ro_link){
                        $this->slaves[$this->slave_index]=$config;
                        $this->slave_links[$this->slave_index]=$this->ro_link;
                    }
                }
            }
            return $this->ro_link;
        }
    }


    /**
     * us-en:get rand available slave(read only) database connection object (pdo)
     * zh-cn:随机获取可用的从数据库链接(只读数据库链接)对象pdo
     * @return mixed null--fail(获取从链接失败)  pdo object--success(获取从链接成功)
     */
    public function getAvailableSlave(){
        if($this->is_disabled_slave){
            //disable slave
            if(!$this->rw_link && !$this->master){
                $this->rw_link=$this->init_connect(true,$this->master);
            }
            return $this->rw_link;
        }else{
            if($this->slaves){
                $index=array_rand($this->slaves,1);
                $config = $this->slaves[$index];
                $this->ro_link=$this->init_connect(false,$config);
            }
            return $this->ro_link;
        }
    }


    /**
     * us-en:add slave connect database config to $this->slaves
     * zh-cn:添加从服务器配置到$this->slaves中
     * @param array $config
     * format(格式):array(
     *      "TYPE"=>'mysql|oci',        //connect type(数据库链接类型)
     *      "HOST"=>'',                 //host(链接主机)
     *      "PORT"=>3306,               //port(链接端口)
     *      "NAME"=>'',                 //db name(链接数据库名称)
     *      "USER"=>'',                 //user name(链接用户名)
     *      "PASS"=>'',                 //password(链接密码)
     *      "CHARSET"=>'utf-8',         //charset(链接字符集)，默认utf-8
     *      "PERSISTENT"=>true|false,   //persistent connection(持久链接)
     *      "CACHE"=>true|false,        //enable cache (缓存启用)
     * )
     * @return mixed false--add fail(添加从服务器配置失败)  true--add success(添加从服务器配置成功)
     */
    public function addSlave($config){
        $is_find_slave=false;
        foreach ($this->slaves as $index => $conf){
            if($config==$conf){
                $is_find_slave=true;
                break;
            }
        }
        if(!$is_find_slave){
            $this->slaves[count($this->slaves)]=$config;
        }
        return true;
    }

    /**
     * us-en:connect database server,and save connection object to $this(or $this->rw_link or $this->ro_link or $this->ro_link)
     * zh-cn:链接到指定的数据库服务器
     * @param array $config
     * format(格式):array(
     *      "TYPE"=>'mysql|oci',        //connect type(数据库链接类型)
     *      "HOST"=>'',                 //host(链接主机)
     *      "PORT"=>3306,               //port(链接端口)
     *      "NAME"=>'',                 //db name(链接数据库名称)
     *      "USER"=>'',                 //user name(链接用户名)
     *      "PASS"=>'',                 //password(链接密码)
     *      "CHARSET"=>'utf-8',         //charset(链接字符集)
     *      "PERSISTENT"=>true|false,   //persistent connection(持久链接)
     *      "CACHE"=>true|false,        //enable cache (缓存启用)
     * )
     * @return mixed null--connect fail(链接失败)  pdo object--connect success(链接成功)
     * @throws \cn\eunionz\exception\DBException
     */
    public function connect($config)
    {
        // query params
        $params = array();
        $link = null;

        // long connect ?
        $config['PERSISTENT'] && $params[\PDO::ATTR_PERSISTENT] = true;

        // connect string
        $dsn = strtolower($config['TYPE']);

        if($dsn=='mysql'){
            //mysql connect
            $dsn .= ':host=' . $config['HOST'];
            $dsn .= ';port=' . $config['PORT'];
            $dsn .= ';dbname=' . $config['NAME'];
            $usr = $config['USER'];
            $pwd = $config['PASS'];
            try
            {
                $link = new \PDO($dsn, $usr, $pwd, $params);
            }
            catch (\PDOException $e)
            {
                throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($e->getMessage())));
            }
            if($link){
                // set charset
                $link->exec('SET NAMES \'' . $config['CHARSET'].'\'');
            }
        }else if($dsn=='oci'){
            //oracle connect
            $dsn .= ':dbname';
            $dsn .= '=//' . $config['HOST'];
            $dsn .= ':' . $config['PORT'];
            $dsn .= '/' . $config['NAME'];
            if($config['CHARSET']){
                $dsn .= ';charset=' . $config['CHARSET'];
            }

            $usr = $config['USER'];
            $pwd = $config['PASS'];

            try
            {
                $link = new \PDO($dsn, $usr, $pwd, $params);
            }
            catch (\PDOException $e)
            {
                throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($e->getMessage())));
            }
        }

        return $link;
    }


    /**
     * us-en:set default cache settings
     * zh-cn:在读写数据库服务器(主数据库服务器)上执行增删改，创建，删除，修改  sql
     * @param string $sql
     * @param array $values
     * @return mixed false--execute fail(执行失败)  int--execute success(执行成功，并返回受影响的记录行数)
     * @throws \cn\eunionz\exception\DBException
     */
    public function setDefaultCacheSettings(){
        $this->cache_settings=array(
            'APP_DB_DATA_QUERY_CACHE' => true,                                            //使用数据查询缓存
            'APP_DB_DATA_QUERY_CACHE_TEMP_PATH' => APP_RUNTIME_REAL_PATH . 'cache' . APP_DS . 'db' . APP_DS . 'data',   //数据库缓存的存储路径（相对于 APP_RUNTIME_REAL_PATH目录下）
            'APP_DB_DATA_QUERY_CACHE_EXPIRES' => 3600,                                          // 使用数据查询缓存的默认过期时间，单位：秒
            'APP_DB_DATA_CACHE_TYPE' => 'file',                                                // 缓存模式（file|memcached|redis）
                                                                                                    // file 模式下需要 FILE 组件支持
                                                                                                    // memcached 模式下需要 MEMCACHE 组件支持
                                                                                                    // redis 模式下需要 REDIS 组件支持
            'APP_DB_DATA_CACHE_DRIVER'=>array(),            //缓存模式对应的驱动
                                                            //file模式 为空数组，file将使用 APP_REAL_PATH 及 APP_DB_DATA_QUERY_CACHE_TEMP_PATH 两个配置变量的组合路径构成缓存路径
                                                            //'APP_DB_DATA_CACHE_DRIVER'=>array(),

                                                            //memcached模式必须配置为： array('server'=>'','port'=>'11211','password'=>'','db_server'=>'','db_port'=>'','db_user'=>'','db_password'=>'','db_name'=>'','db_table'=>'','add_servers'=>array(array('server'=>'',port=>''),));
                                                            //memchaced缓存服务器及密码，如果不支持密码，密码留空，key及前缀保存的数据库连接信息及数据表，其中该表必须具有  prefix字段  key字段，这两个字段构成组合键(主键)
                                                            //将使用mysql扩展进行该表操作
                                                            //    'APP_DB_DATA_CACHE_DRIVER'=>array(
                                                            //        'server'=>'127.0.0.1',
                                                            //        'port'=>11211,
                                                            //        'password'=>'',
                                                            //        'db_server'=>'localhost',
                                                            //        'db_port'=>'3306',
                                                            //        'db_user'=>'root',
                                                            //        'db_password'=>'123',
                                                            //        'db_name'=>'wmwj',
                                                            //        'db_table'=>'memcached_keys',
                                                            //        'add_servers'=>array(),
                                                            //    ),

            //redis模式必须配置为： array('isUseCluster'=>true|false,'server'=>'','port'=>'','password'=>'','dbname'=>1,'add_servers'=>array(array('server'=>'','port'=>'','dbname'=>1,'password'=>'')));//redis缓存服务器及密码，如果没有密码，密码留空
            'APP_DB_DATA_CACHE_DRIVER'=>array(
                'isUseCluster'=>false,
                'server'=>'',//主服务器配置
                'port'=>6379,//主服务器配置
                'password'=>'',//主服务器配置
                'dbname'=>1,//主服务器选择的数据库编号
                'add_servers'=>array(),//从服务器配置
            ),
        );

    }




    /**
     * us-en:Initialization database tables
     * zh-cn:初始化数据库表集合
     * @param bool $is_master  true--master  false--slave
     * @return array
     */
    private function init_tables($is_master=true)
    {
        $database_config=$is_master?$this->master:$this->getSlave();

        if ($database_config['CACHE'])
        {
            $key = str_replace('.', '_', $database_config['HOST']) . '_' . $database_config['PORT'] . '_' . $database_config['NAME'];
            $result = $this->loadComponent('cache')->init($this->cache_settings)->getCache('db_structure',array($key));
            if(!$result){
                $result = $this->get_tables($is_master);
                $cache=serialize($result);
                $this->loadComponent('cache')->init($this->cache_settings)->setCache('db_structure',array($key),$cache);
            }else{
                $result=unserialize($result);
            }
        }
        else
        {
            $result = $this->get_tables($is_master);
        }
        return $result;
    }


    /**
     * us-en:get current database tables
     * zh-cn:获取当前数据库表集合
     * @param bool $is_master  true--master  false--slave
     * @return array
     */
    public function get_tables($is_master=true)
    {
        $database_config=$is_master?$this->master:$this->getSlave();

        switch (strtoupper($database_config['TYPE']))
        {
            case 'ORACLE' :

            case 'OCI' :
                $sql = 'SELECT table_name FROM user_tables';
                break;

            case 'MSSQL' :
                $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                break;

            case 'PGSQL' :
                $sql = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
                break;

            case 'SQLITE' :
                $sql = "SELECT name FROM sqlite_master WHERE type='table' " . "UNION ALL SELECT name FROM sqlite_temp_master " . "WHERE type='table' ORDER BY name";
                break;

            case 'MYSQL' :

            default :
                $sql = 'SHOW TABLES ';
        }

        $result = $this->query($sql);

        if (false === $result)
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));


        $info = array();

        foreach ( $result as $key => $val )
            $info[$key] = current($val);

        return $info;
    }


    /**
     * us-en:on read/write database server(master database server) execute insert,update,delete,create,drop,alter,truncate sql)
     * zh-cn:在读写数据库服务器(主数据库服务器)上执行增删改，创建，删除，修改  sql
     * @param string $sql  sql语句中允许使用 ? 做占位符或者 :字段名 做占位符
     * @param array $values 如果为null，表示没有参数需要绑定，否则应该为一个数组，两种格式：array(0=>'值',1=>'值') 或  array(':字段名'=>'值',':字段名'=>'值')
     * @param array $tables 如果为null，表示没有相关数据表不需要清除表相关缓存，否则应该为一个数组格式：array('表1','表2')，执行成功则针对所有前缀中包含这些表相关的key将清除缓存
     * @return mixed false--execute fail(执行失败)  int--execute success(执行成功，并返回受影响的记录行数)
     * @throws \cn\eunionz\exception\DBException
     */
    public function exec($sql,$values=null,$tables=array())
    {
        $sql=trim($sql);
        // use master server
        if (!$this->rw_link){
            $this->rw_link=$this->init_connect(true,$this->master);
        }
        if (!$this->rw_link){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));
        }

        if(!$this->cache_settings){
            $this->setDefaultCacheSettings();
        }

        if(!$this->cache_settings){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo_cache_config'));
        }

        $this->tables=$this->init_tables(true);


        // record query string
        self::$_query_str = $sql;

        // free history query
        if ($this->_PDOStatement)
            $this->free();

        // before process sql
        $this->_PDOStatement = $this->rw_link->prepare($sql);

        // fail
        if (false === $this->_PDOStatement){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));
        }

        // execute

        if($values){
            $result = $this->_PDOStatement->execute($values);
        }else{
            $result = $this->_PDOStatement->execute();
        }

        if (false === $result){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));

        }

        // return effect rows number
        return $this->_PDOStatement->rowCount();
    }


    /**
     * us-en:on read/write database server(master database server) execute insert,update,delete,create,drop,alter,truncate sql)
     * zh-cn:在读写数据库服务器(主数据库服务器)上执行增删改，创建，删除，修改  sql
     * @param string $sql  sql语句中允许使用 ? 做占位符或者 :字段名 做占位符
     * @param array $values 如果为null，表示没有参数需要绑定，否则应该为一个数组，两种格式：array(0=>'值',1=>'值') 或  array(':字段名'=>'值',':字段名'=>'值')
     * @param bool $index   如果为false返回关联数组，否则返回数字索引数组
     * @param array $tables 如果为null，表示不基于相关数据表进行缓存，否则应该为一个数组格式：array('表1','表2')，表示基于这些表进行升序排序后形成前缀
     * @return mixed false--execute fail(执行失败)  int--execute success(执行成功，并返回受影响的记录行数)
     * @throws \cn\eunionz\exception\DBException
     * @param $sql
     * @param null $values
     * @param bool $index
     * @param array $tables
     * @return mixed
     * @throws \cn\eunionz\exception\DBException
     */
    public function query($sql,$values=null, $index = false,$tables=array())
    {
        // use master server

        $sql=trim($sql);
        // use master server
        if (!$this->ro_link){
            $this->ro_link=$this->getAvailableSlave();
        }
        if (!$this->ro_link){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));
        }

        self::$_query_str = $sql;

        if ($this->_PDOStatement)
            $this->free();

        $this->_PDOStatement = $this->ro_link->prepare($sql);


        // return number index ?
        if (!$index){
            $this->_PDOStatement->setFetchMode(\PDO::FETCH_ASSOC);
        }

        if (false === $this->_PDOStatement){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));
        }


        self::$_execute_querys++;

        if($values){
            $result = $this->_PDOStatement->execute($values);
        }else{
            $result = $this->_PDOStatement->execute();
        }

        if (false === $result){
            throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'),$this->getLang('error_db_pdo',array($this->get_last_error())));
        }

        // return rows collection
        return $this->_PDOStatement->fetchAll();
    }



    /**
     * us-en:return final execute sql
     * zh-cn:获取最近一次执行的sql语句
     * @return null
     */
    public function get_sql(){
        return self::$_query_str;
    }


    /**
     * us-en:return total query times
     * zh-cn:获取当前累计执行的查询次数
     * @return int
     */
    public function get_query_times(){
        return self::$_query_times;
    }



    /**
     * us-en:get last pdo error
     * zh-cn:获取最近一次pdo 错误
     * @return string
     */
    public function get_last_error()
    {
        if($this->_PDOStatement){
            return implode(' ', $this->_PDOStatement->errorInfo());
        }else{
            return "";
        }
    }



    /**
     * us-en:free current pdo Statement object
     * zh-cn:释放当前pdo 语句对象
     */
    public function free()
    {
        $this->_PDOStatement = null;
    }


    /**
     * us-en:get fields by table name
     * zh-cn:当前表名从当前数据库获取字段集合
     * @param string $table  table name
     * @return array
     */
    public function get_fields_by_table($table)
    {
        $result = null;

        if (isset($this->_fields[$table]['fields']))
        {
            $result = $this->_fields[$table]['fields'];
        }
        elseif (self::$_config['APP_DB_STRUCTURE_CACHE'])
        {

            $currDBID = self::$_config['APP_DB_SERVERS'];
            $currDBID = $currDBID[$this->getConnectID()];
            $fileName = str_replace('.', '_', $currDBID['HOST']) .
                '_' . $currDBID['PORT'] . '_' . $currDBID['NAME'] .
                '_' . $table;


            $result = $this->loadPlugin('cache')->getCache('db_structure',array($fileName));
            if(!$result){
                $result = $this->get_fields($this->set_special_char($table));
                $cache=serialize($result);
                $this->loadPlugin('cache')->setCache('db_structure',array($fileName),$cache);
            }else{
                $result=unserialize($result);
            }

        }
        else
        {
            $result = $this->get_fields($this->set_special_char($table));
        }

        return $result;
    }

}
