<?php
/**
 * Eunionz PHP Framework Security component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\security;


defined('APP_IN') or exit('Access Denied');

/**
 * 
 * 安全
 * 
 * 基本数据过滤、加密解密字符串、表单认证。

 */
class Security extends \cn\eunionz\core\Component
{
	// 实例
	static protected $_instance;
	
	// 字符串替换
	private $never_allowed_str = array(
		'document.cookie' => '', 
		'document.write' => '', 
		'.parentNode' => '', 
		'.innerHTML' => '', 
		'window.location' => '', 
		'-moz-binding' => '', 
		'<!--' => '&lt;!--', '-->' => '--&gt;', 
		'<![CDATA[' => '&lt;![CDATA['
	);
	
	// 正则表达式替换
	private $never_allowed_regex = array(
		'javascript\s*:' => '', 
		'expression\s*(\(|&\#40;)' => '', // CSS and IE
		'vbscript\s*:' => '', // IE, surprise!
		'Redirect\s+302' => ''
	);

	/**
	 * 删除不可见字符
	 * 比如空字符,换行,回车,制表符等
	 *
	 * @param   string 要过滤的字符串
	 * @return  string 过滤后的字符串
	 */
	private function _remove_invisible_characters($str)
	{
		static $non_displayables = NULL;
		
		if (!isset($non_displayables))
		{
			$non_displayables = array(
				'/%0[0-8bcef]/', // url encoded 00-08, 11, 12, 14, 15
				'/%1[0-9a-f]/', // url encoded 16-31
				'/[\x00-\x08]/', // 00-08
				'/\x0b/', '/\x0c/', // 11, 12
				'/[\x0e-\x1f]/'
			);// 14-31

		}
		
		do
		{
			$cleaned = $str;
			$str = preg_replace($non_displayables, '', $str);
		}
		while ( $cleaned != $str );
		
		return $str;
	}

	/**
	 * 属性转换
	 *
	 * @param  array
	 * @return string
	 */
	private function _convert_attribute($match)
	{
		return str_replace(
			array('>', '<', '\\'), 
			array('&gt;', '&lt;', '\\\\'), 
			$match[0]
		);
	}

