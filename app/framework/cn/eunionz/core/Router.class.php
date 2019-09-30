<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Router (parse url,find controller  )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:10
 */


namespace cn\eunionz\core;

use cn\eunionz\exception\ControllerNotFoundException;
use cn\eunionz\exception\MethodNotFoundException;

defined('APP_IN') or exit('Access Denied');

class Router extends Kernel
{

    /**
     * get uri str
     * @param string $str
     * @return string
     */
    private function _get_uri_str(string $str): string
    {
        if (false !== strpos($str, '&'))
            $str = substr($str, 0, strpos($str, '&'));

        $str = trim($str, '/');
        return $str;
    }

    /**
     * find controller by uri
     * @param $uri
     * @throws ControllerNotFoundException
     */
    private function _find_controller($uri)
    {
        // controller base path
        $ControllerBasePath = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'controller' . APP_DS;

        $ControllerNameSpace = '';

        $ControllerNames = array();
        // find path
        for ($i = count($uri); $i != 0; $i--) {
            // current path array
            $cur_uri = array_slice($uri, 0, $i);

            // get end item
            $end_uri = $cur_uri[$i - 1];
            // build path

            $Controllerpath = $ControllerBasePath . implode(APP_DS, $cur_uri) . APP_DS;
            $Controllerfile = $Controllerpath . ucfirst($end_uri) . ".class.php";


            // find out
            if (is_file($Controllerfile)) {
                $ControllerNames = array_slice($uri, 0, $i);
                unset($cur_uri, $Controllerpath);
                break;
            }

            // next level directory
            $cur_uri = array_slice($uri, 0, $i - 1);

            // current directory
            $cur_dir = implode(APP_DS, $cur_uri);

            // can not find
            if (empty($cur_dir)) {
                throw new ControllerNotFoundException(ctx()->getI18n()->getLang('error_router_title'), ctx()->getI18n()->getLang('error_router_controller', $uri));
            }

            // build
            $Controllerpath = $ControllerBasePath . $cur_dir . APP_DS;
            $Controllerfile = $Controllerpath . ucfirst($end_uri) . ".class.php";

            // find out
            if (is_file($Controllerfile)) {
                $ControllerNames = array_slice($uri, 0, $i);
                unset($cur_uri, $Controllerpath);
                break;
            }

        }

        return array($Controllerfile, $ControllerNames);
    }

