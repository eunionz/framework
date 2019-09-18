<?php
/**
 * EUnionZ PHP Framework Logistics Plugin class
 * 物流查询插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\page;


defined('APP_IN') or exit('Access Denied');


class Page extends \com\eunionz\core\Plugin
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
    
	
	/**
	 * 分页样
	 * <div class='x-paging'>
            <div id='paging' class='x-inner'><a class='current' href=''>1</a><a href=''>«</a><a href=''>»</a></div>
        </div>
	 * @param unknown_type $recordCount
	 * @param unknown_type $pagesize
	 * @param unknown_type $page
	 * @param unknown_type $page_number
	 */
 	public function css1($recordCount, $pagesize, $page, $page_number,$para=array(),$style='x-inner')    {
 		if($recordCount<=$pagesize) return '';
    	$arr_pages=$this->loadComponent('Paging')->info($recordCount, $pagesize, $page, $page_number);
		$urlpara='?';
 		foreach($para as $k=>$v){
			$urlpara.="$k=$v&";
		}
		$urlpara.="page=";
		//生成代码
		$restr="<div class='x-paging'>";
		$restr.="<div id='paging' class='".$style."'>";
		foreach ($arr_pages['pages'] as $p){
            if($p<=0) continue;
			//判断是否当前页
			if($p==$arr_pages['current']){
				$restr.="<a class='current' href='javascript:void(0)'>".$p."</a>";
			}else{
				$restr.="<a  href='".$urlpara.$p."'>".$p."</a>";
			}
		
		}		
		$restr.="<a href='".$urlpara.$arr_pages['previous']."'>«</a>";
		$restr.="<a href='".$urlpara.$arr_pages['next']."'>»</a>";
		$restr.="</div></div>";
		return $restr;
    }

	
    
