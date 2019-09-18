<?php
/**
 * EUnionZ PHP Framework SortClass Plugin class
 * 针对php 二维数组中某字段进行排序
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\sortclass;


defined('APP_IN') or exit('Access Denied');

class SortClass extends \com\eunionz\core\Plugin{
    /**
     * 对数据库查询出来的二维数组中某个字段进行升序排序
     * @param        $preData   要排序的二维数组
     * @param string $sortType  要排序的字段
     *
     * @return array  排序完成的二维数组
     */
    function sortArrayAsc($preData,$sortField,$sort_type=SORT_NUMERIC){
        return $this->my_sort($preData,$sortField,SORT_ASC,$sort_type);
    }

    /**
     * 对数据库查询出来的二维数组中某个字段进行降序排序
     * @param        $preData   要排序的二维数组
     * @param string $sortType  要排序的字段
     *
     * @return array  排序完成的二维数组
     */
    function sortArrayDesc($preData,$sortField,$sort_type=SORT_NUMERIC){
        return $this->my_sort($preData,$sortField,SORT_DESC,$sort_type);
    }

    /**
     * 针对二维数组进行排序
     * @param $cart_goods  要排序的二维数组
     * @param $field  要排序的二维数组字段
     * @param int $direct 方向  SORT_ASC--升序    SORT_DESC--降序
     */
    public function sortArray($arr,$field,$direct=SORT_ASC){

        foreach ($arr as $key => $row)
        {
            $vals[$key] = $row[$field];
        }

        array_multisort($vals, $direct, $arr);
    }


    /**
     * 二维数组排序
     * @param $arrays  输入二维数组
     * @param $sort_key  要排序的字段
     * @param int $sort_order  升或降序
     * @param int $sort_type  排序类型
     * @return array|bool
     */
    public function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(!$arrays) return $arrays;
        if(count($arrays)<=1) return $arrays;
        if(is_array($arrays) && $arrays){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return $arrays;
                }
            }
        }else{
            return array();
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    public function array_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }
}