<?php
/**
 * Eunionz PHP Framework Cache component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\blade;


defined('APP_IN') or exit('Access Denied');

/**
 * Blade视图类
 * Class Blade
 */
class Blade extends \cn\eunionz\core\Component
{

    private $_view_dir;

    public $_view_vars;


    public function __construct()
    {
        require_once __DIR__ . "/src/ViewFinderInterface.php";
        require_once __DIR__ . "/src/Autoloader.php";
        require_once __DIR__ . "/src/Factory.php";
        require_once __DIR__ . "/src/Filesystem.php";
        require_once __DIR__ . "/src/FileViewFinder.php";
        require_once __DIR__ . "/src/helpers.php";
        require_once __DIR__ . "/src/View.php";
        require_once __DIR__ . "/src/Compilers/CompilerInterface.php";
        require_once __DIR__ . "/src/Compilers/Compiler.php";
        require_once __DIR__ . "/src/Compilers/BladeCompiler.php";
        require_once __DIR__ . "/src/Engines/EngineInterface.php";
        require_once __DIR__ . "/src/Engines/PhpEngine.php";
        require_once __DIR__ . "/src/Engines/CompilerEngine.php";

        $config = self::getConfig('view');
        $this->_view_vars = $config['VIEW_VARS'];
        $this->_view_vars['APP_PATH'] = APP_PATH;
        $this->_view_vars['APP_VERSION'] = self::getConfig('version', 'APP_VERSION');

        $this->_view_vars['APP_DEVENV'] = self::getConfig('app', 'APP_DEVENV');
        $this->_view_vars['APP_DS'] = APP_DS;
        $this->_view_vars['Router'] = ctx()->getRouter();
        $this->_view_vars['APP_PARTITION_NAME'] = strtolower(ctx()->getPartitionName());
        $this->_view_vars['APP_THEME'] = ctx()->getTheme();
        $this->_view_vars['APP_LANGUAGE'] = str_ireplace('_', '-', $this->getLanguage());

    }

    /**
     * set template var
     *
     * set var to template
     *
     * @param $key string
     * @param $value string
     */
    public function __set($key, $value)
    {
        if (!property_exists($this, $key))
            $this->_view_vars[$key] = $value;
    }


    /**
     * 根据当前应用，当前分区，当前主题以及指定的视图名称，设置指定主题网站路径以及物理路径
     * @param $page  不包含主题以及扩展名称的视图名,视图扩展名为.tpl或.tpl.php
     */
    private function setAppThemePath($page)
    {
        if (!$page) return null;
        //获取分区名称
        $partition_name = strtolower(ctx()->getPartitionName());
        //获取主题名称
        $app_theme = ctx()->getTheme();
        //将主题名称存入视图变量

        //独立视图文件
        $single_file = ctx()->getAppStorageRealPath() . 'view' . APP_DS . $app_theme . APP_DS . ltrim(str_ireplace('/', APP_DS, $page), APP_DS);

        //判断分区是否为后台管理分区，如果为后台管理分区则为统一视图文件，否则各分站独立视图文件
        if (in_array($partition_name, self::getConfig('app', 'APP_MANAGE_PARTITIONS')) || !(is_file($single_file . '.tpl') || is_file($single_file . '.tpl.php'))) {
            //是后台管理分区 或独立视图文件不存在 则使用统一视图文件
            $this->_view_dir = APP_PACKAGE_BASE_PATH . 'package' . APP_DS . 'view';
            $this->_view_vars['APP_THEME_PATH'] = rtrim(APP_PATH, '/') . '/' . APP_PROGRAM_NAME . '/package/view/' . $app_theme;
            $this->_view_vars['APP_THEME_PATH'] = str_replace('//', '/', $this->_view_vars['APP_THEME_PATH']);
            $this->_view_vars['APP_THEME_PATH_NO_CDN'] = $this->_view_vars['APP_THEME_PATH'];
            if (self::getConfig('app', 'APP_STATIC_CONTENT_CDN_SITE_DOMAIN_URLS')) {
                $this->_view_vars['APP_THEME_PATH'] = trim(self::getConfig('app', 'APP_STATIC_CONTENT_CDN_SITE_DOMAIN_URLS'), '/') . '/' . ltrim($this->_view_vars['APP_THEME_PATH'], '/');
            }
            $this->_view_vars['APP_THEME_REALPATH'] = $this->_view_dir . APP_DS . $app_theme;
        } else {
            //不是后台管理分区且独立视图文件存在，则使用独立视图文件
            $this->_view_dir = ctx()->getAppStorageRealPath() . 'view';
            $this->_view_vars['APP_THEME_PATH_NO_CDN'] = rtrim(APP_PATH, '/') . '/' . APP_STORAGE_NAME . '/' . ctx()->getSiteName() . '/view/' . $app_theme;
            if (self::getConfig('app', 'APP_STATIC_CONTENT_CDN_SITE_DOMAIN_URLS')) {
                //如果启用了CDN
                $this->_view_vars['APP_THEME_PATH'] = trim(self::getConfig('app', 'APP_STATIC_CONTENT_CDN_SITE_DOMAIN_URLS'), '/') . rtrim(APP_PATH, '/') . '/' . APP_STORAGE_NAME . '/' . ctx()->getSiteName() . '/view/' . $app_theme;
            } else {
                //如果没有启用CDN
                $this->_view_vars['APP_THEME_PATH'] = rtrim(APP_PATH, '/') . '/' . APP_STORAGE_NAME . '/' . ctx()->getSiteName() . '/view/' . $app_theme;
            }
            $this->_view_vars['APP_THEME_REALPATH'] = ctx()->getAppStorageRealPath() . 'view' . APP_DS . $app_theme;
        }
    }


