<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Model class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\core;

use cn\eunionz\component\cdb\Cdb;
use cn\eunionz\exception\ModelException;
use cn\eunionz\exception\ModelValidateException;

defined('APP_IN') or exit('Access Denied');

class Model extends Kernel
{

    use KernelTrait;

    /**
     * 存在就验证
     */
    const VALIDATE_EXISTS = 0;

    /**
     * 必须验证
     */
    const VALIDATE_MUST = 1;

    /**
     * 不为空字符串就验证
     */
    const VALIDATE_VALUE = 2;

    /**
     * 模型添加时验证
     */
    const MODEL_INSERT = 1;


    /**
     * 模型删除时验证
     */
    const MODEL_DELETE = 2;


    /**
     * 模型修改时验证
     */
    const MODEL_UPDATE = 4;


    /**
     * 模型修改或删除时验证
     */
    const MODEL_UPDATE_AND_DELETE = 6;

    /**
     * 模型修改或添加时验证
     */
    const MODEL_UPDATE_AND_INSERT = 5;

    /**
     * 模型删除或添加时验证
     */
    const MODEL_DELETE_AND_INSERT = 3;

    /**
     * 模型删除、修改或添加时验证
     */
    const MODEL_ALL = 7;

    /**
     * 客户端验证
     */
    const VALIDATE_CLIENT = 1;

    /**
     * 服务器端验证
     */
    const VALIDATE_SERVER = 2;


    /**
     * 客户端和服务器端验证
     */
    const VALIDATE_BOTH = 3;


    /**
     * 适用于支持序列的数据库，为主键字段设置序列名称
     * @var array
     */
    public $_pk_sequence_name = null;

    /**
     * 是否开启验证
     * @var bool
     */
    public $_is_validate = false;

    /**
     * 验证规则
     * @var array
     */
    public $_validate_rules = array();


    /**
     * 表名
     * @var string
     */
    public $tablename = "";


    /**
     * 默认查询参数
     * @var array
     */
    public $_params = array();

    /**
     * 模型所使用的数据库配置文件主文件名定义，使用格式如：'db'  'db1'
     * @var string
     */
    public $db_config_name = 'db';

    /**
     * 模型使用的集群定义
     * @var string
     */
    protected $db_cluster_name = 'default';


    /**
     * 是否使用协程数据库
     * @var bool
     */
    private $isCoroutine = false;

    /**
     * 是否使用协程数据库
     * @return bool
     */
    public function isCoroutine(): bool
    {
        return $this->isCoroutine;
    }

    /**
     * 设置是否使用协程数据库
     * @param bool $isCoroutine
     */
    public function setIsCoroutine(bool $isCoroutine): void
    {
        $this->isCoroutine = $isCoroutine;
    }

    /**
     * 初始化Model
     * @param string $tablename
     */
    public function initialize(string $tablename = ""): void
    {

        if (empty($tablename)) {
            $temps = explode("\\", get_called_class());
            $this->tablename = strtolower($temps[count($temps) - 1]);
        } else {
            $this->tablename = strtolower($tablename);
        }
        $this->isCoroutine = self::getConfig($this->db_config_name, 'APP_MODEL_IS_COROUTINE');

    }


    /**
     * 设置模型数据库配置及集群
     * @param string $db_cluster_name 群集名称  'default'
     * @param string|null $db_config_name 数据库配置文件主文件名,'db' 'db1'
     * @return Model
     */
    public function set_db_config(string $db_cluster_name = 'default', string $db_config_name = null): Model
    {
        $this->db_config_name = $db_config_name;
        $this->db_cluster_name = $db_cluster_name;
        return $this;
    }


