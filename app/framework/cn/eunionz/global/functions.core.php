<?php
declare(strict_types=1);

/**
 * Eunionz PHP Framework global core function libaray
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午9:47
 */

defined('APP_IN') or exit('Access Denied');

/**
 * 加载核心常量
 */
require_once __DIR__ . APP_DS . 'constants.core.php';


/**
 * 加载组件
 * @param $class  组件名，小写
 * @param bool $single 是否单例模式
 * @return object 组件对象
 */
function C(string $class, bool $single = false): ?\cn\eunionz\core\Component
{
    return (new \cn\eunionz\core\Kernel())->loadComponent($class, $single);
}

/**
 * 加载服务
 * @param $class  服务全路径类名或直接类名，如果使用直接类名时将与程序集联用用于确定类位置
 * @param bool $single 是否单例模式
 * @return object 服务对象
 */
function S(string $class, bool $single = false): ?\cn\eunionz\core\Service
{
    return (new \cn\eunionz\core\Kernel())->loadService($class, $single);
}

/**
 * 加载插件
 * @param $class  插件全路径类名或直接类名，如果使用直接类名时将与程序集联用用于确定类位置
 * @param bool $single 是否单例模式
 * @return object 插件对象
 */
function P(string $class, bool $single = false): ?\cn\eunionz\core\Plugin
{
    return (new \cn\eunionz\core\Kernel())->loadPlugin($class, $single);
}

/**
 * 加载模型
 * @param $class  模型全路径类名或直接类名，如果使用直接类名时将与程序集联用用于确定类位置
 * @param bool $single 是否单例模式
 * @return object 模型对象
 */
function M(string $class, bool $single = false): ?\cn\eunionz\core\Model
{
    return (new \cn\eunionz\core\Kernel())->loadModel($class, $single);
}

/**
 * 写日志
 * @param $level  日志等级 APP_ERROR APP_WARNING APP_DEBUG APP_INFO
 * @param $message 日志内容
 * @param $filename 日志文件名不包括路径和扩展名
 * @return bool
 */
function L(int $level = APP_ERROR, string $message, string $filename = ''): bool
{
    return (new \cn\eunionz\core\Kernel())->loadCore('log')->log($level, $message, $filename);
}

/**
 * 获取配置数据
 * @param $namespace  配置文件名称
 * @param $key  配置变量key名称，如果为空表示获取所有配置
 * @param $APP_ASSEMBLY_NAME 如果为null则使用当前程序集，''则为默认程序集，否则为指定程序集
 * @return mixed
 */
function F(string $namespace, string $key = '')
{
    return \cn\eunionz\core\Kernel::getConfig($namespace, $key);
}

/**
 * 获取当前进程上下文对像
 * @return \cn\eunionz\core\Context
 */
function ctx(): ?\cn\eunionz\core\Context
{
    return \cn\eunionz\core\Kernel::getContext();
}

/**
 * 向控制台输入调试信息不换行
 * @param int $level
 * @param $msg
 */
function console(string $msg, int $level = 0): string
{
    return \cn\eunionz\core\Kernel::console($msg, $level);
}

/**
 * 向控制台输入调试信息换行
 * @param int $level
 * @param $msg
 */
function consoleln(string $msg = '', int $level = 0): string
{
    return \cn\eunionz\core\Kernel::consoleln($msg, $level);
}

/**
 * 获取配置数据
 * @param $namespace  配置文件名称
 * @param $key  配置变量key名称，如果为空表示获取所有配置
 * @param $APP_ASSEMBLY_NAME 如果为null则使用当前程序集，''则为默认程序集，否则为指定程序集
 * @return mixed
 */
function getConfig(string $namespace, string $key = '')
{
    return \cn\eunionz\core\Kernel::getConfig($namespace, $key);
}


/**
 * 截断数字到指定位，0--表示截断到个数，个位之后的全部舍去，  1--截断到小数点后1位，其后全部为0，2--断到小数点后2位，其后全部为0
 *                                                      -1--截断到十位，其后全部为0， -2--截断到百位  依此类推
 * @param $number    要处理的数字,可正可负
 * @param $position 0--截断到个位 1--到十位  2--百位   -1--小数点后1位  -2--小数点后2位
 */
