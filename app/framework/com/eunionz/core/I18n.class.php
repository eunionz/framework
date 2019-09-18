<?php
/**
 * Eunionz PHP Framework I18n class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\core;


class I18n extends Kernel
{
    /**
     * 核心语言包
     * @var array()
     */
    private $_core_langs = array();

    /**
     * 全局语言包
     * @var array()
     */
    private $_global_langs = array();

    /**
     * 控制器语言包
     * @var array()
     */
    private $_controller_langs = array();

    /**
     * 完全语言包
     * @var array()
     */
    private $_all_langs = array();

    /**
     * @return array
     */
    public function getAllLangs(): array
    {
        return $this->_all_langs;
    }

    /**
     * @param array $all_langs
     */
    public function setAllLangs(array $all_langs): void
    {
        $this->_all_langs = $all_langs;
    }


    public function __construct()
    {
        
    }

    /**
     * 获取当前浏览器语言
     */
    public final function getLanguage()
    {
        $language = '';
        if(ctx()) {
            if (ctx()->getRequest()->get('APP_LANGUAGE')) {
                $language = strtolower(ctx()->getRequest()->get('APP_LANGUAGE'));
            }
            if (ctx()->getRequest()->cookie('APP_LANGUAGE')) {
                $language = strtolower(ctx()->getRequest()->cookie('APP_LANGUAGE'));
            }
            if (ctx()->getSession()->session('APP_LANGUAGE')) {
                $language = strtolower(ctx()->getRequest()->session('APP_LANGUAGE'));
            }
            if (!$language) {
                if (ctx()->getRequest()->server('HTTP_ACCEPT_LANGUAGE')) {
                    $langs = explode(',', ctx()->getRequest()->server('HTTP_ACCEPT_LANGUAGE'));
                    if (!empty($langs[0])) {
                        $language = strtolower($langs[0]);
                    }
                }
            }

            if (!$language) {
                $language = strtolower(getConfig('app', 'APP_DEFAULT_LANGUAGE'));
            }
            $language = str_ireplace('-', '_', $language);
            if ($language == 'zh') $language = "zh_cn";
            if ($language == 'en') $language = "en_us";
        }else{
            $language = 'en_us';
        }
        return $language;
    }

    /**
     * 获取默认语言
     * @return mixed
     */
    public final function getDefaultLanguage()
    {
        if(ctx()){
            return str_replace('-', '_', strtolower(getConfig('app', 'APP_DEFAULT_LANGUAGE')));
        }else{
            return 'en_us';
        }

    }

    /**
     * 合并语言包数据
     */
    public final function mergeLang($langs)
    {
        $curr_language = $this->getLanguage();
        if(!isset($this->_all_langs[$curr_language])){
            $this->_all_langs[$curr_language] = [];
        }
        $this->_all_langs[$curr_language] = array_merge($this->_all_langs[$curr_language], $langs);
    }


    /**
     * 获取框架语言文件
     * @param $name 语言文件名
     */
    public final function getCoreLang($name, $key = '')
    {
        $curr_language = $this->getLanguage();
        if (!isset($this->_core_langs[$curr_language][$name])) {
            $file_path = APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'language' . APP_DS . $name . '.' . $this->getLanguage() . '.php';


            if (!is_file($file_path)) {
                $file_path = APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . 'language' . APP_DS . $name . '.' . $this->getDefaultLanguage() . '.php';
                if (!is_file($file_path)) {
                    $this->_core_langs[$curr_language][$name] = array();
                } else {
                    $this->_core_langs[$curr_language][$name] = require($file_path);
                    self::mergeLang($this->_core_langs[$curr_language][$name]);
                }
            } else {
                $this->_core_langs[$curr_language][$name] = require($file_path);
                self::mergeLang($this->_core_langs[$curr_language][$name]);
            }

        }

        if (!$key)
            return $this->_core_langs[$curr_language][$name];

        if (!isset($this->_core_langs[$curr_language][$name][$key]))
            return '';
        return $this->_core_langs[$curr_language][$name][$key];

    }

    /**
     * 获取全局语言文件
     * @param $name 语言文件名
     */
    public final function getGlobalLang($name, $key = '')
    {
        $curr_language = $this->getLanguage();

        if (!isset($this->_global_langs[$curr_language][$name])) {
            $file_path = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'language' . APP_DS . strtolower($name) . '.' . $this->getLanguage() . '.php';


            if (!file_exists($file_path)) {
                $file_path = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'language' . APP_DS . strtolower($name) . '.' . $this->getDefaultLanguage() . '.php';

                if (!file_exists($file_path)) {
                    $this->_global_langs[$curr_language][$name] = array();
                } else {
                    $this->_global_langs[$curr_language][$name] = require($file_path);
                    self::mergeLang($this->_global_langs[$curr_language][$name]);
                }
            } else {
                $this->_global_langs[$curr_language][$name] = require($file_path);
                self::mergeLang($this->_global_langs[$curr_language][$name]);
            }
        }

        if (!$key)
            return $this->_global_langs[$curr_language][$name];

        if (!isset($this->_global_langs[$curr_language][$name][$key]))
            return '';
        return $this->_global_langs[$curr_language][$name][$key];

    }

    /**
     * 获取控制器语言文件
     * @param $name 语言文件名
     */
    public final function getControllerLang($classname, $key = '')
    {
        $curr_language = $this->getLanguage();

        if (!isset($this->_controller_langs[$curr_language][$classname])) {

            $file_path = APP_PACKAGE_BASE_PATH . trim(str_replace("\\", APP_DS, $classname), APP_DS) . '.' . $this->getLanguage() . '.php';


            if (!file_exists($file_path)) {
                $file_path = APP_PACKAGE_BASE_PATH . trim(str_replace("\\", APP_DS, $classname), APP_DS) . '.' . $this->getDefaultLanguage() . '.php';
                if (!file_exists($file_path)) {
                    $this->_controller_langs[$curr_language][$classname] = array();
                } else {
                    $this->_controller_langs[$curr_language][$classname] = require($file_path);
                    self::mergeLang($this->_controller_langs[$curr_language][$classname]);
                }
            } else {
                $this->_controller_langs[$curr_language][$classname] = require($file_path);
                self::mergeLang($this->_controller_langs[$curr_language][$classname]);
            }

        }

        if (!$key)
            return $this->_controller_langs[$curr_language][$classname];

        if (!isset($this->_controller_langs[$curr_language][$classname][$key]))
            return '';
        return $this->_controller_langs[$curr_language][$classname][$key];
    }

    /**
     * 获取语言数据
     * @param string $key
     */
    public final function getLang($key ='' , $args = null)
    {
        $curr_language = $this->getLanguage();
        $params = [];
        if ($args) {
            if(!is_array($args)){
                $params = array($args);
            }else{
                $params = $args;
            }
        }
        if (empty($key)) {
            return isset($this->_all_langs[$curr_language])?$this->_all_langs[$curr_language]:[];
        }

        if (isset($this->_all_langs[$curr_language][$key])) {
            $data = $this->_all_langs[$curr_language][$key];
            foreach ($params as $index => $value) {
                $data = str_replace('{' . $index . '}', $value, $data);
            }
            $data = preg_replace("/\\{[0-9]+\\}/", '', $data);
            return $data;
        } else {
            return $key;
        }
    }

}