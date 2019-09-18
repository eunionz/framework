<?php
/**
 * Eunionz PHP Framework RSS component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\rss;


defined('APP_IN') or exit('Access Denied');


/**
 * 用于生成RSS聚合

 */
class RSS extends \cn\eunionz\core\Component
{
    // 实例句柄
    static protected $_instance;
	
    // xml对象
    private static $_xml;
    
    /**
     * 创建一个rss文档声明
     */
    private function _createDomDocment($item)
    {
    	
    	$rss = self::$_xml->createElement('rss');
    	$rss->setAttribute('version', '2.0');
    	
    	$channel = self::$_xml->createElement('channel');
    	$rss->appendChild($channel);
    	
    	$data = $this->config('APP_RSS');
    	foreach($data as $key => $value) {
    		$domkey = self::$_xml->createElement($key);
    		$cdata  = self::$_xml->createCDATASection($value);
    		$domkey->appendChild($cdata);
    		$channel->appendChild($domkey);
    	}
    	
    	if(count($item))
    	{
    		foreach($item as $key => $value) {
    			$channel->appendChild($item[$key]);
    		}
    	}
    	
    	
    	
    	self::$_xml->appendChild($rss);
    }
    
    
    /**
     * 根据数据返回接收
     * 
     * @param array $data 数据
     * 
     */
    private function _setElementItem(array $data)
    {
    	if(count($data)) {
    		foreach($data as $key => $value) {
    			$item[$key] = self::$_xml->createElement('item');
    		
    			$title = self::$_xml->createElement("title");
    			$cdata = self::$_xml->createCDATASection($value['title']);
    			$title->appendChild($cdata);
    			$item[$key]->appendChild($title);
    		
    			$link = self::$_xml->createElement("link");
    			$cdata = self::$_xml->createCDATASection($value['url']);
    			$link->appendChild($cdata);
    			$item[$key]->appendChild($link);
    		
    			$description = self::$_xml->createElement("description");
    			$cdata = self::$_xml->createCDATASection($value['description']);
    			$description->appendChild($cdata);
    			$item[$key]->appendChild($description);
    		
    			$author = self::$_xml->createElement("author");
    			$cdata = self::$_xml->createCDATASection($value['dptname']);
    			$author->appendChild($cdata);
    			$item[$key]->appendChild($author);
    		
    			$pubDate = self::$_xml->createElement("pubDate");
    			$cdata = self::$_xml->createCDATASection(date('D, d M Y H:i:s e',$value['inputtime']));
    			$pubDate->appendChild($cdata);
    			$item[$key]->appendChild($pubDate);
    		}
    		 
    		return $item;
    	}
    }
    
    
    /**
     * 读取rss XML文档
     * 
     * @param array $data 数据
     */
    public function loadRSSXML(array $data)
    {
    	self::$_xml = new \DOMDocument('1.0', 'utf-8');
    	
    	$item = $this->_setElementItem($data);
    	
    	$this->_createDomDocment($item);
    	
    	return self::$_xml->saveXML();
    }
    
    
    /**
     * 清除一些导致错误的html标签
     * 
     * @param string $html
     */
    public function noHTML($string)
    {
    	$string = strip_tags($string);
    	$string = preg_replace ('/\n/is', '', $string);
    	$string = preg_replace ('/ |　/is', '', $string);
    	$string = preg_replace ('/&nbsp;/is', '', $string);
    	$string = preg_replace ('/&[a-z]{0,10};/is', '', $string);
    	
    	return $string;
    }
}
