<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Controller
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */


namespace com\eunionz\core;

use com\eunionz\exception\BaseException;
use com\eunionz\exception\ViewException;

defined('APP_IN') or exit('Access Denied');

class Controller extends Kernel
{
    /**
     * 默认是否开启ob_start
     * @var bool
     */
    public $_is_ob_start = true;

    /**
     * 默认开启针对 GET POST COOKIE的过滤
     * @var bool
     */
    public $_is_filter = true;

    /**
     * view data
     * @var array
     */
    public $viewData = array();


    /**
     * is happened invalid method call
     * @var bool
     */
    public $is_invalid_method_call = false;


    /**
     * is cache view result
     * @var bool
     */
    public $is_cache_view = false;

    /**
     * allow enabled action cache actions list
     * @var array
     *      array('_index'=>60,...)
     */
    public $_enabled_cache_view_actions = array();

    /**
     * allow disabled action cache actions list
     * @var array
     *     array('_index')
     */
    public $_disabled_cache_view_actions = array();

    /**
     * view cache lifetime seconds
     */
    public $_cache_view_lifetime = 60;

    /**
     * 是否返回json数据
     * @var bool
     */
    public $_is_json_return = false;

    /**
     * construct
     */
    public function __construct()
    {
        // 当前实例
    }

    public function initialize()
    {
        $this->mergeLang($this->getControllerLang(get_called_class()));
        $this->viewData['all_langs'] = $this->getLang();
        $this->viewData['js_all_langs'] = json_encode($this->getLang());
        $js_headers['shopid'] = ctx()->getShopId();
        $js_headers['sessionname'] = ctx()->getSession()->session_name();
        $js_headers['sessionid'] = ctx()->getSession()->session_id();
        $js_headers['clienttype'] = ctx()->getClinetType();
        $js_headers['clientversion'] = ctx()->getRequest()->getClinetVersion();
        $js_headers['applanguage'] = ctx()->getI18n()->getLanguage();
        $this->viewData['js_headers'] = json_encode($js_headers);
    }

    /**
     * is post request
     *
     * @return bool
     */
    final protected function is_post()
    {
        if (strtolower($this->server('REQUEST_METHOD')) == 'post') {
            /*$http_referer= isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'';
            if(empty($http_referer)){
                echo $this->getLang('access_denied');
                exit;
            }
            $host=isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:'';
            if(empty($host)){
                echo $this->getLang('access_denied');
                exit;
            }
            $http_referer_host=parse_url($http_referer);
            $port=isset($http_referer_host['port'])? $http_referer_host['port']:'80';
            if($port=='80')
                $port='';
            else
                $port=':'.$port;

            if(!isset($http_referer_host['host'])  || ((strtolower($http_referer_host['host']).$port)!= strtolower($host)) ){
                echo $this->getLang('access_denied');
                exit;
            }*/
            return true;
        }
        return false;
    }

    /**
     * is ajax request
     *
     * @return bool
     */
    final protected function is_ajax()
    {
        if (!$this->server('HTTP_X_REQUESTED_WITH')) return false;
        return strtolower($this->server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    /**
     * is get request
     *
     * @return bool
     */
    final protected function is_get()
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'get';
    }

    /**
     * is put request
     *
     * @return bool
     */
    final protected function is_put()
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'put';
    }

