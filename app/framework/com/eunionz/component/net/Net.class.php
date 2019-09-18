<?php
/**
 * Eunionz PHP Framework Net component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\net;


defined('APP_IN') or exit('Access Denied');


/**
 * 网络
 * 
 * 实现客户端信息读取、http、socket、ft等网络功能
 * 无依赖关系
 * 
 */
class Net extends \com\eunionz\core\Component
{
	// 实例
	static protected $_instance;

	/**
	 * 获取当前连接的客户端ip地址
	 * 
	 * @qrcode
	 * $this->getIP();
	 * @endcode
	 * 
	 * @return	string
	 */
	public function getIP()
	{
		$ip_address = false;
		
		if ($this->server('REMOTE_ADDR') and $this->server('HTTP_CLIENT_IP'))
		{
			$ip_address = $this->server('HTTP_CLIENT_IP');
		}
		elseif ($this->server('REMOTE_ADDR'))
		{
			$ip_address = $this->server('REMOTE_ADDR');
		}
		elseif ($this->server('HTTP_CLIENT_IP'))
		{
			$ip_address = $this->server('HTTP_CLIENT_IP');
		}
		elseif ($this->server('HTTP_X_FORWARDED_FOR'))
		{
			$ip_address = $this->server('HTTP_X_FORWARDED_FOR');
		}
		
		if ($ip_address === false)
			return '0.0.0.0';
		
		if (strstr($ip_address, ','))
		{
			$x = explode(',', $ip_address);
			$ip_address = trim(end($x));
		}
		
		if (!$this->isIP($ip_address))
			return '0.0.0.0';
		
		return $ip_address;
	}

	/**
	 * 通过$_SERVER ['HTTP_USER_AGENT']方式获取客户端信息
	 * 
	 * @qrcode
	 * $this->getClinet()
	 * @endcode
	 * 
	 * @return	string
	 */
	public function getClinet()
	{
		return $this->server('HTTP_USER_AGENT') ? $this->server('HTTP_USER_AGENT') : false;
	}

	/**
	 * 验证一个ip地址是否符合ipv4的规范
	 * 
	 * @qrcode
	 * $this->isIP('192.168.1.1');
	 * //返回 true
	 * $this->isIP('192.168.1.256');
	 * //返回 false
	 * @endcode
	 * 
	 * @param	string IP
	 * @return	bool
	 */
	public function isIP($ip)
	{
		$ip_segments = explode('.', $ip);
		
		// 需要四段参数
		if (count($ip_segments) != 4)
			return false;
		
		// 不能以0开头
		if ($ip_segments[0][0] == '0')
			return false;
		
		// 检查片段
		foreach ( $ip_segments as $segment )
		{
			// IP的部分必须是数字,不能超过3位数或大于255
			if ($segment == '' or preg_match("/[^0-9]/", $segment) or $segment > 255 or strlen($segment) > 3)
				return false;
		}
		
		return true;
	}
}
