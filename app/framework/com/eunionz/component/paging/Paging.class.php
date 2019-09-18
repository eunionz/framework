<?php
/**
 * Eunionz PHP Framework Paging component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\paging;


defined('APP_IN') or exit('Access Denied');

/**
 * 分页组件
 * 
 * 用于分页的数据分析
 * 
 * @author 刘林
 */
class Paging extends \com\eunionz\core\Component
{
	// 实例
	static protected $_instance;
	
	/**
	 * 获取分页信息
	 * 
	 * 获取分页的相关数据
	 * 
	 * @param total_rows 总记录数
	 * @param rows_per_page 分页记录数
	 * @param page_num 当前页
	 * @param boxs 分页列表数
	 */
	public function info($total_rows, $rows_per_page, $page_num, $boxs = 5)
	{
		$arr = array();
		
		// 下一页
		$last_page = ceil($total_rows / $rows_per_page);
		
        if($last_page < $boxs){
        	$boxs=$last_page;
        }
		// 当前页数序号 1-第1页
		$page_num = (int) $page_num;


        if ($page_num > $last_page)
        {
            $page_num = $last_page;
        }

		// 第一页，第末页
		if ($page_num < 1)
		{
		   $page_num = 1;
		} 

		// 总记录
		$arr['total'] = $total_rows;
		
		// 分页LIMIT
		$upto = ($page_num - 1) * $rows_per_page;
		$arr['limit'] = array($upto, $rows_per_page);
		
        $arr['page_size'] = $rows_per_page;//每页记录数
		
		// 当前页
		$arr['current'] = $page_num;
		
		// 上一页
		$arr['previous'] = ($page_num == 1) ? $page_num : ($page_num - 1);
		
		// 下一页
		$arr['next'] =  ($page_num == $last_page) ? $last_page : ($page_num + 1);
		
		// 最后页
		$arr['last'] = $last_page;
		// 分页列表信息
		$arr['pages'] = self::get_surrounding_pages($total_rows,$page_num, $last_page, $arr['next'], $boxs);
		
        //该页-起始条数
        $arr['start'] = ($arr['current']-1)*$rows_per_page+1;
        //该页-截止条数
        $arr['end'] = ($arr['current']==$arr['last'])?$arr['total']:$arr['start']+$rows_per_page-1;
        
		return $arr;
	}
	
	/**
	 * 获取分页列表信息
	 * 
	 * @ignore
	 */
	static private function get_surrounding_pages($total_rows,$page_num, $last_page, $next, $show)
	{
		$arr = array();
        if($total_rows<=0) return $arr;

        //以下获取包含当前的起始页序号，
        $leftNum=(int)($show/2);//当前页左边应该有的数量
        if($last_page<=$show){
            //如果总页数小于或等于$show，则直接输入到总页数
            for ($i = 1; $i <= $last_page; $i++) $arr[]=$i;
        }else{
            //总页数大于 $show，表示有足够的数量来获取左边起始页序号
            $start = $page_num - $leftNum;
            if($start<1) $start=1;
            for ($i = $start; $i < $show+$start; $i++){
                if($i>$last_page) break;
                $arr[]=$i;
            }
            if(count($arr)<$show){
                //尽量补够
                if($arr[0]==1){
                    for ($i = 1; $i <= $show; $i++){
                        if($i>$last_page) break;
                        if(in_array($i,$arr)) continue;
                        $arr[]=$i;
                    }
                    sort($arr);
                }else if($arr[count($arr)-1]==$last_page){
                    for ($i = $last_page-$show+1; $i <= $last_page; $i++){
                        if($i<1) continue;
                        if(in_array($i,$arr)) continue;
                        array_unshift($arr,$i);
                    }
                    sort($arr);
                }
            }
        }
		return $arr;
	}
}
