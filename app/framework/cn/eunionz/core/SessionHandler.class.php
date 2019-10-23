<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Launcher (bootstrap class ,to complete  load *.config.php,parse url,find controller ,execute controller , render view, cache view  )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */

namespace cn\eunionz\core;

defined('APP_IN') or exit('Access Denied');

class SessionHandler extends Kernel
{

    /**
     * Session模式 目录仅支持：file|redis
     * @var string
     */
    private $APP_SESSION_MODE = 'file';

    /**
     * 会话生命周期 单位：秒
     * @var int
     */
    private $APP_SESSION_LIFETIME_SECONDS = 7200;

    /**
     * redis会话模式Redis连接配置，格式如： array(
     *   'server' => '192.168.1.125',     //从redis服务器地址或域名
     *   'port' => '6377',                //从redis服务器端口
     *   'password' => 'zFymUyDG',        //从redis密码
     *   'dbname'=>14,                     ////redis服务器选择的数据库编号
     * )
     * @var null
     */
    private $APP_SESSION_REDIS_CONFIG = null;

    /**
     * file会话模式会话文件夹名称，此文件夹位于 APP_RUNTIME_REAL_PATH 常量文件夹下
     * @var string
     */
    private $APP_SESSION_DIR = "";

    /**
     * sql会话模式下会话表名称,表结构：field: session_id varchar(50) primary, expiry int unsigned ,value text
     * @var string
     */
    private $APP_SESSION_TABLE_NAME = "";

    /**
     * sql会话模式mysql连接配置，格式如：array(
     * 'HOST' => '192.168.1.125',  //从mysql服务器地址或域名
     * 'PORT' => '3306',            //从mysql服务器端口
     * 'USER' => 'root',            //从mysql连接用户名
     * 'PASS' => '123456',          //从mysql连接密码
     * 'NAME' => 'kiddevdb',        //从mysql连接数据库
     * ),
     * @var null
     */
    private $APP_SESSION_MYSQL_CONFIG = null;

    /**
     * Redis会话模式时使用的redis协程连接对像
     * @var null|\Swoole\Coroutine\Redis
     */
    private $redis = null;

    /**
     * sql会话模式时使用的mysql协程连接对像
     * @var null
     */
    private $mysql = null;

    /**
     * file会话模式时文件夹物理路径
     * @var string
     */
    private $session_real_path = '';

    /**
     * 会话ID 名称前缀
     * @var string
     */
    private $session_prefix = 'session:';




