<?php
/**
 * Eunionz PHP Framework Utility component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\utility;


defined('APP_IN') or exit('Access Denied');
/**
 * 杂项
 * 
 *
 */

class Utility extends \cn\eunionz\core\Component
{
	// 实例
	static protected $_instance;
	

	/**
	 * 计算两点之间距离
	 * 
	 * @param double $lon1 原点经度
	 * @param double $lat1 原点纬度
	 * @param double $lon2 目标点经度
     * @param double $lat2 目标点纬度
     * @param double $unit 单位
	 * 
	 * @return double 单位为公里的距离
	 */
	function calculateDistance($lon1, $lat1, $lon2, $lat2,$unit)
	{ 
		$theta = $lon1 - $lon2; 
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
		$dist = acos($dist); 
		$dist = rad2deg($dist); 
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		return ($miles * 1.609344);
	}
	
	/**
	 * 根据原点求目标点
	 * 
	 * @param double $lon1 原点经度
	 * @param double $lat1 原点纬度
	 * @param float $distance 距离（公里）
	 * @param int $bearing 方向角度（0-360）
	 * 
	 * @return array 经度/纬度
	 */
	public function calculateDerivedPosition($lon1, $lat1, $linDistance, $bearing)
	{
		$lon1 = deg2rad($lon1); 
		$lat1 = deg2rad($lat1);
		
        $distance = $linDistance / 6371;
        $bearing = deg2rad($bearing);
		
        $lat2 = asin(sin($lat1) * cos($distance) + cos($lat1) * sin($distance) * cos($bearing) );
		
        $lat = asin(sin($lat1) * cos($linDistance/6371) + cos($lat1) * sin($linDistance/6371) * cos($bearing) );
        $lon = $lon1 + atan2( (sin($bearing) * sin($linDistance/6371) * cos($lat1) ), (cos($linDistance/6371) - sin($lat1) * sin($lat2)));

        return array(rad2deg($lon), rad2deg($lat));
	}
}
