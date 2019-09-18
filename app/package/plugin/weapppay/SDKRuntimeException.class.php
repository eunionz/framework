<?php
defined('APP_IN') or exit('Access Denied');
class  SDKRuntimeException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}

}

?>