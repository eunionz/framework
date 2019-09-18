<?php
/**
 * Eunionz PHP Framework Db component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */


namespace cn\eunionz\component\cdb;

use cn\eunionz\exception\IOException;

defined('APP_IN') or exit('Access Denied');
// load pdo class
require_once('Pdo.class.php');

class Cdb extends Pdo
{

    /**
     * 所有表，格式：
     * array(
     *      'default' => array(),
     *      ...
     * )
     * @var
     */
    private $_tables = array();

    /**
     * 所有表字段，格式：
     * array(
     *      'default' => array(),
     *      ...
     * )
     *
     * @var
     */
    private $_fields = array();

    /**
     * 当前操作表列表，格式：
     * array(
     *      'default' => array(),
     *      ...
     * )
     * @var array
     */
    private $_current_tables = array();


    /**
     * 字义万能字段
     * @var string
     */
    private $_omnipotent_field_name = "__field__";


    /**
     * oracle数据库序列名称前缀，oralce序列定义规则为，前缀{表名}
     * @var string
     */
    private $_SEQUENCE_PREFIX_NAME = "SEQ_";

    /**
     * 数据库核心缓存过期时间，365天
     * @var int
     */
    private $_db_cache_expire = 31536000;

    /**
     * 初始化
     */
    public function initialize()
    {
        $this->_tables[$this->curr_cluster_name] = $this->init_tables();
    }

    /**
     * 初始化所有表
     *
     * @return array
     */
    public function init_tables()
    {
        if ($this->APP_DB_STRUCTURE_CACHE) {
            if (!isset($this->APP_DB_SERVERS[$this->curr_cluster_name][0]) || !is_array($this->APP_DB_SERVERS[$this->curr_cluster_name][0])) {
                throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
            }
            $currDBID = $this->APP_DB_SERVERS[$this->curr_cluster_name][0];
            $fileName = str_replace('.', '_', $currDBID['HOST']) . '_' . $currDBID['PORT'] . '_' . $currDBID['NAME'];

            $result = $this->cache('db_structure', array($fileName));
            if (!$result) {
                $result = $this->get_tables();
                $cache = serialize($result);
                $this->cache('db_structure', array($fileName), $cache, $this->_db_cache_expire);
            } else {
                $result = unserialize($result);
            }
        } else {
            $result = $this->get_tables();
        }

        return $result;
    }

