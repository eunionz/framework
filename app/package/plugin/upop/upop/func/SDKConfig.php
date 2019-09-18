<?php
class SDKConfig{

    public static $SDK_CVN2_ENC = 0;
    public static $SDK_DATE_ENC = 0;
    public static $SDK_PAN_ENC = 0;
    public static $SDK_SIGN_CERT_PWD = '';
    public static $SDK_SIGN_CERT_PATH = '';
    public static $SDK_VERIFY_CERT_PATH =  '';
    public static $SDK_ENCRYPT_CERT_PATH = '';
    public static $SDK_VERIFY_CERT_DIR = '';
    public static $SDK_FRONT_TRANS_URL = 'https://gateway.95516.com/gateway/api/frontTransReq.do';
    public static $SDK_BACK_TRANS_URL = 'https://gateway.95516.com/gateway/api/backTransReq.do';
    public static $SDK_BATCH_TRANS_URL = 'https://gateway.95516.com/gateway/api/batchTrans.do';
    public static $SDK_SINGLE_QUERY_URL = 'https://gateway.95516.com/gateway/api/queryTrans.do';
    public static $SDK_FILE_QUERY_URL = 'https://filedownload.95516.com/';
    public static $SDK_Card_Request_Url = 'https://gateway.95516.com/gateway/api/cardTransReq.do';
    public static $SDK_App_Request_Url = 'https://gateway.95516.com/gateway/api/appTransReq.do';
    public static $SDK_FRONT_NOTIFY_URL = '';
    public static $SDK_BACK_NOTIFY_URL = '';
    public static $SDK_FILE_DOWN_PATH = '';
    public static $SDK_LOG_FILE_PATH = '';
    public static $SDK_LOG_LEVEL = 'INFO';
}
?>
