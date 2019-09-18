<?php

!defined("__LOCALE__CODE__") && define("__LOCALE__CODE__", "UTF-8");

header("Content-Type:text/html;charset=" . __LOCALE__CODE__);

if ( !defined("__DEBUG_MODE__") ) {
	
	//调试、测试模式
	 define("__DEBUG_MODE__", true);
	
	//生产模式
	//define("__DEBUG_MODE__", true);
}

//设置错误报告
error_reporting(__DEBUG_MODE__ ? 2047 : 0);

//统一的异常处理
set_exception_handler(
	function($e) {
		
		echo "Uncaught exception: " , $e->getMessage(), "\n<br />";
		echo "Code: " , $e->getCode(), "\n<br />";
		
		if ( __DEBUG_MODE__ ) {
			
			echo "File: " , $e->getFile(), "\n<br />";
			echo "Line: " , $e->getLine(), "\n<br />";
			echo "Trace: " , $e->getTraceAsString(), "\n<br />";
		}
	}
);

//系统配置
//$sysConfig = array();
global $sysConfig;
//商户编号
//$sysConfig["customernumber"] = "10000447996";

//商户密钥
//$sysConfig["keyValue"] = "jj3Q1h0H86FZ7CD46Z5Nr35p67L199WdkgETx85920n128vi2125T9KY2hzv";

//子商户编号：10014282423 、10014282523

//商户编号--微信
//$sysConfig["customernumber"] = "10000449591";

//商户密钥
//$sysConfig["keyValue"] = "png6085B44o8006YDH6gM842Rd0157005Y2M5MDgVAS8D06758v23tqmZS37";
//以下部分请勿修改
//AES密钥
$sysConfig["keyAesValue"] = substr($sysConfig["keyValue"], 0, 16);

//本地编码
$sysConfig["localeCode"] = __LOCALE__CODE__;

//远程编码
$sysConfig["remoteCode"] = "UTF-8";

//请求的服务器
$sysConfig["serverURI"] = "http://o2o.yeepay.com/zgt-api/api";
//子账号注册请求地址
$sysConfig["registerURL"] = "${sysConfig["serverURI"]}/register";
//账号信息修改请求地址
$sysConfig["modifyRequestURL"] = "${sysConfig["serverURI"]}/modifyRequest";
//账号信息修改查询接口地址
$sysConfig["modifyQueryRequestURL"] = "${sysConfig["serverURI"]}/queryModifyRequest";

//交易请求地址
$sysConfig["payURL"] = "${sysConfig["serverURI"]}/pay";
//订单查询请求地址
$sysConfig["queryURL"] = "${sysConfig["serverURI"]}/queryOrder";
//转账请求地址
$sysConfig["transferURL"] = "${sysConfig["serverURI"]}/transfer";
//转账查询请求地址
$sysConfig["transferQueryURL"] = "${sysConfig["serverURI"]}/transferQuery";
//分账请求地址
$sysConfig["divideURL"] = "${sysConfig["serverURI"]}/divide";
//分账查询请求地址
$sysConfig["divideQueryURL"] = "${sysConfig["serverURI"]}/queryDivide";
//分账方资质上传接口地址
$sysConfig["uploadURL"] = "${sysConfig["serverURI"]}/uploadLedgerQualifications";
//订单退款请求地址
$sysConfig["refundURL"] = "${sysConfig["serverURI"]}/refund";
//订单退款查询请求地址
$sysConfig["refundQueryURL"] = "${sysConfig["serverURI"]}/queryRefund";
//担保确认请求地址
$sysConfig["confirmURL"] = "${sysConfig["serverURI"]}/settleConfirm";
//余额查询请求地址
$sysConfig["balanceQueryURL"] = "${sysConfig["serverURI"]}/queryBalance";

