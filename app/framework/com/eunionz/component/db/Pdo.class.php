<?php
/**
 * Eunionz PHP Framework Pdo component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\db;

defined('APP_IN') or exit('Access Denied');

/**
 * PDO Component Class
 *
 * PHP database access interface
 */
class Pdo extends \com\eunionz\core\Component
{
    /**
     * 数据库类型
     * @var string mysql  oic
     */
    protected $APP_DB_TYPE;

    /**
     * 是否使用分布式数据库模式，如果启用则不同的表可以定义不同的数据库连接集群
     * @var bool
     */
    protected $APP_DB_DEPLOY = false;

    /**
     * 是否使用读写分离模式，如果使用读写分离模式，则集群中第1台服务器为读写，其它为只读
     * @var bool
     */
    protected $APP_DB_RW_SEPARATE = false;

    /**
     * 是否使用持久链接
     * @var bool
     */
    protected $APP_DB_PCONNECT = false;

    /**
     * 是否记录查询sql日志
     * @var bool
     */
    protected $APP_DB_SELECT_LOG_ENABLED = false;

    /**
     * 该表必须有如下字段,querylog_id bigint auto_increment pk,querylog_shop_id int,querylog_query text,querylog_params text,querylog_seconds float,querylog_url text,querylog_created int
     * @var string
     */
    protected $APP_DB_SELECT_LOG_TABLE_NAME = "shop_select_logs";


    /**
     * 数据库字符集
     * @var string
     */
    protected $APP_DB_CHAR = "utf8";

    /**
     * 是否启用数据库结构缓存（建议开启），基于核心缓存进行存储
     * @var bool
     */
    protected $APP_DB_STRUCTURE_CACHE = false;

    /**
     * 使用数据语句缓存，基于核心缓存进行存储
     * @var bool
     */
    protected $APP_DB_DATA_STATEMENT_CACHE = false;

    /**
     * 使用数据语句缓存的默认过期时间，单位：秒
     * @var int
     */
    protected $APP_DB_DATA_STATEMENT_CACHE_EXPIRES = 3600;

    /**
     * 数据库服务器定义
     * array(
     * // 默认所有表对应的数据库服务器集群，如果不使用分布式数据库模式时，则使用该数据库服务器群集进行数据库操作，如果使用分布式数据库模式时
     * //在没有定义表对应的数据库服务器集群时，均使用该数据库服务器集群进行数据库操作
     * // 非读写分离模式将使用数据库连接中的第一个数据库连接作为主连接
     * // 读写分离模式将使用数据库连接池中的第一个数据库连接作为写入服务器，写入服务器(索引为0)及其它服务器做只读服务器
     * 'default' => array(  //默认所有表对应的数据库服务器集群
     *                  array(  //读写分离时，写数据库
     *                      'HOST' => '127.0.0.1',
     *                      'PORT' => '3306',
     *                      'NAME' => 'kshopxdb',// kshopxdb
     *                      'USER' => 'root',
     *                      'PASS' => '123456',
     *                  ),
     *              ),
     *)
     * @var
     */
    protected $APP_DB_SERVERS;

    /**
     * 主数据库链接句柄(读写)
     * 格式：
     *      array(
     *          'default' => obj,
     *          ...
     *      )
     * @var array
     */
    protected $master_link_ids = array();


    /**
     * 当前使用的数据库链接句柄索引号
     * 格式：
     *      array(
     *          'default' => 0,
     *          ...
     *      )
     * @var array
     */
    public $curr_link_indexs = array();

    /**
     * 可用的从数据库链接句柄数组(包括主数据库链接句柄索引永远为0，所有可用的从数据库链接句柄，如果从数据库链接失败则将从可用的数据库链接句柄中删除)
     * 格式：
     *      array(
     *          'default' => array(0=>object 1=>object),
     *          ...
     *      )
     * @var array
     */
    protected $available_link_ids = array();//[0=>object 1=>object]

    /**
     * 最近一次执行的sql语句，格式
     * array(
     *      'default' => array(
     *                      'sql' => '',
     *                      'params' => array(),
     *                  ),
     *      ...
     * )
     * @var array
     */
    protected $_query_strs = array();


    /**
     * 最近一次执行sql语句的消耗时间，单位：秒，格式：
     * array(
     *      'default' => 32323.2323,
     *      ...
     * )
     * @var array
     */
    protected $_query_times = array();


    /**
     * 事务次数，主要用于实现事务保存点或进行回滚
     * array(
     *      'default' => 2,
     *      ...
     * )
     * @var array
     */
    protected $_trans_times = array();