function truncate_number(float $number, int $position = 2): float
{
    $sign = 1;
    if ($number < 0) $sign = -1;
    $number = abs($number);
    $number = $number . '';
    if (strpos($number, '.') !== false) {
        $s = trim($number, '0');
    } else {
        $s = ltrim($number, '0');
    }
    $arr = explode('.', $s);
    $integer = $arr[0];
    $decimal = isset($arr[1]) ? $arr[1] : '';
    if ($position <= 0) {
        //对整数部份处理，返回整数
        $s1 = substr($integer, 0, strlen($integer) + $position);
        $s2 = substr($integer, strlen($integer) + $position);
        $s2 = str_pad('', strlen($s2), '0');
        return sprintf("%d", $sign * intval($s1 . $s2)) + 0;
    } else {
        //对小数部份处理，返回小数
        $s1 = substr($decimal, 0, $position);
        $number = abs($integer) + ('0.' . $s1);
        return sprintf("%.{$position}f", $sign * $number) + 0;
    }
}

function do_Post(string $url, string $fields, array $extraheader = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function do_PostJson(string $url, string $fields)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($fields)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function do_PostXml(string $url, string $fields)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: text/xml',
        'Content-Length: ' . strlen($fields)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


function do_Get(string $url, array $extraheader = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回:
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function do_Put(string $url, string $fields, array $extraheader = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    //curl_setopt($ch, CURLOPT_ENCODING, '');
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function do_Delete(string $url, string $fields, array $extraheader = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    //curl_setopt($ch, CURLOPT_ENCODING, '');
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * GET方式异步执行url
 * @param $domain 域名/主机
 * @param $port 端口
 * @param $url 要执行的url
 */
function fsock_open(string $domain, int $port, string $url): void
{
    //获取当前后台的地址
    $fp = fsockopen($domain, $port, $errno, $errstr, 10);
    if (!$fp) {
        consoleln("$errstr ($errno)<br />\n");
    } else {
        stream_set_blocking($fp, 0);
        $out = "GET " . $url . " HTTP/1.1\r\n";
        $out .= "host:" . $domain . "\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        fwrite($fp, $out);
        usleep(10000);
        fclose($fp);
    }
}


/**
 * 如果$string1以$string2为结尾则返回true  否则返回false
 * @param string $string1
 * @param string $string2
 * @return bool
 */
function endsWith(string $string1, string $string2): bool
{
    if (strlen($string1) < strlen($string2)) {  //若第一个字符串长度小于第二个的时候，必须指定返回false，
        return false;                                   //否则substr_compare遇到这种情况会返回0（即相当，与事实不符合）
    } else {
        return !substr_compare($string1, $string2, strlen($string1) - strlen($string2), strlen($string2));//从第一个字符串减去第二个字符串长度处开始判断
    }
}

/**
 * 如果$string1以$string2为开始则返回true  否则返回false
 * @param string $string1
 * @param string $string2
 * @return bool
 */
function startsWith(string $string1, string $string2): bool
{
    return strpos($string1, $string2) === 0;
}


/**
 * 加载应用程序中常量文件
 */
function loadConstrants(): void
{
    $constants_path = APP_PACKAGE_REAL_PATH . 'constants';
    if (is_dir($constants_path)) {
        $dir = @opendir($constants_path);
        if ($dir) {
            while ($filename = readdir($dir)) {
                if ($filename != '.' && $filename != '..') {
                    if (endsWith(strtolower($filename), '.constants.php') && is_file($constants_path . APP_DS . $filename)) {
                        @require_once $constants_path . APP_DS . $filename;
                    }
                }
            }
            @closedir($dir);
        }
    }
}

require_once APP_FRAMEWORK_REAL_PATH . 'cn' . APP_DS . 'eunionz' . APP_DS . 'core' . APP_DS . 'ClassAutoLoader.class.php';
require_once APP_REAL_PATH . 'vendor' . APP_DS . 'autoload.php';
spl_autoload_register(array('\cn\eunionz\core\ClassAutoLoader', 'autoload'));