//结算结果查询
$sysConfig["settlementQueryURL"] = "${sysConfig["serverURI"]}/querySettlement";
//查询绑卡列表接口请求地址
$sysConfig["bindCardsQueryURL"] = "${sysConfig["serverURI"]}/queryBindCards";
//解绑接口
$sysConfig["unbindCardURL"] = "${sysConfig["serverURI"]}/unbindCard";
//卡BIN查询接口
$sysConfig["cardBinQueryURL"] = "${sysConfig["serverURI"]}/queryCardBin";
//对账文件下载接口
$sysConfig["downloadURL"] = "http://o2o.yeepay.com/zgt/auth/AuthDown/downloadOrderDocument";
//分账方审核结果查询
$sysConfig["checkRecordQueryURL"] = "${sysConfig["serverURI"]}/queryCheckRecord";
$sysConfig["idCardAuthURL"]="${sysConfig["serverURI"]}/idCardAuth";

//以下部分，除非接口参数，或者返回参数改动，否则请勿修改
//$infConfig = array();
global $infConfig;
$infConfig["idCardAuth"]=array();
$infConfig["idCardAuth"]["requestURL"] = $sysConfig["idCardAuthURL"];
$infConfig["idCardAuth"]["needRequestHmac"] = array(0=>"requestid",1=>"ledgerno",2=>"idcard");
$infConfig["idCardAuth"]["mustFillRequest"] = array(0=>"requestid",1=>"ledgerno",2=>"idcard");
$infConfig["idCardAuth"]["needRequest"] = array(0=>"requestid",1=>"ledgerno",2=>"idcard");
$infConfig["idCardAuth"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "ledgerno", 3 => "code");

//分账账号注册接口配置
$infConfig["register"] = array();
$infConfig["register"]["requestURL"] = $sysConfig["registerURL"];
$infConfig["register"]["needRequestHmac"] = array(0 => "requestid", 1 => "bindmobile", 2 => "customertype", 3 => "signedname", 4 => "linkman", 5 => "idcard", 6 => "businesslicence", 7 => "legalperson", 8 => "minsettleamount", 9 => "riskreserveday", 10 => "bankaccountnumber", 11 => "bankname", 12 => "accountname", 13 => "bankaccounttype", 14 => "bankprovince", 15 => "bankcity");
$infConfig["register"]["mustFillRequest"] = array(0 => "requestid", 1 => "bindmobile", 2 => "customertype", 3 => "signedname", 4 => "linkman", 5 => "legalperson", 6 => "minsettleamount", 7 => "riskreserveday", 8 => "bankaccountnumber", 9 => "bankname", 10 => "accountname", 11 => "bankaccounttype", 12 => "bankprovince", 13 => "bankcity");
$infConfig["register"]["needRequest"] = array(0 => "requestid", 1 => "bindmobile", 2 => "customertype", 3 => "signedname", 4 => "linkman", 5 => "idcard", 6 => "businesslicence", 7 => "legalperson", 8 => "minsettleamount", 9 => "riskreserveday", 10 => "bankaccountnumber", 11 => "bankname", 12 => "accountname", 13 => "bankaccounttype", 14 => "bankprovince", 15 => "bankcity",16 => "deposit",17 => "email");
$infConfig["register"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "ledgerno");

//账号信息修改接口配置
$infConfig["modifyRequest"] = array();
$infConfig["modifyRequest"]["requestURL"] = $sysConfig["modifyRequestURL"];
$infConfig["modifyRequest"]["needRequestHmac"] = array(0 => "requestid", 1 => "ledgerno", 2 => "bankaccountnumber", 3 => "bankname", 4 => "accountname", 5 => "bankaccounttype", 6 => "bankprovince", 7 => "bankcity", 8 => "minsettleamount", 9 => "riskreserveday", 10 => "manualsettle", 11 => "callbackurl");
$infConfig["modifyRequest"]["mustFillRequest"] = array(0 => "requestid", 1 => "bankaccountnumber", 2 => "bankname", 3 => "accountname", 4 => "bankaccounttype", 5 => "bankprovince", 6 => "bankcity", 7 => "minsettleamount", 8 => "riskreserveday", 9 => "callbackurl", 10 => "bindmobile");
$infConfig["modifyRequest"]["needRequest"] = array(0 => "requestid", 1 => "ledgerno", 2 => "bankaccountnumber", 3 => "bankname", 4 => "accountname", 5 => "bankaccounttype", 6 => "bankprovince", 7 => "bankcity", 8 => "minsettleamount", 9 => "riskreserveday", 10 => "callbackurl", 11 => "bindmobile");
$infConfig["modifyRequest"]["needResponseHmac"] =  array(0 => "customernumber", 1 => "requestid", 2 => "code");

