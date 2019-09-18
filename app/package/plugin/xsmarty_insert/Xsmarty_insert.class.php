<?php
/**
 * EUnionZ PHP Framework Xsmarty_insert Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\xsmarty_insert;


defined('APP_IN') or exit('Access Denied');

/**
 * Xsmarty insert
 * Created by liulin
 **/

class Xsmarty_insert extends \cn\eunionz\core\Plugin
{

    private $smarty=null;

    function init(& $smarty){
        $this->smarty = $smarty;
        return $this;
    }

    /**
     * 获得查询次数以及查询时间
     *
     * @access  public
     * @return  string
     */
    function query_info($arr)
    {

        $need_cache = $this->smarty->caching;
        $need_compile = $this->smarty->force_compile;

        $this->smarty->caching = false;
        $this->smarty->force_compile = true;

        $this->smarty->assign('mydatas', $arr['datas']);

        $val = $this->smarty->fetch('library/datas.tpl');

        $this->smarty->caching = $need_cache;
        $this->smarty->force_compile = $need_compile;

        return $val;

    }

}
