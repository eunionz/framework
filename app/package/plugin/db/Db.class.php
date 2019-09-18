<?php

/**
 * EUnionZ PHP Framework db Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\db;


defined('APP_IN') or exit('Access Denied');


/**
 * Ftp Plugin
 *
 */
class Db extends \cn\eunionz\core\Plugin
{

    private $wheres=array();
    private $where='';

    /**
     * 分析 where数组
     * @param $wheres 数组
     *      格式1：
     *      [
     *          '__logic'=>'AND|OR',
     *          '字段1' => 值1,
     *          '字段2' => 值2,
     *          '字段n' => 值n,
     *      ]
     *      格式2：
     *      [
     *          '__logic'=>'AND|OR',
     *          '字段1' => 值1,
     *          '字段2' => 值2,
     *          '字段n' => 值n,
     *          [
     *              '__logic'=>'AND|OR',
     *              '字段1' => 值1,
     *              '字段2' => 值2,
     *              '字段n' => 值n,
     *          ],
     *          [
     *              '__logic'=>'AND|OR',
     *              '字段1' => 值1,
     *              '字段2' => 值2,
     *              '字段n' => 值n,
     *          ]
     *      ]
     *      格式3：
     *      [
     *          '__logic'=>'AND|OR',
     *          '字段1' => 值1,
     *          '字段2' => 值2,
     *          '字段n' => 值n,
     *          [
     *              '__logic'=>'AND|OR',
     *              '字段1' => 值1,
     *              '字段2' => 值2,
     *              '字段n' => 值n,
     *              [
     *                  '__logic'=>'AND|OR',
     *                  '字段1' => 值1,
     *                  '字段2' => 值2,
     *                  '字段n' => 值n,
     *                  [
     *                      '__logic'=>'AND|OR',
     *                      '字段1' => 值1,
     *                      '字段2' => 值2,
     *                      '字段n' => 值n,
     *                  ],
     *              ],
     *              [
     *                  '__logic'=>'AND|OR',
     *                  '字段1' => 值1,
     *                  '字段2' => 值2,
     *                  '字段n' => 值n,
     *              ]
     *          ],
     *          [
     *              '__logic'=>'AND|OR',
     *              '字段1' => 值1,
     *              '字段2' => 值2,
     *              '字段n' => 值n,
     *          ]
     *      ]
     * @return $sql
    */
    public function parseWhere($wheres){
        $__logic=' AND ';
        if(empty($wheres)) return '';
        $where = '';
        $where .= '(';
        if(is_array($wheres)){
            if(isset($wheres['__logic']) && strtolower($wheres['__logic'])=='or'){
                $__logic=' OR  ';
                unset($wheres['__logic']);
            }
            $subwhere='';
            foreach ($wheres as $key => $val){
                if(is_string($key)){
                    //下标为字段
                    $field = $this->parseField($key);
                    if(is_array($val)){
                        //值为数组
                        $subwhere .= '(';
                        foreach ($val as $k=>$v){
                            $value = $this->parseValue($v,$k);
                            if($value){
                                $subwhere .= "({$field} {$value})" . $__logic;
                            }

                        }
                        if($subwhere) $subwhere =  substr($subwhere,0,strlen($subwhere)-5);
                        $subwhere .= ')'. $__logic;
                    }else{
                        $value = $this->parseValue($val);
                        $subwhere .= "{$field} {$value}" . $__logic;
                    }

                }elseif(is_numeric($key)){
                    //下标为数字
                    if(is_array($val)){
                        $subwhere .= $this->parseWhere($val) . $__logic;
                    }
                }
            }
            if($subwhere) $where .=  substr($subwhere,0,strlen($subwhere)-5);
        }elseif(is_string($wheres)){
            $where .= $wheres;
        }
        $where .= ')';
        return $where;
    }


    /**
     * 分析字段
     * @param $field  字段名称
     * @return mixed
     */
    public function parseField($field){
        return '`' . $field . '`';
    }

    /**
     * 分析值
     * @param $value  值
     * @param $operation  运算符
     * @return mixed 运算符及值部份
     */
    public function parseValue($value,$operation='='){
        $operation=strtolower($operation);
        $operations=array('^','|','&','=','!=','<>','<','>','<=','>=',
            'like','not like','between','not between',
            'in','not in', 'regexp', 'rlike', 'notregexp', 'not rlike','sub not in','sub in','sub equal','sub not equal');
        if(!in_array($operation,$operations)){
            //非法运算符
            return "";
        }

        $value=preg_replace("/(F\\{[a-z0-9_]+\\})/i","`$1`",$value);



        if($operation=='between' || $operation=='not between'){
            if(is_array($value)){
                return strtoupper($operation) . " '" . str_ireplace("'","''",$value[0]) ."' AND '" . str_ireplace("'","''",$value[1]) ."' ";
            }
        }elseif($operation=='in' || $operation=='not in'){
            if(is_array($value)){
                return strtoupper($operation) . " ('" . implode("','",$value)  .  "') ";
            }
        }elseif($operation=='like' || $operation=='not like'){
            return strtoupper($operation) . " '" . str_ireplace("'","''",$value)  .  "' ";
        }elseif($operation=='='){
            if(strtolower($value)=='[null]'){
                return " IS NULL ";
            }elseif(strtolower($value)=='[not null]'){
                return " IS NOT NULL ";
            }elseif(strtolower($value)=='[empty]'){
                return " = '' ";
            }elseif(strtolower($value)=='[not empty]'){
                return " != '' ";
            }else{
                return " = '" . str_ireplace("'","''",$value)  .  "' ";
            }
        }elseif($operation=='!=' || $operation=='<>'){
            if(strtolower($value)=='[null]'){
                return " IS NOT NULL ";
            }elseif(strtolower($value)=='[not null]'){
                return " IS NULL ";
            }elseif(strtolower($value)=='[empty]'){
                return " != '' ";
            }elseif(strtolower($value)=='[not empty]'){
                return " = '' ";
            }else{
                return strtoupper($operation) . " '" . str_ireplace("'","''",$value)  .  "' ";
            }
        }elseif($operation=='sub not in'){
            return " NOT IN(" . $value  .  ") ";
        }elseif($operation=='sub in'){
            return " IN (" . $value  .  ") ";
        }elseif($operation=='sub equal'){
            return " = (" . $value  .  ") ";
        }elseif($operation=='sub not equal'){
            return " != (" . $value  .  ") ";
        }
        else{
            return strtoupper($operation) . " '" . str_ireplace("'","''",$value)  .  "' ";
        }
        return "";
    }

}
