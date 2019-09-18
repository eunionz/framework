<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework language resource for en-us
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */
defined('APP_IN') or exit('Access Denied');
return array(
    'error_server_config' => 'Server config error.',
    'error_main_server_config' => 'Main server config error.',
    'error_main_server_create_failure' => 'Main server create failure.',
    'view_cache_filename_mode_title'=>'View Cache Error',
    'view_cache_filename_mode'=>'view cache filename only support md5 mode.',
    'access_denied' => 'Access Denied',
    'error_controller_not_found'=>'Controller Not Found',
    'error_not_found_theme_title'=>'TheMe Error',
    'error_not_found_theme'=>'Not Found {0} TheMe',
    'error_exception_title'=>'Exception Title: ',
    'error_exception_message'=>'Exception Message: ',
    'error_exception_code'=>'Exception Code: ',
    'error_exception_file'=>'Exception File: ',
    'error_exception_line'=>'Exception Line: ',
    'error_exception_trace'=>'Exception Trace: ',
    'error_config_file_not_found'=>'Can not find config file {0}.config.php. ',
    'error_session_init_failed_title'=>'Session Error',
    'error_session_init_failed'=>'Session initialize failed',
    'error_hook_method_not_found_title'=>'Hook Method Error',
    'error_hook_method_not_found'=>'Hook class {0} member method {1} not exist.',
    'error_log_write_file_title'=>'Log Error',
    'error_log_write_file'=>'Cannot be written to the log file.',
    'error_model_title'=>'Model Error',
    'error_model_validate_title'=>'Model Validate Error',
    'error_model_validate'=>'Model validate error，{0}.',
    'error_model_field_not_exist'=>'Field {0} does not exist.',
    'error_model_table_not_exist'=>'Table {0} does not exist.',
    'error_model_table_lack'=>'Lack of parameter table.',
    'error_model_join'=>'join statement error.',
    'error_model_must_array'=>'must be array, statement error.',
    'error_model_table_field_not_exist'=>'Field {0} does not exist in table {1}.',
    'error_model_parse'=>'Parse field error!',
    'error_model_between'=>'{0} is between type, the value must be an array of length 2!',
    'error_model_orderby'=>'Order value must be ASC or DESC',

    'error_model_bind_table'=>'Model are not binding table.',
    'error_model_execute_jointables'=>'Execute exec_has_result method fail,joinTables params is null.',
    'error_model_execute_jointables_not_table'=>'Execute exec_has_result method fail,joinTables params has not main table.',
    'error_model_execute_limit'=>'Execute exec_has_result method fail,limit params is error.',
    'error_output_title'=>'Output Error',
    'error_output_status'=>'Status codes must be numeric.',
    'error_output_status_text'=>'No status text available.  Please check your status qrcode number or supply your own message text.',
    'error_router_title'=>'Router Error',
    'error_router_controller'=>'Load controller {0} failure.',
    'error_router_controller_method'=>'Controller {0} missing action method.',
    'error_router_controller_miss_method'=>'Controller {0} missing {1} action method.',

    'error_view_title'=>'View Error',
    'error_view_file_not_found'=>'View file {0} does not exist.',

    'error_io_title'=>'IO Error',
    'error_io_create_dir'=>'Can\'t not create directory : {0}.',

    'error_db_title'=>'DB Error',
    'error_db_server_list'=>'DB Server list can not be empty.',
    'error_db_connection'=>'DB connection is not available.',
    'error_db_pdo'=>'PDO Exception: {0}.',
    'error_db_pdo_table'=>'getFields method need a table.',
    'error_db_pdo_table_primary'=>'Table {0} is missing primary key.',
    'error_db_pdo_cache_config'=>'not set cache config.',

    'error_image_title'=>'Image Error',
    'error_image_read'=>'Cannot read source file [{0}].',
    'error_image_gd'=>'Image handle function cannot work,please check gd libaray.',
    'error_image_load'=>'Not load image.',

    'error_upload_title'=>'Upload Error',
    'error_upload_filename'=>'filename  [{0}]  invalid.',
    'error_upload_files'=>'$_FILES has not data.',
    'error_upload_save'=>'File save error.',
    'error_upload_extension'=>'Not allow upload [{0}] file.',
    'error_upload_filesize'=>'file size must <= {0} KB.',
    'error_upload_mode'=>'file upload mode invalid.',
    'error_upload_mkdir'=>'Dir {0} not exists and cannot create.',
    'error_upload_dir_write'=>'Dir {0} cannot write.',
    'error_upload_1_code'=>'upload file size > php.ini in upload_max_filesize.',
    'error_upload_2_code'=>'upload file size > HTML Form in MAX_FILE_SIZE.',
    'error_upload_3_code'=>'file only part upload.',
    'error_upload_4_code'=>'has not file upload.',
    'error_upload_6_code'=>'not find temp dir.',
    'error_upload_7_code'=>'file write fialure.',
    'error_upload_0_code'=>'unkown upload error.',

    'error_validation_title'=>'Validation Error',
    'error_validation_exception'=>'Validation Error: {0} .',

    'error_webservice_title'=>'WebService Error',
    'error_webservice_operation'=>'Did not find a service interface in class {0}.',
    'error_webservice_soap'=>'server environment not support SOAP.',

    'error_cache_CORE_CACHE_CONFIG'=>'Please set CORE_CACHE_CONFIG constrant in /framework/com/global/constants.core.php file.',
    'error_cache_cache_type'=>'not support core cache type[{0}].',

    'error_main_redis_connect'=>'Master redis server[{0}:{1}] connect fial.',
    'error_main_redis_auth'=>'Master redis server[{0}:{1}] auth fial.',


    'explore_version_lt_ie_8_error'=>'Your browser is out of date, please update your browser by going to www.microsoft.com/download',

    'app_404_error_content' => 'Application 404 error',
    'app_404_error_title' => 'Application 404 error',

    'app_500_error_content' => '500 internal server error',
    'app_500_error_title' => '500 internal server error',

    'app_output_trace_close_btn_txt' => 'Close',
    'app_output_trace_display_btn_txt' => 'Display',
    'app_output_trace_hidden_btn_txt' => 'Hidden',
    'app_output_trace_var_name' => 'var name',
    'app_output_trace_var_value' => 'value',

    'error_class_not_found' => 'Class {0} not found .',
    'error_class_method_not_found' => 'Class {0} missing {1} method .',


    'error_rpc_class_not_found' => 'RPC Class {0} not found .',
    'error_rpc_class_method_not_found' => 'RPC Class {0} missing {1} method .',
    'error_rpc_client_connect_fail' => 'Connect RPC Server {0} fail .',
    'error_rpc_server_no_response' => 'RPC Server {0} class {1} method {2} no response .',
    'error_rpc_service_no_response' => 'RPC Service {1} method {2} no response .',

    'error_websocket_class_not_found' => 'WebSocket Class {0} not found .',
    'error_websocket_class_method_not_found' => 'WebSocket Class {0} missing {1} method .',
    'error_websocket_client_connect_fail' => 'Connect WebSocket Server {0} fail .',
    'error_websocket_server_no_response' => 'WebSocket Server {0} class {1} method {2} no response .',
    'error_websocket_service_no_response' => 'WebSocket Service {1} method {2} no response .',


    'error_session_redis_connect_fail' => 'Session(redis:{0}) connect fail. Sesison start fail.',
    'error_session_redis_auth_fail' => 'Session(redis:{0}) auth fail. Sesison start fail.',
    'error_session_redis_selectdb_fail' => 'Session(redis:{0}) select db {1} fail. Sesison start fail.',
    'error_session_mysql_connect_fail' => 'Session(mysql:{0}) connect fail. Sesison start fail.',
    'error_session_file_init_fail' => 'Session(file) init fail,  constant APP_RUNTIME_REAL_PATH undefined. Sesison start fail.',

);