    /**
     * is delete request
     *
     * @return bool
     */
    final protected function is_delete()
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'delete';
    }

    /**
     * is options request
     *
     * @return bool
     */
    final protected function is_options()
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'options';
    }

    /**
     * redirect url
     *
     * @param string $url
     * @param bool
     */
    final protected function redirect($url, $is_exit = true)
    {
        if (ctx()->getResponse()) {
            ctx()->getResponse()->redirect($url);
        }
    }

    /**
     * get config item
     *
     * get this global config item
     *
     * $namespace is config file name, But does not contain ".config.php" suffix.
     * $key is null, get all item.
     *
     * @param string $namespace
     * @param string $key
     *
     * @return mixed
     */
    final function F($namespace, $key = '')
    {
        return getConfig($namespace, $key);
    }

    /**
     * call service
     *
     * This is load_service alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function S($name, $single = true)
    {
        return $this->loadService($name, $single);
    }

    /**
     * call component
     *
     * This is load_component alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function C($name, $single = true)
    {
        return $this->loadComponent($name, $single);
    }

    /**
     * call plugin
     *
     * This is load_plugin alias
     *
     * $class is class name.
     * $single is true for the singleton pattern, false is factory pattern.
     *
     * @param string $name
     * @param bool $single
     *
     * @return object
     */
    final protected function P($name, $single = true)
    {
        return $this->loadPlugin($name, $single);
    }

    /**
     * display view
     *
     * use the template show an view
     *
     * $return is true, return the need to display content.
     *
     * @param string $page
     * @param array $vars
     * @param bool $return
     *
     * @return string
     */
    final protected function display($page = null, $model = array(), $return = false)
    {
        $route_datas = ctx()->getRouter();
        if (empty($page)) {
            $page = strtolower($route_datas['path'] . '/' . $route_datas['act']);
        }

        $rs = $this->displayBlade($page, $model, $return);
        if ($return) return $rs;

    }


    //控制器将返回json，同时关闭跟踪输出
    final protected function ajaxReturn($data = null, $is_return = false)
    {
        $this->_is_json_return = true;
        $this->addHeader("Content-Type", "application/json");
        $return_string = "";
        if (!empty($data)) {
            if (is_string($data) || is_numeric($data)) {
                $return_string = str_ireplace('&amp;', '&', $data);
            } else {
                $return_string = str_ireplace('&amp;', '&', json_encode($data));
            }
        }

        if ($is_return) {
            return $return_string;
        }
        $this->write($return_string);
    }

    /**
     * display view
     *
     * use the template show an view
     *
     * $return is true, return the need to display content.
     *
     * @param string $page
     * @param array $vars
     * @param bool $return
     *
     * @return string
     */
    final protected function displayBlade($page, $model = array(), $return = false)
    {
        if (empty($model)) $model = array();

        $this->viewData = array_merge($this->viewData, $model);
        $this->viewData['page'] = $page;
        $this->viewData['ctx'] = ctx();
        $this->viewData['SESSION'] = $this->session();
        $this->viewData['COOKIE'] = $this->cookie();
        $this->viewData['SERVER'] = $this->server();
        $this->viewData['HEADER'] = $this->header();
        $this->viewData['GET'] = $this->get();
        $this->viewData['POST'] = $this->post();
        $this->viewData['REQUEST'] = $this->request();
        $this->viewData['FILES'] = $this->files();

        $rs = $this->C('blade', false)->display($page, $this->viewData, $return);

        if ($return)
            return $rs;
    }

    /**
     * 释放控制器
     */
    public final function free()
    {
        ctx()->getRouter(true);
    }


    /**
     * 基于当前请求ID设置$_COOKIE变量
     * @param $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return string
     */
    public final function setcookie($key, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true)
    {
        return ctx()->getResponse()->setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 分段输出内容
     * @param $content 内容不能为空，内容不超过2M
     */
    public function write($content)
    {
        return ctx()->getResponse()->ob_write($content);
    }

    /**
     * 发送Http响应体，并结束请求处理
     * @param null $content
     */
    public function end($content = null)
    {
        return ctx()->getResponse()->end($content);
    }

    /**
     * 发送HTTP状态码
     * @param $code 200 302
     */
    public function status($code)
    {
        return ctx()->getResponse()->status($code);
    }

    /**
     * 发送文件
     * @param $filename    文件名
     * @param int $offset 偏移 发送文件的偏移量，可以指定从文件的中间部分开始传输数据。此特性可用于支持断点续传。
     * @param int $length 长度 发送数据的尺寸，默认为整个文件的尺寸
     */
    public function sendfile($filename, $offset = 0, $length = 0)
    {
        return ctx()->getResponse()->sendfile($filename, $offset, $length);
    }


    /**
     * 基于当前请求ID在请求中增加头部信息
     * @param $name  头部信息名称
     * @param $value  头部信息值
     */
    public function addHeader($name, $value)
    {
        if (ctx()->getResponse()) {
            return ctx()->getResponse()->addHeader($name, $value);
        }
        return false;
    }

}