    /**
     * 对于表名或字段名两端添加特殊字符
     * @param    string $str 表名或字段名
     * @return    string
     */
    public function set_special_char($str)
    {
        if ('mysql' == strtolower($this->APP_DB_TYPE)) {
            $str = trim($str);

            if (preg_match('/^[0-9a-zA-Z_]{1,}$/', $str))
                $str = '`' . $str . '`';
        } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
            $str = trim($str);
            if (preg_match('/^[0-9a-zA-Z_]{1,}$/', $str))
                $str = '"' . $str . '"';
        }
        return $str;
    }


    /**
     * 分析值
     *
     * @param $value
     * @param $is_string_default_field  true|false   true--把字符串值默认当字段处理，false--不把字符串值默认当字段处理
     *
     * @return mixed
     */
    public function parse_value($value, $is_string_default_field = false)
    {
        if (is_string($value)) {
            if ('[null]' == strtolower($value)) {
                $value = ' IS NULL';
            } else if ('[not null]' == strtolower($value)) {
                $value = ' IS NOT NULL';
            } else if ('[empty]' == strtolower($value) || 0 === strlen($value)) {
                $value = "''";
            } else if (strpos($value, '(') !== false && strpos($value, ')') !== false && preg_match_all('/F\{([a-zA-Z0-9._]*)\}/', $value, $parse_result)) {
                // 记录表达式，用于下文还原表达式。
                $exp = $value;

                // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                $exp_fields_old = $parse_result[0];

                // 提取表达式中的字段
                $exp_fields_new = $parse_result[1];

                // 循环处理字段
                $old_fields_tmp = array();
                foreach ($exp_fields_new as $_field) {
                    // 解析字段
                    if (!($field_tmp = $this->parse_check_field($_field))) {
                        throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($_field)));
                    }
                    $old_fields_tmp[] = $field_tmp;
                }
                // 还原表达式
                $value = str_replace($exp_fields_old, $old_fields_tmp, $exp);
            } else {
                if ($is_string_default_field) {
                    return $this->parse_check_field($value);
                } else {
                    $value = "'" . str_replace("'", "''", $value) . "'";
                }
            }
        } else if (is_null($value)) {
            $value = ' IS NULL';
        } else if (is_bool($value)) {
            $value = $value ? 1 : 0;
        } else if (is_numeric($value) || is_int($value) || is_float($value)) {
            $value = $value;
        }
        return $value;
    }


    /**
     * 分析表名
     * @param string $table 表名，表名不能包含特殊字符例如 `
     * @return bool
     */
    public function parse_check_table($table)
    {
        if ('oci' == strtolower($this->APP_DB_TYPE)) {
            $table = strtoupper($table);
        }

        if (!in_array($table, $this->_tables[$this->curr_cluster_name]))
            throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_not_exist', array($table)));

        return true;
    }

    /**
     * parse table
     *
     * parse table on query expression
     *
     * @qrcode
     * // method one, use string
     * $sql = array();
     * $sql['table'] = "user, group as _group";
     *
     * // method two, use array
     * $sql = array();
     * $sql['table'][] = "user"
     * $sql['table'][] = "group as _group"
     * @end
     *
     * @param    mixed $tables
     * @return    string
     */
    public function parse_table(array $tables)
    {
        if (empty($tables))
            throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_lack'));

        $tables_result = array();

        foreach ($tables as $alias => $table) {

            if ('oci' == strtolower($this->APP_DB_TYPE)) {
                $table = trim(strtoupper($table));
            } else if ('mysql' == strtolower($this->APP_DB_TYPE)) {
                $table = trim(strtolower($table));
            }

            $this->parse_check_table($table);

            // 检查表的字段信息是否加载
            if (!isset($this->_fields[$this->curr_cluster_name][$table]['fields']))
                $this->_fields[$this->curr_cluster_name][$table] = $this->get_fields_by_table($table);

            // 无别名
            if (is_numeric($alias)) {
                $tables_result[] = $this->set_special_char($table);

                // 将表放入当前操作表列表中
                $this->_current_tables[$this->curr_cluster_name][] = $table;
            } else // 有别名
            {
                $tables_result[] = $this->set_special_char($table) .
                    ' AS ' . $this->set_special_char($alias);

                // 将别名表放入全局表
                array_unshift($this->_tables[$this->curr_cluster_name], $alias);

                // 复制原表到别名表
                $this->_fields[$this->curr_cluster_name][$alias] = $this->_fields[$this->curr_cluster_name][$table];

                // 将表放入当前操作表列表中
                if (!in_array($alias, $this->_current_tables[$this->curr_cluster_name])) {
                    $this->_current_tables[$this->curr_cluster_name][] = $alias;
                }
            }
        }

        $tables_result = array_unique($tables_result);

        return implode(', ', $tables_result);
    }


    /**
     * parse distinct
     *
     * @param    array $fields
     * @return    string
     */
    public function parse_distinct(array $fields)
    {
        $distinctArr = array();

        foreach ($fields as $field) {
            $field = trim($field);

            if (!($new_field = $this->parse_check_field($field)))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($field)));

            $distinctArr[] = $new_field;
        }

        return implode(', ', $distinctArr);
    }

    /**
     * parse join
     *
     * @qrcode
     * method one, use string
     * $sql = array();
     * $sql['join'] = "group on g_id=u_id and g_id<100, article on a_id=u_article_id";
     *
     * method two, use array
     * $sql = array();
     * $sql['join'][] = "group on g_id=u_id"
     * $sql['join'][] = "article on a_id=u_article_id and a_isRead=true"
     * @end
     *
     * @param    mixed $expression
     *
     * @return    string
     */
    public function parseJoin($expression)
    {
        $join_types = array('INNER', 'LEFT', 'RIGHT');

        // 多个join表
        $join_expression = array();
        // 处理join表部分

        foreach ($expression as $table => $join) {
            $table = trim(strtolower($table));

            if (!$this->parse_check_table($table))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_not_exist', array($table)));


            // 检查表的字段信息是否加载
            if (!isset($this->_fields[$this->curr_cluster_name][$table]['fields']))
                $this->_fields[$this->curr_cluster_name][$table] = $this->get_fields_by_table($table);

            // 将表放入当前操作表列表中
            if (!in_array($table, $this->_current_tables[$this->curr_cluster_name])) {
                $this->_current_tables[$this->curr_cluster_name][] = $table;
            }

            $table = $this->set_special_char($table);


            if (!is_array($join) || empty($join))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_join'));


            // 处理join字段部分
            $exp_fe = array();
            $_alias = '';
            $_logic = '';


            if (isset($join['_alias']) && $join['_alias']) {
                $_alias = trim($join['_alias']);
                unset($join['_alias']);
            }

            //查找有没有_logic下标，如果有表示指定了连接运算
            if (isset($join['_logic']) && $join['_logic']) {
                $_logic = trim(strtoupper($join['_logic']));
                unset($join['_logic']);
            }
            if (!in_array($_logic, $join_types)) {
                $_logic = '';
            }


            foreach ($join as $field => $KV) {
                $exp_kv = array();
                $exp_fe = array();

                if (is_integer($field)) {
                    //如果是整数，表示同一张表连接多次
                    if (!is_array($KV))
                        throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_must_array'));


                    //查找有没有_alias_下标，如果有表示指定了表别名
                    if (isset($KV['_alias']) && $KV['_alias']) {
                        $_alias = trim($KV['_alias']);
                        unset($KV['_alias']);
                    }

                    //查找有没有_logic下标，如果有表示指定了连接运算
                    if (isset($KV['_logic']) && $KV['_logic']) {
                        $_logic = trim(strtoupper($KV['_logic']));
                        unset($KV['_logic']);
                    }
                    if (!in_array($_logic, $join_types)) {
                        $_logic = '';
                    }


                    foreach ($KV as $field => $kv) {
                        $exp_kv = array();
                        $field_res = $this->parse_check_field($field, null, array($_alias => $table));

                        if ($field_res)
                            $field = $field_res;

                        // 处理join表达式部分

                        if (is_array($kv)) {
                            foreach ($kv as $key => $val) {

                                $val_tmp = $this->parse_check_field($val, null, array($_alias => $table));

                                if ($val_tmp) {
                                    $val = $val_tmp;
                                } else {
                                    $val = $this->parse_value($val);
                                }


                                $exp_kv[] = $field . " $key " . $val;
                            }
                        } else {
                            $KV_tmp = $this->parse_check_field($kv, null, array($_alias => $table));

                            if ($KV_tmp) {
                                $KV = $KV_tmp;
                            } else {
                                $KV = $this->parse_value($KV);
                            }

                            $exp_kv[] = $field . " = " . $KV;
                        }

                        $exp_fe[] = implode(' AND ', $exp_kv);

                    }
                    if (!in_array($_logic . ' JOIN ' . $table . ' ' . $_alias . ' ON ' . implode(' AND ', $exp_fe), $join_expression))
                        $join_expression[] = $_logic . ' JOIN ' . $table . ' ' . $_alias . ' ON ' . implode(' AND ', $exp_fe);

                } else {

                    $field_res = $this->parse_check_field($field, null, array($_alias => $table));

                    if ($field_res)
                        $field = $field_res;

                    // 处理join表达式部分

                    if (is_array($KV)) {
                        foreach ($KV as $key => $val) {
                            $val_tmp = $this->parse_check_field($val, null, array($_alias => $table));

                            if ($val_tmp) {
                                $val = $val_tmp;
                            } else {
                                $val = $this->parse_value($val);
                            }

                            $exp_kv[] = $field . " $key " . $val;
                        }
                    } else {
                        $KV_tmp = $this->parse_check_field($KV, null, array($_alias => $table));

                        if ($KV_tmp) {
                            $KV = $KV_tmp;
                        } else {
                            $KV = $this->parse_value($KV);
                        }

                        $exp_kv[] = $field . " = " . $KV;
                    }

                    $exp_fe[] = implode(' AND ', $exp_kv);

                }
            }
            if (!in_array($_logic . ' JOIN ' . $table . ' ' . $_alias . ' ON ' . implode(' AND ', $exp_fe), $join_expression))
                $join_expression[] = $_logic . ' JOIN ' . $table . ' ' . $_alias . ' ON ' . implode(' AND ', $exp_fe);
        }
        return implode(' ', $join_expression);
    }


    /**
     * parse and check field
     *
     * @param $field
     * @param null $cur_table
     * @param null $_alias
     *
     * @return bool|null|string
     */
    public function parse_check_field($field, $cur_table = null, $_alias = NULL)
    {
        //如果字段为万能字段，则直接返回空格字符
        if (strtolower($field) == strtolower($this->_omnipotent_field_name)) return ' ';

        $field_result = null;
        // 点分隔符指定表名
        if (stripos($field, '.')) {

            list($table, $field) = explode('.', $field);
            $alias = $table;
            if (isset($_alias[$table]) && $_alias[$table]) {
                $table = $_alias[$table];
            }
            if ('mysql' == strtolower($this->APP_DB_TYPE)) {
                $table = str_replace("`", "", $table);
            } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
                $table = str_replace('"', '', $table);
            }

            // 表不存在
            if (!in_array($table, $this->_tables[$this->curr_cluster_name])) {
                // 在所有表字段中查找当前字段

                foreach ($this->_current_tables[$this->curr_cluster_name] as $table) {
                    // 表字段信息未加载
                    if (!isset($this->_fields[$this->curr_cluster_name][$table])) {
                        $this->_fields[$this->curr_cluster_name][$table] = $this->get_fields_by_table($table);
                    }

                    if (isset($this->_fields[$this->curr_cluster_name][$table]) && @in_array($field, $this->_fields[$this->curr_cluster_name][$table]['fields'])) {
                        $field_result = $this->set_special_char($alias) . '.' . $this->set_special_char($field);
                        return $field_result;
                    }

                }
                return false;
            }


            // 表字段信息未加载
            if (!isset($this->_fields[$this->curr_cluster_name][$table]['fields'])) {
                $this->_fields[$this->curr_cluster_name][$table] = $this->get_fields_by_table($table);
            }

            // 字段不存在
            if (!in_array($field, $this->_fields[$this->curr_cluster_name][$table]['fields']))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_field_not_exist', array($field, $table)));
            if ($alias) {
                $field_result = $this->set_special_char($alias) . '.' . $this->set_special_char($field);
            } else {
                $field_result = $this->set_special_char($table) . '.' . $this->set_special_char($field);
            }
        } else {

            // 指定了表名
            if (!empty($cur_table)) {

                if ('oci' == strtolower($this->APP_DB_TYPE)) {
                    $cur_table = strtoupper($cur_table);
                    $field = strtoupper($field);
                }

                // 表不存在
                $tables = empty($this->_current_tables[$this->curr_cluster_name]) ? $this->_tables[$this->curr_cluster_name] : $this->_current_tables[$this->curr_cluster_name];
                if (!in_array($cur_table, $tables))
                    throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_not_exist', array($cur_table)));


                // 检查表、表加载、字段
                if (!$this->parse_check_table($cur_table)) {
                    throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_table_not_exist', array($cur_table)));
                }
                // 表字段信息未加载
                if (!isset($this->_fields[$this->curr_cluster_name][$cur_table])) {
                    $this->_fields[$this->curr_cluster_name][$cur_table] = $this->get_fields_by_table($cur_table);
                }

                if ($this->parse_check_table($cur_table) && isset($this->_fields[$this->curr_cluster_name]) && isset($this->_fields[$this->curr_cluster_name][$cur_table]) && in_array($field, $this->_fields[$this->curr_cluster_name][$cur_table]['fields'])) {
                    $field_result = $this->set_special_char($cur_table) . '.' . $this->set_special_char($field);
                }

            } else {

                // 在所有表字段中查找当前字段
                $tables = empty($this->_current_tables[$this->curr_cluster_name]) ? $this->_tables[$this->curr_cluster_name] : $this->_current_tables[$this->curr_cluster_name];
                foreach ($tables as $table) {

                    // 表字段信息未加载
                    if (!isset($this->_fields[$this->curr_cluster_name][$table])) {
                        $this->_fields[$this->curr_cluster_name][$table] = $this->get_fields_by_table($table);
                    }

                    if (isset($this->_fields[$this->curr_cluster_name][$table]) &&
                        @in_array($field, $this->_fields[$this->curr_cluster_name][$table]['fields'])
                    ) {


                        $field_result = $this->set_special_char($table) . '.' . $this->set_special_char($field);

                        break;
                    }

                }
            }
        }
        return $field_result;
    }


    /**
     * parse field
     *
     * parse field on query expression
     *
     * @qrcode
     * // method on, use string
     * $sql = array();
     * $sql['field'] = "u_id, u_name as name, count({u_id}) as _counter, {product.price}*50/2 as price";
     *
     * // method two, use array
     * $sql = array();
     * $sql['field'][] = "u_id"
     * $sql['field'][] = "u_name as name"
     * $sql['field'][] = "count({u_id}) as _counter"
     * $sql['field'][] = "{product.price}*50/2 as price"
     * @end
     *
     * @param    mixed $fields
     * @return    string
     */
    public function parse_field($fields)
    {

        $fieldsStr = NULL;
        if (is_string($fields)) $fields = trim($fields);
            // 所有字段
        if(empty($fields) || '*' == $fields){
            $fieldsStr = '*';
        } else if (is_array($fields)) {
            $fields_result = array();

            foreach ($fields as $alias => $field) {

                // 剔除非字符串字段设置
                if (!is_string($field))
                    continue;

                $alias = trim($alias);
                $field = trim($field);

                // 是否启用别名
                if (!is_numeric($alias)) {
                    // 剔除非字符串字段设置
                    if (!is_string($alias))
                        continue;
                    // 判断字段是否属于正常字段
                    if (preg_match_all('/\F{([a-z0-9._]*)\}/i', $field, $parse_result)) {

                        // 记录表达式
                        $exp = $field;


                        // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                        $exp_fields_old = $parse_result[0];
                        // 提取表达式中的字段
                        $exp_fields_new = $parse_result[1];

                        // 循环处理字段，可能存在多个字段AS一个字段。
                        $old_fields_tmp = array();
                        foreach ($exp_fields_new as $field) {
                            // 解析字段
                            if (!($field_tmp = $this->parse_check_field($field)))
                                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($field)));


                            $field = trim($field_tmp);
                            // 将解析后的表和字段分割出来
                            if ('mysql' == strtolower($this->APP_DB_TYPE)) {
                                @list($table_tmp, $field_tmp) = explode('.', str_replace('`', '', $field));
                            } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
                                @list($table_tmp, $field_tmp) = explode('.', str_replace('"', '', $field));
                            }


                            if (!isset($this->_fields[$this->curr_cluster_name][$table_tmp])) {
                                if ($table_tmp && $field_tmp) {
                                    $old_fields_tmp[] = $this->set_special_char($table_tmp) . '.' . $this->set_special_char($field_tmp);
                                } else {
                                    $old_fields_tmp[] = "";
                                }
                            } else {
                                // 推入字段
                                array_push($this->_fields[$this->curr_cluster_name][$table_tmp]['fields'], $alias);

                                $old_fields_tmp[] = $field;

                            }
                        }

                        $fields_str = str_replace($exp_fields_old, $old_fields_tmp, $exp);

                    } else {
                        // 解析字段
                        if (!($field = $this->parse_check_field($field)))
                            continue;

                        // 将解析后的表和字段分割出来
                        if ('mysql' == strtolower($this->APP_DB_TYPE)) {
                            list($table_tmp, $field_tmp) = explode('.', str_replace('`', '', $field));
                        } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
                            list($table_tmp, $field_tmp) = explode('.', str_replace('"', '', $field));
                        }


                        if (!isset($this->_fields[$this->curr_cluster_name][$table_tmp])) {
                            $fields_str = $this->set_special_char($table_tmp) . '.' . $this->set_special_char($field_tmp);
                        } else {
                            // 推入字段
                            array_push($this->_fields[$this->curr_cluster_name][$table_tmp]['fields'], $alias);

                            // 源字段索引
                            $field_index = array_search($field_tmp, $this->_fields[$this->curr_cluster_name][$table_tmp]['fields']);

                            // 复制
                            $new_struct = $this->_fields[$this->curr_cluster_name][$table_tmp]['struct'][$field_index];
                            $new_struct['name'] = $alias;
                            array_push($this->_fields[$this->curr_cluster_name][$table_tmp]['struct'], $new_struct);

                            $fields_str = $field;
                        }
                    }

                    $fields_result[] = $fields_str . ' AS ' . $this->set_special_char($alias);
                } else {
                    if (!($field = $this->parse_check_field($field)))
                        continue;

                    $fields_result[] = $this->set_special_char($field);
                }
            }

            $fieldsStr = implode(', ', $fields_result);
        }