    /**
     * parse controller return router
     * @param string $path
     * @return array
     * @throws ControllerNotFoundException
     * @throws MethodNotFoundException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function parse_controller(string $path = ''): array
    {

        $default_controller = self::getConfig('app', 'APP_ROUTER_DEFAULT');
        $default_action = self::getConfig('app', 'APP_DEFAULT_ACTION');

        // path info mode

        if (ctx()->isCli() && !APP_IS_IN_SWOOLE) {
            if (empty($path)) {
                $path = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : @getenv('PATH_INFO');
            }
            $arr = explode('?', $path);
            ctx()->getRequest()->server('QUERY_STRING', isset($arr[1]) ? $arr[1] : '');
            $path = $arr[0];
            if (ctx()->getRequest()->server('QUERY_STRING')) {
                $arr = explode('~', ctx()->getRequest()->server('QUERY_STRING'));
                foreach ($arr as $v) {
                    if ($v) {
                        $arr1 = explode('=', $v);
                        ctx()->getRequest()->get($arr1[0], isset($arr1[1]) ? $arr1[1] : '');
                        ctx()->getRequest()->request($arr1[0], isset($arr1[1]) ? $arr1[1] : '');
                    }
                }
            }
        } else {
            if (empty($path)) {
                $path = (ctx()->getRequest()->server('PATH_INFO')) ? ctx()->getRequest()->server('PATH_INFO') : @getenv('PATH_INFO');
                if (empty($path)) {
                    $path = (ctx()->getRequest()->server('REDIRECT_PATH_INFO')) ? ctx()->getRequest()->server('REDIRECT_PATH_INFO') : '';
                }
            }
            $path = str_ireplace('index.php', '', $path);
        }

        if (defined("URL_FILE_CONTENT_REWRITE_RULES") && URL_FILE_CONTENT_REWRITE_RULES && is_array(URL_FILE_CONTENT_REWRITE_RULES)) {
            foreach (URL_FILE_CONTENT_REWRITE_RULES as $key => $value) {
                if (preg_match('|' . $key . '|', $path, $arr)) {
                    foreach ($arr as $index => $val) {
                        $value = str_ireplace('$' . $index, $val, $value);
                    }
                    $site_file_name = ctx()->getAppRuntimeRealPath() . trim(trim($value, '/'), APP_DS);
                    if (is_file($site_file_name)) {
                        return $site_file_name;
                    }
                    $file_name = APP_REAL_PATH . trim(trim($value, '/'), APP_DS);
                    if (is_file($file_name)) {
                        return $file_name;
                    }
                }
            }
        }
        if (empty($path)) $path = '/';
        $routes = null;
        if ($GLOBALS['app_route_datas']) {
            foreach ($GLOBALS['app_route_datas'] as $key => $values) {
                if (preg_match('|^' . $key . '$|i', $path)) {
                    $values['path'] = $path;
                    $routes = $values;
                    break;
                }

            }
        }
        if ($routes) {
            $ClassName = $routes[0];
            $classNameArr = explode('::', $ClassName);
            $ClassName = $classNameArr[0];
            $action = isset($classNameArr[1]) ? ltrim($classNameArr[1], '_') : 'index';
            $classNameArr = str_replace('/package/controller/', '', str_replace("\\", "/", $ClassName));
            $classNameArr = explode('/', trim($classNameArr, '/'));
            $uri = (isset($routes[1]) && is_array($routes[1])) ? $routes[1] : array();
        } else {
            $c_path = $path;
            $routers = $this->cache("shop_0_shop_router", array($path));
            if (!$routers) {
                $path = str_replace('\\', '/', $path);
                $path = trim(str_replace('//', '/', $path), '/');
                if ($path != '' && $path != APP_IN) {
                    $uri_str = $this->_get_uri_str($path);
                } else {
                    // query string mode
                    $path = (ctx()->getRequest()->server('QUERY_STRING')) ? ctx()->getRequest()->server('QUERY_STRING') : @getenv('QUERY_STRING');
                    $path = empty($path) ? '' : $path;
                    if (trim($path, '/') != '') {
                        $uri_str = $this->_get_uri_str($path);
                    } else {
                        // ? mode
                        if (is_array(ctx()->getRequest()->get()) && count(ctx()->getRequest()->get()) > 0 && trim(key(ctx()->getRequest()->get()), '/') != '')
                            $uri_str = $this->_get_uri_str($path);
                    }
                }

                $ControllerBasePath = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'controller' . APP_DS;
                // find controller
                if (empty($uri_str))
                    $uri_str = $default_controller;

                $uri = explode('/', $uri_str);

                list($Controllerfile, $ControllerNames) = $this->_find_controller($uri);

                $ClassName = str_replace(".class.php", "", str_replace($ControllerBasePath, '', $Controllerfile));


                $classNameArr = $ControllerNames;
                $ClassName = '\\package\\controller\\' . str_replace("/", "\\", $ClassName);

                // remove controller item
                $uri = array_splice($uri, count($classNameArr));
                if (defined('URL_HTML_SUFFIX')) {
                    if (count($uri) > 0) {
                        $uri[count($uri) - 1] = str_ireplace(URL_HTML_SUFFIX, '', $uri[count($uri) - 1]);
                    }
                }

                // find action
                if (current($uri)) {
                    $action = array_shift($uri);
                } else if (method_exists($ClassName, '_' . $default_action)) {
                    // set default
                    $action = $default_action;
                } else {
                    throw new MethodNotFoundException(ctx()->getI18n()->getLang('error_router_title'), ctx()->getI18n()->getLang('error_router_controller_method', array($ClassName)));
                }
                // update router var
                $this->cache("shop_0_shop_router", array($c_path), array('ClassName' => $ClassName, 'action' => $action, 'uri' => $uri, 'classNameArr' => $classNameArr));
            } else {
                $ClassName = $routers['ClassName'];
                $action = $routers['action'];
                $uri = $routers['uri'];
                $classNameArr = $routers['classNameArr'];
            }
        }

        $route_datas = array(
            'controller' => $ClassName,
            'act' => $action,
            'action' => '_' . $action,
            'params' => $uri,
            'path' => self::getRouterPathByControllerClass($ClassName),
        );
        return $route_datas;
    }


}