	/**
	 * 缩进单词之间空格
	 *
	 * @param    type
	 * @return   type
	 */
	private function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
	}

	/**
	 * 过滤属性
	 *
	 * @param    string
	 * @return   string
	 */
	private function _filter_attributes($str)
	{
		$out = '';
		
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
		{
			foreach ( $matches[0] as $match )
			{
				$out .= preg_replace('#/\*.*?\*/#s', '', $match);
			}
		}
		
		return $out;
	}

	/**
	 * 删除链接中的JS
	 *
	 * @param    array
	 * @return   string
	 */
	private function _js_link_removal($match)
	{
		$attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace('#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', '', $attributes), $match[0]);
	}

	/**
	 * 删除IMG中的JS
	 *
	 * @param    array
	 * @return   string
	 */
	private function _js_img_removal($match)
	{
		$attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace('#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si', "", $attributes), $match[0]);
	}

	/**
	 * 删除HTML定界符
	 *
	 * @param    array
	 * @return   string
	 */
	private function _sanitize_naughty_html($matches)
	{
		$str = '&lt;' . $matches[1] . $matches[2] . $matches[3];
		$str .= str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
		
		return $str;
	}

	/**
	 * 清除不安全索引字符
	 *
	 * @param    string
	 * @return   string
	 */
	private function _clean_input_keys($str)
	{
		if (!preg_match('/^[a-z0-9:_\/-]+$/i', $str))
			return false;
		
		return $str;
	}

	/**
	 * 加密
	 * 
	 * 字符串混淆方式加密
	 *
	 * @param string
	 * @return string
	 */
	function encode($Param)
	{
		if (empty($Param))
			return null;
		
		if (is_numeric($Param))
			$Param = '_KEY_' . $Param;
		
		$rand_char = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
		
		$Param_temp = NULL;
		
		for ($i = 0; $i < strlen($Param); $i++)
			$Param_temp .= $Param[$i] . $rand_char[array_rand($rand_char)];
		
		return base64_encode($Param_temp);
	}

	/**
	 * 解密
	 * 
	 * encode加密的逆转
	 *
	 * @param string
	 * @return string
	 */
	function decode($Param)
	{
		if (empty($Param))
			return null;
		
		$Param = base64_decode($Param);
		
		$Param_temp = NULL;
		
		for ($i = 0; $i < strlen($Param); $i++)
			$Param_temp .= $i % 2 ? '' : $Param[$i];
		
		$Param_temp = str_replace('_KEY_', '', $Param_temp);
		
		return $Param_temp;
	}

	/**
	 * 创建令牌
	 * 
	 * 创建一个访问令牌码
	 *
	 * @param int 有效期(秒)
	 * @return string 认证码
	 */
	function createKey($seconds = 300)
	{
		settype($seconds, 'integer');
		
		// 产生服务器代码
		$date = date('H:i:s:m:d:Y');
		$md5 = md5(rand());
		$this->setSS($md5, $date);
		
		// 产生代码
		return $this->encode($md5 . $date . $seconds);
	}

	/**
	 * 检验令牌
	 * 
	 * 检验令牌是否有效
	 *
	 * @param string 令牌码
	 * @return bool
	 */
	function verifyKey($code)
	{
		if (!preg_match('/^[a-zA-Z0-9\=]{144}$/', $code))
		{
			return false;
		}
		
		$code = $this->decode($code);
		$md5 = substr($code, 0, 32);
		$date = substr($code, 32, 19);
		$seconds = (int)substr($code, 51);
		
		if ($this->getSS($md5) != $date)
		{
			$this->cleanSS($md5);
			return false;
		}
		
		$date = explode(':', $date);
		
		$startdate = mktime($date[0], $date[1], $date[2], $date[3], $date[4], $date[5]);
		$enddate = mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y'));
		
		if (round(($enddate - $startdate)) < $seconds)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * XSS 过滤
	 * 
	 * 过滤跨站脚本
	 *
	 * @param  mixed 要过滤的内容 [string|array]
	 * @return mixed 过滤后的内容 [string|array]
	 */
	public function clean($str)
	{
		// 如果是数组则递归
		if (is_array($str))
		{
			foreach ( $str as $key => $val )
			{
				if (false === $this->_clean_input_keys($key))
					continue;
				
				$str[$key] = $this->clean($str[$key]);
			}
			
			return $str;
		}
		
		// 魔法转义
		if (get_magic_quotes_gpc())
			$str = stripslashes($str);
		
		// 替换换行符
		if (strpos($str, "\r") !== false)
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		
		// 删除不可见字符
		$str = $this->_remove_invisible_characters($str);
		
		// 保护URL中的GET变量
		$str = preg_replace('|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', "\\1=\\2", $str);
		
		// 单字节添加分号
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
		
		// 双字节添加分号
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);
		
		// URL解码
		$str = rawurldecode($str);
		
		// 特殊字符转换为ASCII字符
		$str = preg_replace_callback('/[a-z]+=([\'\"]).*?\\1/si', array($this, '_convert_attribute'), $str);
		
		// 删除不可见字符
		$str = $this->_remove_invisible_characters($str);
		
		// 将所有制表符转换为空格
		if (strpos($str, "\t") !== false)
			$str = str_replace("\t", ' ', $str);
		
		// 替换危险字符
		foreach ( $this->never_allowed_str as $key => $val )
		{
			$str = str_replace($key, $val, $str);
		}
		
		// 替换危险字符(正则)
		foreach ( $this->never_allowed_regex as $key => $val )
		{
			$str = preg_replace("#" . $key . "#i", $val, $str);
		}
		
		// 过滤PHP标签
		$str = str_replace(array('<?', '?' . '>'), array('&lt;?', '?&gt;'), $str);
		
		// 缩进危险字符(j a v a s c r i p t)
		$words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
		
		foreach ( $words as $word )
		{
			$temp = '';
			
			for ($i = 0; $i < count($words); $i++)
			{
				$temp .= substr($word, $i, 1) . '\s*';
			}
			
			$str = preg_replace_callback('#(' . substr($temp, 0, -3) . ')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}
		
		// 删除链接和IMG标签里的JS
		do
		{
			$original = $str;
			
			if (preg_match("/<a/i", $str))
			{
				$str = preg_replace_callback('#<a\s+([^>]*?)(>|$)#si', array($this, '_js_link_removal'), $str);
			}
			
			if (preg_match("/<img/i", $str))
			{
				$str = preg_replace_callback('#<img\s+([^>]*?)(\s?/?>|$)#si', array($this, '_js_img_removal'), $str);

			}
			
			if (preg_match("/script/i", $str) or preg_match("/xss/i", $str))
			{
				$str = preg_replace('#<(/*)(script|xss)(.*?)\>#si', '', $str);
			}
		}
		while ( $original != $str );
		unset($original);
		
		// 删除的JavaScript事件处理程序
		$event_handlers = array('[^a-z_\-]on\w*', 'xmlns');
		
		$str = preg_replace("#<([^><]+?)(" . implode('|', $event_handlers) . ')(\s*=\s*[^><]*)([><]*)#i', "<\\1\\4", $str);
		
		// 删除JS特殊字符
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([>]*)#is', array($this, '_sanitize_naughty_html'), $str);
		
		// 删除evel的可执行代码
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);
		
		// 替换危险字符
		foreach ( $this->never_allowed_str as $key => $val )
		{
			$str = str_replace($key, $val, $str);
		}
		
		// 替换危险字符(正则)
		foreach ( $this->never_allowed_regex as $key => $val )
		{
			$str = preg_replace("#" . $key . "#i", $val, $str);
		}
		
		return $str;
	}
}

