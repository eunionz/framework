<?php

namespace package\application;

use cn\eunionz\core\Context;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Request;
use cn\eunionz\core\Session;

/**
 * Rpc 服务器类
 * Class RpcServer
 * @package package\application
 */
class RpcServer extends \cn\eunionz\core\Server
{

    /**
     * 主服务器/其它监听服务器都可以设置 on_receive事件回调，如果on_receive为空则没有数据接收处理方法
     *      $server  --Server对象  $fd--TCP客户端连接的唯一标识符  $reactor_id--TCP连接所在的Reactor线程ID  $data--收到的数据内容，可能是文本或者二进制内容  $cfg--为当前服务器配置
     *      向RPC客户端返回格式为json，{
     *          'status' => 0,             //说明，0--没有错误   非0--有错误
     *          'msg'    => '错误消息'      //说明，没有错误则为空
     *          'header' => {
     *                          "sessionid"=>session_id,                       //向RPC客户端返回当前RPC服务器端会话ID,如果RPC客户端与RPC服务器端会话ID一致则可共享$_SESSION，否则无法共享$_SESSION
     *                          "sessionname"=>session_name,                   //向RPC客户端返回当前RPC服务器端会话ID,如果RPC客户端与RPC服务器端会话ID一致则可共享$_SESSION，否则无法共享$_SESSION
     *                          "shopid"=>shop_id,                             //向RPC客户端返回当前RPC服务器端店铺ID，应该与RPC客户端店铺ID一致，以使得业务逻辑均在同一客户ID下
     *                          "applanguage"=>app_language,                   //向RPC客户端返回当前RPC服务器端语言，应该与RPC客户端语言一致，以使得应用语言资源包均使用同一语言资源包
     *                          "clientversion"=>1.05,                         //向RPC客户端返回当前RPC服务器端所使用的客户端版本号，应该与RPC客户端版本号一致，以使得业务逻辑均在同一客户端版本下以兼容不同客户端版本业务逻辑
     *                          "clienttype"=>'pc|wap|wx|andriod|ios|weapp|baiduweapp|aliweapp',   //向RPC客户端返回当前RPC服务器端所使用的客户端终端类型，应该与RPC客户端终端类型一致，以使得业务逻辑均在同一客户端终端类型下
     *                      },
     *          'return'   => mix，可为任意类型数据，由RPC服务方法的返回值决定返回到RPC客户端的实际数据
     *      }
     * @param $server
     * @param $fd
     * @param $reactor_id
     * @param $data     接收数据，格式定义为json格式：
     *          {
     *              "service"=>"区分大小写的完全限定类名",                 //区分大小写的完全限定类名
     *              "action"=>"类中公共方法名即rpc服务方法",               //类中公共方法名，方法的返回值将做为rpc远程过程调用的返回值
     *              'header' => {                                        //RPC服务方法接收的header信息，格式数下：
     *                          "sessionid"=>session_id,                       //RPC客户端会话ID,可为空，如果RPC服务器端使用此会话ID建立会话则可共享$_SESSION；如果session_id值为空则RPC服务器将建立新会话，此时与RPC客户端会话隶属于不同会话
     *                          "sessionname"=>session_name,                   //向RPC客户端返回当前RPC服务器端会话ID,如果RPC客户端与RPC服务器端会话ID一致则可共享$_SESSION，否则无法共享$_SESSION
     *                          "shopid"=>shop_id,                             //RPC客户端店铺ID，必须，RPC服务端应该使用此店铺ID进行后续业务逻辑处理，以使得RPC客户端与RPC服务器端业务逻辑均在同一客户ID下
     *                          "applanguage"=>app_language,                   //RPC客户端语言，可为空，如果为空将使用app.config.php中配置的默认语言，RPC服务器端语言应该与RPC客户端语言保持一致，以使得应用语言资源包均使用同一语言资源包
     *                          "clientversion"=>1.05,                         //RPC客户端版本号，可为空，如果为空值为0，RPC服务器端所使用的客户端版本号应该与RPC客户端版本号一致，以使得业务逻辑均在同一客户端版本下以兼容不同客户端版本业务逻辑
     *                          "clienttype"=>'pc|wap|wx|andriod|ios|weapp|baiduweapp|aliweapp',
     * //RPC客户端终端类型，可为空，如果为空值为pc，RPC服务器端所使用的客户端终端类型应该与RPC客户端终端类型一致，以使得业务逻辑均在同一客户端终端类型下
     *                      },
     *              "params"=>array,                                       //必须为数组,数组中元素值将直接成为RPC服务方法的实参，此数组元素可与RPC服务方法形参个数不一致，在不一致情况下，实参将以null值传递给RPC服务方法
     *          }
     * @param $cfg
     */
    public function onReceive($server, $fd, $reactor_id, $data, $cfg)
    {
        $rpc_return = [
            'status' => 0,
            'msg' => '',
            'header' => [],
        ];

        require_once APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'cn' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'ClassAutoLoader.class.php';
        spl_autoload_register(array('\cn\eunionz\core\ClassAutoLoader', 'autoload'));

        $ctx = new Context();
        $ctx->setIsRpcCall(true);
        $ctx->setRequest(new Request());
        self::setContext($ctx);
        $ctx->setI18n(new I18n());
        $ctx->setSession(new Session());

        @libxml_disable_entity_loader(true);
        ctx()->addTimeNode('system_launch');
        //设置当前时区
        date_default_timezone_set(self::getConfig('app', 'APP_DEFAULT_TIMEZONE'));
        //设置脚本最大执行时间
        set_time_limit(self::getConfig('app', 'APP_DEFAULT_SCRIPT_EXECUTE_TIMEOUT_SECONDS'));


        $rpc_datas = @unserialize($data);
        //RPC调用服务类
        $service_class = (isset($rpc_datas['service']) && $rpc_datas['service']) ? $rpc_datas['service'] : '';
        //RPC调用服务类的服务方法
        $action = (isset($rpc_datas['action']) && $rpc_datas['action']) ? $rpc_datas['action'] : '';
        //RPC调用服务类的服务方法前置头部信息
        $header = (isset($rpc_datas['header']) && $rpc_datas['header']) ? $rpc_datas['header'] : [];
        //RPC调用服务类的服务方法实参，如果为null表示无实参
        $params = (isset($rpc_datas['params']) && $rpc_datas['params']) ? $rpc_datas['params'] : [];
        //当前店铺ID，如果店铺为-1,则返回错误
        $shop_id = (isset($header['shopid']) && $header['shopid']) ? $header['shopid'] : -1;
        if ($shop_id < 0) {
            $rpc_return['status'] = 1;
            $rpc_return['msg'] = "not get shopid，call RPC service failure!";
            $rpc_return['header'] = $header;
            $server->send($fd, serialize($rpc_return));
            return;
        }
        $rpc_return['header'] = $header;


        //当前会话ID，如果为空则将生成新会话，否则基于会话ID连接已经存在的会话
        $session_id = (isset($header['sessionid']) && $header['sessionid']) ? $header['sessionid'] : '';
        if (empty($session_id)) {
            $rpc_return['status'] = 1;
            $rpc_return['msg'] = "not get sessionid，call RPC service failure!";
            $rpc_return['header'] = $header;
            $server->send($fd, serialize($rpc_return));
            return;
        }
        $session_name = (isset($header['sessionname']) && $header['sessionname']) ? $header['sessionname'] : '';
        if (empty($session_name)) {
            $rpc_return['status'] = 1;
            $rpc_return['msg'] = "not get sessionname，call RPC service failure!";
            $rpc_return['header'] = $header;
            $server->send($fd, serialize($rpc_return));
            return;
        }


        ctx()->getSession()->session_id($session_id);
        ctx()->getSession()->session_name($session_name);
        ctx()->getSession()->rpcInitSession();


        ctx()->get('APP_SHOP_ID', $shop_id);

        //当前语言，如果为空则使用默认语言
        $appLanguage = (isset($header['applanguage']) && $header['applanguage']) ? $header['applanguage'] : ctx()->getDefaultLanguage();
        ctx()->get('APP_LANGUAGE', $appLanguage);


        //当前终端，如果为空则使用pc终端
        $clientType = (isset($header['clienttype']) && $header['clienttype']) ? $header['clienttype'] : 'pc';
        ctx()->get('APP_CLIENT_TYPE', $clientType);


        //当前客户端接口调用版本号，如果为空则使用0
        $clinetVersion = (isset($header['clientversion']) && $header['clientversion']) ? $header['clientversion'] : 0;
        ctx()->get('APP_CLIENT_VERSION', $clinetVersion);

        ctx()->setShopID($shop_id);

        $site_name = (empty($shop_id) ? self::getConfig('app', 'APP_SHOP_ID_ZERO_FOLDER_NAME') : $shop_id);
        ctx()->setSiteName($site_name);

        //当前站点临时文件夹物理路径
        ctx()->setAppStorageRealPath(APP_STORAGE_REAL_PATH . $site_name . APP_DS);

        //当前站点运行时文件夹物理路径，该文件夹位于 APP_CURRENT_SITE_TEMP_REAL_PATH 下
        ctx()->setAppRuntimeRealPath(ctx()->getAppStorageRealPath() . APP_RUNTIME_NAME . APP_DS);


        if (!is_dir(ctx()->getAppStorageRealPath())) {
            @mkdir(ctx()->getAppStorageRealPath(), 0x777);
            @mkdir(ctx()->getAppStorageRealPath() . 'view', 0x777);
        }

        //定义当前SHOP_ID对应的临时文件夹路径
        ctx()->setAppStoragePath(APP_PATH . APP_STORAGE_NAME . '/' . $site_name . '/');

        //初始化运行时文件夹
        if (!is_dir(ctx()->getAppRuntimeRealPath())) {
            @mkdir(ctx()->getAppRuntimeRealPath());
            @mkdir(ctx()->getAppRuntimeRealPath() . 'cache');
            @mkdir(ctx()->getAppRuntimeRealPath() . 'html');
            @mkdir(ctx()->getAppRuntimeRealPath() . 'logs');
            @mkdir(ctx()->getAppRuntimeRealPath() . 'session');
            @mkdir(ctx()->getAppRuntimeRealPath() . 'uploads');
            @mkdir(ctx()->getAppRuntimeRealPath() . 'templates_c');
        }


        //定义当前SHOP_ID运行时文件夹路径
        ctx()->setAppRuntimePath(ctx()->getAppStoragePath() . 'runtime/');

        //加载核心语言包
        ctx()->getI18n()->getCoreLang('core');


        //加载全局语言包
        ctx()->getI18n()->getGlobalLang("global");

        //检查是否cli模式
        if (strtolower(php_sapi_name()) == 'cli') {
            ctx()->setIsCli(true);
        }

        if (!class_exists($service_class)) {
            $rpc_return['status'] = 4;
            $rpc_return['msg'] = $this->getLang('error_class_not_found', array($service_class));
            $server->send($fd, serialize($rpc_return));
            return;
        }
        // reflect call
        $ref_class = new \ReflectionClass($service_class);
        $comment = $ref_class->getDocComment();
        $docs = $this->loadComponent('docparser')->parse($comment);
        if (!isset($docs['RPC_CLASS'])) {
            $rpc_return['status'] = 4;
            $rpc_return['msg'] = $this->getLang('error_rpc_class_not_found', array($service_class));
            $server->send($fd, serialize($rpc_return));
            return;
        }

        $controller = new $service_class;

        $curr_client_version = 0;
        $client_version_suffix = '';
        if ($clinetVersion > 0) {
            $client_version_suffixs = $this->cache('shop_0_shop_actions', array($service_class, $action, $clinetVersion));
            if (!is_array($client_version_suffixs)) {
                $APP_SHOP_VERSION_LISTS = self::getConfig('version', 'APP_VERSION_LISTS');
                if ($APP_SHOP_VERSION_LISTS) {
                    for ($i = count($APP_SHOP_VERSION_LISTS) - 1; $i >= 0; $i--) {
                        $version = $APP_SHOP_VERSION_LISTS[$i];
                        if ($version <= $clinetVersion) {
                            if (method_exists($controller, '_' . $action . '_' . str_ireplace('.', '_', $version))) {
                                $client_version_suffix = '_' . str_ireplace('.', '_', $version);
                                $curr_client_version = $version;
                                break;
                            }
                        }
                    }
                    $client_version_suffixs = null;
                    $client_version_suffixs['client_version_suffix'] = $client_version_suffix;
                    $client_version_suffixs['curr_client_version'] = $curr_client_version;

                    $this->cache('shop_0_shop_actions', array($service_class, $action, $clinetVersion), $client_version_suffixs);
                }
            }
            $client_version_suffix = $client_version_suffixs['client_version_suffix'];
            $curr_client_version = $client_version_suffixs['curr_client_version'];
        }

        $action = $action . $client_version_suffix;


        $ref_method = new \ReflectionMethod($service_class, $action);
        $comment = $ref_method->getDocComment();
        $docs = $this->loadComponent('docparser')->parse($comment);

        if (!isset($docs['RPC_METHOD'])) {
            $rpc_return['status'] = 2;
            $rpc_return['msg'] = $this->getLang('error_rpc_class_method_not_found', array($service_class, $action));
            $server->send($fd, serialize($rpc_return));
            return;
        }

        //加载类语言包
        $this->getControllerLang($service_class);


        if (method_exists($controller, 'initialize')) {
            $method = new \ReflectionMethod($controller, 'initialize');
            $method->invokeArgs($controller, array());
        }

        if (!method_exists($controller, $action)) {
            $rpc_return['status'] = 2;
            $rpc_return['msg'] = $this->getLang('error_class_method_not_found', array($service_class, $action));
            $server->send($fd, serialize($rpc_return));
            return;
        }

        // get method
        $method = new \ReflectionMethod($controller, $action);

        // call
        $rs = @$method->invokeArgs(
            $controller,
            array_pad($params, $method->getNumberOfParameters(), null)
        );
        $rpc_return['return'] = $rs;

        $server->send($fd, serialize($rpc_return));
        ctx()->getSession()->saveSession();
    }
}