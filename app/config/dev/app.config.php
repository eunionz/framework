<?php
declare(strict_types=1);
///////////////////////////////////////////////////////////////////////////////
///////    Eunionz PHP Framework global core config                    ///////
///////    All copyright at Eunionz.cn                                ///////
///////    Email : master@Eunionz.cn                                  ///////
///////    create at 2015-04-30  上午9:47                               ///////
///////////////////////////////////////////////////////////////////////////////

defined('APP_IN') or exit('Access Denied');
/*
 *
 * 全局应用配置文件
 */
return array(
    //是否开发模式   ，只有开发模式才启用应用跟踪
    'APP_DEVENV' => true,


    //定义请求文件存在但不返回文件内容的文件后缀，小写
    'APP_PHP_EXTENSIONS' => array('php', 'inc', 'shtml'),
    //当前应用默认时区
    'APP_DEFAULT_TIMEZONE' => 'PRC',
    //当前应用默认脚本执行时间
    'APP_DEFAULT_SCRIPT_EXECUTE_TIMEOUT_SECONDS' => 10,
    //当前应用跟踪输出
    'APP_DEV_TRACE_OUTPUT' => true,
    //跨域设置
    'APP_CROSS_DOMAIN_ALLOW' => true,  //是否允许跨域  true--允许  false--禁止
    'APP_CROSS_DOMAIN_ALLOW_ORIGINS' => "*",  //跨域访问时，允许访问的域名，如果允许所有域名则设置为：*，配置格式为：http://www.abcd.com,http://www.abcd1.com
    'APP_CROSS_DOMAIN_ALLOW_METHODS' => "GET, POST, PUT, DELETE, OPTIONS",  //跨域访问时，允许执行的动作
    'APP_CROSS_DOMAIN_ALLOW_HEADERS' => "frontsessionsid,adminsessionsid,clienttype,clientversion,clientplatform,reverseproxyforward,wapdomain,nowxoauth,token,appid,openid,originhost,time",  //跨域访问时，允许访问的头部名称

    //根据请求域名HTTP_HOST设置shop_id的回调方法，格式：字符串全局函数或array("类","方法")
    'APP_SET_SHOP_ID_BY_HTTP_HOST_CALLBACK' => array("cn\\eunionz\\core\\Context", "get_shop_id"),
    //特别的0店铺对应的文件夹名称
    'APP_SHOP_ID_ZERO_FOLDER_NAME' => "service",


    //定义基于KID分库规则，基于KID分库规则将使用db.config.php相同的配置文件使用基于KID分库 INT
    'APP_KID_SPLIT_DATABASE_CONFIG_RULES' => array(
        /**
         *  1、limit array(n,m)
         *     n--描述该分区最多可以承载的B2C站点KID总个数(此处KID总个数不代表子店铺KID个数)，如果为0表示不限制
         *     m--描述该分区最多可以承载的B2B2C平台站点KID总个数(此处KID总个数不代表子店铺KID个数)，如果为0表示不限制
         *  2、range 描述该分区KID范围(包括该分区子店铺KID)
         *      测试/稿件/子店铺KID     110000000000 - 110999999999        12位KID，测试/稿件的B2C/B2B2C 客户KID/平台KID，以及子店铺KID均位于该分区
         *      客户平台/子店铺KID      110000000000 - 199999999999        12位KID，用做测试 B2C/B2B2C 客户KID/平台KID以及子店铺KID
         */
        0 => array('limit' => array(0, 0), 'range' => array(110000000000, 110999999999)),           //表示KID界于    由前三位决定所属数据库分区，例如110，110-110=0，使用db.config.php分区配置，测试及稿件用KID
        1 => array('limit' => array(3000, 150), 'range' => array(111000000000, 111999999999)),      //表示KID界于    由前三位决定所属数据库分区，例如111，111-110=1，使用db1.config.php分区配置
        2 => array('limit' => array(3000, 150), 'range' => array(112000000000, 112999999999)),      //表示KID界于    B2C/B2B2C的平台客户KID与B2B2C子店铺KID必须位于同一分区
    ),
    //定义核心缓存，系统所用一级缓存
    'CORE_CACHE_CONFIG' => array(
        'is_cache' => true,                          //是否启用核心缓存    true  -- 启用   false  -- 启用
        'cache_type' => 'redis',                     //缓存模式: file|redis, file  -- 文件缓存   redis -- REDIS 缓存
        'cache_life_seconds' => 3600,                  //默认缓存过期时间 0--永不过期   单位:秒
        'cache_driver_data' => array(
            'cache_dir' => '%RUNTIME%/core_cache',           //仅file模式有效，相对于 storage 文件夹的路径，如果为%RUNTIME%代表当前站点下的runtime文件夹的物理路径
            'redis_servers' => array(              //仅redis模式有效,必须安装redis扩展，用于配置使用redis做核心缓存时的相关参数
                'isUseCluster' => false,              //是否启用主从配置(主用于读写，从仅读，并随机选择主从进行读)
                'isPersistent' => true,              //是否启用持久链接
                'connect_timeout' => 5,              //链接超时时间，单位：秒
                'dbname' => 1,                         //主从redis服务器选择的数据库编号
                'add_servers' => array(               //配置从redis服务器
                    array(//主(写)服务器
                        'server' => '192.168.1.125',     //从redis服务器地址或域名
                        'port' => '6377',                //从redis服务器端口
                        'password' => 'zFymUyDG',        //从redis密码
                    ),
//                    array(
//                        'server' => '127.0.0.1',         //从redis服务器地址或域名
//                        'port' => '6380',                //从redis服务器端口
//                        'password' => '123456',          //从redis密码
//                    ),
//                    array(
//                        'server' => '127.0.0.1',           //从redis服务器地址或域名
//                        'port' => '6381',                   //从redis服务器端口
//                        'password' => '123456',            //从redis密码
//                    ),
                ),//从服务器配置
            ),
        ),
    ),

    /////////////////////////////////////////////////////////////////
    // 钩子程序
    'APP_HOOKS' => array(
        // 系统开始运行，一般做资源初始化钩子。
        'before_launch' => array(
            // 钩子类, 钩子方法, 参数组
            array('\\package\\hooks\\Log', 'before_launch', array('p1', 'p2')),
        ),
        // 路由解析完成，一般在这里做缓存钩子、重写路由等。
        'override_router' => array(
            array('\\package\\hooks\\Log', 'override_router', array('p1', 'p2')),
        ),
        // 开始解析控制器，一般做权限控制钩子。
        'before_controller' => array(
            array('\\package\\hooks\\Log', 'before_controller', array('p1', 'p2')),
        ),
        // 控制器解析完成，一般做清理局部资源、写日志等。
        'after_controller' => array(
            array('\\package\\hooks\\Log', 'after_controller', array('p1', 'p2')),
        ),
        // 视图输出到浏览器之前，一般做页面缓存、生成静态页面等。
        'override_output' => array(
            array('\\package\\hooks\\Log', 'override_output', array('p1', 'p2')),
        ),
        // 系统执行结束，一般做资源释放钩子。
        'after_launch' => array(
            array('\\package\\hooks\\Log', 'after_launch', array('p1', 'p2')),
        )
    ),

    // 输出页面压缩
    'APP_OUTPUT_COMPRESS' => false,

    //是否所有站点共享静态内容如.css,.js,图片,//是否启用CDN加速站点，如果为空，表示禁用CDN加速站点，否则设置CDN加速站点URL例如http://img.kshopx.dev.cn，不能以/结束，CDN加速站点根目录必须指向/home/www文件夹，并且禁止执行任何php文件
    'APP_STATIC_CONTENT_CDN_SITE_DOMAIN_URLS' => '',

    //默认的主题，可以通过 $_COOKIE['APP_THEME']=default 或 $_SESSION['APP_THEME']=default 或  ?APP_THEME=default来更改当前应用主题
    //优先使用?APP_THEME，其次使用$_COOKIE['APP_THEME']，最后使用$_SESSION['APP_THEME']，主要就是在视图外再套一层文件夹
    'APP_DEFAULT_THEME' => 'default',


    //应用程序分区配置，整个应用程序分为两个区，一个区为admin，其主题theme为admin，其它的将均为默认分区将使用默认主题或修改之后的主题
    //当url中控制器前面部份为admin的所有控制器将隶属于admin分区使用这里设置的应用程序分区主题
    'APP_PARTITIONS' => array(
        'admin' => 'admin',
        'seller' => 'seller',
        'service' => 'pc',
        'home' => 'pc',
        'mobile' => 'wap',
    ),

    //定义应用程序的后台管理分区，后台管理分区可以配置多个，但后台管理分区名称必须在 APP_PARTITIONS 配置中完成定义，
    //后台管理分区视图及静态资源文件不再位于storage/{shop_id}/view下，而是位于package/view下所有站点共用相同的视图及静态资源
    //如果不定义应用程序后台管理分区，则所有视图针对每一个站点均独有，而非共有
    'APP_MANAGE_PARTITIONS' => array('admin'),

    //默认的语言，可以通过 $_COOKIE['APP_LANGUAGE']='en-us' 或 $_SESSION['APP_LANGUAGE']='en-us' 或  ?APP_LANGUAGE=en-us来更改当前应用语言
    //优先使用?APP_LANGUAGE，其次使用$_COOKIE['APP_LANGUAGE']，最后使用$_SESSION['APP_LANGUAGE']，主要用于决定视图或代码中使用的语言资源
    //package/language文件夹下的语言资源文件为通用语言资源文件，与控制器同名扩展名为.en_us.php的资源文件(与控制器文件同层)为控制器的语言资源文件
    'APP_DEFAULT_LANGUAGE' => 'en-us',

    // write log
    // true enable
    // false disable
    'APP_LOG' => true,
    // 日志存放路径
    // 相对于常量 APP_RUNTIME_REAL_PATH 目录之下
    'APP_LOG_DIR' => 'logs',
    // 日志记录级别
    // 0  关闭错误日志记录
    // 1  记录错误信息
    // 2  记录调试信息
    // 3  记录通知信息
    // 4  记录所有信息
    'APP_LOG_LEVEL' => 4,

    //日志文件最大大小,byte
    'APP_LOG_MAXSIZE' => 204800,

    // 默认路由
    'APP_ROUTER_DEFAULT' => 'home',
    // 默认动作
    // 当URI不包含动作时
    'APP_DEFAULT_ACTION' => 'index',

    // 远程服务
    // 此处配置远程服务的WSDL地址
    // 可在控制器中调用此处配置的远程服务
    'APP_SERVICE' => array(),

    //远程服务认证密钥
    'APP_KEY' => 'am45af9F5a7V2s9c1q5j0r2k6y2s94k6',


    // 全局XSS过滤
    'APP_XSS_FILTERING' => true,


    'APP_SESSION_LIFETIME_SECONDS' => 7200,  //会话生命周期，单位：秒
    'APP_SESSION_MODE' => 'redis',  //session，模式：file|redis|sql,使用sql需要将 php.ini 中  session.save_handler = files 修改为 User
    //Redis会话配置，仅在APP_SESSION_MODE = redis 有效
    'APP_SESSION_REDIS_CONFIG' => array(
        'server' => '192.168.1.125',     //从redis服务器地址或域名
        'port' => '6377',                //从redis服务器端口
        'password' => 'zFymUyDG',        //从redis密码
        'dbname' => 0,                     ////redis服务器选择的数据库编号
    ),


    // SESSION 目录  file模式有效
    // 相对于常量 APP_RUNTIME_REAL_PATH 目录之下
    'APP_SESSION_DIR' => 'session',

    // SESSION 主表  sql模式有效
    'APP_SESSION_TABLE_NAME' => 'sessions',//field: session_id varchar(50) primary, expiry int unsigned ,value text
    //sql会话配置，仅在APP_SESSION_MODE = sql 有效
    'APP_SESSION_MYSQL_CONFIG' => array(
        'HOST' => '192.168.1.125',  //mysql服务器地址或域名
        'PORT' => '3306',            //mysql服务器端口
        'USER' => 'root',            //mysql连接用户名
        'PASS' => '123456',          //mysql连接密码
        'NAME' => 'kiddevdb',        //mysql连接数据库
        'CHARSET' => 'utf8mb4',       //mysql连接字符集
    ),
    // SESSION cookie name  APP_SESSION_COOKIE_NAME
    //定义默认SESSION_NAME中传递session_id的HEADER|COOKIE|GET名称，如果对应用分区并未配置指定的SESSION_NAME名称，则使用APP_DEFAULT_SESSION_NAME名称，这个名称需要加入到跨域头部配置中
    //会话ID同时支持优先从header，其次从$_GET，最后从cookie中获取
    'APP_DEFAULT_SESSION_NAME' => 'frontsessionsid',
    //定义应用程序分区对应的SESSION_NAME名称，可针对不同的应用分区定义不同的SESSION_NAME名称，会话ID同时支持优先从header，其次从$_GET，最后从cookie中获取，这个名称需要加入到跨域头部配置中
    'APP_SESSION_NAMES' => array('admin' => 'adminsessionsid', 'seller' => 'sellersessionsid'),
);
