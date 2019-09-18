<?php
/**
 * EUnionZ PHP Framework weapp Plugin class
 * 微信小程序
 * Created by PhpStorm.
 * User: wangtao  (719863381@qq.com)
 * Date: 17-2-8
 * Time: 上午10:16
 */

namespace package\plugin\weixinweapp;


defined('APP_IN') or exit('Access Denied');

class Weixinweapp extends \com\eunionz\core\Plugin {
	function __construct(){
		
	}
	
	/**
	 * 解密用户信息
	 */
	public function decode_encryptedData($appid=null,$sessionKey=null,$encryptedData=null,$iv=null){
		include_once("wxBizDataCrypt.php");
		ob_clean();
		$pc = new \WXBizDataCrypt($appid, $sessionKey);
		$errCode = $pc->decryptData($encryptedData, $iv, $data );
		
		if ($errCode == 0) {
			$retdata['status']=true;
			$retdata['errcode']=null;
			$retdata['data']=json_decode($data,true);
		} else {
			$retdata['status']=false;
			$retdata['errcode']=$errCode;
			$retdata['data']=null;
		}
		return $retdata;
	}
	
}
