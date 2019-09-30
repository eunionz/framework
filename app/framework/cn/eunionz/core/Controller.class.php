<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Controller
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */


namespace cn\eunionz\core;

use cn\eunionz\exception\BaseException;
use cn\eunionz\exception\ViewException;

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

    public function initialize(): Controller
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
        return $this;
    }

    /**
     * is post request
     * @return bool
     */
    public final function is_post(): bool
    {
        if (strtolower($this->server('REQUEST_METHOD')) == 'post') {
            return true;
        }
        return false;
    }

    /**
     * is ajax request
     *
     * @return bool
     */
    public final function is_ajax(): bool
    {
        if (!$this->server('HTTP_X_REQUESTED_WITH')) return false;
        return strtolower($this->server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest';
    }

    /**
     * is get request
     *
     * @return bool
     */
    public final function is_get(): bool
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'get';
    }

    /**
     * is put request
     *
     * @return bool
     */
    public final function is_put(): bool
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'put';
    }

    /**
     * is delete request
     *
     * @return bool
     */
    public final function is_delete(): bool
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'delete';
    }

    /**
     * is options request
     *
     * @return bool
     */
    public final function is_options(): bool
    {
        return strtolower($this->server('REQUEST_METHOD')) == 'options';
    }

    /**
     * redirect url
     *
     * @param string $url
     * @param bool $is_exit
     */
    final protected function redirect(string $url, bool $is_exit = true): void
    {
        if (ctx()->getResponse()) {
            ctx()->getResponse()->redirect($url);
        }
    }

    /**
     * get config item
     * @param string $namespace
     * @param string $key
     * @return mixed
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    final function F(string $namespace, string $key = '')
    {
        return self::getConfig($namespace, $key);
    }

    /**
     * load service
     * @param string $name
     * @param bool $single
     * @return Service|null
     */
    final protected function S(string $name, bool $single = true): ?Service
    {
        return $this->loadService($name, $single);
    }

    /**
     * load component
     * @param string $name
     * @param bool $single
     * @return Component|null
     */
    final protected function C(string $name, bool $single = true): ?Component
    {
        return $this->loadComponent($name, $single);
    }

    /**
     * load plugin
     * @param string $name
     * @param bool $single
     * @return Plugin|null
     */
    final protected function P(string $name, bool $single = true): ?Plugin
    {
        return $this->loadPlugin($name, $single);
    }

    /**
     * display view
     * @param string|null $page
     * @param array $model
     * @param bool $return
     * @return string
     */
    public final function display(string $page = null, array $model = array(), bool $return = false)
    {
        $route_datas = ctx()->getRouter();
        if (empty($page)) {
            $page = strtolower($route_datas['path'] . '/' . $route_datas['act']);
        }

        $rs = $this->displayBlade($page, $model, $return);
        if ($return) return $rs;

    }


    /**
     * 控制器将返回json，同时关闭跟踪输出
     * @param null $data
     * @param bool $is_return
     * @return mixed|string
     */
    public final function ajaxReturn($data = null, bool $is_return = false)
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
     * @param string $page
     * @param array $model
     * @param bool $return
     * @return mixed
     */
    final protected function displayBlade(string $page, array $model = array(), bool $return = false)
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
    public final function free(): void
    {
        ctx()->getRouter(true);
    }


    /**
     * 设置$_COOKIE变量
     * @param string $key
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @return string
     */
    public final function setcookie(string $key, string $value = '', int $expire = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = true): string
    {
        return ctx()->getResponse()->setcookie($key, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 分段输出内容
     * @param string $content 内容不能为空，内容不超过2M
     * @return bool
     */
    public function write(?string $content): bool
    {
        return ctx()->getResponse()->ob_write($content);
    }

    /**
     * 发送Http响应体，并结束请求处理
     * @param string|null $content
     */
    public function end(?string $content = null): void
    {
        ctx()->getResponse()->end($content);
    }

    /**
     * 发送HTTP状态码
     * @param int $code 200 302
     */
    public function status(int $code): void
    {
        ctx()->getResponse()->status($code);
    }

    /**
     * 发送文件
     * @param string $filename 文件名
     * @param int $offset 偏移 发送文件的偏移量，可以指定从文件的中间部分开始传输数据。此特性可用于支持断点续传。
     * @param int $length 长度 发送数据的尺寸，默认为整个文件的尺寸
     */
    public function sendfile(string $filename, int $offset = 0, int $length = 0): void
    {
        ctx()->getResponse()->sendfile($filename, $offset, $length);
    }


    /**
     * 在请求中增加头部信息
     * @param string $name 头部信息名称
     * @param string $value 头部信息值
     * @return bool
     */
    public function addHeader(string $name, string $value)
    {
        if (ctx()->getResponse()) {
            return ctx()->getResponse()->addHeader($name, $value);
        }
        return false;
    }

}
