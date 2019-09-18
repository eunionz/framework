<?php

/**
 * EUnionZ PHP Framework Filter Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\filter;


defined('APP_IN') or exit('Access Denied');


class Filter extends \com\eunionz\core\Plugin
{

    private $APP_BASE_DATA_NOT_FILTER_FIELD_NAMES;

    public function __construct()
    {
        $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES = $this->getConfig('global', 'APP_BASE_DATA_NOT_FILTER_FIELD_NAMES');
    }

    /**
     * 对数组进行递归调用进行关键字过滤
     * 修改为对最多6维数组循环
     * @param $arr
     */
    public function filter(& $arr){


//       if(isset($arr) && $arr && is_array($arr)){
//            foreach($arr as $k=>$v){
//                if(is_string($v)){
//                    $arr[$k]= $this->loadService('Sys_sensitive_words')->filter($v);
//                    if(!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES)){
//                        $arr[$k] = strip_tags( $arr[$k]);
//                    }
//                }else if(is_array($v)){
//                    $this->filter($v);
//                    $this->loadCore('log')->write(APP_DEBUG,print_r($v,true));
//                    $arr[$k]=$v;
//                }
//            }
//
//        }

        if(isset($arr) && $arr && is_array($arr)){
            foreach ($arr as $k=>$v){
                //第一层循环
                if(is_string($v)){
                    $arr[$k]= $this->loadService('Sys_sensitive_words')->filter($v);
                    if(!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES)){
                        $arr[$k] = strip_tags( $arr[$k]);
                    }
                }else if(is_array($v)){
                    foreach ($v as $kk=>$vv){
                        //第二层循环
                        if(is_string($vv)){
                            $arr[$k][$kk]= $this->loadService('Sys_sensitive_words')->filter($vv);

                            $b=!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                            if(empty($kk)){
                                $b=$b && !in_array($k.'[]' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                            }
                            if($b){
                                $arr[$k][$kk] = strip_tags( $arr[$k][$kk]);
                            }
                        }else if(is_array($vv)){
                            foreach ($vv as $kkk=>$vvv){
                                //第三层循环
                                if(is_string($vvv)){
                                    $arr[$k][$kk][$kkk]= $this->loadService('Sys_sensitive_words')->filter($vvv);

                                    $b=!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                    if(empty($kkk)){
                                        $b=$b && !in_array($k.'[' . $kk .'][]' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                    }
                                    if($b){
                                        $arr[$k][$kk][$kkk] = strip_tags($arr[$k][$kk][$kkk]);
                                    }
                                }else if(is_array($vvv)){
                                    foreach ($vvv as $kkkk=>$vvvv){
                                        //第四层循环
                                        if(is_string($vvvv)){
                                            $arr[$k][$kk][$kkk][$kkkk]= $this->loadService('Sys_sensitive_words')->filter($vvvv);

                                            $b=!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                            if(empty($kkkk)){
                                                $b=$b && !in_array($k.'[' . $kk .'][' . $kkk .'][]' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                            }
                                            if($b){
                                                $arr[$k][$kk][$kkk][$kkkk] = strip_tags($arr[$k][$kk][$kkk][$kkkk]);
                                            }
                                        }else if(is_array($vvvv)){
                                            foreach ($arr as $kkkkk=>$vvvvv){
                                                //第五层循环
                                                if(is_string($vvvvv)){
                                                    $arr[$k][$kk][$kkk][$kkkk][$kkkkk]= $this->loadService('Sys_sensitive_words')->filter($vvvvv);

                                                    $b=!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .'][' . $kkkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);

                                                    if(empty($kkkkk)){
                                                        $b=$b && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .'][]' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                                    }
                                                    if($b){
                                                        $arr[$k][$kk][$kkk][$kkkk][$kkkkk] = strip_tags($arr[$k][$kk][$kkk][$kkkk][$kkkkk]);
                                                    }
                                                }else if(is_array($vvvv)){
                                                    foreach ($arr as $kkkkkk=>$vvvvvv){
                                                        //第六层循环
                                                        if(is_string($vvvvvv)){
                                                            $arr[$k][$kk][$kkk][$kkkk][$kkkkk][$kkkkkk]= $this->loadService('Sys_sensitive_words')->filter($vvvvvv);

                                                            $b=!in_array($k , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .'][' . $kkkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES) && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .'][' . $kkkkk .'][' . $kkkkkk .']' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);

                                                            if(empty($kkkkkk)){
                                                                $b=$b && !in_array($k.'[' . $kk .'][' . $kkk .'][' . $kkkk .'][' . $kkkkk .'][]' , $this->APP_BASE_DATA_NOT_FILTER_FIELD_NAMES);
                                                            }
                                                            if($b){
                                                                $arr[$k][$kk][$kkk][$kkkk][$kkkkk][$kkkkkk] = strip_tags($arr[$k][$kk][$kkk][$kkkk][$kkkkk][$kkkkkk]);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }



    /**
     * 对数组进行递归调用进行关键字过滤
     * @param $arr
     */
    public function hasSensitiveWords($arr,&$arr_sensitives){
        if(isset($arr) && $arr){
            if(is_array($arr)){
                foreach($arr as $k=>$v){
                    if(is_string($v)){
                        if($this->loadService('Sys_sensitive_words')->hasSensitiveWords($v,$arr_sensitives)){
                            return true;
                        }
                    }else if(is_array($v)){
                        return $this->hasSensitiveWords($v,$arr_sensitives);
                    }
                }
            }else if(is_string($arr)){
                if($this->loadService('Sys_sensitive_words')->hasSensitiveWords($arr,$arr_sensitives)){
                    return true;
                }
            }
        }
        return false;
    }


    public function filter_input(){

        $this->filter($_POST);
        $this->filter($_GET);
        $this->filter($_COOKIE);
    }

    public function hasSensitive_input(&$arr_sensitives){
        if($this->hasSensitiveWords($_POST,$arr_sensitives)) return true;
        if($this->hasSensitiveWords($_GET,$arr_sensitives)) return true;
        if($this->hasSensitiveWords($_COOKIE,$arr_sensitives)) return true;
        return false;
    }
}
