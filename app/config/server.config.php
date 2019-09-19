<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 上午10:46
 */
defined('APP_IN') or exit('Access Denied');

/*
 *
 * Web 服务器配置文件
 */
return array(

    /**
     * 临听服务器配置列表
     */
    'server_cfgs' => array(
        //主服务器配置，主服务器key必须为main，且主服务器类型server_type必须为http或https
        'main' => array(
            /**
             * 微服务配置，如果不为空将向服务注册中心注册此微服务
             */
            'microservice' => [
                'enable' => false,                        //启用微服务
                'service_id' => 'ms_usercenter_1',       //微服务ID
                'service_name' => 'ms_usercenter',       //微服务名称
                'service_address' => '192.168.1.135',    //微服务IP地址
                'service_port' => 80,                    //微服务端口
                'service_tags' => ['http'],              //微服务标签
                'service_metas' => ['version' => "1.0"],      //微服务元数据
                'service_health_check' => [              //微服务心跳检查配置
                    'id' => 'ms_usercenter_1_check',
                    'name' => '"HTTP API ON PORT 81',
                    'http' => 'http://192.168.1.135:81/health.shtml',
                    'Interval' => '10s',
                    'timeout' => '1s',
                ],
                'service_weights' => [                  //微服务权重配置
                    "Passing" => 10,
                    "Warning" => 1,
                ],
            ],

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_main_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_main_worker_process',

            'server_type' => 'http',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,
            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 80,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\HttpServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\HttpServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\HttpServer', 'onWorkerStart'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\HttpServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\HttpServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\HttpServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\HttpServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\HttpServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\HttpServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\HttpServer', 'onFinish'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\HttpServer', 'onHandShake'),


            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\HttpServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\HttpServer', 'onPacket'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\HttpServer', 'onEnd'),
            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
                 */
                'open_http_protocol' => true,

                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => true,

                /**
                 * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
                 * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
                 */
                'open_websocket_protocol' => true,
                'daemonize' => 0,

            ),
        ),
        /**
         * https服务器配置
         */
        'https' => array(
            /**
             * 微服务配置，如果不为空将向服务注册中心注册此微服务
             */
            'microservice' => [
                'enable' => false,                        //启用微服务
                'service_id' => 'ms_usercenter_https_1',       //微服务ID
                'service_name' => 'ms_usercenter',       //微服务名称
                'service_address' => '192.168.1.135',    //微服务IP地址
                'service_port' => 443,                    //微服务端口
                'service_tags' => ['https'],              //微服务标签
                'service_metas' => ['version' => "1.0"],      //微服务元数据
                'service_health_check' => [              //微服务心跳检查配置
                    'id' => 'ms_usercenter_https_1_check',
                    'name' => '"HTTP API ON PORT 8443',
                    'http' => 'https://192.168.1.135/health.shtml',
                    'Interval' => '10s',
                    'timeout' => '1s',
                ],
                'service_weights' => [                  //微服务权重配置
                    "Passing" => 10,
                    "Warning" => 1,
                ],
            ],

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_https_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_https_worker_process',

            'server_type' => 'https',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,

            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 443,

            /**
             * 是否支持 https 协议
             */
            'is_https' => true,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP | SWOOLE_SSL,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\HttpServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\HttpServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\HttpServer', 'onWorkerStart'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\HttpServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\HttpServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\HttpServer', 'onClose'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
//            'on_receive' => array('\\package\\application\\HttpServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\HttpServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\HttpServer', 'onFinish'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\HttpServer', 'onEnd'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\HttpServer', 'onHandShake'),

            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
//            'on_message' => array('\\package\\application\\HttpServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
//            'on_packet' => array('\\package\\application\\HttpServer', 'onPacket'),

            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
                 */
                'open_http_protocol' => true,

                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => true,

                /**
                 * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
                 * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
                 */
                'open_websocket_protocol' => false,

                /**
                 * 同上
                 */
                'ssl_cert_file' => APP_REAL_PATH . 'ssl/server.crt',

                /**
                 * 同上
                 */
                'ssl_key_file' => APP_REAL_PATH . 'ssl/server.key',
            ),
        ),
        /**
         * rpc服务器配置
         */
        'rpc' => array(

            /**
             * 微服务配置，如果不为空将向服务注册中心注册此微服务
             */
            'microservice' => [
                'enable' => false,                        //启用微服务
                'service_id' => 'ms_usercenter_rpc_1',       //微服务ID
                'service_name' => 'ms_usercenter',       //微服务名称
                'service_address' => '192.168.1.135',    //微服务IP地址
                'service_port' => 8899,                    //微服务端口
                'service_tags' => ['rpc'],              //微服务标签
                'service_metas' => ['version' => "1.0"],      //微服务元数据
                'service_health_check' => [              //微服务心跳检查配置
                    'id' => 'ms_usercenter_rpc_1_check',
                    'name' => '"TCP API ON PORT 8899',
                    'tcp' => '192.168.1.135:8899',
                    'Interval' => '10s',
                    'timeout' => '1s',
                ],
                'service_weights' => [                  //微服务权重配置
                    "Passing" => 10,
                    "Warning" => 1,
                ],
            ],

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_rpc_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_rpc_worker_process',

            'server_type' => 'rpc',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,

            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 8899,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\RpcServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\RpcServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\RpcServer', 'onWorkerStart'),


            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\RpcServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\RpcServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\RpcServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\RpcServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\RpcServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\RpcServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\RpcServer', 'onFinish'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\RpcServer', 'onEnd'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\RpcServer', 'onHandShake'),

            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\RpcServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\RpcServer', 'onPacket'),


            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 设置启动的Worker进程数。
                 * 业务代码是全异步非阻塞的，这里设置为CPU核数的1-4倍最合理
                 * 业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
                 * 默认设置为SWOOLE_CPU_NUM，最大不得超过SWOOLE_CPU_NUM * 1000
                 * 比如1个请求耗时100ms，要提供1000QPS的处理能力，那必须配置100个进程或更多。
                 * 但开的进程越多，占用的内存就会大大增加，而且进程间切换的开销就会越来越大。所以这里适当即可。不要配置过大。
                 * 假设每个进程占用40M内存，100个进程就需要占用4G内存
                 */
                'worker_num' => 8,
            ),
        ),
        /**
         * grpc服务器配置
         */
        'grpc' => array(
            /**
             * 微服务配置，如果不为空将向服务注册中心注册此微服务
             */
            'microservice' => [
                'enable' => false,                        //启用微服务
                'service_id' => 'ms_usercenter_grpc_1',       //微服务ID
                'service_name' => 'ms_usercenter',       //微服务名称
                'service_address' => '192.168.1.135',    //微服务IP地址
                'service_port' => 8888,                    //微服务端口
                'service_tags' => ['grpc'],              //微服务标签
                'service_metas' => ['version' => "1.0"],      //微服务元数据
                'service_health_check' => [              //微服务心跳检查配置
                    'id' => 'ms_usercenter_grpc_1_check',
                    'name' => '"TCP API ON PORT 8888',
                    'tcp' => '192.168.1.135:8888',
                    'Interval' => '10s',
                    'timeout' => '1s',
                ],
                'service_weights' => [                  //微服务权重配置
                    "Passing" => 10,
                    "Warning" => 1,
                ],
            ],

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_grpc_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_grpc_worker_process',

            'server_type' => 'grpc',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,

            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 8888,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\GrpcServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\GrpcServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\GrpcServer', 'onWorkerStart'),


            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\GrpcServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\GrpcServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\GrpcServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\GrpcServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\GrpcServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\GrpcServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\GrpcServer', 'onFinish'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\GrpcServer', 'onEnd'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\RpcServer', 'onHandShake'),

            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\GrpcServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\GrpcServer', 'onPacket'),


            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 设置启动的Worker进程数。
                 * 业务代码是全异步非阻塞的，这里设置为CPU核数的1-4倍最合理
                 * 业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
                 * 默认设置为SWOOLE_CPU_NUM，最大不得超过SWOOLE_CPU_NUM * 1000
                 * 比如1个请求耗时100ms，要提供1000QPS的处理能力，那必须配置100个进程或更多。
                 * 但开的进程越多，占用的内存就会大大增加，而且进程间切换的开销就会越来越大。所以这里适当即可。不要配置过大。
                 * 假设每个进程占用40M内存，100个进程就需要占用4G内存
                 */
                'worker_num' => 8,
                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => true,
            ),
        ),

        //websocket服务器配置
        'websocket' => array(

            /**
             * 微服务配置，如果不为空将向服务注册中心注册此微服务
             */
            'microservice' => [
                'enable' => false,                        //启用微服务
                'service_id' => 'ms_usercenter_websocket_1',       //微服务ID
                'service_name' => 'ms_usercenter',       //微服务名称
                'service_address' => '192.168.1.135',    //微服务IP地址
                'service_port' => 9999,                    //微服务端口
                'service_tags' => ['websocket'],              //微服务标签
                'service_metas' => ['version' => "1.0"],      //微服务元数据
                'service_health_check' => [              //微服务心跳检查配置
                    'id' => 'ms_usercenter_websocket_1_check',
                    'name' => '"HTTP API ON PORT 9999',
                    'http' => 'http://192.168.1.135:9999/health.shtml',
                    'Interval' => '10s',
                    'timeout' => '1s',
                ],
                'service_weights' => [                  //微服务权重配置
                    "Passing" => 10,
                    "Warning" => 1,
                ],
            ],

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_websocket_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_websocket_worker_process',

            'server_type' => 'websocket',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,
            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 9999,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\WebSocketServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\WebSocketServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\WebSocketServer', 'onWorkerStart'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\WebSocketServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\WebSocketServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\WebSocketServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\WebSocketServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\WebSocketServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\WebSocketServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\WebSocketServer', 'onFinish'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\WebSocketServer', 'onHandShake'),


            /**
             *  WebSocket服务器可设置on_open事件回调, 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数
             *  $req 是一个Http请求对象，包含了客户端发来的握手请求信息
             *  onOpen事件函数中可以调用push向客户端发送数据或者调用close关闭连接
             *  onOpen事件回调是可选的
             * 回调方法格式：  function($server, $request, $cfg){}
             * $server  -- 服务器对象
             * $request  --请求对象
             * $cfg--为当前服务器配置
             */
            'on_open' => array('\\package\\application\\WebSocketServer', 'onOpen'),


            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\WebSocketServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\WebSocketServer', 'onPacket'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\WebSocketServer', 'onEnd'),
            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
                 */
                'open_http_protocol' => true,

                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => false,

                /**
                 * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
                 * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
                 */
                'open_websocket_protocol' => true,

            ),
        ),

        //tcp服务器配置
        'tcp' => array(

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_tcp_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_tcp_worker_process',

            'server_type' => 'tcp',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,
            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 9998,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_SOCK_TCP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\TcpServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\TcpServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\TcpServer', 'onWorkerStart'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\TcpServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\TcpServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\TcpServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\TcpServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\TcpServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\TcpServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\TcpServer', 'onFinish'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\TcpServer', 'onHandShake'),

            /**
             * websocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\TcpServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\TcpServer', 'onPacket'),


            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\TcpServer', 'onEnd'),
            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
                 */
                'open_http_protocol' => false,

                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => false,

                /**
                 * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
                 * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
                 */
                'open_websocket_protocol' => false,

            ),
        ),

        //udp 服务器配置
        'udp' => array(

            /**
             * 主进程名称
             */
            'main_process_name' => 'eunionz_udp_manager_process',

            /**
             * 工作进程名称
             */
            'worker_process_name' => 'eunionz_udp_worker_process',

            'server_type' => 'udp',
            /**
             * 服务器配置启用/禁用
             * true--启用  false--禁用
             */
            'enable' => true,
            /**
             * 服务器监听主机
             * 参数用来指定监听的ip地址，如127.0.0.1，或者外网地址，或者0.0.0.0监听全部地址
             * IPv4使用 127.0.0.1表示监听本机，0.0.0.0表示监听所有地址
             * IPv6使用::1表示监听本机，:: (相当于0:0:0:0:0:0:0:0) 表示监听所有地址
             */
            'host' => '0.0.0.0',

            /**
             * 服务器监听端口
             * 监听的端口，如9501
             * 如果$sock_type为UnixSocket Stream/Dgram，此参数将被忽略
             * 监听小于1024端口需要root权限
             * 如果此端口被占用server->start时会失败
             */
            'port' => 9997,

            /**
             * 是否支持 https 协议
             */
            'is_https' => false,

            /**
             * 运行的模式
             * SWOOLE_PROCESS多进程模式（默认）
             * SWOOLE_BASE基本模式
             */
            'mode' => SWOOLE_PROCESS,

            /**
             * 指定Socket的类型，支持TCP、UDP、TCP6、UDP6、UnixSocket Stream/Dgram 6种
             * SWOOLE_TCP/SWOOLE_SOCK_TCP tcp ipv4 socket
             * SWOOLE_TCP6/SWOOLE_SOCK_TCP6 tcp ipv6 socket
             * SWOOLE_UDP/SWOOLE_SOCK_UDP udp ipv4 socket
             * SWOOLE_UDP6/SWOOLE_SOCK_UDP6 udp ipv6 socket
             * SWOOLE_UNIX_DGRAM unix socket dgram
             * SWOOLE_UNIX_STREAM unix socket stream
             *
             * 使用$sock_type | SWOOLE_SSL可以启用SSL隧道加密。启用SSL后必须配置ssl_key_file和ssl_cert_file
             * Unix Socket模式下$host参数必须填写可访问的文件路径，$port参数忽略
             * Unix Socket模式下，客户端$fd将不再是数字，而是一个文件路径的字符串
             * SWOOLE_TCP等是1.7.0后提供的简写方式，与SWOOLE_SOCK_TCP是等同的
             */
            'sock_type' => SWOOLE_UDP,

            /**
             * 仅主服务器才能设置 on_start事件回调，如果on_start为空则没有主服务器启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_start' => array('\\package\\application\\UdpServer', 'onStart'),


            /**
             * 仅主服务器才能设置 on_managerstart事件回调，如果on_managerstart为空则没有管理进程启动方法
             * 回调方法格式：  function($server, $cfg){}
             * 基于  $server--为启动的服务器对像 $cfg--为当前服务器配置
             */
            'on_managerstart' => array('\\package\\application\\UdpServer', 'onManagerStart'),

            /**
             * 仅主服务器才能设置 on_workerstart事件回调，如果on_workerstart为空则没有工作进程启动方法
             * 回调方法格式：  function($server, $worker_id, $cfg){}
             * 基于  $server--为启动的服务器对像  $worker_id--工作进程ID $cfg--为当前服务器配置
             */
            'on_workerstart' => array('\\package\\application\\UdpServer', 'onWorkerStart'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_connect事件回调，如果on_connect为空则没有新连接进入处理方法
             * 回调方法格式：  function($server, $fd, $cfg){}
             * 基于  $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_connect' => array('\\package\\application\\UdpServer', 'onConnect'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_request事件回调，如果on_request为空则没有请求进入处理方法
             * 回调方法格式：  function($request, $response , $cfg){}
             * $request--请求对像 $response--响应对像  $cfg--为当前服务器配置
             */
            'on_request' => array('\\package\\application\\UdpServer', 'onRequest'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_close事件回调，如果on_close为空则没有连接关闭时处理方法
             * 回调方法格式：  function($server, $fd , $cfg){}
             * $server--为启动的服务器对像 $fd--是连接的文件描述符，发送数据/关闭连接时需要此参数  $cfg--为当前服务器配置
             */
            'on_close' => array('\\package\\application\\UdpServer', 'onClose'),

            /**
             * 仅主服务器才能设置 on_workerstop事件回调，如果on_workerstop为空则没有工作进程停止方法
             * 回调方法格式：  function($server, $worker_id , $cfg){}
             * 基于  $server为启动的服务器对像 $worker_id--工作进程ID  $cfg--为当前服务器配置
             */
            'on_workerstop' => array('\\package\\application\\UdpServer', 'onWorkerStop'),

            /**
             * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
             * 回调方法格式：  function($server, $fd, $reactor_id, $data , $cfg){}
             * $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
             */
            'on_receive' => array('\\package\\application\\UdpServer', 'onReceive'),

            /**
             * 主服务器才可以设置 on_task事件回调，如果on_task为空则没有任务处理方法
             * 回调方法格式：  function($server, $task_id, $src_worker_id, $data, $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $src_worker_id --来自于哪个worker进程         $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_task' => array('\\package\\application\\UdpServer', 'onTask'),

            /**
             * 主服务器才可以设置 on_finish事件回调，如果on_finish为空则没有任务完成处理方法
             * 回调方法格式：  function($server, $task_id, $data , $cfg){}
             * $server  --Server对象 $task_id--是任务ID，由swoole扩展内自动生成，用于区分不同的任务。$task_id和$src_worker_id组合起来才是全局唯一的，不同的worker进程投递的任务ID可能会有相同
             * $data--是任务的内容  $cfg--为当前服务器配置
             */
            'on_finish' => array('\\package\\application\\UdpServer', 'onFinish'),

//            /**
//             *  WebSocket服务器可设置on_handshake事件回调,WebSocket建立连接后进行握手。onHandShake事件回调是可选的
//             *  WebSocket服务器已经内置了handshake，如果用户希望自己进行握手处理，可以设置onHandShake事件回调函数。
//             * 设置onHandShake回调函数后不会再触发onOpen事件，需要应用代码自行处理
//             * onHandShake中必须调用response->status设置状态码为101并调用end响应, 否则会握手失败.
//             * 内置的握手协议为Sec-WebSocket-Version: 13，低版本浏览器需要自行实现握手
//             * 注意： 仅仅你需要自行处理handshake的时候再设置这个回调函数，如果您不需要“自定义”握手过程，那么不要设置该回调，用swoole默认的握手即可。下面是“自定义”handshake事件回调函数中必须要具备的：
//             *
//             * 回调方法格式：  function($request, $response, $cfg){}
//             * $request  --请求对象
//             * $response  --响应对象
//             * $cfg--为当前服务器配置
//             */
//            'on_handshake'=> array('\\package\\application\\UdpServer', 'onHandShake'),


            /**
             * WebSocket服务器可设置on_message事件回调，当且仅当 open_websocket_protocol 参数为 true时有效，当WebSocket服务器收到来自客户端的数据帧时会回调此事件
             * 回调方法格式：  function($server, $frame, $cfg){}
             * $server  --Server对象
             * $frame--swoole_websocket_frame对象，包含了客户端发来的数据帧信息
             *         客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
             *         swoole_websocket_frame 共有4个属性，分别是:
             *               $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
             *               $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断  $data 如果是文本类型，编码格式必然是UTF-8，这是WebSocket协议规定的
             *               $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
             *                           WEBSOCKET_OPCODE_TEXT = 0x1 ，文本数据
             *                           WEBSOCKET_OPCODE_BINARY = 0x2 ，二进制数据
             *               $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
             *
             * $cfg--为当前服务器配置
             */
            'on_message' => array('\\package\\application\\UdpServer', 'onMessage'),

            /**
             * Udp服务器可设置 on_packet 事件回调，接收到UDP数据包时回调此函数，发生在worker进程中
             * 回调方法格式：  function($server, $data, $client_info , $cfg){}
             * $server  --Server对象
             * $data--收到的数据内容，可能是文本或者二进制内容
             * $client_info--客户端信息包括address/port/server_socket等多项客户端信息数据
             *      服务器同时监听TCP/UDP端口时，收到TCP协议的数据会回调onReceive，收到UDP数据包回调onPacket。
             *      服务器设置的EOF或Length等自动协议处理，对UDP端口是无效的，因为UDP包本身存在消息边界，不需要额外的协议处理。
             * $cfg--为当前服务器配置
             */
            'on_packet' => array('\\package\\application\\UdpServer', 'onPacket'),

            /**
             * 仅主服务器才能设置 on_end事件回调，如果on_end为空则没有服务器结束方法
             * 回调方法格式：  function(){}
             */
            'on_end' => array('\\package\\application\\UdpServer', 'onEnd'),
            /**
             * 监听服务器允许覆盖设置服务器参数，如果server_params不存在或为空数组，则监听服务器将继承main_server_params服务器参数，否则将覆盖main_server_params服务器参数
             */
            'server_params' => array(
                /**
                 * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
                 */
                'open_http_protocol' => false,

                /**
                 * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
                 */
                'open_http2_protocol' => false,

                /**
                 * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
                 * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
                 */
                'open_websocket_protocol' => false,

            ),
        ),

    ),



    /**
     * 主服务器配置参数
     */
    'main_server_params' => array(
        /**
         * 启用压缩。默认为开启。目前支持gzip、br、deflate 三种压缩格式，底层会根据浏览器客户端传入的Accept-Encoding头自动选择压缩方式。
         */
        'http_compression' => true,

        /**
         * http_gzip_level    压缩的级别, 越高压缩后体积越小, 也越占用CPU
         */
        'http_compression_level' => 1,

        /**
         * 配置静态文件根目录，与enable_static_handler配合使用。
         */
        'document_root' => APP_REAL_PATH  . 'www',
        /**
         * 设置document_root并设置enable_static_handler为true后，底层收到Http请求会先判断document_root路径下是否存在此文件，如果存在会直接发送文件内容给客户端，不再触发onRequest回调。
         * 使用静态文件处理特性时，应当将动态PHP代码和静态文件进行隔离，静态文件存放到特定的目录
        */
        'enable_static_handler' => true,


        /**
         * 设置POST消息解析开关，选项为true时自动将Content-Type为x-www-form-urlencoded的请求包体解析到POST数组。设置为false时将关闭POST解析。
         */
//    'http_parse_post' => false,

        /**
         * 设置上传文件的临时目录
         */
//    'upload_tmp_dir' => '/data/uploadfiles/',

        /**
         * Reactor线程数 reactor_num => 2，通过此参数来调节主进程内事件处理线程的数量，以充分利用多核。
         * 默认会启用CPU核数相同的数量。
         * reactor_num建议设置为CPU核数的1-4倍
         * reactor_num最大不得超过SWOOLE_CPU_NUM * 4
         */
//    'reactor_num' => 4,
        /**
         * 设置启动的Worker进程数。
         * 业务代码是全异步非阻塞的，这里设置为CPU核数的1-4倍最合理
         * 业务代码为同步阻塞，需要根据请求响应时间和系统负载来调整
         * 默认设置为SWOOLE_CPU_NUM，最大不得超过SWOOLE_CPU_NUM * 1000
         * 比如1个请求耗时100ms，要提供1000QPS的处理能力，那必须配置100个进程或更多。
         * 但开的进程越多，占用的内存就会大大增加，而且进程间切换的开销就会越来越大。所以这里适当即可。不要配置过大。
         * 假设每个进程占用40M内存，100个进程就需要占用4G内存
         */
        'worker_num' => 8,
        /**
         * 设置worker进程的最大任务数，默认为0，一个worker进程在处理完超过此数值的任务后将自动退出，进程退出后会释放所有内存和资源。
         * 这个参数的主要作用是解决PHP进程内存溢出问题。PHP应用程序有缓慢的内存泄漏，但无法定位到具体原因、无法解决，可以通过设置max_request解决。
         * max_request只能用于同步阻塞、无状态的请求响应式服务器程序
         * 在swoole中真正维持客户端TCP连接的是master进程，worker进程仅处理客户端发送来的请求，因为客户端是不需要感知Worker进程重启的
         * 纯异步的Server不应当设置max_request
         * 使用Base模式时max_request是无效的
         */
        'max_request' => 0,
        /**
         * 服务器程序，最大允许的连接数，如max_connection => 10000, 此参数用来设置Server最大允许维持多少个TCP连接。超过此数量后，新进入的连接将被拒绝。
         * max_connection最大不得超过操作系统ulimit -n的值，否则会报一条警告信息，并重置为ulimit -n的值
         * 应用层未设置max_connection，底层将使用ulimit -n的值作为缺省设置
         * 在swoole 4.2.9或更高版本，当底层检测到ulimit -n超过10000时将默认设置为10000，原因是某些系统设置了ulimit -n为100万，需要分配大量内存，导致启动失败
         */
//    'max_connection' => 0,

        /**
         * 配置Task进程的数量，配置此参数后将会启用task功能。所以Server务必要注册onTask、onFinish2个事件回调函数。如果没有注册，服务器程序将无法启动。
         * Task进程是同步阻塞的，配置方式与Worker同步模式一致
         * 最大值不得超过SWOOLE_CPU_NUM * 1000
         */
        'task_worker_num' => 2,

        /**
         * 设置Task进程与Worker进程之间通信的方式。
         * 1, 使用Unix Socket通信，默认模式
         * 2, 使用消息队列通信
         * 3, 使用消息队列通信，并设置为争抢模式
         *
         * 使用模式1时，支持定向投递，可在task和taskwait方法中使用dst_worker_id，制定目标Task进程。dst_worker_id设置为-1时，底层会判断每个Task进程的状态，向当前状态为空闲的进程投递任务。
         * 模式2和模式3使用sysvmsg消息队列通信。消息队列模式使用操作系统提供的内存队列存储数据，未指定 mssage_queue_key 消息队列Key，将使用私有队列，在Server程序终止后会删除消息队列。指定消息队列Key后Server程序终止后，消息队列中的数据不会删除，因此进程重启后仍然能取到数据可使用ipcrm -q 消息队列ID手工删除消息队列数据模式2和模式3的不同之处是，模式2支持定向投递，$serv->task($data, $task_worker_id) 可以指定投递到哪个task进程。模式3是完全争抢模式，task进程会争抢队列，将无法使用定向投递，task/taskwait将无法指定目标进程ID，即使指定了$task_worker_id，在模式3下也是无效的。
         */
//    'task_ipc_mode' => 1,

        /**
         * 设置task进程的最大任务数。一个task进程在处理完超过此数值的任务后将自动退出。这个参数是为了防止PHP进程内存溢出。如果不希望进程自动退出可以设置为0。
         * 1.7.17以下版本默认为5000，受swoole_config.h的SW_MAX_REQUEST宏控制
         * 1.7.17以上版本默认值调整为0，不会主动退出进程
         */
//    'task_max_request' => 0,

        /**
         * 设置task的数据临时目录，在Server中，如果投递的数据超过8180字节，将启用临时文件来保存数据。这里的task_tmpdir就是用来设置临时文件保存的位置。
         * 底层默认会使用/tmp目录存储task数据，如果你的Linux内核版本过低，/tmp目录不是内存文件系统，可以设置为 /dev/shm/
         * task_tmpdir目录不存在，底层会尝试自动创建
         * 创建失败时，Server->start会失败
         */
//    'task_tmpdir' => '',

        /**
         * v4.2.12起支持
         * RFC-1014提案使TaskWorker内可以使用异步和协程API。由于Task设计之初未考虑支持异步和协程，因此带来了新的问题。
         * 无法使用return返回值作为任务的结果
         * 在异步或协程的程序中Server::finish可能会使用错误的任务上下文
         * 在onTask回调函数中仍然需要使用go手工创建协程，无法直接使用协程组件
         *
         * 增加task_enable_coroutine，开启后自动在onTask回调中创建协程，php代码可以直接使用协程API。 底层修改了onTask回调的参数。新增Swoole\Server\Task类，用于保存任务上下文，并返回结果。
         * 未开启task_enable_coroutine时，仍然使用旧版本的4参数回调
         */
//    'task_enable_coroutine' => true ,

        /**
         * 数据包分发策略。可以选择7种类型，默认为2
         * 1，轮循模式，收到会轮循分配给每一个Worker进程
         * 2，固定模式，根据连接的文件描述符分配Worker。这样可以保证同一个连接发来的数据只会被同一个Worker处理
         * 3，抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
         * 4，IP分配，根据客户端IP进行取模hash，分配给一个固定的Worker进程。可以保证同一个来源IP的连接数据总会被分配到同一个Worker进程。算法为 ip2long(ClientIP) % worker_num
         * 5，UID分配，需要用户代码中调用 Server->bind() 将一个连接绑定1个uid。然后底层根据UID的值分配到不同的Worker进程。算法为 UID % worker_num，如果需要使用字符串作为UID，可以使用crc32(UID_STRING)
         * 7，stream模式，空闲的Worker会accept连接，并接受Reactor的新请求
         *
         * 使用建议
         * 无状态Server可以使用1或3，同步阻塞Server使用3，异步非阻塞Server使用1
         * 有状态使用2、4、5
         * dispatch_mode 4,5两种模式，在1.7.8以上版本可用
         * dispatch_mode=1/3时，底层会屏蔽onConnect/onClose事件，原因是这2种模式下无法保证onConnect/onClose/onReceive的顺序
         * 非请求响应式的服务器程序，请不要使用模式1或3
         *
         * UDP协议
         * dispatch_mode=2/4/5时为固定分配，底层使用客户端IP取模散列到不同的Worker进程，算法为 ip2long(ClientIP) % worker_num
         * dispatch_mode=1/3时随机分配到不同的Worker进程
         *
         * BASE模式
         * dispatch_mode配置在BASE模式是无效的，因为BASE不存在投递任务，当Reactor线程收到客户端发来的数据后会立即在当前线程/进程回调onReceive，不需要投递Worker进程。
         */
        'dispatch_mode' => 2,

        /**
         * 设置dispatch函数，swoole底层了内置了5种dispatch_mode，如果仍然无法满足需求。可以使用编写C++函数或PHP函数，实现dispatch逻辑。使用方法：
         * $serv->set(array(
         *   'dispatch_func' => 'my_dispatch_function',
         * ));
         *
         * 设置dispatch_func后底层会自动忽略dispatch_mode配置
         * dispatch_func对应的函数不存在，底层将抛出致命错误
         * 如果需要dispatch一个超过8K的包，dispatch_func只能获取到 0-8180 字节的内容
         *
         * 编写PHP函数
         * 由于ZendVM无法支持多线程环境，即使设置了多个Reactor线程，同一时间只能执行一个dispatch_func。因此底层在执行此PHP函数时会进行加锁操作，可能会存在锁的争抢问题。请勿在dispatch_func中执行任何阻塞操作，否则会导致Reactor线程组停止工作。
         *
         * $serv->set(array(
         *     'dispatch_func' => function ($serv, $fd, $type, $data) {
         *          var_dump($fd, $type, $data);
         *          return intval($data[0]);
         *     },
         * ));
         *
         * $fd为客户端连接的唯一标识符，可使用Server::getClientInfo获取连接信息
         * $type数据的类型，0表示来自客户端的数据发送，4表示客户端连接关闭，5表示客户端连接建立
         * $data数据内容，需要注意：如果启用了Http、EOF、Length等协议处理参数后，底层会进行包的拼接。但在dispatch_func函数中只能传入数据包的前8K内容，不能得到完整的包内容。
         * 必须返回一个[0-serv->worker_num)的数字，表示数据包投递的目标工作进程ID
         * 小于0或大于等于serv->worker_num为异常目标ID，dispatch的数据将会被丢弃
         */
//    'dispatch_func' => '',

        /**
         * 设置消息队列的KEY，仅在task_ipc_mode = 2/3时使用。设置的Key仅作为Task任务队列的KEY，此参数的默认值为ftok($php_script_file, 1)
         * task队列在server结束后不会销毁，重新启动程序后，task进程仍然会接着处理队列中的任务。如果不希望程序重新启动后执行旧的Task任务。可以手工删除此消息队列。
         * ipcs -q
         * ipcrm -Q [msgkey]
         */
//    'message_queue_key' => '',

        /**
         * 守护进程化。设置daemonize => 1时，程序将转入后台作为守护进程运行。长时间运行的服务器端程序必须启用此项。
         * 如果不启用守护进程，当ssh终端退出后，程序将被终止运行。
         *
         * 启用守护进程后，标准输入和输出会被重定向到 log_file
         * 如果未设置log_file，将重定向到 /dev/null，所有打印屏幕的信息都会被丢弃
         * 启用守护进程后，CWD（当前目录）环境变量的值会发生变更，相对路径的文件读写会出错。PHP程序中必须使用绝对路径
         *
         * systemd
         *
         * 使用systemd管理Swoole服务时，请勿设置daemonize = 1。主要原因是systemd的机制与init不同。init进程的PID为1，程序使用daemonize后，会脱离终端，最终被init进程托管，与init关系变为父子进程关系。
         * 但systemd是启动了一个单独的后台进程，自行fork管理其他服务进程，因此不需要daemonize，反而使用了daemonize = 1会使得Swoole程序与该管理进程失去父子进程关系。
         */
//    'daemonize' => 1,

        /**
         * Listen队列长度，如backlog => 128，此参数将决定最多同时有多少个等待accept的连接。
         *
         * 关于tcp的backlog
         * 我们知道tcp有三次握手的过程，客户端syn=>服务端syn+ack=>客户端ack，当服务器收到客户端的ack后会将连接放到一个叫做accept queue的队列里面（注1），队列的大小由backlog参数和配置somaxconn 的最小值决定，我们可以通过ss -lt命令查看最终的accept queue队列大小，swoole的主进程调用accept（注2）从accept queue里面取走。 当accept queue满了之后连接有可能成功（注4），也有可能失败，失败后客户端的表现就是连接被重置（注3）或者连接超时，而服务端会记录失败的记录，可以通过 netstat -s|grep 'times the listen queue of a socket overflowed'来查看日志。如果出现了上述现象，你就应该调大该值了。 幸运的是swoole与php-fpm/apache等软件不同，并不依赖backlog来解决连接排队的问题。所以基本不会遇到上述现象。
         *
         * 注1:linux2.2之后握手过程分为syn queue和accept queue两个队列, syn queue长度由tcp_max_syn_backlog决定。
         * 注2:高版本内核调用的是accept4，为了节省一次set no block系统调用。
         * 注3:客户端收到syn+ack包就认为连接成功了，实际上服务端还处于半连接状态，有可能发送rst包给客户端，客户端的表现就是Connection reset by peer。
         * 注4:成功是通过tcp的重传机制，相关的配置有tcp_synack_retries和tcp_abort_on_overflow。
         */
//    'backlog' => 128,

        /**
         * log_file => '/data/log/swoole.log', 指定swoole错误日志文件。在swoole运行期发生的异常信息会记录到这个文件中。默认会打印到屏幕。
         * 注意log_file不会自动切分文件，所以需要定期清理此文件。观察log_file的输出，可以得到服务器的各类异常信息和警告。
         * log_file中的日志仅仅是做运行时错误记录，没有长久存储的必要。
         * 开启守护进程模式后(daemonize => true)，标准输出将会被重定向到log_file。在PHP代码中echo/var_dump/print等打印到屏幕的内容会写入到log_file文件
         *
         * 日志标号
         * 在日志信息中，进程ID前会加一些标号，表示日志产生的线程/进程类型。
         * # Master进程
         * $ Manager进程
         * * Worker进程
         * ^ Task进程
         *
         * 重新打开日志文件
         * 在服务器程序运行期间日志文件被mv移动或unlink删除后，日志信息将无法正常写入，这时可以向Server发送SIGRTMIN信号实现重新打开日志文件。
         * 在1.8.10或更高版本可用
         * 仅支持Linux平台
         * 不支持UserProcess进程
         */
//    'log_file' => '/data/log/swoole.log',

        /**
         * 设置Server错误日志打印的等级，范围是0-5。低于log_level设置的日志信息不会抛出。
         * $serv->set(array(
         *     'log_level' => 1,
         * ));
         *
         * 级别对应
         * 0 => SWOOLE_LOG_DEBUG
         * 1 => SWOOLE_LOG_TRACE
         * 2 => SWOOLE_LOG_INFO
         * 3 => SWOOLE_LOG_NOTICE
         * 4 => SWOOLE_LOG_WARNING
         * 5 => SWOOLE_LOG_ERROR
         * SWOOLE_LOG_DEBUG和SWOOLE_LOG_TRACE仅在编译为--enable-debug-log和--enable-trace-log版本时可用
         * 默认为SWOOLE_LOG_DEBUG也就是所有级别都打印
         *
         * 输出捕获
         *
         * 在开启daemonize守护进程时，底层将把程序中的所有打印屏幕的输出内容写入到log_file，这部分内容不受log_level控制。
         */
//    'log_level' => 1,

        /**
         * 启用心跳检测，此选项表示每隔多久轮循一次，单位为秒。如 heartbeat_check_interval => 60，表示每60秒，遍历所有连接，如果该连接在60秒内，没有向服务器发送任何数据，此连接将被强制关闭。
         * Server并不会主动向客户端发送心跳包，而是被动等待客户端发送心跳。服务器端的heartbeat_check仅仅是检测连接上一次发送数据的时间，如果超过限制，将切断连接。
         * 被心跳检测切断的连接依然会触发onClose事件回调
         * heartbeat_check仅支持TCP连接
         */
//    'heartbeat_check_interval' => 60,

        /**
         * 与heartbeat_check_interval配合使用。表示连接最大允许空闲的时间。如
         * array(
         *     'heartbeat_idle_time' => 600,
         *     'heartbeat_check_interval' => 60,
         * );
         * 表示每60秒遍历一次，一个连接如果600秒内未向服务器发送任何数据，此连接将被强制关闭
         * 启用heartbeat_idle_time后，服务器并不会主动向客户端发送数据包
         * 如果只设置了heartbeat_idle_time未设置heartbeat_check_interval底层将不会创建心跳检测线程，PHP代码中可以调用heartbeat方法手工处理超时的连接
         */
//    'heartbeat_idle_time' => 600,

        /**
         * 打开EOF检测，此选项将检测客户端连接发来的数据，当数据包结尾是指定的字符串时才会投递给Worker进程。否则会一直拼接数据包，直到超过缓存区或者超时才会中止。当出错时底层会认为是恶意连接，丢弃数据并强制关闭连接。
         *
         * 参数类型
         * 布尔型，必须为 true 或 false，传入其他类型数值会被强制转为布尔型
         *
         * array(
         *     'open_eof_check' => true, //打开EOF检测
         *     'package_eof' => "\r\n", //设置EOF
         * )
         *
         * 常见的Memcache/SMTP/POP等协议都是以\r\n结束的，就可以使用此配置。开启后可以保证Worker进程一次性总是收到一个或者多个完整的数据包。
         * EOF检测不会从数据中间查找eof字符串，所以Worker进程可能会同时收到多个数据包，需要在应用层代码中自行explode("\r\n", $data) 来拆分数据包
         * 1.7.15版本增加了open_eof_split配置项，支持从数据中查找EOF，并切分数据
         */
//    'open_eof_check' => true,

        /**
         * 启用EOF自动分包。当设置open_eof_check后，底层检测数据是否以特定的字符串结尾来进行数据缓冲。但默认只截取收到数据的末尾部分做对比。这时候可能会产生多条数据合并在一个包内。
         * EOF切割需要遍历整个数据包的内容，查找EOF，因此会消耗大量CPU资源。假设每个数据包为2M，每秒10000个请求，这可能会产生20G条CPU字符匹配指令。
         *
         * 参数类型
         * 布尔型，必须为 true 或 false，传入其他类型数值会被强制转为布尔型
         * 启用open_eof_split参数后，底层会从数据包中间查找EOF，并拆分数据包。onReceive每次仅收到一个以EOF字串结尾的数据包。
         * 启用open_eof_split参数后，无论参数open_eof_check是否设置，open_eof_split都将生效。
         *
         * 与 open_eof_check 的差异
         *
         * open_eof_check 只检查接收数据的末尾是否为 EOF，因此它的性能最好，几乎没有消耗
         * open_eof_check 无法解决多个数据包合并的问题，比如同时发送两条带有 EOF 的数据，底层可能会一次全部返回
         * open_eof_split 会从左到右对数据进行逐字节对比，查找数据中的 EOF 进行分包，性能较差。但是每次只会返回一个数据包
         *
         * array(
         *     'open_eof_split' => true, //打开EOF_SPLIT检测
         *     'package_eof' => "\r\n", //设置EOF
         * )
         *
         */
//    'open_eof_split' => true ,

        /**
         * 与 open_eof_check 或者 open_eof_split 配合使用，设置EOF字符串。
         * package_eof最大只允许传入8个字节的字符串
         */
//    'package_eof' => '',

        /**
         * 打开包长检测特性。包长检测提供了固定包头+包体这种格式协议的解析。启用后，可以保证Worker进程onReceive每次都会收到一个完整的数据包。
         * 长度检测协议，只需要计算一次长度，数据处理仅进行指针偏移，性能非常高，推荐使用
         *
         * 参数类型
         * 布尔型，必须传入true或false，其他类型将被强制转换为布尔型
         *
         * 长度协议提供了3个选项来控制协议细节。
         * 此配置仅对STREAM类型的Socket有效，如TCP、Unix Socket Stream
         *
         * package_length_type
         * 包头中某个字段作为包长度的值，底层支持了10种长度类型。请参考 package_length_type
         *
         * package_body_offset
         * 从第几个字节开始计算长度，一般有2种情况：
         * length的值包含了整个包（包头+包体），package_body_offset 为0
         * 包头长度为N字节，length的值不包含包头，仅包含包体，package_body_offset设置为N
         *
         * package_length_offset
         *length长度值在包头的第几个字节。
         *
         * $server->set(array(
         *     'open_length_check' => true,
         *     'package_max_length' => 81920,
         *     'package_length_type' => 'N',
         *     'package_length_offset' => 8,
         *     'package_body_offset' => 16,
         * ));
         * 以上通信协议的设计中，包头长度为4个整型，16字节，length长度值在第3个整型处。因此package_length_offset设置为8，0-3字节为type，4-7字节为uid，8-11字节为length，12-15字节为serid。
         *
         */
//    'open_length_check' => true,

        /**
         * 长度值的类型，接受一个字符参数，与php的 pack 函数一致。目前Swoole支持10种类型：
         * c：有符号、1字节
         * C：无符号、1字节
         * s ：有符号、主机字节序、2字节
         * S：无符号、主机字节序、2字节
         * n：无符号、网络字节序、2字节
         * N：无符号、网络字节序、4字节
         * l：有符号、主机字节序、4字节（小写L）
         * L：无符号、主机字节序、4字节（大写L）
         * v：无符号、小端字节序、2字节
         * V：无符号、小端字节序、4字节
         */
//    'package_length_type' => 'N',

        /**
         * 设置长度解析函数，支持C++或PHP的2种类型的函数。长度函数必须返回一个整数。
         *
         * 返回0，数据不足，需要接收更多数据
         * 返回-1，数据错误，底层会自动关闭连接
         * 返回包长度值（包括包头和包体的总长度），底层会自动将包拼好后返回给回调函数
         *
         * 默认底层最大会读取8K的数据，如果包头的长度较小可能会存在内存复制的消耗。可设置package_body_offset参数，底层只读取包头进行长度解析。
         *
         * PHP长度解析函数
         * 由于ZendVM不支持运行在多线程环境，因此底层会自动使用Mutex互斥锁对PHP长度函数进行加锁，避免并发执行PHP函数。在1.9.3或更高版本可用。
         * 请勿在长度解析函数中执行阻塞IO操作，可能导致所有Reactor线程发生阻塞
         * 'package_length_func' => function ($data) {
         *     if (strlen($data) < 8) {
         *          return 0;
         *     }
         *     $length = intval(trim(substr($data, 0, 8)));
         *     if ($length <= 0) {
         *          return -1;
         *     }
         *     return $length + 8;
         * }
         */
//    'package_length_func' => '',

        /**
         * 设置最大数据包尺寸，单位为字节。开启open_length_check/open_eof_check/open_http_protocol等协议解析后。swoole底层会进行数据包拼接。这时在数据包未收取完整时，所有数据都是保存在内存中的。
         * 所以需要设定package_max_length，一个数据包最大允许占用的内存尺寸。如果同时有1万个TCP连接在发送数据，每个数据包2M，那么最极限的情况下，就会占用20G的内存空间。
         *
         * open_length_check，当发现包长度超过package_max_length，将直接丢弃此数据，并关闭连接，不会占用任何内存。包括websocket、mqtt、http2协议。
         * open_eof_check，因为无法事先得知数据包长度，所以收到的数据还是会保存到内存中，持续增长。当发现内存占用已超过package_max_length时，将直接丢弃此数据，并关闭连接
         * open_http_protocol，GET请求最大允许8K，而且无法修改配置。POST请求会检测Content-Length，如果Content-Length超过package_max_length，将直接丢弃此数据，发送http 400错误，并关闭连接
         *
         * 此参数不宜设置过大，否则会占用很大的内存
         *
         */
//    'package_max_length' => 81920,

        /**
         * 启用CPU亲和性设置。在多核的硬件平台中，启用此特性会将swoole的reactor线程/worker进程绑定到固定的一个核上。可以避免进程/线程的运行时在多个核之间互相切换，提高CPU Cache的命中率。
         * 使用taskset命令查看进程的CPU亲和设置：
         * taskset -p 进程ID
         *     pid 24666's current affinity mask: f
         *     pid 24901's current affinity mask: 8
         *
         * mask是一个掩码数字，按bit计算每bit对应一个CPU核，如果某一位为0表示绑定此核，进程会被调度到此CPU上，为0表示进程不会被调度到此CPU。
         * 示例中pid为24666的进程mask = f 表示未绑定到CPU，操作系统会将此进程调度到任意一个CPU核上。 pid为24901的进程mask = 8，8转为二进制是 1000，表示此进程绑定在第4个CPU核上。
         * 仅推荐在全异步非阻塞的Server程序中启用
         */
//    'open_cpu_affinity' => true ,

        /**
         * IO密集型程序中，所有网络中断都是用CPU0来处理，如果网络IO很重，CPU0负载过高会导致网络中断无法及时处理，那网络收发包的能力就会下降。
         * 如果不设置此选项，swoole将会使用全部CPU核，底层根据reactor_id或worker_id与CPU核数取模来设置CPU绑定。
         *
         * 如果内核与网卡有多队列特性，网络中断会分布到多核，可以缓解网络中断的压力
         * 此选项必须与open_cpu_affinity同时设置才会生效
         *
         * array('cpu_affinity_ignore' => array(0, 1))
         * 接受一个数组作为参数，array(0, 1) 表示不使用CPU0,CPU1，专门空出来处理网络中断。
         */
//    'cpu_affinity_ignore' => array(0, 1),

        /**
         * 启用open_tcp_nodelay，开启后TCP连接发送数据时会关闭Nagle合并算法，立即发往客户端连接。在某些场景下，如http服务器，可以提升响应速度。
         * 默认情况下，发送数据采用Nagle 算法。这样虽然提高了网络吞吐量，但是实时性却降低了，在一些交互性很强的应用程序来说是不允许的，使用TCP_NODELAY选项可以禁止Nagle 算法。
         */
//    'open_tcp_nodelay' => true,

        /**
         * 启用tcp_defer_accept特性，可以设置为一个数值，表示当一个TCP连接有数据发送时才触发accept。
         * tcp_defer_accept => 5
         *
         * 启用tcp_defer_accept特性后，accept和onConnect对应的时间会发生变化。如果设置为5秒：
         *     客户端连接到服务器后不会立即触发accept
         *     在5秒内客户端发送数据，此时会同时顺序触发accept/onConnect/onReceive
         *     在5秒内客户端没有发送任何数据，此时会触发accept/onConnect
         *
         * tcp_defer_accept的可以提高Accept操作的效率
         */
//    'tcp_defer_accept' => 5,

        /**
         * 设置SSL隧道加密，设置值为一个文件名字符串，制定cert证书和key私钥的路径。
         * https应用浏览器必须信任证书才能浏览网页
         * wss应用中，发起WebSocket连接的页面必须使用https
         * 浏览器不信任SSL证书将无法使用wss
         * 文件必须为PEM格式，不支持DER格式，可使用openssl工具进行转换
         * 使用SSL必须在编译swoole时加入--enable-openssl选项
         *
         * $serv->set(array(
         *     'ssl_cert_file' => __DIR__.'/config/ssl.crt',
         *     'ssl_key_file' => __DIR__.'/config/ssl.key',
         * ));
         *
         * PEM转DER格式
         * openssl x509 -in cert.crt -outform der -out cert.der
         *
         * DER转PEM格式
         * openssl x509 -in cert.crt -inform der -outform pem -out cert.pem
         */
//        'ssl_cert_file' => APP_REAL_PATH . 'ssl/server.crt',

        /**
         * 同上
         */
//        'ssl_key_file' => APP_REAL_PATH . 'ssl/server.key',

        /**
         * 设置OpenSSL隧道加密的算法。Server与Client使用的算法必须一致，否则SSL/TLS握手会失败，连接会被切断。 默认算法为 SWOOLE_SSLv23_METHOD
         *
         * $server->set(array(
         *     'ssl_method' => SWOOLE_SSLv3_CLIENT_METHOD,
         * ));
         *
         * 此配置在1.7.20或更高版本可用
         * 支持的类型请参考 预定义常量
         */
//    'ssl_method' => SWOOLE_SSLv23_METHOD,

        /**
         * 启用SSL后，设置ssl_ciphers来改变openssl默认的加密算法。Swoole底层默认使用EECDH+AESGCM:EDH+AESGCM:AES256+EECDH:AES256+EDH
         */
//    'ssl_ciphers' => 'ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP',


        /**
         * 服务SSL设置验证对端证书。默认关闭，即不验证客户端证书。若开启，必须同时设置 ssl_client_cert_file选项
         *     tcp服务若验证失败，会底层会主动关闭连接。
         *     ssl_verify_peer 开启验证对端证书功能，
         *     ssl_allow_self_signed 允许自签名证书
         *     ssl_client_cert_file 客户端正证书
         *     $serv = new swoole_server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
         *     $serv->set(array(
         *          'ssl_cert_file' => __DIR__.'/config/ssl.crt',
         *          'ssl_key_file' => __DIR__.'/config/ssl.key',
         *          'ssl_verify_peer' => true,
         *          'ssl_allow_self_signed' => true,
         *          'ssl_client_cert_file' => __DIR__ . '/config/client.crt',
         *     ));
         *
         */
//    'ssl_verify_peer' => true,
//    'ssl_allow_self_signed' => true,

        /**
         * 设置Worker/TaskWorker子进程的所属用户。服务器如果需要监听1024以下的端口，必须有root权限。但程序运行在root用户下，代码中一旦有漏洞，攻击者就可以以root的方式执行远程指令，风险很大。配置了user项之后，可以让主进程运行在root权限下，子进程运行在普通用户权限下。
         *
         * $serv->set(array('user' => 'apache'));
         * 此配置在1.7.9以上版本可用
         * 仅在使用root用户启动时有效
         *
         * 注意事项
         * 使用user/group配置项将工作进程设置为普通用户后，将无法在工作进程调用shutdown/reload方法关闭或重启服务。只能使用root账户在shell终端执行kill命令。
         */
        'user' => 'www',

        /**
         * 设置worker/task子进程的进程用户组。与user配置相同，此配置是修改进程所属用户组，提升服务器程序的安全性。
         *
         * $serv->set(array('group' => 'www'));
         * 此配置在swoole-1.7.9以上版本可用
         * 仅在使用root用户启动时有效
         */
        'group' => 'www',

        /**
         * 重定向Worker进程的文件系统根目录。此设置可以使进程对文件系统的读写与实际的操作系统文件系统隔离。提升安全性。
         *
         * $serv->set(array('chroot' => '/data/server/'));
         * 此配置在swoole-1.7.9以上版本可用
         */
//    'chroot' => '/data/server/',

        /**
         * 在Server启动时自动将master进程的PID写入到文件，在Server关闭时自动删除PID文件。
         *
         * $server->set(array(
         *     'pid_file' => __DIR__.'/server.pid',
         * ));
         *
         * 使用时需要注意如果Server非正常结束，PID文件不会删除，需要使用swoole_process::kill($pid, 0)来侦测进程是否真的存在
         * 此选项在1.9.5或更高版本可用
         */
//    'pid_file' =>  __DIR__.'/server.pid',

        /**
         * 调整管道通信的内存缓存区长度。Swoole使用Unix Socket实现进程间通信。
         *
         * $server->set([
         *     'pipe_buffer_size' => 32 * 1024 *1024, //必须为数字
         * ])
         * swoole的reactor线程与worker进程之间
         * worker进程与task进程之间
         * 1.9.16或更高版本已移除此配置项，底层不再限制管道缓存区的长度
         * 都是使用unix socket进行通信的，在收发大量数据的场景下，需要启用内存缓存队列。此函数可以修改内存缓存的长度。
         *
         * task_ipc_mode=2/3时会使用消息队列通信不受此参数控制
         * 管道缓存队列已满会导致reactor线程、worker进程发生阻塞
         * 此参数在1.7.17以上版本默认为32M，1.7.17以下版本默认为8M
         */
//    'pipe_buffer_size' => 32 * 1024 *1024,

        /**
         * 配置发送输出缓存区内存尺寸。
         *
         * $server->set([
         *     'buffer_output_size' => 32 * 1024 *1024, //必须为数字
         * ])
         *
         * 单位为字节，默认为2M，如设置32 * 1024 *1024表示，单次Server->send最大允许发送32M字节的数据
         * 调用Server->send， Http\Server->end/write，WebSocket\Server->push 等发送数据指令时，单次最大发送的数据不得超过buffer_output_size配置。
         * 注意此函数不应当调整过大，避免拥塞的数据过多，导致吃光机器内存
         * 开启大量Worker进程时，将会占用worker_num * buffer_output_size字节的内存
         */
        'buffer_output_size' => 64 * 1024 * 1024,

        /**
         * 配置客户端连接的缓存区长度。从1.8.8版本开始底层对于缓存区控制的参数分离成buffer_output_size和socket_buffer_size两项配置。
         * 参数buffer_output_size用于设置单次最大发送长度。socket_buffer_size用于设置客户端连接最大允许占用内存数量。
         *
         * $server->set([
         *     'socket_buffer_size' => 128 * 1024 *1024, //必须为数字
         * ])
         *
         * 单位为字节，如128 * 1024 *1024表示每个TCP客户端连接最大允许有128M待发送的数据
         * 默认为2M字节
         *
         * 数据发送缓存区
         *
         * 调整连接发送缓存区的大小。TCP通信有拥塞控制机制，服务器向客户端发送大量数据时，并不能立即发出。这时发送的数据会存放在服务器端的内存缓存区内。此参数可以调整内存缓存区的大小。
         *
         * 如果发送数据过多，客户端阻塞，数据占满缓存区后Server会报如下错误信息：
         *   swFactoryProcess_finish: send failed, session#1 output buffer has been overflowed.
         * 发送缓冲区塞满导致send失败，只会影响当前的客户端，其他客户端不受影响
         * 服务器有大量TCP连接时，最差的情况下将会占用serv->max_connection * socket_buffer_size字节的内存
         * 尤其是外围通信的服务器程序，网络通信较慢，如果持续连续发送数据，缓冲区很快就会塞满。发送的数据会全部堆积在Server的内存里。因此此类应用应当从设计上考虑到网络的传输能力，先将消息存入磁盘，等客户端通知服务器已接受完毕后，再发送新的数据。
         * 如视频直播服务，A用户带宽是 100M，1秒内发送10M的数据是完全可以的。B用户带宽只有1M，如果1秒内发送10M的数据，B用户可能需要100秒才能接收完毕。这时数据会全部堆积在服务器内存中。
         * 可以根据数据内容的类型，进行不同的处理。如果是可丢弃的内容，如视频直播等业务，网络差的情况下丢弃一些数据帧完全可以接受。如果内容是不可丢失的，如微信消息，可以先存储到服务器的磁盘中，按照100条消息为一组。当用户接受完这一组消息后，再从磁盘中取出下一组消息发送到客户端。
         */
//    'socket_buffer_size' => 128 * 1024 *1024,

        /**
         * swoole在配置dispatch_mode=1或3后，因为系统无法保证onConnect/onReceive/onClose的顺序，默认关闭了onConnect/onClose事件。
         * 如果应用程序需要onConnect/onClose事件，并且能接受顺序问题可能带来的安全风险，可以通过设置enable_unsafe_event为true，启用onConnect/onClose事件
         * enable_unsafe_event配置在1.7.18以上版本可用
         */
//    'enable_unsafe_event' => true,

        /**
         * swoole在配置dispatch_mode=1或3后，系统无法保证onConnect/onReceive/onClose的顺序，因此可能会有一些请求数据在连接关闭后，才能到达Worker进程。
         * discard_timeout_request配置默认为true，表示如果worker进程收到了已关闭连接的数据请求，将自动丢弃。discard_timeout_request如果设置为false，表示无论连接是否关闭Worker进程都会处理数据请求。
         * discard_timeout_request 在1.7.16以上可用
         */
//    'discard_timeout_request' => true,

        /**
         * 设置端口重用，此参数用于优化TCP连接的Accept性能，启用端口重用后多个进程可以同时进行Accept操作。
         *
         * enable_reuse_port = true 打开端口重用
         * enable_reuse_port = false 关闭端口重用
         * 仅在Linux-3.9.0以上版本的内核可用
         * 启用端口重用后可以重复启动同一个端口的Server程序
         */
//    'enable_reuse_port' => true,

        /**
         * 设置此选项为true后，accept客户端连接后将不会自动加入EventLoop，仅触发onConnect回调。worker进程可以调用$serv->confirm($fd)对连接进行确认，此时才会将fd加入EventLoop开始进行数据收发，也可以调用$serv->close($fd)关闭此连接。
         *
         * 实例：
         *
         * //开启enable_delay_receive选项
         * $serv->set(array(
         *     'enable_delay_receive' => true,
         * ));
         *
         * $serv->on("Connect", function ($serv, $fd, $reactorId) {
         * $serv->after(2000, function() use ($serv, $fd) {
         *     //确认连接，开始接收数据
         *     $serv->confirm($fd);
         *     });
         * });
         * enable_delay_receive在1.8.8或更高版本可用
         */
//    'enable_delay_receive' => true,

        /**
         * 启用Http协议处理，Swoole\Http\Server会自动启用此选项。设置为false表示关闭Http协议处理。
         */
//    'open_http_protocol' => true,

        /**
         * 启用HTTP2协议解析，需要依赖--enable-http2编译选项。默认为false
         */
//    'open_http2_protocol' => true,

        /**
         * 启用websocket协议处理，Swoole\WebSocket\Server会自动启用此选项。设置为false表示关闭websocket协议处理。
         * 设置open_websocket_protocol选项为true后，会自动设置open_http_protocol协议也为true。
         */
//    'open_websocket_protocol' => true,

        /**
         * 启用mqtt协议处理，启用后会解析mqtt包头，worker进程onReceive每次会返回一个完整的mqtt数据包。
         * $serv->set(array('open_mqtt_protocol' => true));
         */
//    'open_mqtt_protocol' => true,

        /**
         * 启用websocket协议中关闭帧（opcode为0x08的帧）在onMessage回调中接收，默认为false。
         * 开启后，可在WebSocketServer中的onMessage回调中接收到客户端或服务端发送的关闭帧，开发者可自行对其进行处理。
         *
         * 示例：
         *    $server = new swoole_websocket_server("0.0.0.0", 9501);
         *    $server->set(array("open_websocket_close_frame" => true));
         *    $server->on('open', function (swoole_websocket_server $server, $request) {});
         *    $server->on('message', function (swoole_websocket_server $server, $frame) {
         *         if ($frame->opcode == 0x08) {
         *              echo "Close frame received: Code {$frame->code} Reason {$frame->reason}\n";
         *         } else {
         *              echo "Message received: {$frame->data}\n";
         *         }
         *    });
         *    $server->on('close', function ($ser, $fd) {});
         *    $server->start();
         */
//    'open_websocket_close_frame' => true,

        /**
         * 设置异步重启开关。设置为true时，将启用异步安全重启特性，Worker进程会等待异步事件完成后再退出。详细信息请参见 异步安全重启特性
         * $serv->set(['reload_async' => true]);
         *
         * 协程模式
         * 在4.x版本中开启enable_coroutine时，底层会额外增加一个协程数量的检测。当前无任何协程时进程才会退出。
         */
//    'reload_async' => true,

        /**
         * 开启TCP快速握手特性。此项特性，可以提升TCP短连接的响应速度，在客户端完成握手的第三步，发送SYN包时携带数据。
         * $server->set(['tcp_fastopen' => true]);
         * 此参数可以设置到监听端口上
         */
//    'tcp_fastopen' => true,

        /**
         * 开启请求慢日志。启用后Manager进程会设置一个时钟信号，定时侦测所有Task和Worker进程，一旦进程阻塞导致请求超过规定的时间，将自动打印进程的PHP函数调用栈。
         * 底层基于ptrace系统调用实现，某些系统可能关闭了ptrace，无法跟踪慢请求。请确认kernel.yama.ptrace_scope内核参数是否0。
         * array(
         *     'request_slowlog_file' => '/tmp/trace.log',
         * )
         * 与trace_event_worker和request_slowlog_timeout配置项配合使用。
         *
         * 注意事项
         *     需要1.10.0或更高版本
         *     仅在同步阻塞的程序中有效，请勿使用与协程和异步回调的服务器中
         *     必须是具有可写权限的文件，否则创建文件失败底层会抛出致命错误
         *     默认仅监听Task进程，通过增加trace_event_worker => true来开启对Worker进程的跟踪
         *     超时时间
         *     通过request_slowlog_timeout来设置请求超时时间，单位为秒。
         *
         *     array(
         *          'request_slowlog_timeout' => 2, //2秒
         *          'request_slowlog_file' => '/tmp/trace.log',
         *          'trace_event_worker' => true, //跟踪 Task 和 Worker 进程
         *     )
         */
//    'request_slowlog_file' => '/tmp/trace.log',

        /**
         * 根据 RFC1011 实现
         * enable_coroutine 选项相当于在回调中关闭以前版本的SW_COROUTINE宏开关, 关闭时在回调事件中不再创建协程，但是保留用户创建协程的能力。
         * enable_coroutine选项影响范围
         *     所有原有自动创建协程的回调, 包括
         *     onWorkerStart
         *     onConnect
         *     onOpen
         *     onReceive
         *     redis_onReceive
         *     onPacket
         *     onRequest
         *     onMessage
         *     onPipeMessage
         *     onClose
         *     tick/after 定时器
         *     4.0以下版本
         *     2.0-4.0版本默认会在Server的回调函数中自动创建协程，如果在此事件中未使用任何协程API，实际上是浪费的。而且造成了与1.x的不兼容性。
         *
         *     此外还包括Timer定时器的相关API也会自动创建协程。
         *     简介
         *     enable_coroutine参数，默认为true，通过设置为false可关闭内置协程。
         */
//    'enable_coroutine' => true,

        /**
         * 设置当前工作进程最大协程数量。超过max_coroutine底层将无法创建新的协程，底层会抛出错误，并直接关闭连接。
         * 在Server程序中实际最大可创建协程数量等于 worker_num * max_coroutine
         * $server->set(array(
         *     'max_coroutine' => 3000,
         * ))
         *     默认值为3000
         */
        'max_coroutine' => 20000,


        /**
         * Worker进程收到停止服务通知后最大等待时间，默认为30秒
         *
         * 经常会碰到由于worker阻塞卡顿导致worker无法正常reload, 无法满足一些生产场景，例如发布代码热更新需要reload进程。所以，我们加入了进程重启超时时间的选项。
         *
         * 同步重启
         *     v4.3.0开始支持同步模式重启超时时间配置
         *     经常会碰到由于worker阻塞卡住导致worker无法正常reload, 无法满足一些场景，例如发布代码热更新需要reload进程，我们加入了进程重启超时时间
         *     Worker进程收到SIGTERM或者达到max_request时，管理进程会重起该worker进程。分以下几个步骤：
         *
         *     底层会增加一个(max_wait_time)秒的定时器，触发定时器后，检查进程是否依然存在，如果是，会强制杀掉,重新拉一个进程。
         *     依次向目标进程发送SIGTERM信号，尝试杀掉进程。
         *
         * 异步重启
         *     参考1.9.17 支持异步安全重启特性
         */
//    'max_wait_time' => 30,


    ),
);
