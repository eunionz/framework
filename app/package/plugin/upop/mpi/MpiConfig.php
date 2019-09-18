<?php
// cvn2加密 1：加密 0:不加密
const MPI_CVN2_ENC = 0;
// 有效期加密 1:加密 0:不加密
const MPI_DATE_ENC = 0;
// 卡号加密 1：加密 0:不加密
const MPI_PAN_ENC = 0;

// 签名证书路径
const MPI_SIGN_CERT_PATH = 'D:/phpworkspace/mpi/UPOnlineMPIUtilPhp/key/106660149170027.pfx';
// 签名证书密码
const MPI_SIGN_CERT_PWD = '000000';
// 验签证书
const MPI_VERIFY_CERT_PATH = 'D:/phpworkspace/mpi/UPOnlineMPIUtilPhp/key/EBPPTest.cer';

// 验签证书路径
const MPI_VERIFY_CERT_DIR = 'D:/phpworkspace/mpi/UPOnlineMPIUtilPhp/key/';

// 密码加密证书
const MPI_ENCRYPT_CERT_PATH = 'D:/phpworkspace/mpi/UPOnlineMPIUtilPhp/key/UPOPPM3.cer';

// 前台请求地址
const MPI_FRONT_TRANS_URL = 'http://172.17.138.27:10086/gateway/api/frontTransRequest.do';

// 后台请求地址
const MPI_BACK_TRANS_URL = 'http://172.17.138.27:10086/gateway/api/backTransRequest.do';

// 批量交易
const MPI_BATCH_TRANS_URL = 'http://172.17.138.27:10086/gateway/api/batchTransRequest.do';

//批量交易状态查询
const MPI_BATCH_QUERY_URL = 'http://172.17.138.27:10086/gateway/api/batchQueryRequest.do';

//单笔查询请求地址
const MPI_SINGLE_QUERY_URL = 'http://172.17.138.27:10086/gateway/api/singleQueryRequest.do';

//文件传输请求地址
const MPI_FILE_QUERY_URL = 'http://172.17.138.27:10086/gateway/api/fileTransRequest.do';

// 前台通知地址
const MPI_FRONT_NOTIFY_URL = 'http://localhost/test/utf8/response.php';
// 后台通知地址
const MPI_BACK_NOTIFY_URL = 'http://localhost/test/utf8/response.php';

//文件下载目录 
const MPI_FILE_DOWN_PATH = 'd:\\';

//日志 目录 
const MPI_LOG_FILE_PATH = 'D:/phpworkspace/mpi/UPOnlineMPIUtilPhp/logs/';

//日志级别
const MPI_LOG_LEVEL = 'INFO';
	
	