//        else if (is_string($fields)) {
//            $fieldsStr = $fields;
//        }
        else {
            throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_parse'));
        }

        if (empty($fieldsStr))
            $fieldsStr = '*';

        return $fieldsStr;
    }

    /**
     * parse where
     *
     * parse where on query expression
     *
     * @param array $expression
     *
     * @return string
     */
    public function parse_where(array $expression)
    {
        // 多条件分组连接符
        $Connector = (isset($expression['_logic']) &&
            ('OR' == strtoupper($expression['_logic']))) ? ' OR ' : ' AND ';

        $where_condition = array();
        $sql = "";
        if (isset($expression['__sql__'])) {
            $sql = $expression['__sql__'];
            unset($expression['__sql__']);
        }

        foreach ($expression as $field => $value) {
            // 多条件分组查询
            if (is_numeric($field)) {
                //数字下标一定是条件分组
                if (is_array($value)) {
                    $where_condition[] = $this->parse_where($value);
                }
            } else // 单条件查询
            {
                if (!$field = $this->parse_check_field(trim($field)))
                    continue;
                // 值比较表达式
                if (!is_array($value)) {
                    $new_value = $this->parse_value($value);

                    if ('undefined' === $new_value)
                        continue;

                    // 处理连接符
                    $cc = ' = ';

                    if (is_string($new_value) && false === strpos($new_value, '\''))
                        $cc = '';

                    $where_condition[] = $field . $cc . $new_value;
                } else {
                    $symbol = array(
                        '^', '|', '&', '=', '!=', '>', '>=', '<', '<=',
                        'NOT LIKE', 'LIKE', 'REGEXP', 'RLIKE', 'NOTREGEXP',
                        'NOT RLIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN',
                        'SQL IN', 'SQL NOT IN',
                        'SQL =', 'SQL !=',
                        'SQL >', 'SQL >=',
                        'SQL <', 'SQL <=',
                    );

                    foreach ($value as $field_key => $field_val) {
                        $field_key = trim(strtoupper($field_key));

                        // 运算符必须是预设运算符
                        if (in_array($field_key, $symbol)) {
                            $is_sql_expression = false;
                            if (in_array($field_key, array('IN', 'NOT IN'))) {
                                if (!is_array($field_val))
                                    $field_val = array($field_val);

                                foreach ($field_val as $kkkk => $vvvv) {
                                    $field_val[$kkkk] = $this->parse_value($vvvv);
                                }

                                if (empty($field_val))
                                    continue;

                                $field_val = '(' . implode(', ', $field_val) . ')';
                            } elseif (in_array($field_key, array('BETWEEN', 'NOT BETWEEN'))) {
                                if (!is_array($field_val) || (count($field_val) != 2))
                                    throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_between', array($field)));


                                if (0 == strlen($field_val[0]) || 0 == strlen($field_val[1]))
                                    continue;

                                $field_val = $this->parse_value($field_val[0]) . ' AND ' . $this->parse_value($field_val[1]);
                            } elseif (in_array($field_key, array('SQL IN', 'SQL NOT IN', 'SQL =', 'SQL !=', 'SQL >', 'SQL >=', 'SQL <', 'SQL <='))) {
                                if (0 == strlen($field_val))
                                    continue;
                                $is_sql_expression = true;
                                $field_key = str_ireplace('SQL ', '', $field_key);
//                                $field_val = trim("'", $this->parse_value($field_val));
                            } else {
                                if (0 == strlen($field_val))
                                    continue;

                                $field_val = $this->parse_value($field_val);
                            }

                            //if(is_string($field_val)) $field_val=str_replace("'","''",$field_val);
                            if ($is_sql_expression) {
                                $where_condition[] = $field . ' ' . $field_key . ' ' . $field_val;
                            } else {
                                $where_condition[] = $field . ' ' . $field_key . ' ' . $field_val;
                            }
                        }
                    }
                }


            }
        }
        $where_string = "";
        if (!empty($where_condition)) {
            if (count($where_condition) > 1) {
                $where_string = '(' . implode($Connector, $where_condition) . ')';
            } else {
                $where_string = implode($Connector, $where_condition);
            }
        }
        if ($sql) {
            if ($where_string) {
                $where_string .= $Connector . ' (' . $sql . ')';
            } else {
                $where_string = $sql;
            }
            return '(' . $where_string . ')';
        } else {
            return $where_string;
        }
    }


    /**
     * parse group
     *
     * @param    mixed $groups
     * @return    string
     */
    public function parse_group(array $groups)
    {

        $groupArr = array();
        foreach ($groups as $group) {
            $group = trim($group);

            if (!($new_group = $this->parse_check_field($group)))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($group)));


            $groupArr[] = $new_group;
        }

        return implode(', ', $groupArr);
    }

    /**
     * parse order
     *
     * parse order on query expression
     *
     * @param mixed $orders
     * @return    string
     */
    public function parse_order(array $orders)
    {
        $orderArr = array();
        $spec_funtions = array('rand()');

        foreach ($orders as $field => $orderStr) {
            $field = strtolower(str_ireplace(' ', '', trim($field)));
            $orderStr = trim(strtoupper($orderStr));


            if (!in_array($field, $spec_funtions)) {
                if (!($new_field = $this->parse_value($field, true)))
                    throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($field)));
            } else {
                $new_field = $field;
            }

            if (!in_array($orderStr, array('DESC', 'ASC')))
                throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_orderby'));

            $orderArr[] = $new_field . ' ' . $orderStr;
        }

        return implode(', ', $orderArr);
    }

    /**
     * parse limit
     *
     * parse limit on query expression
     *
     * @param    array $limit
     * @return    string
     */
    public function parse_limit(array $limit)
    {

        if ('mysql' == strtolower($this->APP_DB_TYPE)) {
            if (count($limit) === 1)
                array_unshift($limit, 0);

            list($start, $size) = $limit;

            $size = $size ? ', ' . trim($size) : '';

            return $start . $size;
        } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
            return $limit;
        }
    }


    /**
     * 解析添加语句，并返回数组，数组格式为：array("sql"=>"... :field ...","params"=>array(":field"=>value,...),'sql_params'=>("sql"=>"... :field ...","params"=>array(":field"=>value,...)))
     * @param    mixed $expression 字段值数组，格式：array("field1"=>value1,...)
     * @param    string $table 表名，必须
     * @return    array("sql"=>"... :field ...","params"=>array(":field"=>value,...),'sql_params'=>("sql"=>"... :field ...","params"=>array(":field"=>value,...)))
     */
    public function parse_insert(array $expression, $table)
    {
        $table = $this->get_define_name($table);
        $fields = array();//字段名数组
        $param_names = array();//参数名数组
        $params = array();//参数化查询参数数组


        foreach ($expression as $k => $v) {
            $k = $this->get_define_name($k);
            //值为数组则不做处理
            if (is_array($v)) continue;

            //如果为$k不是$table表的字段则不做处理
            if (!$field = $this->parse_check_field($k, $table))
                continue;

            if (preg_match_all('/F\{([a-z0-9._]*)\}/i', $v, $parse_result)) {
                //如果是表达式字段
                // 记录表达式，用于下文还原表达式。
                $exp = $v;

                // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                $exp_fields_old = $parse_result[0];

                // 提取表达式中的字段
                $exp_fields_new = $parse_result[1];

                // 循环处理字段
                $old_fields_tmp = array();
                foreach ($exp_fields_new as $_field) {
                    // 解析字段
                    if (!($field_tmp = $this->parse_check_field($_field)))
                        throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($_field)));


                    $old_fields_tmp[] = $field_tmp;
                }

                // 还原表达式
                $value = str_replace($exp_fields_old, $old_fields_tmp, $exp);

                $fields[] = $field;
                $param_name = $value;
                $param_names[] = $param_name;
            } elseif (($this->_SEQUENCE_PREFIX_NAME . $table . ".NEXTVAL") == $v) {
                //oracle序列化字段
                $fields[] = $field;
                $param_name = $v;
                $param_names[] = $param_name;
            } elseif (in_array(strtolower($v),array('uuid()','uuid_short()'))) {
                //mysql uuid字段值
                $fields[] = $field;
                $param_name = strtoupper($v);
                $param_names[] = $param_name;
            } else {
                //普通类型字段
                $fields[] = $field;
                $param_name = '?';
                $param_names[] = $param_name;
                $params[] = $v;
            }
        }

        if ($fields) {
            $fields = implode(', ', $fields);
            $param_names = implode(', ', $param_names);
            $sql = ' (' . $fields . ') VALUES (' . $param_names . ') ';
            return array('sql' => $sql, 'params' => $params);
        }
        return array();

    }


    /**
     * 解析修改语句，并返回数组，数组格式为：array("sql"=>"... :field ...","params"=>array(":field"=>value,...))
     *
     * @param    array $expression 字段值数组，格式：array("field1"=>value1,...)
     * @param    string $table 表名，必须
     * @return    array
     */
    public function parse_update(array $expression, $table)
    {
        $table = $this->get_define_name($table);
        $fields = array();//字段名数组
        $param_names = array();//参数名数组
        $params = array();//参数化查询参数数组

        foreach ($expression as $k => $v) {
            $k = $this->get_define_name($k);
            //值为数组则不做处理
            if (is_array($v)) continue;

            //如果为$k不是$table表的字段则不做处理
            if (!$field = $this->parse_check_field($k, $table))
                continue;

            if (preg_match_all('/F\{([a-z0-9._]*)\}/i', $v, $parse_result)) {
                //如果是表达式字段
                // 记录表达式，用于下文还原表达式。
                $exp = $v;

                // 提取表达式中的字段及包含字段的化括号以备后文批量替换
                $exp_fields_old = $parse_result[0];

                // 提取表达式中的字段
                $exp_fields_new = $parse_result[1];

                // 循环处理字段
                $old_fields_tmp = array();
                foreach ($exp_fields_new as $_field) {
                    // 解析字段
                    if (!($field_tmp = $this->parse_check_field($_field)))
                        throw new \cn\eunionz\exception\ModelException($this->getLang('error_model_title'), $this->getLang('error_model_field_not_exist', array($_field)));


                    $old_fields_tmp[] = $field_tmp;
                }

                // 还原表达式
                $value = str_replace($exp_fields_old, $old_fields_tmp, $exp);

                $fields[] = $field;
                $param_name = $value;
                $param_names[] = $param_name;
            } elseif (($this->_SEQUENCE_PREFIX_NAME . $table . ".NEXTVAL") == $v) {
                //oracle序列化字段
                $fields[] = $field;
                $param_name = $v;
                $param_names[] = $param_name;
            } else {
                //普通类型字段
                $fields[] = $field;
                $param_name = '?';//':' . $this->get_define_name($k);
                $param_names[] = $param_name;
                $params[] = $v;//$params[$param_name] = $v;
            }
        }


        if ($fields) {
            $sql = array();
            foreach ($fields as $index => $field) {
                $sql[] = $field . " = " . $param_names[$index];
            }
            $sql = implode(', ', $sql);
            return array('sql' => $sql, 'params' => $params);
        }
        return array();
    }


    /**
     * 执行查询语句
     *
     * @qrcode
     * $options = array();
     *
     * // 设定表
     * $options['table'][] = 'atricle';
     * $options['table'][] = 'category as cat';
     *
     * // 查询条件 1
     * $options['where']['c_id'] = 'a_id';
     * // 或者指定字段所属表名
     * $options['where']['cat.c_id'] = 'article.id';
     *
     * // 查询条件 2
     * $options['orwhere']['c_id'] = '1';
     * $options['orwhere']['c_id'] = '9';
     *
     * $rows = $this->select($options);
     * @end
     *
     * @param array $options
     * @param bool $return_sql
     *
     * @return    array
     */
    public function select($tablename, array $options = array(), $return_sql = false)
    {
        $opts = $options;
        $sql = $this->cache('db_query_select', array($tablename, $opts));
        if (!$sql) {
            // 初始化局部变量
            $_distinctStr = null;
            $_fieldStr = null;
            $_tableStr = null;
            $_joinStr = null;
            $_whereStr = null;
            $_groupStr = null;
            $_havingStr = null;
            $_orderStr = null;
            $_limitStr = null;

            // 解析 表
            $_tableStr = $this->parse_table(array($tablename));
            // 解析 连接表
            // 优先解析连接表能够读取表字段以便下文进行字段筛选


            if (isset($options['join'])) {
                $_joinStr = $this->parseJoin($options['join']);
            }


            // 解析 是否加排它锁
            if (!isset($options['forupdate']))
                $options['forupdate'] = false;

            // 解析 字段
            // 字段为*
            if (!isset($options['field']))
                $options['field'] = null;

            $_fieldStr = $this->parse_field($options['field']);

            // 解析 排除重复字段值
            // 与GROUP BY功能类似
            // 设置了该值之后，建议不要设置字段筛选。
            if (isset($options['distinct']) && $options['distinct']) {
                $_distinctStr = 'DISTINCT ';

                if ('*' == $_fieldStr)
                    $_fieldStr = null;

                $_distinctStr .= ' ';
            }

            // 解析 分组
            if (isset($options['where'])) {
                $where = $this->parse_where($options['where']);

                $_whereStr = empty($where) ? null : 'WHERE ' . $where;
            }

            // 解析 分组
            if (isset($options['group']))
                $_groupStr = 'GROUP BY ' . $this->parse_group($options['group']);

            // 解析 分组结果筛选
            // HAVING 能够对 GROUP BY 之后的分组数据进行筛选
            // 与 where 类似，只是一个在分组前一个在分组后。
            if (isset($options['having']))
                $_havingStr = 'HAVING ' . $this->parse_where($options['having']);

            // 解析 排序
            if (isset($options['order']))
                $_orderStr = 'ORDER BY ' . $this->parse_order($options['order']);

            if ('mysql' == strtolower($this->APP_DB_TYPE)) {
                // 解析 游标
                if (isset($options['limit']))
                    $_limitStr = 'LIMIT ' . $this->parse_limit($options['limit']);


                // 生成 SQL 语句
                $sql = "SELECT $_distinctStr $_fieldStr FROM $_tableStr $_joinStr $_whereStr $_groupStr $_havingStr $_orderStr $_limitStr";
            } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
                // 生成 SQL 语句
                if (isset($options['limit']) && count($options['limit']) == 1) {
                    $sql = "SELECT * FROM (SELECT $_distinctStr $_fieldStr FROM $_tableStr $_joinStr $_whereStr $_groupStr $_havingStr $_orderStr) WHERE rownum <= " . $options['limit'][0];
                } else if (isset($options['limit']) && count($options['limit']) >= 2) {
                    $sql = "SELECT * FROM (SELECT *,rownum as row_num FROM (SELECT $_distinctStr $_fieldStr FROM $_tableStr $_joinStr $_whereStr $_groupStr $_havingStr $_orderStr) WHERE rownum <= " . $options['limit'][1] . ") WHERE row_num>" . $options['limit'][0];
                } else {
                    $sql = "SELECT $_distinctStr $_fieldStr FROM $_tableStr $_joinStr $_whereStr $_groupStr $_havingStr $_orderStr";
                }
            }
            if ($options['forupdate']) {
                $sql .= " FOR UPDATE";
            }
            $this->cache('db_query_select', array($tablename, $opts), $sql, $this->_db_cache_expire);
        }
        // 生成查询结果或直接返回SQL语句
        return $return_sql ? $sql : $this->query($sql);
    }


    /**
     * 执行添加
     *
     * @param array $data
     * @param array $options
     * @param bool $return_sql
     *
     * @return    integer
     */
    public function insert($tablename, array $data, array $options = null, $return_sql = false)
    {
        // 解析 表

        if ('oci' == strtolower($this->APP_DB_TYPE)) {
            $tablename = strtoupper($tablename);
        }


        $_tableStr = $this->parse_table(array($tablename));

        $_setStr = $this->parse_insert($data, $tablename);
        if (empty($_setStr)) return false;
        $sql = 'INSERT INTO ' . $_tableStr . $_setStr['sql'];
        $params = null;
        if (isset($_setStr['params']) && $_setStr['params'] && is_array($_setStr['params'])) {
            $params = $_setStr['params'];
        }
        $_setStr['sql'] = $sql;
        $_setStr['params'] = $params;


        if ($return_sql)
            return $this->get_sql($_setStr);

        if ($params) {
            $rs = $this->exec($sql, $params);
        } else {
            $rs = $this->exec($sql);
        }
        if (!$rs) return false;
        $is_sequence = stripos($this->_SEQUENCE_PREFIX_NAME . $this->get_define_name($tablename) . ".NETXVAL", $sql) === false ? false : true;

        $tablename = $this->get_define_name($tablename);

        if ('oci' == strtolower($this->APP_DB_TYPE)) {
            if ($is_sequence)
                return $this->get_insert_id($this->_SEQUENCE_PREFIX_NAME . $tablename);
            else
                return true;
        }


        // 插入成功的情况下，如果主键是自增型则返回插入ID否则返回true。
        if (true === $this->_fields[$this->curr_cluster_name][$tablename]['struct'][0]['autoinc']) {
            return $this->get_insert_id();
        } else {
            return true;
        }
    }


    /**
     *
     * updtae
     *
     * update an row data
     *
     * @param array $data
     * @param array $options
     * @param bool $return_sql
     *
     * @return int
     */
    public function update($tablename, array $data, array $options, $return_sql = false)
    {
        // 初始化局部变量
        $_tableStr = null;
        $_setStr = null;
        $_whereStr = null;
        $_orderStr = null;

        // 解析 表
        $_tableStr = $this->parse_table(array($tablename));

        // 解析值
        $_setStr = $this->parse_update($data , $tablename);

        if (empty($_setStr)) return false;

        // 解析 条件
        if (isset($options['where']))
            $_whereStr = 'WHERE ' . $this->parse_where($options['where']);

        // 解析 排序
        if (isset($options['order']))
            $_orderStr = 'ORDER BY ' . $this->parse_order($options['order']);

        $sql = 'UPDATE ' . $_tableStr . ' SET ' . $_setStr['sql'] . " $_whereStr $_orderStr";
        $params = null;
        if (isset($_setStr['params']) && $_setStr['params'] && is_array($_setStr['params'])) {
            $params = $_setStr['params'];
        }
        $_setStr['sql'] = $sql;
        $_setStr['params'] = $params;

        if ($return_sql)
            return $this->get_sql($_setStr);

        if ($params) {
            $rs = $this->exec($sql, $params);
        } else {
            $rs = $this->exec($sql);
        }
        return $rs;
    }

    /**
     * delete
     *
     * delete an row
     *
     * @param array $options
     * @param bool $return_sql
     *
     * @return    integer
     */
    public function delete($tablename, array $options, $return_sql = false)
    {
        // 初始化局部变量
        $_tableStr = null;
        $_limitStr = null;
        $_whereStr = null;
        $_orderStr = null;

        // 解析表
        $_tableStr = $this->parse_table(array($tablename));

        // 解析 条件
        if (isset($options['where']))
            $_whereStr = 'WHERE ' . $this->parse_where($options['where']);

        // 解析 排序
        if (isset($options['order']))
            $_orderStr = 'ORDER BY ' . $this->parse_order($options['order']);

        // 解析游标
        if (isset($options['limit']))
            $_limitStr = $this->parse_limit($options['limit']);

        if ('mysql' == strtolower($this->APP_DB_TYPE)) {
            $sql = "DELETE FROM $_tableStr $_whereStr $_orderStr $_limitStr";
        } else if ('oci' == strtolower($this->APP_DB_TYPE)) {
            $sql = "DELETE FROM $_tableStr $_whereStr $_orderStr";
        }
        return $return_sql ? $sql : $this->exec($sql);
    }


    /**
     * get fields
     *
     *
     * get fields by table name
     *
     * @param    string $table
     *
     * @return    array
     */
    public function get_fields_by_table($table)
    {
        $result = null;

        if (isset($this->_fields[$this->curr_cluster_name][$table]['fields'])) {
            $result = $this->_fields[$this->curr_cluster_name][$table];
        } elseif ($this->APP_DB_STRUCTURE_CACHE) {
            if (!isset($this->APP_DB_SERVERS[$this->curr_cluster_name][0]) || !is_array($this->APP_DB_SERVERS[$this->curr_cluster_name][0])) {
                throw new \cn\eunionz\exception\DBException($this->getLang('error_db_title'), $this->getLang('error_db_server_list'));
            }
            $currDBID = $this->APP_DB_SERVERS[$this->curr_cluster_name][0];
            $fileName = str_replace('.', '_', $currDBID['HOST']) . '_' . $currDBID['PORT'] . '_' . $currDBID['NAME'] . '_' . $table;

            $result = $this->cache('db_structure', array($fileName));
            if (!$result) {
                $result = $this->get_fields($this->set_special_char($table));
                $cache = serialize($result);
                $this->cache('db_structure', array($fileName), $cache, $this->_db_cache_expire);
            } else {
                $result = unserialize($result);
            }

        } else {
            $result = $this->get_fields($this->set_special_char($table));
        }

        return $result;
    }

    /**
     * get pk
     *
     * get an table pk
     *
     * @param    string $table
     *
     * @return    String
     */
    public function get_pk($table = null)
    {
        if ('oci' == strtolower($this->APP_DB_TYPE)) {
            $table = strtoupper($table);
        }

        if (!empty($table)) {
            if (!isset($this->_fields[$this->curr_cluster_name][$table])) {
                $this->parse_check_table($table);
                $this->parse_table(array($table));
            }
            $table = $this->_fields[$this->curr_cluster_name][$table];
        } else {
            if (!empty($this->_fields[$this->curr_cluster_name]))
                $table = $this->_fields[$this->curr_cluster_name][0];
        }

        return !empty($table['primary']) ? $table['primary'] : $table['fields'][0];
    }

    /**
     * 获取字段，表名除特殊字符之外的名称
     * @param $name  字段名/表名
     * @return 去除特殊字符之外的名称
     */
    public function get_define_name($name)
    {
        if (preg_match("/[0-9a-zA-Z_]{1,}/", $name, $arr)) {
            return $arr[0];
        }
        return "";
    }

}