    /**
     * 分布式事务名称 格式
     * array(
     *      'default' => 'ads',
     *      ...
     * )
     * @var array
     */
    protected $_xa_trans_names = array();

    /**
     * 分布式事务状态
     * array(
     *      'default' => false|true,
     *      ...
     * )
     * @var array
     */
    protected $_xa_trans_status = array();


    /**
     * 当前数据库集群名称
     * @var string
     */
    protected $curr_cluster_name = 'default';

    /**
     * 当前配置文件名称
     * @var string
     */
    protected $curr_config = 'db';


    /**
     * 构造函数
     * Pdo constructor.
     * @param null $config 获取配置默认
     * @param string $cluster_name 集群名称，默认default
     */
    public function __construct($config = 'db', $cluster_name = 'default')
    {
        $this->init($config, $cluster_name);
    }

    /**
     * 初始化函数
     * Pdo constructor.
     * @param null $config 获取配置默认
     * @param string $cluster_name 集群名称，默认default
     */
    public function init($config = 'db', $cluster_name = 'default')
    {
        $this->curr_config = $config;
        $this->APP_DB_TYPE = getConfig($config, 'APP_DB_TYPE');
        $this->APP_DB_DEPLOY = getConfig($config, 'APP_DB_DEPLOY');
        $this->APP_DB_RW_SEPARATE = getConfig($config, 'APP_DB_RW_SEPARATE');
        $this->APP_DB_PCONNECT = getConfig($config, 'APP_DB_PCONNECT');
        $this->APP_DB_SELECT_LOG_ENABLED = getConfig($config, 'APP_DB_SELECT_LOG_ENABLED');
        $this->APP_DB_SELECT_LOG_TABLE_NAME = getConfig($config, 'APP_DB_SELECT_LOG_TABLE_NAME');
        $this->APP_DB_CHAR = getConfig($config, 'APP_DB_CHAR');
        $this->APP_DB_SERVERS = getConfig($config, 'APP_DB_SERVERS');
        $this->APP_DB_STRUCTURE_CACHE = getConfig($config, 'APP_DB_STRUCTURE_CACHE');
        $this->APP_DB_DATA_STATEMENT_CACHE = getConfig($config, 'APP_DB_DATA_STATEMENT_CACHE');
        $this->APP_DB_DATA_STATEMENT_CACHE_EXPIRES = getConfig($config, 'APP_DB_DATA_STATEMENT_CACHE_EXPIRES');
        if (!$this->APP_DB_DEPLOY) $cluster_name = 'default';
        $this->curr_cluster_name = $cluster_name;

        //建立主数据库链接
        if (!isset($this->master_link_ids[$this->curr_cluster_name]) || empty($this->master_link_ids[$this->curr_cluster_name])) {
            //如果没有建立主数据库链接，建立
            $this->master_link_ids[$this->curr_cluster_name] = $this->connect($this->APP_DB_SERVERS[$this->curr_cluster_name]);
        }
        //如果启用读写分离，则建立从数据库链接
        if ($this->APP_DB_RW_SEPARATE) {
            if (!isset($this->available_link_ids[$this->curr_cluster_name]) || empty($this->available_link_ids[$this->curr_cluster_name])) {
                $salve_links = $this->connect($this->APP_DB_SERVERS[$this->curr_cluster_name], false);
                $salve_links[0] = $this->master_link_ids[$this->curr_cluster_name];
                $this->available_link_ids[$this->curr_cluster_name] = $salve_links;
            }
        }
        return $this;
    }