    /**
     * Session会话构造函数
     * SessionHandler constructor.
     * @param string $mode 会话模式：file|redis|sql
     * @param int $lefttime
     * @param null $redis_config
     * @param string $session_dir
     * @param string $session_table
     */
    public function __construct($mode = 'file', $lefttime = 7200, $redis_config = null, $session_dir = 'session', $session_table = 'sessions.class', $mysql_config = null)
    {
        $this->APP_SESSION_MODE = $mode;
        $this->APP_SESSION_LIFETIME_SECONDS = $lefttime;
        $this->APP_SESSION_REDIS_CONFIG = $redis_config;
        $this->APP_SESSION_DIR = $session_dir;
        $this->APP_SESSION_TABLE_NAME = $session_table;
        $this->APP_SESSION_MYSQL_CONFIG = $mysql_config;
        switch (strtolower($this->APP_SESSION_MODE)) {
            case 'redis':
//                $this->redis = new \Swoole\Coroutine\Redis();
                $this->redis = new \Redis();
                if (false === $this->redis->connect($this->APP_SESSION_REDIS_CONFIG['server'], (int)$this->APP_SESSION_REDIS_CONFIG['port'])) {
                    throw new \Exception(ctx()->getI18n()->getLang("error_session_redis_connect_fail", array($this->APP_SESSION_REDIS_CONFIG['server'] . ':' . $this->APP_SESSION_REDIS_CONFIG['port'])));
                }
                if ("" !== $this->APP_SESSION_REDIS_CONFIG['password']) {
                    if (!$this->redis->auth($this->APP_SESSION_REDIS_CONFIG['password'])) {
                        throw new \Exception(ctx()->getI18n()->getLang("error_session_redis_auth_fail", array($this->APP_SESSION_REDIS_CONFIG['server'] . ':' . $this->APP_SESSION_REDIS_CONFIG['port'])));
                    }
                }
                if (!$this->redis->select($this->APP_SESSION_REDIS_CONFIG['dbname'])) {
                    throw new \Exception(ctx()->getI18n()->getLang("error_session_redis_auth_fail", array($this->APP_SESSION_REDIS_CONFIG['server'] . ':' . $this->APP_SESSION_REDIS_CONFIG['port']), $this->APP_SESSION_REDIS_CONFIG['dbname']));
                }
                break;
            case 'sql':

                if (APP_IS_IN_SWOOLE) {
                    $this->mysql = new \Swoole\Coroutine\MySQL();
                    if (!$this->mysql->connect(['host' => $this->APP_SESSION_MYSQL_CONFIG['HOST'], 'port' => $this->APP_SESSION_MYSQL_CONFIG['PORT'], 'user' => $this->APP_SESSION_MYSQL_CONFIG['USER'],
                        'password' => $this->APP_SESSION_MYSQL_CONFIG['PASS'], 'database' => $this->APP_SESSION_MYSQL_CONFIG['NAME'], 'charset' => $this->APP_SESSION_MYSQL_CONFIG['CHARSET'], 'timeout' => 3,
                    ])) {
                        throw new \Exception(ctx()->getI18n()->getLang("error_session_redis_connect_fail", array($this->APP_SESSION_MYSQL_CONFIG['HOST'] . ':' . $this->APP_SESSION_MYSQL_CONFIG['port'])));
                    }
                } else {
                    $dsn = 'mysql:host=' . $this->APP_SESSION_MYSQL_CONFIG['HOST'];
                    $dsn .= ';port=' . $this->APP_SESSION_MYSQL_CONFIG['PORT'];
                    $dsn .= ';dbname=' . $this->APP_SESSION_MYSQL_CONFIG['NAME'];
                    $usr = $this->APP_SESSION_MYSQL_CONFIG['USER'];
                    $pwd = $this->APP_SESSION_MYSQL_CONFIG['PASS'];
                    try {
                        $params = array();
                        $this->mysql = new \PDO($dsn, $usr, $pwd, $params);
                        $this->mysql->exec('SET NAMES \'' . $this->APP_SESSION_MYSQL_CONFIG['CHARSET'] . '\'');
                    } catch (\PDOException $e) {
                        throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_pdo', array($e->getMessage())));
                    }
                }
                break;
            case 'file':
                $this->session_prefix = str_replace(':', '_', $this->session_prefix);
                $this->session_lock_prefix = str_replace(':', '_', $this->session_lock_prefix);
                if (!ctx()->getAppRuntimeRealPath()) {
                    throw new \Exception(ctx()->getI18n()->getLang("error_session_file_init_fail"));
                }
                $this->session_real_path = ctx()->getAppRuntimeRealPath() . $this->APP_SESSION_DIR . APP_DS;
            default:
                break;
        }
    }


    /**
     * 从会话中读数据
     * @param $session_id 会话ID
     */
    public function read($session_id)
    {
        if (empty($session_id)) return [];
        $sessin_redis_name  = $this->session_prefix . $session_id;
        $SESSION = [];

        switch (strtolower($this->APP_SESSION_MODE)) {
            case 'redis':
                try{
                    if ($this->lock($sessin_redis_name)) {
                        if ($this->redis->get($sessin_redis_name)) {
                            $SESSION = unserialize($this->redis->get($sessin_redis_name));
                        } else {
                            $SESSION = [];
                        }
                    }else{
                        throw new \Exception("Redis session lock fail. ");
                    }
                }catch (\Exception $err){
                    throw $err;
                }finally{
                    $this->unlock($sessin_redis_name);
                }
                if (empty($SESSION)) $SESSION = [];
                break;
            case 'sql':
                //field: session_id varchar(50) primary, expiry int unsigned ,value text

                if (APP_IS_IN_SWOOLE) {
                    $sql = "SELECT `value` FROM `" . $this->APP_SESSION_TABLE_NAME . '` WHERE `session_id`=? AND `expiry`>=? FOR UPDATE';
                    $stmt = $this->mysql->prepare($sql);
                    if ($stmt) {
                        $res = $stmt->execute(array($this->session_prefix . $session_id, time()));
                        if ($res) {
                            $SESSION = unserialize($res[0]['value']);
                        }
                    }
                }else{
                    $sql = "SELECT `value` FROM `" . $this->APP_SESSION_TABLE_NAME . '` WHERE `session_id`=? AND `expiry`>=? FOR UPDATE';
                    $stmt = $this->mysql->prepare($sql);
                    if ($stmt) {
                        $res = $stmt->execute(array($this->session_prefix . $session_id, time()));
                        $rs = [];
                        if ($res) {
                            $rs = $stmt->fetchAll();
                        }
                        if ($rs) {
                            $SESSION = unserialize($rs[0]['value']);
                        }
                    }
                }
                if (empty($SESSION)) $SESSION = [];
                break;
            case 'file':
                $file = $this->session_real_path . $this->session_prefix . $session_id;
                if(is_file($file)){
                    $SESSION = unserialize(file_get_contents($file));
                }
                if (empty($SESSION)) $SESSION = [];

            default:
                break;
        }
        return $SESSION;
    }