/**
	 * 分页样
	 *  <div class="pager tc pb10">
        <a href="">上一页</a> <a href="">下一页</a>
    	</div>
	 * @param unknown_type $recordCount
	 * @param unknown_type $pagesize
	 * @param unknown_type $page
	 * @param unknown_type $page_number
	 */
 	public function cssformobile($recordCount, $pagesize, $page, $page_number,$para=array(),$style='x-inner')    {
    	if($recordCount<=$pagesize) return '';
 		$arr_pages=$this->loadComponent('Paging')->info($recordCount, $pagesize, $page, $page_number);
		$urlpara='?';
 		foreach($para as $k=>$v){
			$urlpara.="$k=$v&";
		}
		$urlpara.="page=";
		//生成代码
		$restr="";
		if($recordCount>$pagesize) {		
			$restr="<div class='pager tc pb10'>";
			//判断是否显示上一页
			if($page>1)
			$restr.="<a href='".$urlpara.$arr_pages['previous']."'>上一页</a>";
			//判断是否现实下一页
			if($page<$arr_pages['last'])
			$restr.="<a href='".$urlpara.$arr_pages['next']."'>下一页</a>";			
			$restr.="</div>";
		}
		
		return $restr;
    }

    /**
     *<div class="pager tc ptb10"><a href="">下一页</a><a >1/20</a><a href="">下一页</a>
     * @param        $recordCount
     * @param        $pagesize
     * @param        $page
     * @param        $page_number
     * @param array  $para
     * @param string $style
     *
     * @return string
     */
    public function cssformobile2($recordCount, $pagesize, $page, $page_number,$para=array(),$style='x-inner')    {
        if($recordCount<=$pagesize) return '';
        $arr_pages=$this->loadComponent('Paging')->info($recordCount, $pagesize, $page, $page_number);
        $urlpara='?';
        foreach($para as $k=>$v){
            $urlpara.="$k=$v&";
        }
        $urlpara.="page=";
        //生成代码
        $restr="";
        if($recordCount>$pagesize) {
            $restr="<div class='pager tc pb10'>";
            //判断是否显示上一页
            if($page>1){
                $restr.="<a href='".$urlpara.$arr_pages['previous']."'>上一页</a>";
            }else{
                $restr.="<a href='javascript:;'>上一页</a>";
            }
            $restr.='<a>'.$page.'/'.$arr_pages['last'].'</a>';
            //判断是否现实下一页
            if($page<$arr_pages['last']){
                $restr.="<a href='".$urlpara.$arr_pages['next']."'>下一页</a>";
            }else{
                $restr.="<a href='javascript:;'>下一页</a>";
            }
            $restr.="</div>";
        }

        return $restr;
    }
    
    /**
     * 分页样
     * <div class='x-paging'>
    <div id='paging' class='x-inner'><a class='current' href=''>1</a><a href=''>«</a><a href=''>»</a></div>
    </div>
     * @param unknown_type $recordCount
     * @param unknown_type $pagesize
     * @param unknown_type $page
     * @param unknown_type $page_number
     */
    public function pager($arr_pages,$para=array(),$style='x-inner')    {
        if($arr_pages['total']<=0) {
            $restr="<div class='x-paging'>";
            $restr.="<div id='paging' class='".$style."'>";
            $restr.="</div></div>";
            return $restr;
        }
        $urlpara='?';
        if($para && is_array($para))
        {
            foreach($para as $k=>$v){
                $urlpara.="$k=$v&";
            }
        }elseif($para && is_string($para)){
            $urlpara.=$para;
            $urlpara .="&";
        }
        $urlpara.="page=";
        //生成代码
        $restr="<div class='x-paging'>";
        $restr.="<div id='paging' class='".$style."'>";
        if($arr_pages['next']<=1) return '';
        foreach ($arr_pages['pages'] as $p){
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr.="<a class='current' href='javascript:void(0)'>".$p."</a>";
            }else{
                $restr.="<a  href='".$urlpara.$p."'>".$p."</a>";
            }

        }
        $restr.="<a href='".$urlpara.$arr_pages['previous']."'>«</a>";
        $restr.="<a href='".$urlpara.$arr_pages['next']."'>»</a>";
        $restr.="</div></div>";
        return $restr;
    }

    /**
     * @param $arr_pages
     * @param array $para
     * @param string $style
     * @return string
     */
    public function pager1($arr_pages,$para=array(),$style='x-inner')    {

        if($arr_pages['total']<=0) {

            $restr="<div class='x-paging'>";
            $restr.="<div id='paging' class='".$style."'>";
            $restr.="</div></div>";
            return $restr;
        }
        if($arr_pages['url']){
            $urlpara = $arr_pages['url'].'?';
        }else{

            $urlpara='?';
        }
        $oStatus = isset($arr_pages['oStatus'])?$arr_pages['oStatus']:'';
        if($para && is_array($para))
        {
            foreach($para as $k=>$v){
                $urlpara.="$k=$v&";
            }
        }elseif($para && is_string($para)){
            $urlpara.=$para;
            $urlpara .="&";
        }
        $urlpara.="page=";
        //生成代码
        $restr="<div id='data-table_paginate' class='dataTables_paginate paging_full_numbers'>";

        if($arr_pages['current'] == 1 ) $disabled ='disabled';else $disabled='';



        if( !isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0)
        {
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0'
          href='".$urlpara."1&oStatus=".$oStatus."' onclick=\"return pjax('".$urlpara."1&oStatus=".$oStatus."');\">首页</a>";

            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0' href='".$urlpara.$arr_pages['previous']."&oStatus=".$oStatus."'  onclick=\"return pjax('".$urlpara.$arr_pages['previous']."&oStatus=".$oStatus."');\">上一页</a>";

        }else{
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0'
          href='javascript:loadList(\"\",\"\",\"\",\"\",\"\",1)'>首页</a>";
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['previous'] . ");'>上一页</a>";

        }

        if($arr_pages['next']<=1)
            return '';
        $restr .="<span>";

        foreach ($arr_pages['pages'] as $p){
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr.="<a  class='paginate_button current' aria-controls='data-table'  aria-controls='data-table' tabindex='0'  href='javascript:void(0)'>".$p."</a>";
            }else{
                if( !isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0   ) {

                    $restr.="<a  class='paginate_button ' aria-controls='data-table' tabindex='0'  href='".$urlpara.$p."&oStatus=".$oStatus."' onclick=\"return pjax('".$urlpara.$p."&oStatus=".$oStatus."');\">".$p."</a>";
                }else{
                    $restr .= "<a  class='paginate_button ' aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>" . $p . "</a>";
                }


            }

        }

       $disable_last =  $arr_pages['current'] == $arr_pages['last'] ? 'disabled' : '';

        if(!isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0){
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='".$urlpara.$arr_pages['next']."&oStatus=".$oStatus."'  onclick=\"return pjax('".$urlpara.$arr_pages['next']."&oStatus=".$oStatus."');\">下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='".$urlpara.$arr_pages['last']."&oStatus=".$oStatus."'  onclick=\"return pjax('".$urlpara.$arr_pages['last']."&oStatus=".$oStatus."');\">末页</a>";
            $restr.="</div>";
        }else{
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['last'] . ");' >末页</a>";
            $restr.="</div>";
        }

        return $restr;
    }









    /**
     * 分页样1
     * @param unknown_type $recordCount
     * @param unknown_type $pagesize
     * @param unknown_type $page
     * @param unknown_type $page_number
     */
    public function pager2($arr_pages,$para=array(),$style='x-inner')    {
        if($arr_pages['total']<=0) {
            return "";
        }


        $restr ="";
        if($arr_pages['current'] <=1){
            $restr .='<a href="javascript:;">&lt;</a>';
        }else{
            $restr .='<a href="javascript:;" onclick="return get_goods_list($(\'#cat_id\').val(),$(\'#keywords\').val(),' . ($arr_pages['current']-1) . ')">&lt;</a>';
        }
        //生成代码
        if($arr_pages['next']<1) return '';

        for($i=0;$i<count($arr_pages['pages']);$i++){
            $p=$arr_pages['pages'][$i];
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr.= '<span>' . $p .'</span>';
            }else{
                $restr .='<a href="javascript:;" onclick="return get_goods_list($(\'#cat_id\').val(),$(\'#keywords\').val(),' . $p . ')">' . $p .'</a>';
            }
        }

        if($arr_pages['current'] >=$arr_pages['last']){
            $restr .='<a href="javascript:;">&gt;</a>';
        }else{
            $restr .='<a href="javascript:;" onclick="return get_goods_list($(\'#cat_id\').val(),$(\'#keywords\').val(),' . ($arr_pages['current']+1) . ')">&gt;</a>';
        }

        return $restr;
    }


    /**
     * 分页样1
     * @param unknown_type $recordCount
     * @param unknown_type $pagesize
     * @param unknown_type $page
     * @param unknown_type $page_number
     */
    public function pager3($arr_pages,$para=array(),$style='x-inner')    {
        if($arr_pages['total']<=0) {
            return "";
        }




        $restr ='';
        //生成代码
        if($arr_pages['next']<1) return '';

        for($i=0;$i<count($arr_pages['pages']);$i++){
            $p=$arr_pages['pages'][$i];
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr .='<a class="paginate_button current disabled" aria-controls="data-table" data-dt-idx="2" tabindex="0" href="javascript:;" onclick="return get_list(' . $p . ')">' . $p .'</a>';
            }else{
                $restr .='<a class="paginate_button current" aria-controls="data-table" data-dt-idx="2" tabindex="0" href="javascript:;" onclick="return get_list(' . $p . ')">' . $p .'</a>';

            }
        }

        return $restr;
    }
    
    /**
     * @param $arr_pages
     * @param array $para
     * @param string $style
     * @return string
     */
    public function pager4($arr_pages,$para=array(),$style='x-inner')    {

        if($arr_pages['total']<=0) {

            $restr="<div class='x-paging'>";
            $restr.="<div id='paging' class='".$style."'>";
            $restr.="</div></div>";
            return $restr;
        }
        if($arr_pages['url']){
            $urlpara=$arr_pages['url'].'?';
        }else{
            $urlpara='?';
        }
        $refunds_status = isset($arr_pages['refunds_status'])?$arr_pages['refunds_status']:'';
        if($para && is_array($para))
        {
            foreach($para as $k=>$v){
                $urlpara.="$k=$v&";
            }
        }elseif($para && is_string($para)){
            $urlpara.=$para;
            $urlpara .="&";
        }
        $urlpara.="page=";
        //生成代码
        $restr = "";
        //当前页
        $restr .='<div class="dataTables_info" id="data-table_info" role="status" aria-live="polite">' .
                    '共'.$arr_pages['total'].'条记录 第'.$arr_pages['start'].'到'.$arr_pages['end'].'条&nbsp;&nbsp; &nbsp;&nbsp; 当前页：' .
                    '<select id="dt_sel_page">';
        for ($p = 1; $p <= $arr_pages['page_total']; $p++){
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr .=   '<option value="'.$p.'" selected >'.$p.'</option>';
            }else{
                $restr .=   '<option value="'.$p.'">'.$p.'</option>';
            }
        }
        $restr .=    '</select>&nbsp;&nbsp; ' .
                '</div>';
        //页面大小
        $page_size_array = array(10,20,30,40,50,100,200);
        array_push($page_size_array,$arr_pages['page_size']);//如果有自定义的页面大小则自动添加进数组并去重且重排序
        $page_size_array = array_flip(array_flip($page_size_array));
        sort($page_size_array);
        $restr .='<div class="dataTables_length" id="data-table_length">' .
                    '<label>　　页大小：' .
                    '<select id="dt_sel_page_size" >';
                    foreach($page_size_array as $vo){
                    	$restr .= '<option '.(($arr_pages['page_size']==$vo)?'selected':'').'>'.$vo.'</option>';
                    }
         $restr .=   '</select></label>' .
                 '</div>'; 
        //分页码
        $restr.="<div id='data-table_paginate' class='dataTables_paginate paging_full_numbers'>";
        if($arr_pages['current'] == 1 ) $disabled ='disabled';else $disabled='';
        if( !isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0){
            $restr.="<a id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table' data-dt-idx='0' tabindex='0' href='".$urlpara."1&refunds_status=".$refunds_status."' onclick=\"return pjax('".$urlpara."1&refunds_status=".$refunds_status."');\">首页</a>";
            $restr.="<a id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table' data-dt-idx='0' tabindex='0' href='".$urlpara.$arr_pages['previous']."' onclick=\"return pjax('".$urlpara.$arr_pages['previous']."');\">上一页</a>";
        }else{
            $restr.="<a id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table' data-dt-idx='0' tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\",1)'>首页</a>";
            $restr.="<a id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table' data-dt-idx='0' tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['previous'] . ");'>上一页</a>";
        }
        if($arr_pages['next']<=0)
            return '';
        $restr .="<span>";
        foreach ($arr_pages['pages'] as $p){
            if($p<=0 && $p != '...') continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr.="<a  class='paginate_button current' aria-controls='data-table'  aria-controls='data-table' tabindex='0'  href='javascript:void(0)'>".$p."</a>";
            }else{
                if (!isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0) {
                    if ($p == '...') {
                        $restr .= "<span>...</span>";
                    } else {
                        $restr .= "<a  class='paginate_button paginate_button" . $p . "' aria-controls='data-table' tabindex='0'  href='" . $urlpara . $p . "'  onclick=\"return pjax('" . $urlpara . $p . "');\">" . $p . "</a>";
                    }
                }else{
                    $restr .= "<a  class='paginate_button paginate_button".$p."' aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>" . $p . "</a>";
                }
            }
        }
       $disable_last =  $arr_pages['current'] == $arr_pages['last'] ? 'disabled' : '';
        if(!isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0){
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='".$urlpara.$arr_pages['next']."'  onclick=\"return pjax('".$urlpara.$arr_pages['next']."');\">下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='".$urlpara.$arr_pages['last']."' onclick=\"return pjax('".$urlpara.$arr_pages['last']."');\">末页</a>";
            $restr.="</div>";
        }else{
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['last'] . ");' >末页</a>";
            $restr.="</div>";
        }
        
        //        
        $restr .= $this->get_js_fun_str();
        
        return $restr;
    }

    /**
     * @param $arr_pages
     * @param array $para
     * @param string $style
     * @return string
     */
    public function pager5($arr_pages,$para=array(),$style='x-inner')    {

        if($arr_pages['total']<=0) {

            $restr="<div class='x-paging'>";
            $restr.="<div id='paging' class='".$style."'>";
            $restr.="</div></div>";
            return $restr;
        }
        if($arr_pages['url']){
            $urlpara = $arr_pages['url'].'&';
        }else{

            $urlpara='?';
        }
//        $oStatus = isset($arr_pages['oStatus'])?$arr_pages['oStatus']:'';
        if($para && is_array($para))
        {
            foreach($para as $k=>$v){
                $urlpara.="$k=$v&";
            }
        }elseif($para && is_string($para)){
            $urlpara.=$para;
            $urlpara .="&";
        }
        $urlpara.="page=";
        //生成代码
        $restr="<div id='data-table_paginate' class='dataTables_paginate paging_full_numbers'>";

        if($arr_pages['current'] == 1 ) $disabled ='disabled';else $disabled='';



        if( !isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0)
        {
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0'
          href='".$urlpara."1' onclick=\"return pjax('".$urlpara."1');\">首页</a>";

            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0' href='".$urlpara.$arr_pages['previous']."'  onclick=\"return pjax('".$urlpara.$arr_pages['previous']."');\">上一页</a>";

        }else{
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0'
          href='javascript:loadList(\"\",\"\",\"\",\"\",\"\",1)'>首页</a>";
            $restr.="<a
           id='data-table_first' class='paginate_button first ".$disabled."' aria-controls='data-table'
                       data-dt-idx='0' tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['previous'] . ");'>上一页</a>";

        }

        if($arr_pages['next']<=1)
            return '';
        $restr .="<span>";

        foreach ($arr_pages['pages'] as $p){
            if($p<=0) continue;
            //判断是否当前页
            if($p==$arr_pages['current']){
                $restr.="<a  class='paginate_button current' aria-controls='data-table'  aria-controls='data-table' tabindex='0'  href='javascript:void(0)'>".$p."</a>";
            }else{
                if( !isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0   ) {

                    $restr.="<a  class='paginate_button ' aria-controls='data-table' tabindex='0'  href='".$urlpara.$p."' onclick=\"return pjax('".$urlpara.$p."');\">".$p."</a>";
                }else{
                    $restr .= "<a  class='paginate_button ' aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>" . $p . "</a>";
                }


            }

        }

        $disable_last =  $arr_pages['current'] == $arr_pages['last'] ? 'disabled' : '';

        if(!isset($arr_pages['recycle_list']) || $arr_pages['recycle_list'] !== 0){
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='".$urlpara.$arr_pages['next']."'  onclick=\"return pjax('".$urlpara.$arr_pages['next']."');\">下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='".$urlpara.$arr_pages['last']."'  onclick=\"return pjax('".$urlpara.$arr_pages['last']."');\">末页</a>";
            $restr.="</div>";
        }else{
            $restr.="</span><a id='data-table_next'  class='paginate_button next ".$disable_last."'  aria-controls='data-table' tabindex='0'  href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $p . ");'>下一页</a>";
            $restr .="<a id='data-table_last' class='paginate_button last ".$disable_last."' aria-controls='data-table' data-dt-idx='7'
                       tabindex='0' href='javascript:loadList(\"\",\"\",\"\",\"\",\"\"," . $arr_pages['last'] . ");' >末页</a>";
            $restr.="</div>";
        }

        return $restr;
    }
    
    
    public function get_js_fun_str(){
        $ret_str = "\n<script>\r\n" . 
                "    $(document).ready(function() {\r\n" . 
                "        //分页函数\r\n" . 
                "        $(document).on('change','#dt_sel_page',function(){\r\n" . 
                "            var select_page = $(this).val();\r\n" . 
                "            if(!select_page){\r\n" . 
                "                select_page = 1;\r\n" . 
                "            }\r\n" . 
                "            //页面地址栏的参数\r\n" . 
                "            var params = decodeURIComponent(window.location.hash);\r\n" .
                "            //判断地址栏中是否有page=字符串\r\n" . 
                "            if(params.indexOf(\"page=\") > 0){\r\n" . 
                "                //正则替换地址栏中的page=1 的值\r\n" . 
                "                var reg = /(page=)\\d{0,10}/;\r\n" . 
                "                var tostr = \"page=\"+select_page;\r\n" . 
                "                params = params.replace(reg,tostr);\r\n" . 
                "            }else{\r\n" . 
                "                //是否有参数\r\n" . 
                "                if(params.indexOf(\"?\")>0){\r\n" .
                "                    params += \"&page=\"+select_page; \r\n" . 
                "                }else{\r\n" . 
                "                    params += \"?page=\"+select_page; \r\n" . 
                "                }\r\n" . 
                "            }\r\n" . 
                "            //跳转页面\r\n" .
                "            var url_arr = params.split('#');".
                "            pjax(url_arr[1] ? url_arr[1] : params);\r\n" .
                "        })\r\n" . 
                "        \r\n" . 
                "        //页面大小函数\r\n" . 
                "        $(document).on('change','#dt_sel_page_size',function(){\r\n" . 
                "            var select_page_size = $(this).val();\r\n" . 
                "            if(!select_page_size){\r\n" . 
                "                select_page_size = 10;\r\n" . 
                "            }\r\n" . 
                "            //页面地址栏的参数\r\n" . 
                "            var params = decodeURIComponent(window.location.hash);\r\n" .
                "            //判断地址栏中是否有page=字符串\r\n" . 
                "            if(params.indexOf(\"page_size=\") > 0){\r\n" . 
                "                //正则替换地址栏中的page=1 的值\r\n" . 
                "                var reg = /(page_size=)\\d{0,10}/;\r\n" .
                "                var tostr = \"page_size=\"+select_page_size;\r\n" .
                "                params = params.replace(reg,tostr);\r\n" .
                "            }else{\r\n" . 
                "                //是否有参数\r\n" . 
                "                if(params.indexOf(\"?\")>0){\r\n" .
                "                    params += \"&page_size=\"+select_page_size; \r\n" . 
                "                }else{\r\n" . 
                "                    params += \"?page_size=\"+select_page_size; \r\n" . 
                "                }\r\n" . 
                "            }\r\n" . 
                "            //跳转页面\r\n" .
                "            var url_arr = params.split('#');".
                "            pjax(url_arr[1] ? url_arr[1] : params);\r\n" .
                "        })\r\n" . 
                "    })\r\n" . 
                "</script>";
    	return $ret_str;
    }
    
}