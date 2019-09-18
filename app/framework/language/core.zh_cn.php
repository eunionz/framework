<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework language resource for zh-cn
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */
defined('APP_IN') or exit('Access Denied');
return array(
    'error_server_config' => '服务器配置错误。',
    'error_main_server_config' => '主服务器配置错误。',
    'error_main_server_create_failure' => '主服务器创建失败。',
    'view_cache_filename_mode_title'=>'视图缓存错误',
    'view_cache_filename_mode'=>'视图缓存文件名仅支持 md5 模式。',
    'access_denied' => '访问被拒绝',
    'error_controller_not_found'=>'控制器不存在',
    'error_not_found_theme_title'=>'主题错误',
    'error_not_found_theme'=>'未找到相应的主题 {0}',
    'error_exception_title'=>'错误标题：',
    'error_exception_message'=>'错误消息：',
    'error_exception_code'=>'错误代码：',
    'error_exception_file'=>'错误文件：',
    'error_exception_line'=>'错误行号：',
    'error_exception_trace'=>'跟踪信息：',
    'error_config_file_not_found'=>'配置文件 {0}.config.php 未找到。 ',
    'error_session_init_failed_title'=>'会话错误',
    'error_session_init_failed'=>'会话初始化失败',
    'error_hook_method_not_found_title'=>'钩子方法错误',
    'error_hook_method_not_found'=>'钩子类 {0} 成员方法 {1} 不存在。',
    'error_log_write_file_title'=>'日志错误',
    'error_log_write_file'=>'不能写日志文件。',
    'error_model_title'=>'模型错误',
    'error_model_validate_title'=>'模型验证错误',
    'error_model_validate'=>'模型验证错误，{0}。',
    'error_model_field_not_exist'=>'字段 {0} 不存在。',
    'error_model_table_not_exist'=>'表 {0} 不存在。',
    'error_model_table_lack'=>'表参数不足。',
    'error_model_join'=>'join 语句错误。',
    'error_model_must_array'=>'必须为数组，语句错误。',
    'error_model_table_field_not_exist'=>'{1}表中{0}字段不存在。',
    'error_model_parse'=>'分析字段错误！',
    'error_model_between'=>'{0} 是Between类型字段，值必须为2元素数组！',
    'error_model_orderby'=>'排序值必须为 ASC 或者 DESC。',

    'error_model_bind_table'=>'模型没有绑定到数据表。',
    'error_model_execute_jointables'=>'执行 exec_has_result 方法失败,joinTables 参数不能为空。',
    'error_model_execute_jointables_not_table'=>'执行 exec_has_result 方法失败，joinTables 参数中没有指定主表。',
    'error_model_execute_limit'=>'执行 exec_has_result 方法失败，limit 参数格式错误。',
    'error_output_title'=>'视图输出错误',
    'error_output_status'=>'状态码必须为数字。',
    'error_output_status_text'=>'没有状态码文本可用。',
    'error_router_title'=>'路由错误',
    'error_router_controller'=>'加载控制器 {0} 失败。',
    'error_router_controller_method'=>'控制器 {0} 丢失 action 方法。',
    'error_router_controller_miss_method'=>'控制器 {0} 丢失 {1} action 方法。',

    'error_view_title'=>'视图错误',
    'error_view_file_not_found'=>'视图文件 {0} 不存在。',

    'error_io_title'=>'输入输出错误',
    'error_io_create_dir'=>'不能创建文件夹：{0}。',

    'error_db_title'=>'数据库错误',
    'error_db_server_list'=>'数据库服务器列表不能为空。',
    'error_db_connection'=>'数据库连接不可用。',
    'error_db_pdo'=>'PDO错误：{0}。',
    'error_db_pdo_table'=>'getFields方法需要表。',
    'error_db_pdo_table_primary'=>'表 {0} 丢失了主键。',
    'error_db_pdo_cache_config'=>'没有配置缓存。',

    'error_image_title'=>'图片错误',
    'error_image_read'=>'不能读取源文件 [{0}] 。',
    'error_image_gd'=>'图像处理函数无法工作请检查GD库。',
    'error_image_load'=>'没有加载图像。',

    'error_upload_title'=>'上传错误',
    'error_upload_filename'=>'文件名无效 [{0}] 。',
    'error_upload_files'=>'$_FILES 没有数据。',
    'error_upload_save'=>'不允许上传 [{0}] 格式文件。',
    'error_upload_extension'=>'不允许上传 [{0}] 这种类型的文件！',
    'error_upload_filesize'=>'文件大小不允许超过 {0} KB。',
    'error_upload_mode'=>'不正确的上传方式。',
    'error_upload_mkdir'=>'目录 {0} 不存在且无法创建。',
    'error_upload_dir_write'=>'目录 {0} 无法写入。',
    'error_upload_1_code'=>'上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。',
    'error_upload_2_code'=>'上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。',
    'error_upload_3_code'=>'文件只有部分被上传。',
    'error_upload_4_code'=>'没有文件被上传。',
    'error_upload_6_code'=>'找不到临时文件夹。',
    'error_upload_7_code'=>'文件写入失败。',
    'error_upload_0_code'=>'未知上传错误。',

    'error_validation_title'=>'验证错误',
    'error_validation_exception'=>'验证错误：{0} 。',

    'error_webservice_title'=>'Web服务错误',
    'error_webservice_operation'=>'在 {0} 类中没有找到服务接口。',
    'error_webservice_soap'=>'服务器环境不支持 SOAP 。',

    'error_cache_CORE_CACHE_CONFIG'=>'请在/framework/cn/global/constants.core.php文件中对CORE_CACHE_CONFIG常量进行配置。',
    'error_cache_cache_type'=>'不支持的核心缓存类型【{0}】',

    'error_main_redis_connect'=>'主Redis服务器【{0}:{1}】连接失败。',
    'error_main_redis_auth'=>'主Redis服务器【{0}:{1}】授权密码错误。',


    'explore_version_lt_ie_8_error'=>'您的浏览器版本过低，请访问 www.microsoft.com/download 站点升级您的浏览器',

    'app_404_error_content' => '应用程序 404 错误',
    'app_404_error_title' => '应用程序 404 错误',

    'app_500_error_content' => '500 内部服务器错误',
    'app_500_error_title' => '500 内部服务器错误',

    'app_output_trace_close_btn_txt' => '关闭',
    'app_output_trace_display_btn_txt' => '显示',
    'app_output_trace_hidden_btn_txt' => '隐藏',
    'app_output_trace_var_name' => '变量名',
    'app_output_trace_var_value' => '变量值',

    'error_class_not_found' => '类 {0} 没有找到。',
    'error_class_method_not_found' => '类 {0} 丢失 {1} 方法。',

    'error_rpc_class_not_found' => 'RPC 类 {0} 没有找到。',
    'error_rpc_class_method_not_found' => 'RPC 类 {0} 丢失 {1} 方法。',
    'error_rpc_client_connect_fail' => '连接 RPC服务器 {0} 失败。',
    'error_rpc_server_no_response' => 'RPC服务器 {0} 类 {1} 方法 {1} 无响应。',
    'error_rpc_service_no_response' => 'RPC服务 {1} 方法 {2} 无响应。',

    'error_websocket_class_not_found' => 'WebSocket 类 {0} 没有找到。',
    'error_websocket_class_method_not_found' => 'WebSocket 类 {0} 丢失 {1} 方法。',
    'error_websocket_client_connect_fail' => '连接 WebSocket服务器 {0} 失败。',
    'error_websocket_server_no_response' => 'WebSocket服务器 {0} 类 {1} 方法 {1} 无响应。',
    'error_websocket_service_no_response' => 'WebSocket服务 {1} 方法 {2} 无响应。',

    'error_session_redis_connect_fail' => '会话(Redis:{0}) 连接失败。会话开始失败。',
    'error_session_redis_auth_fail' => '会话(Redis:{0}) 授权失败。会话开始失败。',
    'error_session_redis_selectdb_fail' => '会话(Redis:{0}) 选择数据库 {1} 失败。会话开始失败。',
    'error_session_mysql_connect_fail' => '会话(Mysql:{0}) 连接失败。会话开始失败。',
    'error_session_file_init_fail' => '会话(file) 初始化失败, 常量 APP_RUNTIME_REAL_PATH 未定义。会话开始失败。',

);