    public function display($page, & $model = array(), $return = false)
    {


        $this->setAppThemePath($page);

        $text = '';
        if (!empty($page)) {

            // 视图文件目录，这是数组，可以有多个目录
            $view_dirs = [
                $this->_view_vars['APP_THEME_REALPATH'],
                ctx()->getAppRuntimeRealPath() . 'blade'
            ];


            if (!file_exists(ctx()->getAppRuntimeRealPath() . 'blade')) {
                @mkdir(ctx()->getAppRuntimeRealPath() . 'blade');
            }
            // 编译文件缓存目录
            $cachePath = ctx()->getAppRuntimeRealPath() . 'cache' . APP_DS . 'blade_compiler';
            if (!file_exists($cachePath)) {
                @mkdir($cachePath);
            }

            $compiler = new \Xiaoler\Blade\Compilers\BladeCompiler($cachePath);

            // 如过有需要，你可以添加自定义关键字
            $compiler->directive('datetime', function ($timestamp) {
                return preg_replace('/\((.+?)\)/', '<?php echo date("Y-m-d H:i:s", $1); ?>', $timestamp);
            });

            // 如过有需要，你可以添加自定义关键字
            $compiler->directive('getLang', function ($key) {
                return str_replace('#29;', ')', str_replace('#28;', '(', preg_replace('/\((.+?)\)/', '<?php echo $core->getLang($1); ?>', $key)));
            });

            // 如过有需要，你可以添加自定义关键字
            $compiler->directive('csrftoken', function () {
                return '<?php echo $core->csrftoken(); ?>';
            });

            $engine = new \Xiaoler\Blade\Engines\CompilerEngine($compiler);
            $finder = new \Xiaoler\Blade\FileViewFinder($view_dirs);

            // 如果需要添加自定义的文件扩展，使用以下方法
            $finder->addExtension('tpl');
            $finder->addExtension('tpl.php');

            // 实例化 Factory
            $factory = new \Xiaoler\Blade\Factory($engine, $finder);

            // 渲染视图并输出
            $this->_view_vars = array_merge($this->_view_vars, $model);
            $this->_view_vars['__env'] = $factory;
            $this->_view_vars['core'] = $this;
            $model = $this->_view_vars;

            $text = $factory->make($page, $this->_view_vars)->render();
        }
        if ($return) return $text;
        ctx()->getResponse()->ob_write($text);
        return $text;

    }

}