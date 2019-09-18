<?php
declare(strict_types=1);
namespace cn\eunionz\core;
defined('APP_IN') or exit('Access Denied');
/**
 * Eunionz PHP Framework 类自动加载器(将自动加载 *.class.php文件相关的class和interface)
 * 如果加载的类或接口是在以 com\Eunionz打头的命名空间中，则将从framework文件夹中加载
 * 否则将从package文件夹中进行加载
 * Created by PhpStorm.
 * User: liulin
 * Date: 15-4-30
 * Time: 上午9:47
 */

class ClassAutoLoader {

    public static function autoload($class){
        $lcase_class=trim(strtolower($class),"\\");
        if(startsWith($lcase_class,"cn\\eunionz\\")){
            $file = APP_PACKAGE_BASE_PATH . 'framework' . APP_DS . str_replace("\\",  APP_DS , $class) . '.class.php';
        }else{
            $file = APP_PACKAGE_BASE_PATH .  str_replace("\\",  APP_DS , $class) . '.class.php';
        }
        if(is_file($file)){
            include_once $file;
        }else{
            $file = str_replace(".class.php" , ".php" , $file);
            if(is_file($file)){
                include_once $file;
            }
        }
    }
}