    /**
     * 根据配置建立数据库链接
     * @param $config  配置
     * array(  //默认所有表对应的数据库服务器集群
     *                  array(  //读写分离时，写数据库
     *                      'HOST' => '127.0.0.1',
     *                      'PORT' => '3306',
     *                      'NAME' => 'kshopxdb',// kshopxdb
     *                      'USER' => 'root',
     *                      'PASS' => '123456',
     *                  ),
     *              ),
     * @param bool $is_master true--主数据库链接，false从数据库链接
     */
    public function connect($config, $is_master = true)
    {
        if (!isset($config) || !is_array($config)) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if ($is_master) {
            //主数据库链接
            if (!isset($config[0]) || !is_array($config[0])) {
                throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
            }

            $db_link = null;

            $params = array();
            $this->APP_DB_PCONNECT && $params[\PDO::ATTR_PERSISTENT] = true;
            $config = $config[0];

            // connect string
            $APP_DB_TYPE = strtolower($this->APP_DB_TYPE);
            switch ($APP_DB_TYPE) {
                case "mysql":
                    $dsn = $APP_DB_TYPE . ':host=' . $config['HOST'];
                    $dsn .= ';port=' . $config['PORT'];
                    $dsn .= ';dbname=' . $config['NAME'];
                    $usr = $config['USER'];
                    $pwd = $config['PASS'];

                    try {
                        $db_link = new \PDO($dsn, $usr, $pwd, $params);
                        $db_link->exec('SET NAMES \'' . $this->APP_DB_CHAR . '\'');
                    } catch (\PDOException $e) {
                        throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($e->getMessage())));
                    }
                    return $db_link;
                    break;
                case "oci":
                    $dsn = $APP_DB_TYPE . ':dbname';
                    $dsn .= '=//' . $config['HOST'];
                    $dsn .= ':' . $config['PORT'];
                    $dsn .= '/' . $config['NAME'];
                    if ($this->APP_DB_CHAR) {
                        $dsn .= ';charset=' . $this->APP_DB_CHAR;
                    }

                    $usr = $config['USER'];
                    $pwd = $config['PASS'];

                    try {
                        $db_link = new \PDO($dsn, $usr, $pwd, $params);
                    } catch (\PDOException $e) {
                        throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($e->getMessage())));
                    }
                    return $db_link;
                    break;
            }
            return $db_link;
        } else {
            //从数据库链接
            $db_links = array();
            $n = 1;
            for ($i = 1; $i < count($config); $i++) {
                if (!isset($config[$i]) || !is_array($config[$i])) {
                    throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
                }


                $db_link = null;

                $params = array();
                $this->APP_DB_PCONNECT && $params[\PDO::ATTR_PERSISTENT] = true;
                $slave_config = $config[$i];

                // connect string
                $APP_DB_TYPE = strtolower($this->APP_DB_TYPE);
                switch ($APP_DB_TYPE) {
                    case "mysql":
                        $dsn = $APP_DB_TYPE . ':host=' . $slave_config['HOST'];
                        $dsn .= ';port=' . $slave_config['PORT'];
                        $dsn .= ';dbname=' . $slave_config['NAME'];
                        $usr = $slave_config['USER'];
                        $pwd = $slave_config['PASS'];

                        try {
                            $db_link = new \PDO($dsn, $usr, $pwd, $params);
                            $db_link->exec('SET NAMES \'' . $this->APP_DB_CHAR . '\'');
                            $db_links[$n] = $db_link;
                            $n++;
                        } catch (\PDOException $e) {

                        }
                        break;
                    case "oci":
                        $dsn = $APP_DB_TYPE . ':dbname';
                        $dsn .= '=//' . $slave_config['HOST'];
                        $dsn .= ':' . $slave_config['PORT'];
                        $dsn .= '/' . $slave_config['NAME'];
                        if ($this->APP_DB_CHAR) {
                            $dsn .= ';charset=' . $this->APP_DB_CHAR;
                        }

                        $usr = $slave_config['USER'];
                        $pwd = $slave_config['PASS'];

                        try {
                            $db_link = new \PDO($dsn, $usr, $pwd, $params);
                            $db_links[$n] = $db_link;
                            $n++;
                        } catch (\PDOException $e) {

                        }
                        break;
                }
            }
            return $db_links;
        }
    }

    /**
     * 得到数据库链接对象，如果是同一请求中获取从数据库连接则使用相同的从数据库连接
     *
     * @param boolean $is_master 返回数据库连接类型 true:返回Master false:返回Slave
     * @return redis object
     */
    public function getConnect($is_master = true)
    {
        if ($is_master) {
            if (isset($this->master_link_ids[$this->curr_cluster_name]) && $this->master_link_ids[$this->curr_cluster_name]) {
                return $this->master_link_ids[$this->curr_cluster_name];
            } else {
                $this->master_link_ids[$this->curr_cluster_name] = $this->connect($this->APP_DB_SERVERS, true);
                return $this->master_link_ids[$this->curr_cluster_name];
            }
        }

        if (!$this->APP_DB_RW_SEPARATE) {
            if (isset($this->master_link_ids[$this->curr_cluster_name]) && $this->master_link_ids[$this->curr_cluster_name]) {
                return $this->master_link_ids[$this->curr_cluster_name];
            } else {
                $this->master_link_ids[$this->curr_cluster_name] = $this->connect($this->APP_DB_SERVERS, true);
                return $this->master_link_ids[$this->curr_cluster_name];
            }
        }
        if (isset($this->curr_link_indexs[$this->curr_cluster_name])) {
            $index = $this->curr_link_indexs[$this->curr_cluster_name];
            return $this->available_link_ids[$this->curr_cluster_name][$index];
        }
        $count = count($this->available_link_ids[$this->curr_cluster_name]);
        $index = intval(mt_rand(0, $count - 1));
        $this->curr_link_indexs[$this->curr_cluster_name] = $index;
        return $this->available_link_ids[$this->curr_cluster_name][$index];
    }


    /**
     * 重置从数据库链接，重置之后再获取从数据库连接则随机获取
     */
    public function resetSlaveConnect()
    {
        unset($this->curr_link_indexs[$this->curr_cluster_name]);
    }

    /**
     * 执行增、册、改语句，将在主数据库链接上进行
     * @param $sql  要执行的sql语句，语句允许使用参数化查询指定参数，例如： :name  :id  或  ? ?
     * @param null $values 如果要执行的sql语句包含参数化查询参数，则必须在该参数中指定对应参数的值，格式如：array(":name"=>"abc",":id"=>12) 或者array("abc",12)
     * @return mixed 返回受影响的记录行数
     * @throws \com\eunionz\exception\DBException
     */
    public function exec($sql, $values = null)
    {
        //使用主数据库连接
        $start_times = explode(' ', microtime());

        $pdo = $this->getConnect(true);
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        // 记录最近的sql语句
        $this->_query_strs[$this->curr_cluster_name] = array(
            'sql' => $sql,
            'params' => $values,
        );
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("PDO Statement error.")));
        }
        if ($values) {
            $result = $_PDOStatement->execute($values);
        } else {
            $result = $_PDOStatement->execute();
        }

        if (false === $result) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));
        }
        $end_times = explode(' ', microtime());
        $use_seconds = ($end_times[1] - $start_times[1]) + ($end_times[0] - $start_times[0]);
        $this->_query_times[$this->curr_cluster_name] = $use_seconds;

        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