//账号信息修改查询接口配置
$infConfig["modifyQueryRequest"] = array();
$infConfig["modifyQueryRequest"]["requestURL"] = $sysConfig["modifyQueryRequestURL"];
$infConfig["modifyQueryRequest"]["needRequestHmac"] = array(0 => "requestid");
$infConfig["modifyQueryRequest"]["mustFillRequest"] = array(0 => "requestid");
$infConfig["modifyQueryRequest"]["needRequest"] = array(0 => "requestid");
$infConfig["modifyQueryRequest"]["needResponseHmac"] =  array(0 => "customernumber", 1 => "requestid", 2 => "code",3 => "status", 4 => "desc");

//订单支付接口配置
$infConfig["pay"] = array();
$infConfig["pay"]["requestURL"] = $sysConfig["payURL"];
$infConfig["pay"]["needRequestHmac"] = array(0 => "requestid", 1 => "amount", 2 => "assure", 3 => "productname", 4 => "productcat", 5 => "productdesc", 6 => "divideinfo", 7 => "callbackurl", 8 => "webcallbackurl", 9 => "bankid", 10 => "period", 11 => "memo");
$infConfig["pay"]["mustFillRequest"] = array(0 => "requestid", 1 => "amount", 2 => "callbackurl");
$infConfig["pay"]["mustFillRequest_SALES"] = array(0 => "payproducttype");
$infConfig["pay"]["mustFillRequest_ONEKEY"] = array(0 => "payproducttype");
$infConfig["pay"]["mustFillRequest_WECHATU"] = array(0 => "payproducttype",1 => "ip");
$infConfig["pay"]["mustFillRequest_APP_WX"] = array(0 => "payproducttype",1 => "ip");
$infConfig["pay"]["mustFillRequest_APP_ZFB"] = array(0 => "payproducttype",1=> "ip");
$infConfig["pay"]["needRequest"] = array(0 => "requestid", 1 => "amount", 2 => "assure", 3 => "productname", 4 => "productcat", 5 => "productdesc", 6 => "divideinfo", 7 => "callbackurl", 8 => "webcallbackurl", 9 => "bankid", 10 => "period", 11 => "memo", 12 => "payproducttype", 13 => "userno", 14 => "ip", 15 => "cardname", 16 => "idcard", 17 => "bankcardnum",18=> "mobilephone",19 => "orderexpdate", 20=>"appid",21=>"openid",22=>"directcode");
$infConfig["pay"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "externalid", 4 => "amount", 5 => "payurl");
$infConfig["pay"]["needCallbackHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "notifytype", 4 => "externalid", 5 => "amount", 6 => "cardno");


//订单查询接口配置 
$infConfig["paymentQuery"] = array();
$infConfig["paymentQuery"]["requestURL"] = $sysConfig["queryURL"];
$infConfig["paymentQuery"]["needRequestHmac"] = array(0 => "requestid");
$infConfig["paymentQuery"]["mustFillRequest"] = array(0 => "requestid");
$infConfig["paymentQuery"]["needRequest"] = array(0 => "requestid");
$infConfig["paymentQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "externalid", 4 => "amount", 5 => "productname", 6 => "productcat", 7 => "productdesc", 8 => "status", 9 => "ordertype", 10 => "busitype", 11 => "orderdate", 12 => "createdate", 13 => "bankid");

//转账接口配置
$infConfig["transfer"] = array();
$infConfig["transfer"]["requestURL"] = $sysConfig["transferURL"];
$infConfig["transfer"]["needRequestHmac"] = array(0 => "requestid", 1 => "ledgerno", 2 => "amount");
$infConfig["transfer"]["mustFillRequest"] = array(0 => "requestid", 1 => "amount");
$infConfig["transfer"]["needRequest"] = array(0 => "requestid", 1 => "ledgerno", 2 => "amount");
$infConfig["transfer"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code");

//转账查询接口配置
$infConfig["transferQuery"] = array();
$infConfig["transferQuery"]["requestURL"] = $sysConfig["transferQueryURL"];
$infConfig["transferQuery"]["needRequestHmac"] = array(0 => "requestid");
$infConfig["transferQuery"]["mustFillRequest"] = array(0 => "requestid");
$infConfig["transferQuery"]["needRequest"] = array(0 => "requestid");
$infConfig["transferQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "ledgerno", 4 => "amount", 5 => "status");

//分账接口配置
$infConfig["divide"] = array();
$infConfig["divide"]["requestURL"] = $sysConfig["divideURL"];
$infConfig["divide"]["needRequestHmac"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "divideinfo");
$infConfig["divide"]["mustFillRequest"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "divideinfo");
$infConfig["divide"]["needRequest"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "divideinfo");
$infConfig["divide"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code");

//分账查询接口配置
$infConfig["divideQuery"] = array();
$infConfig["divideQuery"]["requestURL"] = $sysConfig["divideQueryURL"];
$infConfig["divideQuery"]["needRequestHmac"] = array(0 => "orderrequestid", 1 => "dividerequestid", 2 => "ledgerno");
$infConfig["divideQuery"]["mustFillRequest"] = array(0 => "orderrequestid");
$infConfig["divideQuery"]["needRequest"] = array(0 => "orderrequestid", 1 => "dividerequestid", 2 => "ledgerno");
$infConfig["divideQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "orderrequestid", 2 => "code", 3 => "divideinfo");

//分账方资质上传接口配置
$infConfig["upload"] = array();
$infConfig["upload"]["requestURL"] = $sysConfig["uploadURL"];
$infConfig["upload"]["needRequestHmac"] = array(0 => "ledgerno", 1 => "filetype");
$infConfig["upload"]["mustFillRequest"] = array(0 => "ledgerno", 1 => "filetype");
$infConfig["upload"]["needRequest"] = array(0 => "ledgerno", 1 => "filetype");
$infConfig["upload"]["needResponseHmac"] = array(0 => "customernumber", 1 => "ledgerno", 2 => "code", 3 => "filetype");

//退款接口配置
$infConfig["refund"] = array();
$infConfig["refund"]["requestURL"] = $sysConfig["refundURL"];
$infConfig["refund"]["needRequestHmac"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "amount", 3 => "divideinfo", 4 => "confirm", 5 => "memo");
$infConfig["refund"]["mustFillRequest"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "amount", 3 => "confirm");
$infConfig["refund"]["needRequest"] = array(0 => "requestid", 1 => "orderrequestid", 2 => "amount", 3 => "divideinfo", 4 => "confirm", 5 => "memo");
$infConfig["refund"]["needResponseHmac"] = array(0 => "customernumber", 1 => "requestid", 2 => "code", 3 => "refundexternalid");

//退款查询接口配置
$infConfig["refundQuery"] = array();
$infConfig["refundQuery"]["requestURL"] = $sysConfig["refundQueryURL"];
$infConfig["refundQuery"]["needRequestHmac"] = array(0 => "orderrequestid", 1 => "refundrequestid");
$infConfig["refundQuery"]["mustFillRequest"] = array(0 => "orderrequestid");
$infConfig["refundQuery"]["needRequest"] = array(0 => "orderrequestid", 1 => "refundrequestid");
$infConfig["refundQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "orderrequestid", 2 => "code", 3 => "externalid", 4 => "refundinfo");

//担保确认接口配置
$infConfig["confirm"] = array();
$infConfig["confirm"]["requestURL"] = $sysConfig["confirmURL"];
$infConfig["confirm"]["needRequestHmac"] = array(0 => "orderrequestid");
$infConfig["confirm"]["mustFillRequest"] = array(0 => "orderrequestid");
$infConfig["confirm"]["needRequest"] = array(0 => "orderrequestid");
$infConfig["confirm"]["needResponseHmac"] = array(0 => "customernumber", 1 => "orderrequestid", 2 => "code");


//余额查询接口配置
$infConfig["balanceQuery"] = array();
$infConfig["balanceQuery"]["requestURL"] = $sysConfig["balanceQueryURL"];
$infConfig["balanceQuery"]["needRequestHmac"] = array(0 => "ledgerno");
$infConfig["balanceQuery"]["mustFillRequest"] = array();
$infConfig["balanceQuery"]["needRequest"] = array(0 => "ledgerno");
$infConfig["balanceQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "code", 2 => "balance", 3 => "ledgerbalance");


// 结算结果查询接口配置
$infConfig["settlementQuery"] = array();
$infConfig["settlementQuery"]["requestURL"] = $sysConfig["settlementQueryURL"];
$infConfig["settlementQuery"]["needRequestHmac"] = array(0 => "ledgerno", 1 => "date");
$infConfig["settlementQuery"]["mustFillRequest"] = array();
$infConfig["settlementQuery"]["needRequest"] = array(0 => "ledgerno", 1 => "date", 2 => "isdetail");
$infConfig["settlementQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "code", 2 => "info");

// 绑卡列表查询接口配置
$infConfig["bindCardsQuery"] = array();
$infConfig["bindCardsQuery"]["requestURL"] = $sysConfig["bindCardsQueryURL"];
$infConfig["bindCardsQuery"]["needRequestHmac"] = array(0 => "userno");
$infConfig["bindCardsQuery"]["mustFillRequest"] = array(0 => "userno");
$infConfig["bindCardsQuery"]["needRequest"] = array(0 => "userno", 1 => "bindid");
$infConfig["bindCardsQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "userno", 2 => "code");

// 解绑接口配置
$infConfig["unbindCard"] = array();
$infConfig["unbindCard"]["requestURL"] = $sysConfig["unbindCardURL"];
$infConfig["unbindCard"]["needRequestHmac"] = array(0 => "userno", 1 => "bindid");
$infConfig["unbindCard"]["mustFillRequest"] = array(0 => "userno", 1 => "bindid");
$infConfig["unbindCard"]["needRequest"] = array(0 => "userno", 1 => "bindid",2 => "cause");
$infConfig["unbindCard"]["needResponseHmac"] = array(0 => "customernumber", 1 => "userno", 2 => "bindid", 3 =>"code");

// 对账文件下载接口
$infConfig["download"] = array();
$infConfig["download"]["requestURL"] = $sysConfig["downloadURL"];
$infConfig["download"]["needRequestHmac"] = array(0 => "checkDate", 1 => "orderType");
$infConfig["download"]["mustFillRequest"] = array(0 => "checkDate", 1 => "orderType");
$infConfig["download"]["needRequest"] = array(0 => "checkDate", 1 => "orderType");
$infConfig["download"]["needResponseHmac"] = array();

//卡 bin 查询接口配置
$infConfig["cardBinQuery"] = array();
$infConfig["cardBinQuery"]["requestURL"] = $sysConfig["cardBinQueryURL"];
$infConfig["cardBinQuery"]["needRequestHmac"] = array(0 => "bankcardnum");
$infConfig["cardBinQuery"]["mustFillRequest"] = array(0 => "bankcardnum");
$infConfig["cardBinQuery"]["needRequest"] = array(0 => "bankcardnum");
$infConfig["cardBinQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "bankcardnum", 2 => "code", 3 => "bankcode", 4 => "bankname", 5 => "cardname",6 => "cardtype");


// 分账方审核结果查询接口配置
$infConfig["checkRecordQuery"] = array();
$infConfig["checkRecordQuery"]["requestURL"] = $sysConfig["checkRecordQueryURL"];
$infConfig["checkRecordQuery"]["needRequestHmac"] = array(0 => "ledgerno");
$infConfig["checkRecordQuery"]["mustFillRequest"] = array(0 => "ledgerno");
$infConfig["checkRecordQuery"]["needRequest"] = array(0 => "ledgerno");
$infConfig["checkRecordQuery"]["needResponseHmac"] = array(0 => "customernumber", 1 => "ledgerno", 2 => "status", 3 => "checkdate", 4 =>"reason");





//包含系统文件
require_once(__DIR__ . "/toolsFunc.php");
require_once(__DIR__ . "/func.php");
require_once(__DIR__ . "/RequestService.php");

//包含加解密文件
require_once(__DIR__ . "/CryptAES.php");

//包含自定义异常文件
require_once(__DIR__ . "/ZGTException.php");

?>