    /**
     * 默认验证方法
     * @param array $model
     * @param int $action
     * @return \stdClass
     */
    protected function _validate(array $model, int $action): \stdClass
    {
        $obj = new \stdClass();
        $obj->success = true;
        $obj->msg = "";
        if (!$this->_is_validate) return $obj;


        if (!$this->_validate_rules || !is_array($this->_validate_rules)) return $obj;

        $obj->success = true;
        $obj->msg = "";

        if ($this->_validate_rules) {
            foreach ($this->_validate_rules as $field => $rules) {
                if (!is_array($rules)) {
                    continue;
                }
                $field_text = '';
                foreach ($rules as $rule) {
                    if (is_string($rule)) {
                        $field_text = $rule;
                    } else {
                        $field_validate_mode = isset($rule[0]) ? $rule[0] : self::VALIDATE_BOTH;
                        $field_rule = isset($rule[1]) ? $rule[1] : '';
                        $field_error = (isset($rule[2]) ? $rule[2] : '');
                        $field_error = str_replace('{0}', $field_text, $field_error);
                        $field_error = str_replace('{1}', isset($model[$field]) ? $model[$field] : '', $field_error);

                        $field_validate_type = isset($rule[3]) ? $rule[3] : self::VALIDATE_MUST;
                        $field_ext_datas = isset($rule[4]) ? $rule[4] : array();

                        if (is_string($field_ext_datas)) $field_ext_datas = explode(',', $field_ext_datas);
                        $field_validate_action = isset($rule[5]) ? $rule[5] : self::MODEL_ALL;
                        if ($field_rule == 'equalTo') {
                            $field_ext_datas = $model[$field_ext_datas[0]];
                        } else if ($field_rule == 'maxlength' || $field_rule == 'minlength' || $field_rule == 'length' || $field_rule == 'min' || $field_rule == 'max' || $field_rule == 'regex' || $field_rule == 'equal' || $field_rule == 'notequal') {
                            $field_ext_datas = $field_ext_datas[0];
                        }
                        $field_error = str_replace('{2}', is_array($field_ext_datas) ? implode(',', $field_ext_datas) : $field_ext_datas, $field_error);

                        if ($field_validate_mode & self::VALIDATE_SERVER) {

                            switch ($field_validate_type) {
                                case self::VALIDATE_MUST:
                                    if ($field_validate_action & $action) {
                                        if (!isset($model[$field])) {
                                            $obj->success = false;
                                            $obj->msg = $field_error;
                                            return $obj;
                                        }
                                        if (!call_user_func(array($this->loadComponent('validation'), 'v' . strtoupper(substr($field_rule, 0, 1)) . substr($field_rule, 1)), $model[$field], $field_ext_datas)) {
                                            $obj->success = false;
                                            $obj->msg = $field_error;
                                            return $obj;
                                        }
                                    }
                                    break;
                                case self::VALIDATE_EXISTS:
                                    if ($field_validate_action & $action) {
                                        if (isset($model[$field])) {
                                            if (!call_user_func(array($this->loadComponent('validation'), 'v' . strtoupper(substr($field_rule, 0, 1)) . substr($field_rule, 1)), $model[$field], $field_ext_datas)) {
                                                $obj->success = false;
                                                $obj->msg = $field_error;
                                                return $obj;
                                            }
                                        }
                                    }
                                    break;
                                case self::VALIDATE_VALUE:

                                    if ($field_validate_action & $action) {

                                        if (isset($model[$field]) && strlen(trim($model[$field])) > 0) {

                                            if (!call_user_func(array($this->loadComponent('validation'), 'v' . strtoupper(substr($field_rule, 0, 1)) . substr($field_rule, 1)), $model[$field], $field_ext_datas)) {
                                                $obj->success = false;
                                                $obj->msg = $field_error;
                                                return $obj;
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }
        return $obj;
    }


    /**
     * 根据字段列表筛选自动创建日期时间赋值字段及值列表
     * @param array $fields
     * @return array('自动赋值字段名'=>值,..)
     */
    private function get_auto_create_datetime_field_and_datas(array $fields): array
    {
        $auto_fields = [];
        $APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE = self::getConfig('db', 'APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE');
        $APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX = self::getConfig('db', 'APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX');

        if ($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE && $APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX) {
            $curr_time = time();
            if (is_string($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE)) {
                $curr_time = date($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE, $curr_time);
            }

            if (isset($fields['fields']) && is_array($fields['fields'])) {
                foreach ($fields['fields'] as $field) {
                    foreach ($APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX as $suffix) {
                        if (endsWith($field, $suffix)) {
                            $auto_fields[$field] = $curr_time;
                        }
                    }
                }
            }
        }
        return $auto_fields;
    }


    /**
     * 根据字段列表筛选自动修改日期时间赋值字段及值列表
     * @param array $fields
     * @return array('自动赋值字段名'=>值,..)
     */
    private function get_auto_update_datetime_field_and_datas(array $fields): array
    {
        $auto_fields = [];
        $APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE = self::getConfig('db', 'APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE');
        $APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX = self::getConfig('db', 'APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX');
        if ($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE && $APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX) {
            $curr_time = time();
            if (is_string($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE)) {
                $curr_time = date($APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE, $curr_time);
            }
            if (isset($fields['fields']) && is_array($fields['fields'])) {
                foreach ($fields['fields'] as $field) {
                    foreach ($APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX as $suffix) {
                        if (endsWith($field, $suffix)) {
                            $auto_fields[$field] = $curr_time;
                        }
                    }
                }
            }
        }
        return $auto_fields;
    }

    /**
     * insert an row data
     * @param array $data
     * @throws ModelException
     * @throws ModelValidateException
     */
    public function insert(array $data)
    {
        //生成创建日期
        $auto_datetime_field_datas = $this->get_auto_create_datetime_field_and_datas($this->fields());
        $data = array_merge($auto_datetime_field_datas, $data);
        $params = $this->_params;
        if ($this->_pk_sequence_name) {
            $data[$this->pk()] = $this->_pk_sequence_name . '.NEXTVAL';
        }

        // callback
        if (method_exists($this, '_validate')) {

            $obj = $this->_validate($data, self::MODEL_INSERT);
            if (!$obj->success) {
                throw new ModelValidateException(ctx()->getI18n()->getLang('error_model_validate_title'), ctx()->getI18n()->getLang('error_model_validate', array($obj->msg)));
            }
        }


        // callback
        if (method_exists($this, 'befor_insert')) {
            call_user_func(array($this, 'befor_insert'), $data);
        }


        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        $result = $this->current_db()->insert($this->tablename, $data, $params, false, ($this->_pk_sequence_name) ? $this->_pk_sequence_name : '');
        // callback
        if (false !== $result)
            if (method_exists($this, 'end_insert'))
                call_user_func(array($this, 'end_insert'), $data, $result);

        return $result;
    }

    /**
     * update an row data
     * @param array $data
     * @param array $options
     * @return int
     * @throws ModelException
     * @throws ModelValidateException
     */
    public function update(array $data, array $options = array())
    {
        // 生成更新日期
        $auto_datetime_field_datas = $this->get_auto_update_datetime_field_and_datas($this->fields());
        $data = array_merge($auto_datetime_field_datas, $data);

        $params = $this->_params;


        // callback
        if (method_exists($this, '_validate')) {

            $obj = $this->_validate($data, self::MODEL_UPDATE);
            if (!$obj->success) {
                throw new ModelValidateException(ctx()->getI18n()->getLang('error_model_validate_title'), ctx()->getI18n()->getLang('error_model_validate', array($obj->msg)));
            }
        }

        // callback
        if (method_exists($this, 'befor_update'))
            call_user_func(array($this, 'befor_update'), $data, $options);

        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));


        if (isset($options['where']))
            $params['where'] = $options['where'];

        if (isset($params['order']))
            $params['order'] = $options['order'];

        if (isset($params['limit']))
            $params['limit'] = $options['limit'];

        $result = $this->current_db()->update($this->tablename, $data, $params);

        // callback
        if (false !== $result)
            if (method_exists($this, 'end_update'))
                call_user_func(array($this, 'end_update'), $data, $options, $result);

        return $result;
    }

    /**
     * delete an row data
     * @param array $options
     * @return int
     * @throws ModelException
     */
    public function delete(array $options = array())
    {
        $params = $this->_params;

        // callback
        if (method_exists($this, 'befor_delete'))
            call_user_func(array($this, 'befor_delete'), $options);

        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        if (isset($options['where']))
            $params['where'] = $options['where'];

        if (isset($options['order']))
            $params['order'] = $options['order'];

        if (isset($options['limit']))
            $params['limit'] = $options['limit'];


        $result = $this->current_db()->delete($this->tablename, $params);

        // callback
        if (false !== $result)
            if (method_exists($this, 'end_delete'))
                call_user_func(array($this, 'end_delete'), $options, $result);

        return $result;
    }

    /**
     * truncate
     * @throws ModelException
     * @throws \cn\eunionz\exception\DBException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function truncate()
    {
        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        if ('mysql' == strtolower(self::getConfig('db', 'APP_DB_TYPE'))) {
            return $this->current_db()->query('TRUNCATE TABLE `' . $this->tablename . '`');
        } else if ('oci' == strtolower(self::getConfig('db', 'APP_DB_TYPE'))) {
            return $this->current_db()->query('TRUNCATE TABLE "' . $this->tablename . '"');
        }
    }

    /**
     * find all data by expression
     * @param array $options
     * @return array|null
     * @throws ModelException
     */
    public function find(array $options = array()): ?array
    {
        $params = $this->_params;


        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        if (isset($options['join'])) {
            if (empty($options['join'])) {
                unset($params['join']);
            } else {
                $params['join'] = $options['join'];
            }
        }
        if (isset($options['forupdate'])) {
            $params['forupdate'] = $options['forupdate'];
        } else {
            $params['forupdate'] = false;
        }
        if (isset($options['where']))
            $params['where'] = (isset($params['where']) && is_array($params['where'])) ? array_merge($params['where'], $options['where']) : $options['where'];
        if (isset($options['field']))
            $params['field'] = is_array($options['field']) ? $options['field'] : array($options['field']);

        if (isset($options['distinct']))
            $params['distinct'] = $options['distinct'];

        if (isset($options['order']))
            $params['order'] = $options['order'];

        if (isset($options['limit']))
            $params['limit'] = is_array($options['limit']) ? $options['limit'] : array($options['limit']);

        if (isset($options['group']))
            $params['group'] = $options['group'];

        if (isset($options['having']))
            $params['having'] = $options['having'];

        $result = $this->current_db()->select($this->tablename, $params);

        return !is_array($result) ? array() : $result;
    }

    /**
     * find single row data
     * @param array $options
     * @return array|mixed
     * @throws ModelException
     */
    public function find_one(array $options = array())
    {
        $options['limit'] = 1;
        $result = current($this->find($options));

        return !is_array($result) ? array() : $result;
    }

    /**
     * find an field value by expression
     * @param array $options
     * @param string|array $field
     * @return mixed
     * @throws ModelException
     */
    public function find_field(array $options = array(), $field)
    {
        $options['field'] = $field;
        return current($this->find_one($options));
    }

    /**
     * count rows
     * @param array $options
     * @param string $field
     * @param bool $distinct
     * @return mixed
     * @throws ModelException
     */
    public function count(array $options = array(), string $field = '', bool $distinct = false)
    {
        if (empty($field)) {
            if ($distinct) {
                return $this->find_field($options, array('_COUNT' => 'COUNT(DISTINCT F{' . $this->pk() . '})'));
            } else {
                return $this->find_field($options, array('_COUNT' => 'COUNT(F{' . $this->pk() . '})'));
            }
        } else {
            if ($distinct) {
                return $this->find_field($options, array('_COUNT' => 'COUNT(DISTINCT F{' . $field . '})'));
            } else {
                return $this->find_field($options, array('_COUNT' => 'COUNT(F{' . $field . '})'));
            }
        }
    }

    /**
     * sum rows
     * @param array $options
     * @param string $field
     * @return mixed
     * @throws ModelException
     */
    public function sum(array $options = array(), string $field)
    {
        return $this->find_field($options, array('_SUM' => 'SUM(F{' . $field . '})'));
    }

    /**
     * avg rows
     * @param array $options
     * @param string $field
     * @return mixed
     * @throws ModelException
     */
    public function avg(array $options = array(), string $field)
    {
        return $this->find_field($options, array('_AVG' => 'AVG(F{' . $field . '})'));
    }

    /**
     * min rows
     * @param array $options
     * @param string $field
     * @return mixed
     * @throws ModelException
     */
    public function min(array $options = array(), string $field)
    {
        return $this->find_field($options, array('_MIN' => 'MIN(F{' . $field . '})'));
    }

    /**
     * max rows
     * @param array $options
     * @param string $field
     * @return mixed
     * @throws ModelException
     */
    public function max(array $options = array(), string $field)
    {
        return $this->find_field($options, array('_MAX' => 'MAX(F{' . $field . '})'));
    }

    /**
     * get main table name
     * @return string
     * @throws ModelException
     */
    public function table(): string
    {
        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        return $this->tablename;
    }

    /**
     * get table pk field
     * @return string
     * @throws ModelException
     */
    public function pk(): string
    {
        return $this->current_db()->get_pk($this->table());
    }

    /**
     * get table pk field fix
     * @return string
     * @throws ModelException
     */
    public function prefix(): string
    {
        return substr($this->pk(), 0, strpos($this->pk(), '_')) . '_';
    }

    /**
     * get table fields
     * @return array
     * @throws ModelException
     * @throws \cn\eunionz\exception\DBException
     */
    public function fields(): array
    {
        return $this->current_db()->get_fields_by_table($this->table());
    }

    /**
     * get db connect
     * @return Cdb|null
     */
    public function current_db()
    {
        if ($this->isCoroutine) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name);
        } else {
            $db = ctx()->db($this->db_cluster_name, $this->db_config_name);
            return $db;
        }
    }


    /**
     * load component
     * @param string $name
     * @param bool $single
     * @return Component
     */
    final protected function C(string $name, bool $single = true): Component
    {
        return $this->loadComponent($name, $single);
    }

    /**
     * load plugin
     * @param string $name
     * @param bool $single
     * @return Plugin|null
     */
    final protected function P(string $name, bool $single = true)
    {
        return $this->loadPlugin($name, $single);
    }

    /**
     * get this config item
     * @param string $namespace
     * @param string $key
     * @return mixed
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    final function F(string $namespace, string $key = '')
    {
        return self::getConfig($namespace, $key);
    }

    /**
     * 字段转换，将 $field 描述的字段格式转换为sql字段列表
     * @param $field    格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @return string   sql field_list
     */
    final public function get_fields($field): string
    {
        if ($field && is_array($field)) {
            $field_str = "";
            foreach ($field as $k => $v) {
                if (is_numeric($k)) {
                    if ($v) {
                        $field_str .= "{$v},";
                    }

                } elseif (is_string($k)) {
                    if ($k) {
                        $field_str .= "{$k} as ";
                        echo $k;
                        var_dump($v);
                        $field_str .= "{$v},";
                    }

                }
            }
            if (strlen($field_str) > 0) $field_str = substr($field_str, 0, strlen($field_str) - 1);
        } elseif ($field && is_string($field)) {
            $field_str = $field;
        } else {
            $field_str = "*";
        }
        return $field_str;
    }

    /**
     * 排序转换，将 $order 描述的排序格式转换为sql排序列表
     * @param $order $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @return string   sql order_list
     */
    final public function get_orders($order): string
    {
        $order_str = "";
        if ($order && is_string($order)) {
            $order_str .= " ORDER BY {$order} ";
        } else if ($order && is_array($order)) {
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $order_str .= " {$val} ,";
                } elseif (is_string($key)) {
                    $order_str .= " {$key} {$val} ,";
                }
            }
            if (strlen($order_str) > 0) $order_str = substr($order_str, 0, strlen($order_str) - 1);
            $order_str = " ORDER BY {$order_str} ";
        }
        return $order_str;
    }

    /**
     * limit转换，将 $limit 描述的limit格式转换为sql limit格式
     * @param $limit       格式： 10
     *                     或格式： '0,10'
     *                     或格式： array(10)
     *                     或格式： array(0,10)
     * @return string      sql limit_list
     * @throws ModelException
     */
    final public function get_limit($limit): string
    {
        $limit_str = "";
        if ($limit && is_numeric($limit)) {
            $limit_str .= " LIMIT {$limit} ";
        } elseif ($limit && is_string($limit) && preg_match("/^[0-9]+,[0-9]+$/", $limit)) {
            $limit_str .= " LIMIT {$limit} ";
        } elseif ($limit && is_array($limit)) {
            if (count($limit) == 1) {
                $limit_str .= " LIMIT " . intval($limit[0]);
            } else if (count($limit) == 2) {
                $limit_str .= " LIMIT " . intval($limit[0]) . "," . intval($limit[1]);
            } else {
                throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_limit'));
            }
        }
        return $limit_str;
    }

}