//        写数据库日志
//        $this->write_db_logs($pdo, $use_seconds, $values);

        return $_PDOStatement->rowCount();
    }

    private function write_db_logs($pdo, $use_seconds, $values)
    {
        if ($this->APP_DB_SELECT_LOG_ENABLED && $this->APP_DB_SELECT_LOG_TABLE_NAME) {
            try {
                $shop_id = getConfig('app', 'SHOP_ID');
                $visit_url = str_replace("'", "\\'", ($this->server('REQUEST_SCHEME') ? $this->server('REQUEST_SCHEME') : 'http') . "://" . $this->server('HTTP_HOST') . $this->server('REQUEST_URI'));
                $querylog_type = 0;
                $sql = str_replace("'", "\\'", $this->get_sql());
                $sql = "INSERT INTO `" . $this->APP_DB_SELECT_LOG_TABLE_NAME . "` VALUES(0,'{$shop_id}','{$sql}','" . ($values ? str_replace("'", "\\'", implode(',', $values)) : '') . "','{$use_seconds}','" . $visit_url . "','{$querylog_type}','" . date("Y-m-d H:i:s") . "')";
                $tmp_PDOStatement = $pdo->prepare($sql);
                $tmp_PDOStatement->execute();
                ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

            } catch (\Exception $err) {

            }
        }
    }

    /**
     * 执行查询语句，将在主数据库或从数据库中执行查询语句
     * @param string $sql 要执行的sql语句，语句允许使用参数化查询指定参数，例如： :name  :id  或  ? ?
     * @param null $values 如果要执行的sql语句包含参数化查询参数，则必须在该参数中指定对应参数的值，格式如：array(":name"=>"abc",":id"=>12) 或者array("abc",12)
     * @param bool $index false--返回关联数组  true--返回数字数组
     *
     * @return    array
     */
    public function query($sql, $values = null, $index = false)
    {
        //使用从数据库连接
        if ((isset($this->_trans_times[$this->curr_cluster_name]) && $this->_trans_times[$this->curr_cluster_name] > 0) || (isset($this->_xa_trans_status[$this->curr_cluster_name]) && $this->_xa_trans_status[$this->curr_cluster_name])) {
            $pdo = $this->getConnect();
        } else {
            $pdo = $this->getConnect(false);
        }
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        $start_times = explode(' ', microtime());

        // 记录最近的sql语句
        $this->_query_strs[$this->curr_cluster_name] = array(
            'sql' => $sql,
            'params' => $values,
        );

        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("PDO Statement error.")));
        }

        // return number index ?
        if (!$index) {
            $_PDOStatement->setFetchMode(\PDO::FETCH_ASSOC);
        }

        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        if ($values) {
            $result = $_PDOStatement->execute($values);
        } else {
            $result = $_PDOStatement->execute();
        }

        if (false === $result) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));
        }

        // return rows collection
        $rs = $_PDOStatement->fetchAll();

        $end_times = explode(' ', microtime());
        $use_seconds = ($end_times[1] - $start_times[1]) + ($end_times[0] - $start_times[0]);
        $this->_query_times[$this->curr_cluster_name] = $use_seconds;

        //写数据库日志
        $this->write_db_logs($pdo, $use_seconds, $values);

        return $rs;
    }

    /**
     * 开始事务，事务总是在主数据库链接中进行
     * @return    bool
     */
    public function start_trans()
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (!isset($this->_trans_times[$this->curr_cluster_name]) || $this->_trans_times[$this->curr_cluster_name] == 0) {
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
            $sql = 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;';//设置当前会话的事务隔离等级为已提交读
            $pdo->exec($sql);
            $pdo->beginTransaction();
        } else {
            $sql = 'SAVEPOINT savepoint_' . $this->_trans_times[$this->curr_cluster_name];//创建事务保存点
            $pdo->exec($sql);
        }

        if (!isset($this->_trans_times[$this->curr_cluster_name])) {
            $this->_trans_times[$this->curr_cluster_name] = 1;
        } else {
            $this->_trans_times[$this->curr_cluster_name]++;
        }
        return true;
    }


    /**
     * 提交事务
     * @return    bool
     */
    public function commit()
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }

        if (!isset($this->_trans_times[$this->curr_cluster_name]) || $this->_trans_times[$this->curr_cluster_name] <= 0) {
            $this->_trans_times[$this->curr_cluster_name] = 0;
        } else {
            $this->_trans_times[$this->curr_cluster_name]--;
        }
        if ($this->_trans_times[$this->curr_cluster_name] <= 0) {
            $result = $pdo->commit();
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
            $this->_trans_times[$this->curr_cluster_name] = 0;
            if (!$result) {
                throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("tran commit fail.")));
                return false;
            }
        }
        return true;
    }


    /**
     * 回滚事务
     *
     * @return    bool
     */
    public function rollback()
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }

        if (!isset($this->_trans_times[$this->curr_cluster_name]) || $this->_trans_times[$this->curr_cluster_name] <= 0) {
            $this->_trans_times[$this->curr_cluster_name] = 0;
        } else {
            $this->_trans_times[$this->curr_cluster_name]--;
        }
        if ($this->_trans_times[$this->curr_cluster_name] <= 0) {
            $result = $pdo->rollback();
            $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
            $this->_trans_times[$this->curr_cluster_name] = 0;

            if (!$result) {
                throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("tran rollback fail.")));
                return false;
            }
        } else {
            $sql = 'ROLLBACK TO SAVEPOINT savepoint_' . $this->_trans_times[$this->curr_cluster_name];//回滚到指定事务保存点
            $pdo->exec($sql);
        }
        return true;
    }


    /**
     * 获取字段列表，仅在主数据库链接上获取字段列表
     * @param string $table_name
     * @return    array
     */
    public function get_fields($table_name)
    {
        if (empty($table_name)) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo_table'));
            return false;
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        switch (strtoupper($this->APP_DB_TYPE)) {
            case 'MSSQL' :
                $sql = "SELECT column_name as 'Name',   data_type as 'Type',   column_default as 'Default',   is_nullable as 'Null'
			        FROM    information_schema.tables AS t
			        JOIN    information_schema.columns AS c
			        ON  t.table_catalog = c.table_catalog
			        AND t.table_schema  = c.table_schema
			        AND t.table_name    = c.table_name
			        WHERE   t.table_name = '$table_name'";
                break;

            case 'SQLITE' :
                $sql = 'PRAGMA table_info (' . $table_name . ') ';
                break;

            case 'ORACLE' :
            case 'OCI' :
                $sql = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" " . "FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col " . "WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='" . strtoupper(str_replace('"', '', $table_name)) . "') b where table_name='" . strtoupper(str_replace('"', '', $table_name)) . "' and a.column_name=b.column_name(+)";
                break;

            case 'PGSQL' :
                $sql = 'select fields_name as "Field",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg(' . $table_name . ');';
                break;

            case 'MYSQL' :
            default :
                $table_name = (0 === strpos($table_name, '`')) ? $table_name : "`$table_name`";
                $sql = 'DESCRIBE ' . $table_name;
        }


        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();

        if (false === $result) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));
        }

        // return rows collection
        $result = $_PDOStatement->fetchAll();
        if (empty($result))
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("get `{$table_name}` table fields fial.")));


        $fields = array();

        if ($result) {
            foreach ($result as $val) {
                $name = (isset($val['Field']) ? $val['Field'] : $val['Name']);

                if (isset($val['Key']) ? strtolower($val['Key']) == 'pri' : (isset($val['pk']) ? $val['pk'] : false))
                    $fields['primary'] = $name;

                if (!isset($fields['primary']))
                    throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo_table_primary', array($table_name)));


                $fields['fields'][] = $name;
                $fields['struct'][] = array(
                    'name' => $name,
                    'type' => $val['Type'],
                    'notnull' => (bool)(((isset($val['Null'])) && ($val['Null'] === 'NO')) || (isset($val['notnull']) && $val['notnull'] === 'YES')),
                    'default' => isset($val['Default']) ? $val['Default'] : (isset($val['dflt_value']) ? $val['dflt_value'] : ""),
                    'primary' => $fields['primary'],
                    'autoinc' => isset($val['Extra']) ? strtolower($val['Extra']) == 'auto_increment' : (isset($val['Key']) ? $val['Key'] : false),
                    'convert' => $this->convert(strtoupper($this->APP_DB_TYPE), $val['Type'])
                );
            }
        }

        return $fields;
    }


    /**
     * 获取所有表，基于主数据库连接进行
     * @return    array
     */
    public function get_tables()
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }

        switch (strtoupper($this->APP_DB_TYPE)) {
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


        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("PDO Statement error.")));
        }

        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();

        if (false === $result)
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));

        $result = $_PDOStatement->fetchAll();
        if (empty($result))
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));


        $info = array();

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }


    /**
     * 获取最后一次自动增长字段的值，只能在主数据库链接上进行
     * @param $sequence_name  序列名称
     * @return    integer
     */
    public function get_insert_id($sequence_name = '')
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }

        switch (strtoupper($this->APP_DB_TYPE)) {
            case 'ORACLE' :
            case 'OCI' :
                $sql = "SELECT {$sequence_name}.CURRVAL AS ID FROM DUAL";
                $_PDOStatement = $pdo->prepare($sql);
                if (!$_PDOStatement) {
                    throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("PDO Statement error.")));
                }
                ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

                $result = $_PDOStatement->execute();

                if (false === $result)
                    throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));

                $result = $_PDOStatement->fetchAll();
                if (empty($result))
                    throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));
                if ($result) return $result[0]['ID'];
                return 1;
            case 'PGSQL' :
                return $pdo->lastInsertId($sequence_name);
            case 'MSSQL' :
            case 'SQLITE' :
            case 'MYSQL' :
            default :
                return $pdo->lastInsertId();
        }
    }


    /**
     * 获取最近一次执行的sql 语句
     * @param array
     * @return    string
     */
    public function get_sql($params = null)
    {
        if (empty($params) || !is_array($params)) {
            if (!isset($this->_query_strs[$this->curr_cluster_name])) {
                return "";
            }
            $params = $this->_query_strs[$this->curr_cluster_name];
            if (empty($params)) return "";
        }
        if (!isset($params['sql'])) {
            return "";
        }
        if (!isset($params['params']) || empty($params['params'])) {
            return $params['sql'];
        } else {
            $sql = $params['sql'];
            $sqls = explode('?', $sql);
            $sql = $sqls[0];
            for ($i = 1; $i < count($sqls); $i++) {
                $sql .= '?' . ($i - 1) . '?' . $sqls[$i];
            }
            foreach ($params['params'] as $key => $value) {
                if (is_numeric($key)) {
                    $sql = str_replace('?' . $key . '?', "'" . str_replace("'", "''", $value) . "'", $sql);
                } else {
                    $sql = str_replace($key, "'" . str_replace("'", "''", $value) . "'", $sql);
                }
            }
            return $sql;
        }
    }

    /**
     * 获取最近一次执行的sql 语句的使用时间，单位：秒
     * @return    number
     */
    public function get_use_time()
    {
        if (isset($this->_query_times[$this->curr_cluster_name])) {
            return $this->_query_times[$this->curr_cluster_name];
        }
        return 0;
    }

    /**
     * 获取pdo 语句对象的最近一次错误信息
     * @return string
     */
    public function get_last_error($_PDOStatement)
    {
        return implode(' ', $_PDOStatement->errorInfo());
    }

    /**
     * 获取当前数据库类型中指定数据类型的php转换函数
     * @param $db_type  数据库类型
     * @param $type 数据类型
     */
    public function convert($db_type, $type)
    {
        $arr = explode('(', $type);
        $type_name = strtolower($arr[0]);
        switch ($db_type) {
            case 'ORACLE' :
            case 'OCI' :
                if (in_array($type_name, array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'))) {
                    return "bcadd(%1, 0)";
                } elseif (in_array($type_name, array('raw', 'long', 'blob', 'bfile'))) {
                    return "(binary)%1";
                } elseif (in_array($type_name, array('real', 'single', 'double', 'float', 'decimal', 'number', 'numeric'))) {
                    return "bcadd(%1, 0.00)";
                } elseif (in_array($type_name, array('date'))) {
                    return "is_numeric(%1)?date('d-m-Y H:i:s',%1):%1";
                }
                return "";
            case 'PGSQL' :
                if (in_array($type_name, array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year'))) {
                    return "bcadd(%1, 0)";
                } elseif (in_array($type_name, array('bit', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'varbinary'))) {
                    return "(binary)%1";
                } elseif (in_array($type_name, array('real', 'single', 'double', 'float', 'decimal', 'number', 'numeric'))) {
                    return "bcadd(%1, 0.00)";
                } elseif (in_array($type_name, array('date'))) {
                    return "is_numeric(%1)?date('Y-m-d',%1):%1";
                } elseif (in_array($type_name, array('time'))) {
                    return "is_numeric(%1)?date('H:i:s',%1):%1";
                } elseif (in_array($type_name, array('datetime', 'timestamp'))) {
                    return "is_numeric(%1)?date('Y-m-d H:i:s',%1):%1";
                }
                return "";
            case 'MSSQL' :
            case 'SQLITE' :
            case 'MYSQL' :
            default :
                if (in_array($type_name, array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year'))) {
                    return "bcadd(%1, 0)";
                } elseif (in_array($type_name, array('bit', 'tinyblob', 'blob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'varbinary'))) {
                    return "(binary)%1";
                } elseif (in_array($type_name, array('real', 'single', 'double', 'float', 'decimal', 'number', 'numeric'))) {
                    return "bcadd(%1, 0.00)";
                } elseif (in_array($type_name, array('date'))) {
                    return "is_numeric(%1)?date('Y-m-d',%1):%1";
                } elseif (in_array($type_name, array('time'))) {
                    return "is_numeric(%1)?date('H:i:s',%1):%1";
                } elseif (in_array($type_name, array('datetime', 'timestamp'))) {
                    return "is_numeric(%1)?date('Y-m-d H:i:s',%1):%1";
                }
        }
        return "";
    }

    /**
     * 开始一个分布式事务
     * @param null $xa_name 分布式事务名称，如果为空由系统自动生成
     */
    public function xa_start($xa_name = null)
    {
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (empty($xa_name)) {
            $xa_name = uniqid('xa_');
        }
        $sql = "XA START '{$xa_name}'";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();
        if ($result) {
            $this->_xa_trans_names[$this->curr_cluster_name] = $xa_name;
            $this->_xa_trans_status[$this->curr_cluster_name] = true;
        }
        return $result;
    }


    /**
     * 结束一个分布式事务
     * @param null $xa_name 分布式事务名称，如果为空由自动获取
     */
    public function xa_end($xa_name = null)
    {
        if (!isset($this->_xa_trans_status[$this->curr_cluster_name]) || !$this->_xa_trans_status[$this->curr_cluster_name]) {
            //如果没有开始分布式事务，则直接结束
            return false;
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (empty($xa_name)) {
            $xa_name = $this->_xa_trans_names[$this->curr_cluster_name];
        }
        $sql = "XA END '{$xa_name}'";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();
        return $result;
    }


    /**
     * 准备一个分布式事务
     * @param null $xa_name 分布式事务名称，如果为空由自动获取
     */
    public function xa_prepare($xa_name = null)
    {
        if (!isset($this->_xa_trans_status[$this->curr_cluster_name]) || !$this->_xa_trans_status[$this->curr_cluster_name]) {
            //如果没有开始分布式事务，则直接返回
            return false;
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (empty($xa_name)) {
            $xa_name = $this->_xa_trans_names[$this->curr_cluster_name];
        }
        $sql = "XA PREPARE '{$xa_name}'";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();
        return $result;
    }


    /**
     * 提交一个分布式事务
     * @param null $xa_name 分布式事务名称，如果为空由自动获取
     */
    public function xa_commit($xa_name = null)
    {
        if (!isset($this->_xa_trans_status[$this->curr_cluster_name]) || !$this->_xa_trans_status[$this->curr_cluster_name]) {
            //如果没有开始分布式事务，则直接返回
            return false;
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (empty($xa_name)) {
            $xa_name = $this->_xa_trans_names[$this->curr_cluster_name];
        }
        $sql = "XA COMMIT '{$xa_name}'";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();
        unset($this->_xa_trans_names[$this->curr_cluster_name]);
        $this->_xa_trans_status[$this->curr_cluster_name] = false;
        return $result;
    }


    /**
     * 回滚一个分布式事务
     * @param null $xa_name 分布式事务名称，如果为空由自动获取
     */
    public function xa_rollback($xa_name = null)
    {
        if (!isset($this->_xa_trans_status[$this->curr_cluster_name]) || !$this->_xa_trans_status[$this->curr_cluster_name]) {
            //如果没有开始分布式事务，则直接返回
            return false;
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        if (empty($xa_name)) {
            $xa_name = $this->_xa_trans_names[$this->curr_cluster_name];
        }
        $sql = "XA ROLLBACK '{$xa_name}'";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();
        unset($this->_xa_trans_names[$this->curr_cluster_name]);
        $this->_xa_trans_status[$this->curr_cluster_name] = false;
        return $result;
    }


    /**
     * 查看处于准备阶段的所有分布式事务
     * @return array
     */
    public function xa_recover()
    {
        if (!isset($this->_xa_trans_status[$this->curr_cluster_name]) || !$this->_xa_trans_status[$this->curr_cluster_name]) {
            //如果没有开始分布式事务，则直接返回
            return array();
        }
        $pdo = $this->getConnect();
        if (!$pdo) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
        }
        $sql = "XA RECOVER";
        $_PDOStatement = $pdo->prepare($sql);
        if (!$_PDOStatement) {
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array("XA PDO Statement error.")));
        }
        ctx()->setExecuteQuerys(ctx()->getExecuteQuerys() + 1);

        $result = $_PDOStatement->execute();

        if (false === $result)
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));

        $result = $_PDOStatement->fetchAll();
        if (empty($result))
            throw new \com\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($this->get_last_error($_PDOStatement))));

        return $result;
    }

    ////////////////////////////////////////////////////////////////////////////////////
    //////////////              分布式事务使用事例开始                      ////////////
    //        $pdo = $this->db();
    //        $db_config = null;
    //        $pdo1 = $this->db("test",$db_config);
    //
    //            //数据库1的事务
    //        $pdo->xa_start();
    //        $xa_rs=false;
    //        try{
    //        $sql = "INSERT INTO `sessions` VALUES(?,?,?,?)";
    //        $num = $pdo->exec($sql, array('11111111111234521', 15, 1, '4334443433443'));
    //        if (!$num) {
    //        throw  new \Exception("ddd");
    //        }
    //        $sql = "SELECT * FROM `sessions`";
    //        $rs = $pdo->query($sql);
    //        $xa_rs = true;
    //        }catch (\Exception $err){
    //
    //        }
    //        $pdo->xa_end();
    //        if($xa_rs){
    //            $pdo->xa_prepare();
    //        }
    //
    //        //数据库2的事务
    //        $pdo1->xa_start();
    //        $xa1_rs=false;
    //        try{
    //            $sql = "INSERT INTO `sessions` VALUES(?,?,?,?)";
    //            $num = $pdo1->exec($sql, array('11111111111234521', 15, 1, '4334443433443'));
    //            if (!$num) {
    //                throw  new \Exception("ddd");
    //            }
    //            $sql = "SELECT * FROM `sessions`";
    //            $rs = $pdo1->query($sql);
    //
    //            $sql = "INSERT INTO `sessions` VALUES(?,?,?,?)";
    //            $num = $pdo1->exec($sql, array('11111111111234521', 15, 1, '4334443433443'));
    //            if (!$num) {
    //                throw  new \Exception("ddd");
    //            }
    //
    //            $xa1_rs = true;
    //        }catch (\Exception $err){
    //
    //        }
    //        $pdo1->xa_end();
    //        if($xa1_rs){
    //            $pdo1->xa_prepare();
    //        }
    //
    //        if($xa_rs && $xa1_rs){
    //            $pdo->xa_commit();
    //            $pdo1->xa_commit();
    //        }else{
    //            $pdo->xa_rollback();
    //            $pdo1->xa_rollback();
    //        }
    //////////////              分布式事务使用事例结束                      ////////////
    ////////////////////////////////////////////////////////////////////////////////////


}
