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
     * @var null
     */
    public $db_config_name = null;

    /**
     * 模型使用的集群定义
     * @var string
     */
    protected $db_cluster_name = 'default';

    public function initialize($tablename = "")
    {

        if (empty($tablename)) {
            $temps = explode("\\", get_called_class());
            $this->tablename = strtolower($temps[count($temps) - 1]);
        } else {
            $this->tablename = strtolower($tablename);
        }

    }


    /**
     * 设置模型数据库配置及集群
     * @param string $db_cluster_name 群集名称  'default'
     * @param null $db_config 数据库配置文件主文件名,'db' 'db1'
     */
    public function set_db_config($db_cluster_name = 'default', $db_config_name = null)
    {
        $this->db_config_name = $db_config_name;
        $this->db_cluster_name = $db_cluster_name;
        return $this;
    }


    /**
     * 默认验证方法
     * @param $model
     */
    protected function _validate($model, $action)
    {
        $obj = new \stdClass();
        $obj->success = true;
        $obj->msg = "";
        if (!$this->_is_validate) return $obj;


        if (!$this->_validate_rules || !is_array($this->_validate_rules)) return $obj;

        $obj = new \stdClass();
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
     * @param $fields
     * @return array('自动赋值字段名'=>值,..)
     */
    private function get_auto_create_datetime_field_and_datas($fields)
    {
        $auto_fields = [];
        $APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE = getConfig('db', 'APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE');
        $APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX = getConfig('db', 'APP_DB_TABLE_AUTO_CREATE_DATETIME_FIELD_SUFFIX');

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
     * @param $fields
     * @return array('自动赋值字段名'=>值,..)
     */
    private function get_auto_update_datetime_field_and_datas($fields)
    {
        $auto_fields = [];
        $APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE = getConfig('db', 'APP_DB_TABLE_AUTO_DATETIME_FIELD_DATA_TYPE');
        $APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX = getConfig('db', 'APP_DB_TABLE_AUTO_UPDATE_DATETIME_FIELD_SUFFIX');
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
     * insert
     *
     * insert an row data
     *
     * @qrcode
     * $data = array('name'=>'zxd', 'age'=>'22', 'email'=>'my@qq.com');
     *
     * $this->r->user->insert($data);
     * @end
     *
     * @param array $data
     * @return    int
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


        $result = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->insert($this->tablename, $data, $params, false, ($this->_pk_sequence_name) ? $this->_pk_sequence_name : '');

        // callback
        if (false !== $result)
            if (method_exists($this, 'end_insert'))
                call_user_func(array($this, 'end_insert'), $data, $result);

        return $result;
    }

    /**
     * update
     *
     * update an row data
     *
     * @qrcode
     * $data = array('name'=>'zxd', 'age'=>'22', 'email'=>'my@qq.com');
     *
     * $options = array();
     * $options['age']['>'] = 22;
     *
     * if (!$this->r->user->update($data, $options))
     *        echo $this->getError();
     * @end
     *
     * @param array $data
     * @param array $options
     * @return    string
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


        $result = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->update($this->tablename, $data, $params);

        // callback
        if (false !== $result)
            if (method_exists($this, 'end_update'))
                call_user_func(array($this, 'end_update'), $data, $options, $result);

        return $result;
    }

    /**
     * delete
     *
     * delete an row data
     *
     * @param array $options
     * @return    string
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

        $result = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->delete($this->tablename, $params);

        // callback
        if (false !== $result)
            if (method_exists($this, 'end_delete'))
                call_user_func(array($this, 'end_delete'), $options, $result);

        return $result;
    }

    /**
     * truncate
     *
     * truncate an table data
     */
    public function truncate()
    {
        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        if ('mysql' == strtolower(getConfig('db', 'APP_DB_TYPE'))) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query('TRUNCATE TABLE `' . $this->tablename . '`');
        } else if ('oci' == strtolower(getConfig('db', 'APP_DB_TYPE'))) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query('TRUNCATE TABLE "' . $this->tablename . '"');
        }
    }

    /**
     * find all data
     *
     * find all data by expression
     *
     * @qrcode
     * $search = array();
     * $search['school'] = '1';
     * $search['age']['>'] = '30';
     * $field = array('id', 'name');
     *
     * $result = $this->find($search, $field, 'id desc, age desc', '0,10');
     * @end
     *
     * @param array $options
     *
     * @return array
     */
    public
    function find(array $options = array())
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

        $result = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->select($this->tablename, $params);

        return !is_array($result) ? array() : $result;
    }

    /**
     * find single row
     *
     * find single row data
     *
     * @param array $options
     *
     * @return array
     */
    public
    function find_one(array $options = array())
    {
        $options['limit'] = 1;
        $result = current($this->find($options));

        return !is_array($result) ? array() : $result;
    }

    /**
     * find field value
     *
     * find an field value by expression
     *
     * @param array $options
     * @param array $field
     *
     * @return    mixed
     */
    public
    function find_field(array $options = array(), $field)
    {
        $options['field'] = $field;
        return current($this->find_one($options));
    }

    /**
     * count
     *
     * count rows
     *
     * @param array $options
     *
     * @return    integer
     */
    public
    function count(array $options = array(), $field = '', $distinct = false)
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
     * sum
     *
     * sum rows
     *
     * @param array $options
     * @param string $field
     *
     * @return    integer
     */
    public
    function sum(array $options = array(), $field)
    {
        return $this->find_field($options, array('_SUM' => 'SUM(F{' . $field . '})'));
    }

    /**
     * avg
     *
     * avg rows
     *
     * @param array $options
     * @param string $field
     *
     * @return    integer
     */
    public
    function avg(array $options = array(), $field)
    {
        return $this->find_field($options, array('_AVG' => 'AVG(F{' . $field . '})'));
    }

    /**
     * min
     *
     * min rows
     *
     * @param array $options
     * @param string $field
     *
     * @return integer
     */
    public
    function min(array $options = array(), $field)
    {
        return $this->find_field($options, array('_MIN' => 'MIN(F{' . $field . '})'));
    }

    /**
     * max
     *
     * max rows
     *
     * @param array $options
     * @param string $field
     *
     * @return integer
     */
    public
    function max(array $options = array(), $field)
    {
        return $this->find_field($options, array('_MAX' => 'MAX(F{' . $field . '})'));
    }

    /**
     * get table name
     *
     * get main table name
     *
     * @return string
     */
    public
    function table()
    {
        if (empty($this->tablename))
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_bind_table'));

        return $this->tablename;
    }

    /**
     * get pk
     *
     * get main table pk
     *
     * @return string
     */
    public function pk()
    {

        return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->get_pk($this->table());
    }

    /**
     * get field fix
     *
     * get main table field fix
     *
     * @return string
     */
    public
    function prefix()
    {
        return substr($this->pk(), 0, strpos($this->pk(), '_')) . '_';
    }

    /**
     * get field
     *
     * get main table fields
     *
     * @return array
     */
    public function fields()
    {
        return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->get_fields_by_table($this->table());
    }

    /**
     * get db connect
     *
     * get current server db connect
     *
     * @return DBComponentObject
     */
    public function current_db()
    {
        return ctx()->cdb($this->db_cluster_name, $this->db_config_name);
    }


    /**
     * 基于 mysql pdo 底层 查询，返回第1条记录第1个字段的值
     * @param      $field  格式：array('字段1') 或者 字符串，单一字段
     * @param      $joinTables 格式：'主表名'
     *                          或格式：'主表名 as 别名'
     *                          或格式：'主表名 as 别名 LEFT JOIN 从表名 as 别名  ON 连接条件'
     *                          或格式：array('主表名')
     *                          或格式：array('主表名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     *                          或格式：array('主表名'=>'别名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     * @param      $where  格式：'不带?条件'  字段名建议 ``引起来
     *                      或格式：array('不带?条件')  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     *                      或格式：array('带?条件'=>array(值列表))  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param   $forupdate 格式：是否使用排它锁,必须在事务中才可使用
     *
     * @return mixed
     */

    public
    function raw_find_field($field, $joinTables, $where, $order = null, $forupdate = false)
    {
        $rs = $this->raw_query($field, $joinTables, $where, $order, 1, $forupdate);
        if ($rs) {
            if (is_array($field)) {
                $field_name = '';
                foreach ($field as $key => $value) {
                    $field_name = $value;
                    break;
                }
                if (trim($field_name)) return $rs[0][trim($field_name)];
            } else {
                $field = strtolower($field);
                $field = explode('as', $field);
                $field_name = count($field) == 1 ? $field[0] : $field[1];
                if (trim($field_name)) return $rs[0][trim($field_name)];
            }

        }
        return '';
    }


    /**
     * 基于 mysql pdo 底层 查询，返回一条记录
     * @param      $field  格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @param      $joinTables 格式：'主表名'
     *                          或格式：'主表名 as 别名'
     *                          或格式：'主表名 as 别名 LEFT JOIN 从表名 as 别名  ON 连接条件'
     *                          或格式：array('主表名')
     *                          或格式：array('主表名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     *                          或格式：array('主表名'=>'别名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     * @param      $where  格式：'不带?条件'  字段名建议 ``引起来
     *                      或格式：array('不带?条件')  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     *                      或格式：array('带?条件'=>array(值列表))  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param   $forupdate 格式：是否使用排它锁,必须在事务中才可使用
     *
     * @return mixed
     */

    public
    function raw_find_one($field, $joinTables, $where, $order = null, $forupdate = false)
    {
        $rs = $this->raw_query($field, $joinTables, $where, $order, 1, $forupdate);
        if ($rs) return $rs[0];
        return null;
    }


    /**
     * 基于 mysql pdo 底层 查询
     * @param      $field  格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @param      $joinTables 格式：'主表名'
     *                          或格式：'主表名 as 别名'
     *                          或格式：'主表名 as 别名 LEFT JOIN 从表名 as 别名  ON 连接条件'
     *                          或格式：array('主表名')
     *                          或格式：array('主表名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     *                          或格式：array('主表名'=>'别名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     * @param      $where  格式：'不带?条件'  字段名建议 ``引起来
     *                      或格式：array('不带?条件')  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     *                      或格式：array('带?条件'=>array(值列表))  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param null $limit 格式： 10
     *                     或格式： '0,10'
     *                     或格式： array(10)
     *                     或格式： array(0,10)
     * @param   $forupdate 格式：是否使用排它锁,必须在事务中才可使用
     *
     * @return mixed
     */

    public
    function raw_query($field, $joinTables, $where, $order = null, $limit = null, $forupdate = false)
    {
        $field_str = $this->get_fields($field);

        $sql = "SELECT {$field_str} FROM ";
        if (!$joinTables)
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));

        $table_str = "";
        if (is_string($joinTables)) {
            $table_str .= "{$joinTables} ";
        } else if (is_array($joinTables)) {
            $table_str = "";
            $is_find = false;
            foreach ($joinTables as $key => $val) {
                if (is_string($val)) {
                    //是主表
                    if (is_numeric($key)) {
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    } elseif (is_string($key)) {
                        if (strpos($key, '`') === false) {
                            $table_str .= "`{$key}` as ";
                        } else {
                            $table_str .= "{$key} as ";
                        }
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    }
                    break;
                }
            }
            if (!$is_find) {
                throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables_not_table'));
            }

            $join_table_str = "";
            foreach ($joinTables as $key => $val) {
                if (is_numeric($key) && is_array($val)) {
                    //连接表 array('join'=>array('表1'=>'别名'),'on'=>'连接条件')
                    foreach ($val as $k => $v) {
                        $is_find = false;
                        if ((strtolower($k) == "join" || strtolower($k) == "inner join" || strtolower($k) == "left join" || strtolower($k) == "left outer join" || strtolower($k) == "right join" || strtolower($k) == "right outer join") && (isset($val['on']) && is_string($val['on']))) {
                            $is_find = true;
                            $join_table_str .= strtoupper($k) . ' ';
                            if (is_array($v)) {
                                foreach ($v as $k1 => $v1) {
                                    if (is_numeric($k1)) {
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    } else {
                                        if (strpos($k1, '`') === false) {
                                            $join_table_str .= "`{$k1}` as ";
                                        } else {
                                            $join_table_str .= "{$k1} as ";
                                        }
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    }
                                }
                            } else {
                                if (strpos($v, '`') === false) {
                                    $join_table_str .= "`{$v}`";
                                } else {
                                    $join_table_str .= "{$v}";
                                }
                            }
                        }
                        if ($is_find) {
                            $join_table_str .= " ON " . $val['on'] . " ";
                        }
                    }
                }
            }
            $table_str .= "{$join_table_str} ";
        } else {
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));
        }
        $sql .= "{$table_str} ";

        $val_array = array();

        $where_str = "";
        if ($where && is_string($where)) {
            $where_str .= " WHERE {$where} ";
        } else if ($where && is_array($where)) {
            foreach ($where as $key => $val) {
                if (is_numeric($key)) {
                    $where_str .= " WHERE {$val} ";
                } else {
                    $where_str .= " WHERE {$key} ";
                    $val_array = $val;
                }
            }
        }
        $sql .= "{$where_str} ";

        $order_str = $this->get_orders($order);

        $sql .= "{$order_str} ";


        $limit_str = $this->get_limit($limit);
        $sql .= " {$limit_str} ";
        if ($forupdate) $sql .= " FOR UPDATE";


        if ($val_array) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql, false, $val_array);
        } else {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql);
        }
    }

    /**
     * 基于 mysql pdo 底层 分页查询
     * @param      $field  格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @param      $joinTables 格式：'主表名'
     *                          或格式：'主表名 as 别名'
     *                          或格式：'主表名 as 别名 LEFT JOIN 从表名 as 别名  ON 连接条件'
     *                          或格式：array('主表名')
     *                          或格式：array('主表名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     *                          或格式：array('主表名'=>'别名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     * @param      $where  格式：'不带?条件'  字段名建议 ``引起来
     *                      或格式：array('不带?条件')  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     *                      或格式：array('带?条件'=>array(值列表))  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param   $pagecount 格式：引用方式输出总页数，传递变量
     * @param   $recordcount 格式：引用方式输出记录总数，传递变量
     * @param   $pageno 格式：引用方式输出记录当前页，传递变量 1,2,3,4,5,
     * @param   $pagesize 格式：页大小
     * @param   $is_change_pageno 当$pageno大于总页数时是否改变$pageno的值
     * @param   $forupdate 格式：是否使用排它锁,必须在事务中才可使用
     *
     * @return mixed
     */

    public
    function raw_page_query($field, $joinTables, $where, $order, & $pagecount, & $recordcount, & $pageno, $pagesize = 10, $is_change_pageno = 1, $forupdate = false)
    {
        $field_str = $this->get_fields($field);

        if (!$joinTables) throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));

        $table_str = "";
        if (is_string($joinTables)) {
            $table_str .= "{$joinTables} ";
        } else if (is_array($joinTables)) {
            $table_str = "";
            $is_find = false;
            foreach ($joinTables as $key => $val) {
                if (is_string($val)) {
                    //是主表
                    if (is_numeric($key)) {
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    } elseif (is_string($key)) {
                        if (strpos($key, '`') === false) {
                            $table_str .= "`{$key}` as ";
                        } else {
                            $table_str .= "{$key} as ";
                        }
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    }
                    break;
                }
            }
            if (!$is_find) {
                throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables_not_table'));
            }

            $join_table_str = "";
            foreach ($joinTables as $key => $val) {
                if (is_numeric($key) && is_array($val)) {
                    //连接表 array('join'=>array('表1'=>'别名'),'on'=>'连接条件')
                    foreach ($val as $k => $v) {
                        $is_find = false;
                        if ((strtolower($k) == "join" || strtolower($k) == "inner join" || strtolower($k) == "left join" || strtolower($k) == "left outer join" || strtolower($k) == "right join" || strtolower($k) == "right outer join") && (isset($val['on']) && is_string($val['on']))) {
                            $is_find = true;
                            $join_table_str .= strtoupper($k) . ' ';
                            if (is_array($v)) {
                                foreach ($v as $k1 => $v1) {
                                    if (is_numeric($k1)) {
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    } else {
                                        if (strpos($k1, '`') === false) {
                                            $join_table_str .= "`{$k1}` as ";
                                        } else {
                                            $join_table_str .= "{$k1} as ";
                                        }
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    }
                                }
                            } else {
                                if (strpos($v, '`') === false) {
                                    $join_table_str .= "`{$v}`";
                                } else {
                                    $join_table_str .= "{$v}";
                                }
                            }
                        }
                        if ($is_find) {
                            $join_table_str .= " ON " . $val['on'] . " ";
                        }
                    }
                }
            }
            $table_str .= "{$join_table_str} ";
        } else {
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));
        }

        $val_array = array();

        $where_str = "";
        if ($where && is_string($where)) {
            $where_str .= " WHERE {$where} ";
        } else if ($where && is_array($where)) {
            foreach ($where as $key => $val) {
                if (is_numeric($key)) {
                    $where_str .= " WHERE {$val} ";
                } else {
                    $where_str .= " WHERE {$key} ";
                    $val_array = $val;
                }
            }
        }
        $sql = "SELECT COUNT(*) as num FROM {$table_str} {$where_str} ";
        if ($forupdate) $sql .= " FOR UPDATE";

        //获取记录总数
        if ($val_array) {
            $rs = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql, false, $val_array);
        } else {
            $rs = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql);
        }
        $recordcount = $rs[0]['num'];
        $pagecount = ceil($recordcount / $pagesize);

        if ($is_change_pageno) {
            if ($pageno > $pagecount) $pageno = $pagecount;
        }
        if ($pageno < 1) $pageno = 1;

        $order_str = $this->get_orders($order);

        $limit_str = " LIMIT " . (($pageno - 1) * $pagesize) . "," . $pagesize;
        $sql = "SELECT {$field_str} FROM {$table_str} {$where_str} {$order_str} {$limit_str}";
        if ($forupdate) $sql .= " FOR UPDATE";

        if ($val_array) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql, false, $val_array);
        } else {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql);
        }
    }


    /**
     * 基于 mysql pdo 底层 分页查询
     * @param      $field  格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @param      $joinTables 格式：'主表名'
     *                          或格式：'主表名 as 别名'
     *                          或格式：'主表名 as 别名 LEFT JOIN 从表名 as 别名  ON 连接条件'
     *                          或格式：array('主表名')
     *                          或格式：array('主表名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     *                          或格式：array('主表名'=>'别名',array('join'=>array('表1'=>'别名'),'on'=>'连接条件'),array('left join'=>array('表1'=>'别名'),'on'=>'连接条件')))
     * @param      $where  格式：'不带?条件'  字段名建议 ``引起来
     *                      或格式：array('不带?条件')  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     *                      或格式：array('带?条件'=>array(值列表))  字段名建议 ``引起来，如果没有值列表，则直接为一个字符串
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param   $pagecount 格式：引用方式输出总页数，传递变量
     * @param   $recordcount 格式：引用方式输出记录总数，传递变量
     * @param   $start 格式：开始行，从0开始
     * @param   $pagesize 格式：页大小
     * @param   $forupdate 格式：是否使用排它锁,必须在事务中才可使用
     *
     * @return mixed
     */

    public
    function raw_page_query_by_limit($field, $joinTables, $where, $order, & $pagecount, & $recordCount, $start = 0, $pagesize = 10, $forupdate = false)
    {
        $field_str = $this->get_fields($field);

        if (!$joinTables) throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));

        $table_str = "";
        if (is_string($joinTables)) {
            $table_str .= "{$joinTables} ";
        } else if (is_array($joinTables)) {
            $table_str = "";
            $is_find = false;
            foreach ($joinTables as $key => $val) {
                if (is_string($val)) {
                    //是主表
                    if (is_numeric($key)) {
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    } elseif (is_string($key)) {
                        if (strpos($key, '`') === false) {
                            $table_str .= "`{$key}` as ";
                        } else {
                            $table_str .= "{$key} as ";
                        }
                        if (strpos($val, '`') === false) {
                            $table_str .= "`{$val}`";
                        } else {
                            $table_str .= "{$val}";
                        }
                        $is_find = true;
                    }
                    break;
                }
            }
            if (!$is_find) {
                throw new ModelException($this->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables_not_table'));
            }

            $join_table_str = "";
            foreach ($joinTables as $key => $val) {
                if (is_numeric($key) && is_array($val)) {
                    //连接表 array('join'=>array('表1'=>'别名'),'on'=>'连接条件')
                    foreach ($val as $k => $v) {
                        $is_find = false;
                        if ((strtolower($k) == "join" || strtolower($k) == "inner join" || strtolower($k) == "left join" || strtolower($k) == "left outer join" || strtolower($k) == "right join" || strtolower($k) == "right outer join") && (isset($val['on']) && is_string($val['on']))) {
                            $is_find = true;
                            $join_table_str .= strtoupper($k) . ' ';
                            if (is_array($v)) {
                                foreach ($v as $k1 => $v1) {
                                    if (is_numeric($k1)) {
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    } else {
                                        if (strpos($k1, '`') === false) {
                                            $join_table_str .= "`{$k1}` as ";
                                        } else {
                                            $join_table_str .= "{$k1} as ";
                                        }
                                        if (strpos($v1, '`') === false) {
                                            $join_table_str .= "`{$v1}`";
                                        } else {
                                            $join_table_str .= "{$v1}";
                                        }
                                    }
                                }
                            } else {
                                if (strpos($v, '`') === false) {
                                    $join_table_str .= "`{$v}`";
                                } else {
                                    $join_table_str .= "{$v}";
                                }
                            }
                        }
                        if ($is_find) {
                            $join_table_str .= " ON " . $val['on'] . " ";
                        }
                    }
                }
            }
            $table_str .= "{$join_table_str} ";
        } else {
            throw new ModelException(ctx()->getI18n()->getLang('error_model_title'), ctx()->getI18n()->getLang('error_model_execute_jointables'));
        }

        $val_array = array();

        $where_str = "";
        if ($where && is_string($where)) {
            $where_str .= " WHERE {$where} ";
        } else if ($where && is_array($where)) {
            foreach ($where as $key => $val) {
                if (is_numeric($key)) {
                    $where_str .= " WHERE {$val} ";
                } else {
                    $where_str .= " WHERE {$key} ";
                    $val_array = $val;
                }
            }
        }
        $sql = "SELECT COUNT(*) as num FROM {$table_str} {$where_str} ";
        if ($forupdate) $sql .= " FOR UPDATE";
        //获取记录总数
        if ($val_array) {
            $rs = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql, false, $val_array);
        } else {
            $rs = ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql);
        }
        $recordCount = $rs[0]['num'];
        $pagecount = ceil($recordCount / $pagesize);
        $pageno = 0;
        if ($pageno > $pagecount) $pageno = $pagecount;
        if ($pageno < 1) $pageno = 1;

        //$start=($pageno-1)*$pagesize;

        $order_str = $this->get_orders($order);

        $limit_str = " LIMIT " . ($start) . "," . $pagesize;
        $sql = "SELECT {$field_str} FROM {$table_str} {$where_str} {$order_str} {$limit_str}";
        if ($forupdate) $sql .= " FOR UPDATE";
        if ($val_array) {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql, false, $val_array);
        } else {
            return ctx()->cdb($this->db_cluster_name, $this->db_config_name)->query($sql);
        }
    }


    /**
     * call component
     *
     * This is load_component alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function C($name, $single = true)
    {
        return $this->loadComponent($name, $single);
    }

    /**
     * call plugin
     *
     * This is load_plugin alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function P($name, $single = true, $APP_ASSEMBLY_NAME = '')
    {
        return $this->loadPlugin($name, $single, $APP_ASSEMBLY_NAME);
    }

    /**
     * get config item
     *
     * get this global config item
     *
     * $namespace is config file name, But does not contain ".config.php" suffix.
     * $key is null, get all item.
     *
     * @param string $namespace
     * @param string $key
     *
     * @return mixed
     */
    final function F($namespace, $key = '', $APP_ASSEMBLY_NAME = '')
    {
        return getConfig($namespace, $key, $APP_ASSEMBLY_NAME);
    }

    /**
     * 字段转换，将 $field 描述的字段格式转换为sql字段列表
     * @param  $field  格式：array('字段1','字段2','字段3'=>'字段3别名','b.字段4') 或者 字符串 或者  * 或者  null
     * @param string sql field_list
     */
    final public function get_fields($field)
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
     * @param      $order  格式：'id asc'
     *                      或格式：'id asc,id1 desc'
     *                      或格式：array('id','id1')
     *                      或格式：array('id'=>'ASC','id1'=>'DESC')
     * @param string sql order_list
     */
    final public function get_orders($order)
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
     * @param null $limit 格式： 10
     *                     或格式： '0,10'
     *                     或格式： array(10)
     *                     或格式： array(0,10)
     * @param string sql limit_list
     */
    final public function get_limit($limit)
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