    /**
     * 向会话中写入数据
     * @param $session_id 会话ID
     * @param $session_data 数据
     */
    public function write($session_id, $SESSION)
    {
        if (empty($session_id)) return false;
        if (empty($SESSION)) {
            $SESSION = [];
        }
        $sessin_redis_name  = $this->session_prefix . $session_id;
        switch (strtolower($this->APP_SESSION_MODE)) {
            case 'redis':
                try{
                    if ($this->lock($sessin_redis_name)) {
                        $session_datas = serialize($SESSION);
                        $this->redis->setex($sessin_redis_name, intval($this->APP_SESSION_LIFETIME_SECONDS), $session_datas);
                    }else{
                        throw new \Exception("Redis session lock fail. ");
                    }
                }catch (\Exception $err){
                    throw $err;
                }finally{
                    $this->unlock($sessin_redis_name);
                }
                return true;
                break;
            case 'sql':
                //field: session_id varchar(50) primary, expiry int unsigned ,value text
                if (APP_IS_IN_SWOOLE) {
                    $this->mysql->begin();
                    try {
                        $sql = "SELECT `value` FROM `" . $this->APP_SESSION_TABLE_NAME . '` WHERE `session_id`=? AND `expiry`>=?';
                        $stmt = $this->mysql->prepare($sql);
                        if ($stmt) {
                            $res = $stmt->execute(array($this->session_prefix . $session_id, time()));
                            if ($res) {
                                $sql = "UPDATE `" . $this->APP_SESSION_TABLE_NAME . '` SET `value` =?,`expiry`=? WHERE `session_id`=?';
                                $stmt = $this->mysql->prepare($sql);
                                if ($stmt) {
                                    $session_datas = serialize($SESSION);
                                    $stmt->execute(array($session_datas, time() + $this->APP_SESSION_LIFETIME_SECONDS, $this->session_prefix . $session_id));
                                }
                            } else {
                                $sql = "INSERT INTO `" . $this->APP_SESSION_TABLE_NAME . '` VALUES(?,?,?)';
                                $stmt = $this->mysql->prepare($sql);
                                if ($stmt) {
                                    $session_datas = serialize($SESSION);
                                    $stmt->execute(array($this->session_prefix . $session_id, time() + $this->APP_SESSION_LIFETIME_SECONDS, $session_datas));
                                }
                            }
                        }
                        $this->mysql->commit();
                        return true;
                    } catch (\Exception $err) {
                        $this->mysql->rollback();
                        return false;
                    }
                } else {
                    $this->mysql->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
                    $sql = 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;';//设置当前会话的事务隔离等级为已提交读
                    $this->mysql->exec($sql);
                    $this->mysql->beginTransaction();
                    try {
                        $sql = "SELECT `value` FROM `" . $this->APP_SESSION_TABLE_NAME . '` WHERE `session_id`=? AND `expiry`>=?';
                        $stmt = $this->mysql->prepare($sql);
                        if ($stmt) {
                            $res = $stmt->execute(array($this->session_prefix . $session_id, time()));
                            $rs = [];
                            if ($res) {
                                $rs = $stmt->fetchAll();
                            }
                            if ($rs) {
                                $sql = "UPDATE `" . $this->APP_SESSION_TABLE_NAME . '` SET `value` =?,`expiry`=? WHERE `session_id`=?';
                                $stmt = $this->mysql->prepare($sql);
                                if ($stmt) {
                                    $session_datas = serialize($SESSION);
                                    $stmt->execute(array($session_datas, time() + $this->APP_SESSION_LIFETIME_SECONDS, $this->session_prefix . $session_id));
                                }
                            } else {
                                $sql = "INSERT INTO `" . $this->APP_SESSION_TABLE_NAME . '` VALUES(?,?,?)';
                                $stmt = $this->mysql->prepare($sql);
                                if ($stmt) {
                                    $session_datas = serialize($SESSION);
                                    $stmt->execute(array($this->session_prefix . $session_id, time() + $this->APP_SESSION_LIFETIME_SECONDS, $session_datas));
                                }
                            }
                        }
                        $this->mysql->commit();
                        $this->mysql->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
                        return true;
                    } catch (\Exception $err) {
                        $this->mysql->rollback();
                        $this->mysql->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
                        return false;
                    }
                }
                break;
            case 'file':
                $file = $this->session_real_path . $this->session_prefix . $session_id;
                return file_put_contents($file, serialize($SESSION), LOCK_EX) > 0 ? true : false;
            default:
                break;
        }
        return false;
    }

}