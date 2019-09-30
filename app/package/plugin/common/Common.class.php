<?php
/**
 * EUnionZ PHP Framework Common Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\common;


defined('APP_IN') or exit('Access Denied');


/**
 * 通用插件类，工具类
 * Class Common Plugin
 */
class Common extends \cn\eunionz\core\Plugin
{

    public function hmac($data, $key)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac('md5', $data, $key);
        }
        $key = (strlen($key) > 64) ? pack('H32', 'md5') : str_pad($key, 64, chr(0));
        $ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);
        return md5($opad . pack('H32', md5($ipad . $data)));
    }

    /**
     *
     * 将jQuery DataTables表格 ajax传递过来的数据转换为key,value关联数组
     *
     * @param $aoData   jQuery DataTables表格 ajax传递过来的数据  $aoData格式为：array(obj1,obj2,obj3)，其中 obj1-obj3的内部结构为
     *                  obj1->name  名称    obj1->value   值
     *
     * @return array  转换之后的关联数组  格式为  array( "name"=>"value","name"=>"value","name"=>"value" )
     */
    public function getaoData($aoData)
    {
        $arr = array();
        foreach ($aoData as $v) {
            $arr[$v->name] = $v->value;
        }

        return $arr;
    }

    /**
     * 获取协议类型
     * @return string
     */
    public function get_scheme()
    {
        $SERVER = ctx()->server();

        $scheme = isset($SERVER['REQUEST_SCHEME']) ? $SERVER['REQUEST_SCHEME'] : '';
        if (empty($scheme)) {
            $https_scheme = isset($SERVER['HTTPS']) ? $SERVER['HTTPS'] : '';
            $https_scheme = strtolower($https_scheme);
            $https_scheme = $https_scheme == 'on' ? 'https' : 'http';
            $scheme = $https_scheme;
        }
        return $scheme;
    }

    /**
     * 向页面中输出js，并决定是否中止程序
     *
     * @param      $charset  字符集  utf-8 或者  gbk
     * @param      $code     js代码
     * @param bool $is_exit 是否退出
     */
    public function write_js($code, $charset = 'utf-8', $is_exit = true)
    {
        header("Content-Type: text/html;charset=" . $charset);
        echo "<script type='text/javascript'>";
        echo $code;
        echo "</script>";
        if ($is_exit) {
            exit;
        }
    }

    /**
     * @basename 的升级版  返回中文名可以
     * @param $filename
     * @return string
     */
    function get_basename($filename)
    {
        return preg_replace('/^.+[\\\\\\/]/', '', $filename);
    }

    /**
     * 根据时间问好
     * @return string
     */
    public function hello()
    {
        $h = date('G');
        if ($h < 11) {
            return '早上好';
        } else {
            if ($h < 13) {
                return '中午好';
            } else {
                if ($h < 18) {
                    return '下午好';
                } else {
                    return '晚上好';
                }
            }
        }
    }

    public function get_security_level(array $data)
    {
        $level = 0;
        foreach ($data as $v) {
            if (!empty($v)) {
                $level++;
            }
        }
        if ($level <= 1) {
            $level = 0;
        } else {
            if ($level <= 2) {
                $level = 1;
            } else {
                $level = 2;
            }
        }


        $arr = self::getConfig('params', 'SECURITY_LEVELS');

        return $arr[$level];
    }


    /**
     * //获取客户端ip地址
     * @return string
     */
    public function getIP()
    {
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else {
                if (getenv("REMOTE_ADDR")) {
                    $ip = getenv("REMOTE_ADDR");
                } else {
                    $ip = "127.0.0.1";
                }
            }
        }

        //兼容处理IPV6的问题，::ffff:218.89.222.137->218.89.222.137
        $ip_reg = '((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))';
        preg_match_all($ip_reg, $ip, $list_tmp);
        $ip = $list_tmp[0][0];
        return $ip;
    }

    /**
     * 随机数
     *
     * @param        $l
     * @param string $c
     *
     * @return string
     */
    function mt_rand_str($l, $c = '1234567890')
    {
        for ($s = '', $cl = strlen($c) - 1, $i = 0; $i < $l; $s .= $c[mt_rand(0, $cl)], ++$i) {
            ;
        }

        return $s;
    }

    /**
     * 根据用户生成10位随机码
     *
     * @param $id
     *
     * @return string
     */
    public function exchange_random_code($id)
    {
        $sid = strval(decoct($id)) . '9';

        return $sid . $this->mt_rand_str(10 - strlen($sid));
    }

    /**
     * 隐藏手机号码的中间4位 如果不是手机直接返回
     *
     * @param        $mobile  手机号码
     * @param string $char 隐藏字符
     *
     * @return string  返回隐藏之后的字符串
     */
    public function hide_mobile_middle_4($mobile, $char = "*", $all_hide = false)
    {
        if (!$this->loadPlugin('Validation')->vPhone($mobile)) {
            return $mobile;
        }

        return substr($mobile, 0, 3) . $char . $char . $char . $char . substr($mobile, 7);
    }

    /**
     * 隐藏电子邮箱中@以及紧挨着的.之间的字符 如果不是电子邮箱直接返回
     *
     * @param        $mail  电子邮箱
     * @param string $char 隐藏字符
     *
     * @return string  返回隐藏之后的字符串
     */
    public function hide_email_part($mail, $char = "*")
    {
        if (!$this->loadPlugin('Validation')->vEmail($mail)) {
            return $mail;
        }
        preg_match("/@(.+?)\\./i", $mail, $arr);
        $num = strlen($arr[1]);
        $s = "";
        for ($i = 0; $i < $num; $i++) {
            $s .= $char;
        }

        return str_replace($arr[1], $s, $mail);
    }

    /**
     * @param $user array 用户信息
     *
     * @return mixed 返回用户昵称
     */
    public function get_nickname($user, $real_name = 0)
    {
        if (isset($user['bas_type']) && ($user['bas_type'] == 2 || $user['bas_type'] == 3 || $user['bas_type'] == 5)) {
            return $user['per_name'] ? $user['per_name'] : $user['per_nickname'];
        }
        return $real_name ? ($user['per_name'] ? $user['per_name'] : $user['per_nickname']) : $user['per_nickname'];
    }

    /**
     * 二维数组转换成一维数组
     *
     * @param $arr
     *
     * @return array
     */
    public function arrayChange($arr)
    {
        static $_arr;
        foreach ($arr as $v) {
            if (is_array($v)) {
                $this->arrayChange($v);
            } else {
                $_arr[] = $v;
            }
        }

        return $_arr;
    }

    /**
     * sql,字符串过滤
     *
     * @param $value
     *
     * @return mixed|string
     */
    function value_check($value)
    {
        if (!get_magic_quotes_gpc()) { // 判断magic_quotes_gpc是否为打开
            $value = addslashes($value); // 进行magic_quotes_gpc没有打开的情况对提交数据的过滤
        }
        $value = str_replace("_", "\_", $value);
        $value = str_replace("%", "\%", $value);
        //$value = str_replace(",", "", $value);
        //$value = str_replace("*", "", $value);
        $value = nl2br($value); // 回车转换
        $value = htmlspecialchars($value); // html标记转换
        return $value;
    }

    /**
     * 数组拆分组合字段（sql insert）
     *
     * @param $arr 二维数组
     *
     * @return string
     */
    function arr_to_sqlstr($arr)
    {
        $values = array();
        foreach ($arr as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                if ($key == 7) {
                    $value[] = $val == null ? 'null' : "'" . $val . "'";
                } else {
                    $value[] = "'" . $val . "'";
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        $str = implode(',', $values);

        return $str;
    }

    /**
     * 时间显示转换
     * @param $old_time
     *
     * @return string
     */
    function time_format($old_time)
    {
        if (!$old_time) {
            return '';
        }
        $now_time = time();
        $t = $now_time - $old_time;
        $str = '';
        if ($t > 365 * 24 * 60 * 60) {
            $str = floor($t / (365 * 24 * 60 * 60)) . '年前';
        } else {
            if ($t < 60) {
                $str = $t . '秒前';
            } else {
                $_t = $t / (24 * 60 * 60 * 30);
                if ($_t >= 1) {
                    $str = floor($_t) . '月前';
                } else {
                    $_t = $_t * 30;
                    if ($_t < 1) {
                        $_t = $_t * 24;
                        if ($_t < 1) {
                            $str = floor($_t * 60) . '分钟前';
                        } else {
                            $str = floor($_t) . '小时前';
                        }
                    } else {
                        $str = floor($_t) . '天前';
                    }
                }
            }
        }
        return $str;
    }


    /**
     * 截取utf-8 字符串方法
     * @param $str  源utf-8字符串
     * @param $start 开始位置
     * @param $len  截取长度
     * @param $encode
     * @return string 新的字符串
     */

    function subString($str, $start, $len, $encode = 'utf8', $appended = '...')
    {
        if ($encode != 'utf8') {
            $str = mb_convert_encoding($str, 'utf8', $encode);
        }
        $osLen = mb_strlen($str);
        if ($osLen * 3 <= $len) {
            return $str;
        }
        $string = mb_substr($str, $start, $len, 'utf8');
        $sLen = mb_strlen($string, 'utf8');
        $bLen = strlen($string);
        $sCharCount = ($sLen * 3 - $bLen) / 2;
        if ($osLen <= $sCharCount + $len) {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $len, $osLen, 'utf8'));//将中英混合字符串分割成数组（UTF8下有效）
        } else {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $len, $sCharCount, 'utf8'));
        }
        foreach ($arr as $value) {
            if (ord($value) < 128 && ord($value) > 0) {
                $sCharCount = $sCharCount - 1;
            } else {
                $sCharCount = $sCharCount - 2;
            }
            if ($sCharCount < 0) {
                break;
            }
            $string .= $value;
        }
        return $string . $appended;
    }

    /**
     * 获得用户的真实IP地址
     *
     * @access  public
     * @return  string
     */
    function get_ip()
    {
        $ip = "";
        $ips = array();
        $SERVER = ctx()->server();
        if (!empty($SERVER["HTTP_CLIENT_IP"])) {
            $ip = $SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($SERVER['HTTP_X_FORWARDED_FOR'])) { //获取代理ip
            $ips = explode(',', $SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if ($ip != "") {
            $ips = array_unshift($ips, $ip);
        }

        $count = count($ips);
        for ($i = 0; $i < $count; $i++) {
            if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) {//排除局域网ip
                $ip = $ips[$i];
                break;
            }
        }
        //$tip = ($_SERVER['REMOTE_ADDR']="127.0.0.1") ? $ip : $_SERVER['REMOTE_ADDR'];
        $tip = (empty($ip)) ? (isset($SERVER['REMOTE_ADDR']) ? $SERVER['REMOTE_ADDR'] : '127.0.0.1') : $ip;

        //兼容处理IPV6的问题，::ffff:218.89.222.137->218.89.222.137
        $ip_reg = '((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))';
        preg_match_all($ip_reg, $tip, $list_tmp);
        $tip = $list_tmp[0][0];

        if ($tip == "127.0.0.1") { //获得本地真实IP
            return $tip;//$this->get_onlineip();
        } else {
            return $tip;
        }
    }

    /**
     * 获得用户的真实IP地址
     *
     * @access  public
     * @return  string
     */
    function get_ip_old()
    {
        static $realip = NULL;
        $SERVER = ctx()->server();
        if ($realip !== NULL) {
            return $realip;
        }

        if (isset($SERVER)) {
            if (isset($SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $SERVER['HTTP_X_FORWARDED_FOR']);

                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
                foreach ($arr AS $ip) {
                    $ip = trim($ip);

                    if ($ip != 'unknown') {
                        $realip = $ip;

                        break;
                    }
                }
            } elseif (isset($SERVER['HTTP_CLIENT_IP'])) {
                $realip = $SERVER['HTTP_CLIENT_IP'];
            } else {
                if (isset($SERVER['REMOTE_ADDR'])) {
                    $realip = $SERVER['REMOTE_ADDR'];
                } else {
                    $realip = '0.0.0.0';
                }
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $realip = getenv('HTTP_CLIENT_IP');
            } else {
                $realip = getenv('REMOTE_ADDR');
            }
        }

        preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

        //兼容处理IPV6的问题，::ffff:218.89.222.137->218.89.222.137
        $ip_reg = '((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d))))';
        preg_match_all($ip_reg, $realip, $list_tmp);
        $realip = $list_tmp[0][0];

        return $realip;
    }

    /**
     * 获取用户头像
     * @param $bas_uc_uid
     * @param $per_face
     */
    public function get_per_face($bas_uc_uid, $per_face)
    {
        if (defined('UC_AVATAR_PATH')) {
            //检查有没有缓存，如果有则直接使用缓存
            return UC_AVATAR_PATH . 'avatar.php?uid=' . $bas_uc_uid . '&size=big';


        } else {
            if (self::getConfig('UPLOAD_PREFIX_URL')) {
                //远程上传图片模式
                if (!$this->remote_file_exists(self::getConfig('UPLOAD_PREFIX_URL') . '/runtime/' . self::getConfig('app', 'UPLOAD_PATH') . '/' . $per_face)) {
                    return APP_PATH . 'images/headda.gif';
                } else {
                    return self::getConfig('UPLOAD_PREFIX_URL') . '/runtime/' . self::getConfig('app', 'UPLOAD_PATH') . '/' . $per_face;
                }

            } else {
                //本地上传图片模式
                if (!is_file(ctx()->getAppRuntimeRealPath() . self::getConfig('app', 'UPLOAD_PATH') . APP_DS . $per_face) || !file_exists(ctx()->getAppRuntimeRealPath() . self::getConfig('app', 'UPLOAD_PATH') . APP_DS . $per_face)) {
                    return APP_PATH . 'images/headda.gif';
                } else {
                    return APP_PATH . 'runtime/' . self::getConfig('app', 'UPLOAD_PATH') . '/' . $per_face;
                }
            }

        }
    }

    /**
     * 检查手机验证码是否正确
     * @return bool
     */
    public function check_mobile_checkcode($mobile, $checkcode)
    {
        $SESSION = ctx()->session();
        $MOBILE_CHECKCODE_TIMEOUT = self::getConfig('params', 'MOBILE_CHECKCODE_TIMEOUT');
        if (isset($SESSION['s' . $mobile]) && is_array($SESSION['s' . $mobile])) {
            if (time() - $SESSION['s' . $mobile]['time'] >= $MOBILE_CHECKCODE_TIMEOUT) {
                if (isset($SESSION['s' . $mobile])) {
                    unset($SESSION['s' . $mobile]);
                }
                return false;
            }
            if ($checkcode != $SESSION['s' . $mobile]['checkcode']) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 检查是否为手机访问
     * @return bool
     */
    function isMobileSite()
    {
        $SERVER = ctx()->server();
        $user_agent = $SERVER['HTTP_USER_AGENT'];
        $mobile_agents = Array("240x320", "acer", "acoon",
            "acs-", "abacho", "ahong", "airness", "alcatel",
            "amoi", "android", "anywhereyougo.com",
            "applewebkit/525", "applewebkit/532", "asus",
            "audio", "au-mic", "avantogo", "becker", "benq",
            "bilbo", "bird", "blackberry", "blazer", "bleu",
            "cdm-", "compal", "coolpad", "danger", "dbtel",
            "dopod", "elaine", "eric", "etouch", "fly ",
            "fly_", "fly-", "go.web", "goodaccess",
            "gradiente", "grundig", "haier", "hedy",
            "hitachi", "htc", "huawei", "hutchison",
            "inno", "ipad", "ipaq", "ipod", "jbrowser",
            "kddi", "kgt", "kwc", "lenovo", "lg ", "lg2",
            "lg3", "lg4", "lg5", "lg7", "lg8", "lg9", "lg-",
            "lge-", "lge9", "longcos", "maemo", "mercator",
            "meridian", "micromax", "midp", "mini", "mitsu",
            "mmm", "mmp", "mobi", "mot-", "moto", "nec-",
            "netfront", "newgen", "nexian", "nf-browser",
            "nintendo", "nitro", "nokia", "nook", "novarra",
            "obigo", "palm", "panasonic", "pantech", "philips",
            "phone", "pg-", "playstation", "pocket", "pt-",
            "qc-", "qtek", "rover", "sagem", "sama", "samu",
            "sanyo", "samsung", "sch-", "scooter", "sec-",
            "sendo", "sgh-", "sharp", "siemens", "sie-",
            "softbank", "sony", "spice", "sprint", "spv",
            "symbian", "tablet", "talkabout", "tcl-",
            "teleca", "telit", "tianyu", "tim-", "toshiba",
            "tsm", "up.browser", "utec", "utstar", "verykool",
            "virgin", "vk-", "voda", "voxtel", "vx", "wap",
            "wellco", "wig browser", "wii", "windows ce",
            "wireless", "xda", "xde", "zte");
        $is_mobile = false;
        foreach ($mobile_agents as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }

        if (!$is_mobile) {
            $regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|";
            $regex_match .= "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|";
            $regex_match .= "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|";
            $regex_match .= "symbian|smartphone|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|";
            $regex_match .= "jig\sbrowser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320×320|240×320|176×220";
            $regex_match .= ")/i";
            return isset($SERVER['HTTP_X_WAP_PROFILE']) || isset($SERVER['HTTP_PROFILE']) || preg_match($regex_match, strtolower($SERVER['HTTP_USER_AGENT']));

        }
        return $is_mobile;
    }

    //二位数组去重
    function assoc_unique($arr, $key)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr))//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            {
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }


    public function endsWith($string1, $string2)
    {
        if (strlen($string1) < strlen($string2)) {  //若第一个字符串长度小于第二个的时候，必须指定返回false，
            return false;                                   //否则substr_compare遇到这种情况会返回0（即相当，与事实不符合）
        } else {
            return !substr_compare($string1, $string2, strlen($string1) - strlen($string2), strlen($string2));//从第一个字符串减去第二个字符串长度处开始判断
        }
    }


    public function startsWith($str, $needle)
    {
        return stripos($str, $needle) === 0;
    }


    /**
     * 检查远程文件是否存在
     * @param $remote_file_path 远程文件路径
     */
    public function remote_file_exists($remote_file_path)
    {
        $rs = false;
        try {
            $UPLOAD_REOMTE_FTP_SERVER = self::getConfig('app', 'UPLOAD_REOMTE_FTP_SERVER');
            if ($UPLOAD_REOMTE_FTP_SERVER) {
                $fp = @fopen($remote_file_path, "r");
                if ($fp) {
                    $rs = true;
                }
                @fclose($fp);
            } else if (self::getConfig('app', 'UPLOAD_REOMTE_SHARE_DIR')) {
                $UPLOAD_REOMTE_SHARE_DIR = self::getConfig('app', 'UPLOAD_REOMTE_SHARE_DIR');
                if (strpos($UPLOAD_REOMTE_SHARE_DIR, '/') === false) {
                    $UPLOAD_REOMTE_SHARE_DIR = APP_REAL_PATH . $UPLOAD_REOMTE_SHARE_DIR;
                }
                return file_exists($UPLOAD_REOMTE_SHARE_DIR . self::getConfig('app', 'UPLOAD_REMOTE_INIT_DIR') . '/' . basename($remote_file_path));
            } else {
                return file_exists(APP_REAL_PATH . substr($remote_file_path, 1));
            }
        } catch (\Exception $err) {

        }
        return $rs;
    }


    /**
     * 随机密码生成
     * @param int $length 密码长度
     *
     * @return string
     */
    public function generatePassword($length = 8)
    {
        $chars = array_merge(
            range('a', 'z'),
            range('A', 'Z')
        );
        $num_chars = array_merge(range(0, 9));

        shuffle($chars);
        shuffle($num_chars);
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            if ($i % 2 == 0) {
                $password .= $chars[rand(0, count($chars) - 1)];
            } else {
                $password .= $num_chars[rand(0, count($num_chars) - 1)];
            }

        }
        return $password;
    }

    public function array2string($keys)
    {
        $s = "";
        foreach ($keys as $k => $v) {
            $s .= $k;
            if (is_array($v)) {
                $s .= $this->array2string($v);
            } else {
                $s .= $v;
            }
        }
        return $s;
    }

    /**
     * php文件管理代码检查，主要检查如下代码
     * phpinfo、eval、copy、rmdir、unlink、delete、fwrite、chmod、fgetc、fgetcsv、fgets、fgetss、file、file_get_contents、fread、readfile、ftruncate、file_put_contents、fputcsv、fputs、move_uploaded_file
     * @param $content
     * @return true--包含，false--不包含
     */
    public function php_file_manage_code_check($content)
    {
        $not_functions = array(
            'system', 'exec', 'shell_exec', 'passthru', 'proc_open', 'proc_close', 'proc_get_status', 'checkdnsrr', 'getmxrr',
            'getservbyname', 'getservbyport', 'syslog', 'popen', 'show_source', 'highlight_file', 'dl', 'socket_listen',
            'socket_create', 'socket_bind', 'socket_accept', 'socket_connect', 'stream_socket_server', 'stream_socket_accept',
            'stream_socket_client', 'ftp_connect', 'ftp_login', 'ftp_pasv', 'ftp_get', 'sys_getloadavg', 'disk_total_space',
            'disk_free_space', 'posix_ctermid', 'posix_get_last_error', 'posix_getcwd', 'posix_getegid', 'posix_geteuid',
            'posix_getgid', 'posix_getgrgid', 'posix_getgrnam', 'posix_getgroups', 'posix_getlogin', 'posix_getpgid',
            'posix_getpgrp', 'posix_getpid', 'posix_getppid', 'posix_getpwnam', 'posix_getpwuid', 'posix_getrlimit',
            'posix_getsid', 'posix_getuid', 'posix_isatty', 'posix_kill', 'posix_mkfifo', 'posix_setegid', 'posix_seteuid',
            'posix_setgid', 'posix_setpgid', 'posix_setsid', 'posix_setuid', 'posix_strerror', 'posix_times', 'posix_ttyname',
            'posix_uname', 'phpinfo', 'eval', 'opendir', 'fsockopen', 'fopen', 'copy', 'rmdir', 'unlink', 'delete', 'fwrite',
            'chmod', 'fgetc', 'fgetcsv', 'fgets', 'fgetss', 'file', 'file_get_contents', 'fread', 'readfile', 'ftruncate',
            'file_put_contents', 'fputcsv', 'fputs', 'move_uploaded_file', 'curl');
        foreach ($not_functions as $func) {
            $sub_regex = $func . " *\\(";
            if (preg_match("/{$sub_regex}/i", $content)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 删除style中的height和width
     * @param $content
     */
    public function remove_style_widthAndHeight($content)
    {
        return preg_replace('/(<img[^>]*?)(style=".+?")([^>]*?>)/i', "$1$3", $content);
    }

    /**
     * 生成guid
     * @return string
     */
    public function guid()
    {
        if (function_exists('com_create_guid')) {
            return trim(com_create_guid(), '{}');
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
            return $uuid;
        }
    }


    /**
     * 扩展代码转对象数组
     * @param $arr
     */
    public function extension_code2obj($arr)
    {
        $rs = array();
        foreach ($arr as $key => $val) {
            $rs[] = array('number' => $key, 'value' => $val);
        }
        return $rs;
    }

    /**
     * 计算两个经纬度点的距离
     * @param $latitude1  第1点纬度
     * @param $longitude1  第1点经度
     * @param $latitude2  第2点纬度
     * @param $longitude2 第2点经度
     * @return array  返回 miles--英里  feet--英尺  yards--码  kilometers--千米  meters--米
     */
    public function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2)
    {

        //file_put_contents('c:/1.txt','点1：lat='.$latitude1.',lng='.$longitude1.' 点2：lat='.$latitude2.',lng='.$longitude2);
        $theta = $longitude1 - $longitude2;
        $miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        $meters = $kilometers * 1000;
        //return compact('miles','feet','yards','kilometers','meters');
        return $kilometers;
    }


    /**
     * 删除文件夹及其下所有文件夹及文件
     * @param $dir  要删除的文件夹路径
     * @param $del_self  是否删除文件夹本身
     * @return bool  true--成功  false--失败
     */
    function deldir($dir, $del_self = true)
    {
        //先删除目录下的文件：
        $dh = @opendir($dir);
        if ($dh) {
            while ($file = readdir($dh)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $dir . "/" . $file;
                    if (!is_dir($fullpath)) {
                        @unlink($fullpath);
                    } else {
                        $this->deldir($fullpath);
                    }
                }
            }
            @closedir($dh);
            if ($del_self) {
                //删除当前文件夹：
                if (@rmdir($dir)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * 向页面中输出js，并使用bootstrap 自身弹出框显示提示内容，要求视图中有 如下内容
     * <!-- #modal-dialog start -->
     * <div class="modal fade" id="modal-dialog">
     * <div class="modal-dialog">
     * <div class="modal-content">
     * <div class="modal-header">
     * <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
     * <h4 class="modal-title"><i class="fa fa-exclamation-circle"></i> [Title]</h4>
     * </div>
     * <div class="modal-body small">
     * <p>[Message]</p>
     * </div>
     * <div class="modal-footer">
     * <button type="button" class="btn btn-primary ok" data-dismiss="modal">[BtnOk]</button>
     * <button type="button" class="btn btn-default cancel" data-dismiss="modal">[BtnCancel]</button>
     * </div>
     * </div>
     * </div>
     * </div>
     * <!-- #modal-dialog end -->
     *
     * @param      $message  弹出层的消息
     * @param      $is_exit     是否中止程序执行,true--是  false--否
     * @param      $is_reload   是否重新加载父窗口  true--是  false--否
     */
    public function write_message($message = "", $is_exit = true, $is_reload = false, $ms = 1500, $ispjax = true)
    {
        header("Content-Type: text/html;charset=utf-8");
        echo "<script type='text/javascript'>";
        if ($message != "") {
            echo "parent.Modal.alert({ msg: '" . str_replace("'", "\\'", $message) . "'});
        	if( typeof parent.showsubmit === 'function' ){
        	   parent.showsubmit();
            }
            ";
        }
        if ($is_reload) {
            if (is_string($is_reload)) {
                if ($ispjax) {
                    echo "setTimeout(function(){parent.pjax('" . $is_reload . "',true);},{$ms});";
                } else {
                    echo "setTimeout(function(){parent.location.href='$is_reload'},{$ms});";
                }
            } else {
                echo "setTimeout(function(){parent.location.reload();},{$ms});";
            }

        }
        echo " ;
         parent.submitdisabledfale();</script>";
        if ($is_exit) {
            exit;
        }
    }


    public function write_message_refresh_table($message, $is_exit = true, $is_reload = false)
    {
        header("Content-Type: text/html;charset=utf-8");
        echo "<script type='text/javascript'>";
        echo "parent.Modal.alert({ msg: '" . str_replace("'", "\\'", $message) . "'});";
        if ($is_reload) {
            echo "parent.doRefresh();";
            echo "parent.hideReplayModal();";
        }
        echo "</script>";
        if ($is_exit) {
            exit;
        }
    }


    public function write_redirect($url, $is_exit = true, $is_ajax = false, $is_output_ajax = false, $is_changehash = false)
    {
        $rs = "<script type='text/javascript'>";

        $this->loadCore('log')->log(APP_ERROR, '$is_changehash=' . $is_changehash, "add_goods_fun");
        if ($is_changehash) {
            $rs .= "parent.location.hash ='#" . $url . "';";
        }

        if ($is_ajax) {
            $rs .= "parent.pjax('" . $url . "');";

        } else {
            $rs .= "parent.redirect('" . $url . "');";
        }
        $rs .= "</script>";

        $this->loadCore('log')->log(APP_ERROR, $rs, "add_goods_fun");
        if ($is_output_ajax) {
            echo json_encode(array('location' => $url));
        } else {
            header("Content-Type: text/html;charset=utf-8");
            echo $rs;
        }
        if ($is_exit) {
            exit;
        }
    }

    public function write_alert_redirect($message, $url = '', $is_exit = true)
    {
        header("Content-Type: text/html;charset=utf-8");
        echo "<script type='text/javascript'>";
        if ($url != '') {
            echo "parent.Modal.alert({ msg: '" . str_replace("'", "\\'", $message) . "'}).on(function(e){parent.redirect('" . $url . "');});";
        } else {
            echo "parent.Modal.alert({ msg: '" . str_replace("'", "\\'", $message) . "'});";
        }
        echo "</script>";
        if ($is_exit) {
            exit;
        }
    }

    public function write_page_message($message, $is_exit = true)
    {
        header("Content-Type: text/html;charset=utf-8");
        echo $message;
        if ($is_exit) {
            exit;
        }
    }

    /**
     * 获得 当前环境的 HTTP 协议方式
     *
     * @access  public
     *
     * @return  void
     */
    public function getHttp()
    {
        $SERVER = ctx()->server();
        return (isset($SERVER['HTTPS']) && (strtolower($SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    }

    /**
     * 取得当前的域名
     *
     * @access  public
     *
     * @return  string      当前的域名
     */
    function getDomain()
    {
        /* 协议 */
        $protocol = $this->getHttp();
        $SERVER = ctx()->server();

        /* 域名或IP地址 */
        if (isset($SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($SERVER['HTTP_HOST'])) {
            $host = $SERVER['HTTP_HOST'];
        } else {
            /* 端口 */
            if (isset($SERVER['SERVER_PORT'])) {
                $port = ':' . $SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (isset($SERVER['SERVER_NAME'])) {
                $host = $SERVER['SERVER_NAME'] . $port;
            } elseif (isset($SERVER['SERVER_ADDR'])) {
                $host = $SERVER['SERVER_ADDR'] . $port;
            }
        }

        return $protocol . $host;
    }

    /**
     * 根据url获取内容
     * @param $url
     * @return 内容
     */
    public function getUrlContents($url)
    {
        $content = $this->loadPlugin('cache')->getCache('url_', $url);
        if (!$content) {
            $content = file_get_contents($url);
            $this->loadPlugin('cache')->setCache('url_', $url, $content);
        }
        return $content;

    }

    /**
     * 获取站点根目录网址
     *
     * @access  private
     * @return  Bool
     */
    public function get_site_root_url()
    {
        $SERVER = ctx()->server();
        return 'http://' . $SERVER['HTTP_HOST'];

    }

    /**
     * 判断当前浏览器是否微信浏览器
     * @return bool false--非微信浏览器  true--微信浏览器
     */
    public function is_weixin_browser()
    {
        $SERVER = ctx()->server();
        $user_agent = isset($SERVER['HTTP_USER_AGENT']) ? $SERVER['HTTP_USER_AGENT'] : '';
        $header_user_agent = $this->getallheaders('HTTP_USER_AGENT');
        if (stripos($user_agent, 'MicroMessenger') === false && stripos($header_user_agent, 'MicroMessenger') === false) {
            return false;
        } else {
            // 微信浏览器，允许访问
            preg_match('/.*?(MicroMessenger\/([0-9.]+))\s*/', $user_agent, $matches);
            return true;
        }
    }

    /**
     * 判断是否是手机浏览器
     * @return bool  true--手机浏览器 false--PC浏览器
     */
    public function is_mobile_browser()
    {
//        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
//        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";
//        if(($ua == '' || preg_match($uachar, $ua))&& !strpos(strtolower($_SERVER['REQUEST_URI']),'wap'))
//            return true;
//        else
//            return false;
        //        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
//
//        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|cldc|midp|mobile)/i";
//        if(($ua == '' || preg_match($uachar, $ua)) && strpos(strtolower($_SERVER['REQUEST_URI']),'wap')){
//            return true;
//        }
//        else
//            return false;
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        $SERVER = ctx()->server();
        if (isset($SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset($SERVER['HTTP_VIA'])) {
            // 找不到为flase,否则为true
            return stristr($SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
        if (isset($SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile', 'MicroMessenger');
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;

    }

    /**
     * 将 0.00 这样的浮点数转换为整数
     * @return bool  true--手机浏览器 false--PC浏览器
     */
    public function floattoint($val)
    {
        preg_match("#^([+-]|)([0-9]*)(.([0-9]*?)|)(0*)$#", trim($val), $o);
        return $o[1] . sprintf('%d', $o[2]) . ($o[3] != '.' ? $o[3] : '');
    }

    /**
     * 对价格进行格式化输出
     * @param $price
     * @param $prision
     */
    public function formatPrice($price)
    {
        $SITE_CURRENCY_FORMAT = $this->loadService('shop_params')->get_value_by_key('SITE_CURRENCY_FORMAT', ctx()->getShopId());
        if (empty($SITE_CURRENCY_FORMAT)) {
            return $price;
        }
        return sprintf($SITE_CURRENCY_FORMAT, $price) . "";
    }

    /**
     * 对价格进行格式化输出,自定义保留小数位数
     * @param $price
     * @param $bit 保留位数
     * @param $type 进位类型，true-四舍五入    false-不四舍五入
     */
    public function formatFloat($price, $bit = 2, $type = false)
    {
        if ($type) {
            $price = sprintf("%." . $bit . "f", $price);
        } else {
            $price = sprintf("%." . $bit . "f", substr(sprintf("%." . ($bit + 1) . "f", $price), 0, -1));
        }
        return $price;
    }


    /**
     *
     * 根据数据库中保存的图片地址获取图片路径
     * @param $imagepath  不包含路径的文件名或以/打头的相对于站点根目录的图片路径
     * @param $image_type  图片类型 空--原图片  xs--超小 60*60  sm--小 120*120  md--中  240*240  lg--大 480*480  xl--超大  960*960
     * @param $is_enable_cdn  true--使用cdn，false--不使用cdn
     */
    public function getImageUrl($imagepath, $image_type = '', $is_enable_cdn = true, $is_upload_prefix = true)
    {
        if (empty($imagepath)) return '';
        $arr = array('xs', 'sm', 'md', 'lg', 'xl', '');
        if (!in_array($image_type, $arr)) return '';
        if (!($this->startsWith($imagepath, 'http://') || $this->startsWith($imagepath, 'https://'))) {
            $imagepath = str_replace(ctx()->getSiteName() . '/public_html', "", $imagepath);
        }
        $imagepath = str_replace("\\", "/", $imagepath);
        if ($image_type) $image_type = '_' . $image_type . '.jpg';
        $APP_CDN_SITE_DOMAIN_URL = $is_enable_cdn ? self::getConfig('app', 'APP_CDN_SITE_DOMAIN_URL') : '';
        $UPLOAD_PREFIX_URL = self::getConfig('app', 'UPLOAD_PREFIX_URL');
        if ($UPLOAD_PREFIX_URL && $is_upload_prefix) {
            $url_prefix = (self::getConfig('app', 'UPLOAD_PREFIX_URL') ? self::getConfig('app', 'UPLOAD_PREFIX_URL') : $this->getDomain()) . APP_PATH . ctx()->getSiteName();
        } else {
            if ($APP_CDN_SITE_DOMAIN_URL) {
                $url_prefix = $APP_CDN_SITE_DOMAIN_URL . '/' . ctx()->getShopId() . str_ireplace('.', '_', self::getConfig('params', 'DEFAULT_ADMIN_DOMAIN_SUFFIX')) . '/runtime/uploads';
            } else {
                $url_prefix = $this->getDomain() . APP_PATH . ctx()->getSiteName() . '/public_html';
            }
        }
        if (strpos($imagepath, '/') !== false) {
            if (empty($UPLOAD_PREFIX_URL) && $APP_CDN_SITE_DOMAIN_URL) {
                $url_prefix = $APP_CDN_SITE_DOMAIN_URL . '/' . ctx()->getShopId() . str_ireplace('.', '_', self::getConfig('params', 'DEFAULT_ADMIN_DOMAIN_SUFFIX'));
            }
            if ($this->startsWith($imagepath, 'http://') || $this->startsWith($imagepath, 'https://')) {
                return $imagepath . $image_type;
            } else {
                if (strpos($imagepath, 'runtime') === false) {
                    $url_prefix .= '/runtime/uploads';
                }
                return $url_prefix . '/' . ltrim($imagepath, '/') . $image_type;
            }
        }
        $imagepath = $imagepath . $image_type;
        if (stripos($url_prefix, '/runtime/uploads') === false) {
            $url_prefix .= '/runtime/uploads';
        }
        return $url_prefix . '/' . $imagepath;
    }

    public function getImageUrl_emotion($imagepath)
    {
        if (empty($imagepath)) return '';
        $APP_CDN_SITE_DOMAIN_URL = self::getConfig('app', 'APP_CDN_SITE_DOMAIN_URL');
        if ($APP_CDN_SITE_DOMAIN_URL) {
            $url_prefix = $APP_CDN_SITE_DOMAIN_URL;
        } else {
            $url_prefix = $this->getDomain() . APP_PATH;
        }
        return rtrim($url_prefix, '/') . '/' . ltrim($imagepath, '/');
    }

    /**
     * 判断字符串是否为以,间隔的整数列表
     * @param $list
     */
    public function isIntList($list)
    {
        return preg_match('/^[0-9]+(,[0-9]+)*$/', $list);
    }


    /*
     * 通过自建接口生成短链接
     */
    function shortUrl($url)
    {
        $SERVER = ctx()->server();
        $access_domain = 'http://' . $SERVER['HTTP_HOST'];
        /* if ($_SERVER['SERVER_PORT'] != 80) {
             $access_domain .=":" . $_SERVER['SERVER_PORT'];
         }
         $url = empty($url)?"":$access_domain . '/' . $url.".html";*/
        $arrResponse = $this->loadService('short_url')->create($url);
        if ($arrResponse['status'] == false) {
            /*             * 错误处理 */
            echo iconv('UTF-8', 'GBK', $arrResponse['msg']) . "\n";
        }
        /** short_url */
        return isset($arrResponse['short_url']) ? $arrResponse['short_url'] : '';
    }

    function shortUrl_old($url)
    {
        $SERVER = ctx()->server();
        $access_domain = 'http://' . $SERVER['HTTP_HOST'];
        if ($SERVER['SERVER_PORT'] != 80) {
            $access_domain .= ":" . $SERVER['SERVER_PORT'];
        }
        $url = $access_domain . '/' . $url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://dwz.cn/create.php");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = array('url' => $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $strRes = curl_exec($ch);
        curl_close($ch);
        $arrResponse = json_decode($strRes, true);
        if ($arrResponse['status'] == 0) {
            /*             * 错误处理 */
            echo iconv('UTF-8', 'GBK', $arrResponse['err_msg']) . "\n";
        }
        /** tinyurl */
        return isset($arrResponse['tinyurl']) ? $arrResponse['tinyurl'] . "\n" : '';
    }


    /**
     * 判断是否整数
     * @param $value
     */
    public function isInteger($value)
    {
        return preg_match('/^[0-9]+$/', $value);
    }

    /**
     * 判断是否整数列表，以,间隔的整数列表
     * @param $value
     */
    public function isIntegerList($value)
    {
        return preg_match('/^[0-9]+(,[0-9]+)*$/', $value);
    }

    /**
     * 随机生成验证码
     * @return string
     */
    public function get_code()
    {
        mt_srand((double)microtime() * 1000000);
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function distributor_get_code()
    {
        mt_srand((double)microtime() * 1000000);
        return str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     *  查找头像用于前端
     * @return string
     */

    public function get_head_img($img_name)
    {
        $img_name = @basename($img_name);
        $img_dir = @dir($img_name);
        $SERVER = ctx()->server();
        return $img_name ? $this->getImageUrl(ltrim(rtrim($img_dir, '/') . $img_name, '/')) : "http://" . $SERVER['SERVER_NAME'] . "/images/avatar.png";
    }

    /**
     * 获取文件后缀名,并判断是否合法
     *
     * @param string $file_name
     * @param array $allow_type
     * @return blob
     */
    public function get_file_suffix($file_name, $allow_type = array())
    {
        $file_name = explode('.', $file_name);
        $file_suffix = array_pop($file_name);
        if (empty($allow_type)) {
            return $file_suffix;
        } else {
            if (in_array($file_suffix, $allow_type)) {
                return true;
            } else {
                return false;
            }
        }
    }

    function time2second($seconds)
    {
        $seconds = (int)$seconds;
        if ($seconds < 86400) {//如果不到一天
            $arr_format_time = explode(' ', gmstrftime('%H %M %S', $seconds));
            $format_time = $arr_format_time[0] . '时' . $arr_format_time[1] . '分' . $arr_format_time[2] . '秒';
        } else {
            $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));//Array ( [0] => 04 [1] => 14 [2] => 14 [3] => 35 )
            $format_time = ($time[0] - 1) . '天' . $time[1] . '时' . $time[2] . '分' . $time[3] . '秒';
        }

        return $format_time;
    }


    /**
     * 使用异步方法发起对当前站点指定url的请求
     * @param $host  主机
     * @param $path  url
     * @param $post  当前站点端口
     * @return int|string
     */
    function async($host, $path, $post)
    {
//        $post = "test=test";//async.php 页面获取的数据，$_POST获取
//        $path = '/web/async.php';//执行异步处理的php文件
//        $host = 'www.yourdomain.com';//域名，或者ip
        $timeout = 30;
        $SERVER = ctx()->server();
        $port = isset($SERVER['SERVER_PORT']) ? $SERVER['SERVER_PORT'] : 80;

        $header = "POST $path HTTP/1.0\r\n";
        $header .= "Accept: */*\r\n";
        $header .= "Accept-Language: zh-cn\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n";
        $header .= "Host: $host\r\n";
        $header .= 'Content-Length: ' . strlen($post) . "\r\n";
        $header .= "Connection: Close\r\n";
        $header .= "Cache-Control: no-cache\r\n\r\n";
        $header .= $post;
        if (function_exists('fsockopen')) {
            $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        } elseif (function_exists('pfsockopen')) {
            $fp = @pfsockopen($host, $port, $errno, $errstr, $timeout);
        } else {
            $fp = false;
        }
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, 1);// 1 为非阻塞
            stream_set_timeheader($fp, $timeout);
            @fwrite($fp, $header);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_header']) {
                return 1;
            }
            @fclose($fp);
            return '';
        }
    }


    /** 获取当前时间戳，精确到毫秒
     * 1. 获取当前时间戳(精确到毫秒)：microtime_float()
     */
    function microtime_float($microtime = null)
    {
        if (empty($microtime)) $microtime = microtime();
        list($usec, $sec) = explode(" ", $microtime);
        return ((float)$usec + (float)$sec);
    }

    /** 格式化时间戳，精确到毫秒，x代表毫秒
     * 2. 时间戳转换时间：microtime_format('Y年m月d日 H时i分s秒 x毫秒', 1270626578.6
     */
    function microtime_format($tag, $time)
    {
        list($usec, $sec) = explode(".", $time);
        $date = date($tag, $usec);
        return str_replace('x', $sec, $date);
    }


    /**
     * xml 转 json
     * @param $source 来源
     * @return string json
     */
    public function xml_to_json($source)
    {
        if (is_file($source)) { //传的是文件，还是xml的string的判断
            $xml_array = @simplexml_load_file($source);
        } else {
            $xml_array = @simplexml_load_string($source);
        }
        $json = json_encode($xml_array); //php5，以及以上，如果是更早版本，请查看JSON.php
        return $json;
    }

    /**
     * 获取路由及查询串参数数据
     */
    public function get_route_datas()
    {
        $params = $this->getRouter('params');
        if ($params) {
            $params = '/' . trim(implode('/', $params), '/');
        } else {
            $params = '';
        }
//        if(!empty($_SERVER['QUERY_STRING'])){
//            $params .= '?' . $_SERVER['QUERY_STRING'];
//        }

        return $params;

    }


    /**
     * 批量检查 bom 头文件夹
     * @param $basedir
     * @param $is_remove 1--去除 bom头  0--不去除
     */
    function check_bom_dir($basedir, $is_remove = 1)
    {
        if ($dh = opendir($basedir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '.' && $file != '..') {
                    if (!is_dir($basedir . '/' . $file)) {
                        echo '文件: ' . $basedir . '/' . $file . $this->checkBOM($basedir . '/' . $file, $is_remove) . ' <br>';
                    } else {
                        $dirname = $basedir . '/' . $file;
                        $this->check_bom_dir($dirname, $is_remove);
                    }
                }
            }
            closedir($dh);
        }
    }

    /**
     * 检查 bom 头文件夹
     * @param $filename  文件名
     * @param $is_remove 1--去除 0--不去除
     * @return string
     */
    function checkBOM($filename, $is_remove = 1)
    {
        global $auto;
        $contents = file_get_contents($filename);
        $charset[1] = substr($contents, 0, 1);
        $charset[2] = substr($contents, 1, 1);
        $charset[3] = substr($contents, 2, 1);
        if (ord($charset[1]) == 239 && ord($charset[2]) == 187 && ord($charset[3]) == 191) {
            if ($auto == 1) {
                $rest = substr($contents, 3);
                $this->rewrite($filename, $rest);
                return (' <font color=red>找到BOM并已自动去除</font>');
            } else {
                return (' <font color=red>找到BOM</font>');
            }
        } else {
            return (' 没有找到BOM');
        }
    }

    function rewrite($filename, $data)
    {
        $filenum = fopen($filename, 'w');
        flock($filenum, LOCK_EX);
        fwrite($filenum, $data);
        fclose($filenum);
    }


    /**
     * 隐藏用户名
     *
     * @param        $username  用户名
     * @param string $char 隐藏字符
     *
     * @return string  返回隐藏之后的字符串
     */
    public function hide_user_name($username, $char = "*")
    {
        if (empty($username)) return '';
        if (mb_strlen($username, 'UTF-8') < 2) return '*';
        $first = mb_substr($username, 0, 1, 'UTF-8');
        if (mb_strlen($username, 'UTF-8') < 3) return $first . '*';
        $last = mb_substr($username, mb_strlen($username, 'UTF-8') - 1, 1, 'UTF-8');
        return $first . str_pad('', mb_strlen($username, 'UTF-8') - 2, $char) . $last;
    }


    /**
     * 获取短订单号
     * @param $full_order_sn  完整订单号
     */
    public function get_order_sn($full_order_sn)
    {
        if (empty($full_order_sn)) return '';
        if (strlen($full_order_sn) == 32) {
            return substr($full_order_sn, 10, 19);
        } else {
            return $full_order_sn;
        }
    }


    /**
     * 验证当前要发送的短信时间是否符合短信服务商规则
     * 1、30秒内只能发一条，1分钟内不能超过2条，30分钟内不能超过3条
     * @param int $curr_sms_time 当前要发送的短信时间
     * @param array $sms_times 查询出来的最近最多3条短信发送时间
     * @param bool $is_exists false--不满足规则修正时间   true--不满足规则直接发送失败
     * @return array('status' => -1|0|1, 'msg' => '错误消息', 'time' => 未修改或已修正之后的时间)  -1--发送失败  0--满足规则  1--修正时间满足规则
     */
    public function validate_sms_rule($curr_sms_time, $sms_times, $is_exists = false)
    {
        $sms_send_rules = array(3 => 1800, 2 => 60, 1 => 30);
        $rs = array('status' => 0, 'msg' => '', 'time' => $curr_sms_time);
        if (count($sms_times) == 3) {
            //有3条发送或准备发送记录
            if ($curr_sms_time - $sms_times[2] < $sms_send_rules[3]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[2] + $sms_send_rules[3];
                $curr_sms_time = $rs['time'];
            }
            if ($curr_sms_time - $sms_times[1] < $sms_send_rules[2]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[1] + $sms_send_rules[2];
                $curr_sms_time = $rs['time'];
            }

            if ($curr_sms_time - $sms_times[0] < $sms_send_rules[1]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[0] + $sms_send_rules[1];
            }

        } elseif (count($sms_times) == 2) {
            //只有2条发送或准备发送记录
            if ($curr_sms_time - $sms_times[1] < $sms_send_rules[2]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[1] + $sms_send_rules[2];
                $curr_sms_time = $rs['time'];
            }

            if ($curr_sms_time - $sms_times[0] < $sms_send_rules[1]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[0] + $sms_send_rules[1];
            }

        } elseif (count($sms_times) == 1) {
            //只有1条发送或准备发送记录
            if ($curr_sms_time - $sms_times[0] < $sms_send_rules[1]) {
                if ($is_exists) {
                    $rs['status'] = -1;
                    return $rs;
                }
                $rs['status'] = 1;
                $rs['time'] = $sms_times[0] + $sms_send_rules[1];
            }
        } else {
            $rs['status'] = 0;
        }

        return $rs;
    }


    /**
     * 二维数组排序
     * @param $arrays  输入二维数组
     * @param $sort_key  要排序的字段
     * @param int $sort_order 升或降序
     * @param int $sort_type 排序类型
     * @return array|bool
     */
    public function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }


    /**
     * 删除回车换行符
     * @param $content
     */
    public function removecrlf($content)
    {
        $content = str_replace('\n', '', $content);
        $content = str_replace('\r', '', $content);
        return str_replace(PHP_EOL, '', $content);
    }

    /**
     * 获取当前站点的临时访问主机，例如： kid.kms.dev
     * @return string
     */
    public function get_admin_tmp_host()
    {
        return ctx()->getShopId() . self::getConfig('params', 'DEFAULT_ADMIN_DOMAIN_SUFFIX');
    }

    /**
     * 替换内容中临访地址为cdn地址或者当前主机头地址
     */
    public function replace_conent_temp_host($content)
    {
        $APP_CDN_SITE_DOMAIN_URL = self::getConfig('app', 'APP_CDN_SITE_DOMAIN_URL');
        $SERVER = ctx()->server();
        if (empty($APP_CDN_SITE_DOMAIN_URL)) {
            //未启用CDN加速
            return str_ireplace($this->get_admin_tmp_host(), $SERVER['HTTP_HOST'], $content);
        } else {
            //启用了CDN加速
            return str_ireplace('http://' . $this->get_admin_tmp_host(), $APP_CDN_SITE_DOMAIN_URL . '/' . str_ireplace('.', '_', $this->get_admin_tmp_host()) . '/public_html', $content);
        }
    }

    /*
     * 获取浏览器的类型
     */
    function get_browser_type()
    {
        $SERVER = ctx()->server();
        if (empty($SERVER['HTTP_USER_AGENT'])) {
            return '命令行，机器人来了！';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'rv:11.0')) {
            return 'Internet Explorer 11.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) {
            return 'Internet Explorer 10.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            return 'Internet Explorer 9.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            return 'Internet Explorer 8.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            return 'Internet Explorer 7.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            return 'Internet Explorer 6.0';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'Firefox')) {
            return 'Firefox';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            return 'Chrome';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'Safari')) {
            return 'Safari';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], 'Opera')) {
            return 'Opera';
        }
        if (false !== strpos($SERVER['HTTP_USER_AGENT'], '360SE')) {
            return '360SE';
        }
        return "Internet Explorer 11.0";
    }


    /**
     * 下载文件
     * @param $file
     */
    public function download($file, $charset = 'utf-8')
    {
        $file = strtolower($file);
        if ($this->startsWith($file, 'http://')) {
            header("Content-type: application/octet-stream;charset=" . $charset);
            header("Content-Disposition: attachment;filename=" . basename($file));
            ob_end_clean();
            echo file_get_contents($file);
        } else {
            if (file_exists($file)) {
                header("Content-type: application/octet-stream;charset=" . $charset);
                header("Content-Disposition: attachment;filename=" . basename($file));
                ob_end_clean();
                echo file_get_contents($file);
            } else {
                echo "file not exists,download file falure.";
            }

        }
        exit;
    }


    /**
     * 获取某月第一天及最后一天数组
     * @param in $date 日期时间
     * @return array(第1天，最后1天)
     */
    public function getTheMonth($date)
    {
        $firstday = date('Y-m-01', $date);
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        return array($firstday, $lastday);
    }


    /**
     * 加密字符串
     * @param $str
     */
    public function encrypt($str)
    {
        return $str;
        //return base64_encode($this->loadPlugin('crypt')->encrypt($str.''));
    }

    /**
     * 解密字符串
     * @param $en_str
     */
    public function decrypt($en_str)
    {
        return $en_str;
//        return $this->loadPlugin('crypt')->decrypt(base64_decode($en_str));
    }


    /**
     * 获取中文星期
     * @param $week_day 当前星期，取值  0-6
     */
    public function getWeekDay($week_day)
    {
        $week_days = array('星期一', '星期二', '星期三', '星期四', '星期五', '星期六', '星期天');
        return $week_days[$week_day];
    }


    /**
     * 隐藏银行卡号码仅保留前各后4位
     *
     * @param        $cardno  手机号码
     * @param string $char 隐藏字符
     *
     * @return string  返回隐藏之后的字符串
     */
    public function hide_bank_cardno($cardno, $char = "*")
    {
        $len = strlen($cardno);
        $start = $len - 4;
        if ($start < 0) return $cardno;
        $left_str = substr($cardno, 0, 4);
        $center_str = substr($cardno, 4, $start);
        $right_str = substr($cardno, $start);
        return $left_str . str_pad('', strlen($center_str), $char) . $right_str;
    }

    /**
     * 隐藏银行卡开户人姓名仅保留后1一个汉字
     *
     * @param        $cardno  手机号码
     * @param string $char 隐藏字符
     *
     * @return string  返回隐藏之后的字符串
     */
    public function hide_bank_account($account, $char = "*")
    {
        $len = mb_strlen($account, "utf-8");
        $start = $len - 1;
        if ($start < 0) return $account;
        $right_str = mb_substr($account, $start, 1, 'utf-8');//
        return "*" . $right_str;
    }

    /**
     * 根据开始时间获取最近5周的开始以及结束时间数组
     * @param $start_time 开始时间
     */
    public function get_5_week_times_by_start_time($start_time)
    {
        $start = $start_time;
        $week_times = [];
        $i = 1;
        for (; ;) {
            $end = $start_time + 7 * $i * 24 * 3600;
            $week_times[] = array(
                strtotime(date('Y-m-d 00:00:00', $start)), strtotime(date('Y-m-d 23:59:59', $end - 24 * 3600))
            );
            $i++;
            if ($i > 5) break;
            $start = $end;
        }

        return $week_times;
    }


    /**
     * 将消息中心中http://server替换为对应客户端url
     * @param $client_type 内容（内容中包含pc url）
     * @param $content 要替换的内容
     */
    public function message_url_2_wapurl($content, $client_type = 'web')
    {
        $arr = $this->loadService('shop_base')->get_domain_by_kid();
        if (!$arr) {
            $site_pc_url = 'http://' . ctx()->getShopId() . self::getConfig('params', 'DEFAULT_WWW_DOMAIN_SUFFIX');
            $site_wap_url = 'http://' . ctx()->getShopId() . self::getConfig('params', 'DEFAULT_MOBILE_DOMAIN_SUFFIX');
            $api_host_url = 'http://' . ctx()->getShopId() . self::getConfig('params', 'DEFAULT_ADMIN_DOMAIN_SUFFIX');
        } else {
            $site_pc_url = 'http://' . $arr['main_domain'];
            $site_wap_url = $arr['mobile_host'];
            $api_host_url = $arr['api_host'];
        }
        $APP_DISTRIBUTER_url_2_wap_urls = self::getConfig('global', 'APP_DISTRIBUTER_url_2_wap_urls');
        if ($client_type == 'web') {
            //pc
            $content = str_ireplace('http://server', $site_pc_url, $content);
        } else {
            //wap
            $content = str_ireplace('http://server', $site_wap_url, $content);
            foreach ($APP_DISTRIBUTER_url_2_wap_urls as $url => $wap_url) {
                $content = str_ireplace($url, $wap_url, $content);
            }
        }
        return $content;

    }


    /**
     * 获取指定文件夹占用的磁盘空间
     * @param $path 指定文件夹路径
     * @return float
     */
    public function get_space($path)
    {
        return $this->getRealSize($this->getDirSize($path), 'MB');
    }

    private function getDirSize($dir)
    {
        $handle = opendir($dir);
        $sizeResult = 0;
        while (false !== ($FolderOrFile = readdir($handle))) {
            if ($FolderOrFile != "." && $FolderOrFile != "..") {
                if (is_dir("$dir/$FolderOrFile")) {
                    $sizeResult += $this->getDirSize("$dir/$FolderOrFile");
                } else {
                    $sizeResult += filesize("$dir/$FolderOrFile");
                }
            }
        }
        closedir($handle);
        return $sizeResult;
    }

    // 单位自动转换函数
    private function getRealSize($size, $type)
    {
        $kb = 1024;         // Kilobyte
        $mb = 1024 * $kb;   // Megabyte
        $gb = 1024 * $mb;   // Gigabyte
        $tb = 1024 * $gb;   // Terabyte

        if ($type == 'B') {
            return $size;
        } else if ($type == 'KB') {
            return round($size / $kb, 2);
        } else if ($type == 'MB') {
            return round($size / $mb, 2);
        } else if ($type == 'GB') {
            return round($size / $gb, 2);
        } else if ($type == 'TB') {
            return round($size / $tb, 2);
        }
    }

    /**
     * 获得指定文件夹下所有文件的md5哈稀值
     * @param $path  文件夹，必须以/结束
     * @param $excludeDirs  要排除的文件夹数组，必须设置为相对于 $path的相对路径，不能以/开始
     * @param $excludeFiles  要排除的文件数组，必须设置为相对于 $path的相对路径，不能以/开始
     * @param array $returns 输出数组：key=文件路径  value=md5
     */
    public function getDirFileContentMd5($path, $excludeDirs, $excludeFiles, &$returns)
    {
        $path = str_replace("\\", "/", $path);
        $handle = opendir($path);
        while (false !== ($FolderOrFile = readdir($handle))) {
            if ($FolderOrFile != "." && $FolderOrFile != "..") {
                if (is_dir($path . $FolderOrFile)) {
                    if ($excludeDirs) {
                        $isFind = false;
                        foreach ($excludeDirs as $file) {
                            if ($this->endsWith($path . $FolderOrFile, '/' . $file)) {
                                $isFind = true;
                                break;
                            }
                        }
                        if (!$isFind) $this->getDirFileContentMd5($path . $FolderOrFile . '/', $excludeDirs, $excludeFiles, $returns);
                    } else {
                        $this->getDirFileContentMd5($path . $FolderOrFile . '/', $excludeDirs, $excludeFiles, $returns);
                    }
                } else {
                    if ($excludeFiles) {
                        $isFind = false;
                        foreach ($excludeFiles as $file) {
                            if ($this->endsWith($path . $FolderOrFile, '/' . $file)) {
                                $isFind = true;
                                break;
                            }
                        }
                        if (!$isFind) $returns[$path . $FolderOrFile] = md5(file_get_contents($path . $FolderOrFile));

                    } else {
                        $returns[$path . $FolderOrFile] = md5(file_get_contents($path . $FolderOrFile));
                    }

                }
            }
        }
        closedir($handle);
    }

    /**
     * 发送二进制流
     * @param $file
     */
    public function sendStream($file)
    {
        echo file_get_contents($file);
        exit;
    }

    /**
     * 修改app.config.php中version部份信息
     * @param $versions 版本号数组
     */
    public function update_app_config_versions($versions)
    {
        sort($versions);
        $content = file_get_contents(APP_PACKAGE_BASE_PATH . "package/config/version.config.php");
        $versions_code = "'APP_SHOP_VERSION_LISTS'=>array(";
        foreach ($versions as $version) {
            $versions_code .= $version . ',' . PHP_EOL;
        }
        $versions_code .= ")";

        $content = preg_replace("/'APP_SHOP_VERSION_LISTS'=\\>array\\(((.|\\n)*?)\\)/", $versions_code, $content);

        $version_code = "'APP_SHOP_VERSION'=>" . $versions[count($versions) - 1] . ",";

        $content = preg_replace("/'APP_SHOP_VERSION'=\\>.+?,/", $version_code, $content);
        return file_put_contents(APP_PACKAGE_BASE_PATH . "package/config/version.config.php", $content) ? true : false;


    }

    public function addemotion($content = '')
    {
        $SESSION = ctx()->session();
        $host = intval($SESSION['PLATFORM_SHOP_ID']) . self::getConfig('params', 'DEFAULT_ADMIN_DOMAIN_SUFFIX');
        $host = '//' . $host . "/service/home/imgpath.html?path=";
        $newcontent = preg_replace('/src="(\/plugins\/ueditor\/dialogs\/emotion\/images\/([0-9_\.\/a-zA-Z])*)\?ksopx"/', 'src="' . $host . '${1}&type=1" ', $content);
        return $newcontent;
    }

    public function removeEmoji($text)
    {
        $clean_text = "";
        // Match Emoticons

        $text = json_encode($text);
        $text = preg_replace("#(\\\ue[0-9a-f]{3})#ie", "", $text);
        $text = json_decode($text);

        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);

        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);

        $clean_text = preg_replace('/&#x(e[0-9a-f][0-9a-f][0-9a-f]|f[0-8][0-9a-f][0-9a-f])/i', '', $clean_text);

        return $clean_text;
    }

    function unescape($str)
    {
        $str = rawurldecode($str);
        preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v) {
            if (substr($v, 0, 2) == "%u")
                $ar[$k] = iconv("UCS-2", "utf-8", pack("H4", substr($v, -4)));
            elseif (substr($v, 0, 3) == "&#x")
                $ar[$k] = iconv("UCS-2", "utf-8", pack("H4", substr($v, 3, -1)));
            elseif (substr($v, 0, 2) == "&#") {
                $ar[$k] = iconv("UCS-2", "utf-8", pack("n", substr($v, 2, -1)));
            }
        }
        return join("", $ar);
    }

    function phpescape($str)
    {
        preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v) {
            if (ord($v[0]) < 128)
                $ar[$k] = rawurlencode($v);
            else
                $ar[$k] = "%u" . bin2hex(iconv("utf-8", "UCS-2", $v));
        }
        return join("", $ar);
    }

    /**
     * 获取指定日期(时间戳)对应月的最后一天
     * @param int $date
     */
    public function getMonthLastDay($date)
    {
        $days = date('t', $date);
        return date("Y-m", $date) . '-' . $days;
    }

    /**
     * 根据当前会话中保存的小程序app_id获取微信小程序支付方式中的app_id以及app_secret
     * 如果当前会话中保存的小程序app_id为空则直接使用0下标的app_id以及app_secret
     * @param $app_ids 以,间隔或者无,的微信小程序app_id列表
     * @param $app_secrets  以,间隔或无,的微信小程序app_secret列表
     * @param $curr_app_id  当前app_id，如果为空则使用session中的app_id
     * @return array('app_id'=>'','app_secret'=>'')
     */
    public function get_weapp_info($app_ids, $app_secrets, $curr_app_id = null)
    {
        $app_ids = explode(',', $app_ids);
        $app_secrets = explode(',', $app_secrets);
        $SESSION = ctx()->session();
        if (is_null($curr_app_id)) {
            $curr_app_id = (isset($SESSION['weapp_app_id']) && $SESSION['weapp_app_id']) ? $SESSION['weapp_app_id'] : '';
        }

        $rs = array('app_id' => $curr_app_id, 'app_secret' => '');
        if ($curr_app_id) {
            foreach ($app_ids as $index => $app_id) {
                if (strtolower($app_id) == strtolower($curr_app_id)) {
                    $rs['app_id'] = $app_id;
                    $rs['app_secret'] = isset($app_secrets[$index]) ? $app_secrets[$index] : '';
                    break;
                }
            }
        } else {
            $rs['app_id'] = isset($app_ids[0]) ? $app_ids[0] : '';
            $rs['app_secret'] = isset($app_secrets[0]) ? $app_secrets[0] : '';
        }
        return $rs;
    }

    /**
     * 密码检查，至少8位，至少包含一个数字、一个大写字母、一个小写字母、一个符号，不能有三位连续字母或数字
     * @param $password
     * @param $msg  不符号要求时返回的错误消息
     * @return true--符合要求  false--不符合要求
     */
    public function check_password($password, & $msg)
    {
        $len = strlen($password);
        if ($len < 8) {
            $msg = "密码至少8位字符";
            return false;
        }
        if (!preg_match("/[0-9]+/", $password)) {
            $msg = "密码至少包含1位数字";
            return false;
        }

        if (!preg_match("/[A-Z]+/", $password)) {
            $msg = "密码至少包含1位大写字母";
            return false;
        }

        if (!preg_match("/[a-z]+/", $password)) {
            $msg = "密码至少包含1位小写字母";
            return false;
        }
        if (!preg_match("/[@`~!#\$%^&*()_+=\{\}\[\];:\"'\|\\\\<>,\.\?-]+/", $password)) {
            $msg = "密码至少包含1位符号";
            return false;
        }

        for ($i = 2; $i < $len; $i++) {
            $ascii1 = ord($password[$i - 2]);
            $ascii2 = ord($password[$i - 1]);
            $ascii3 = ord($password[$i]);
            if (($ascii1 - $ascii2) == 0 && ($ascii3 - $ascii2) == 0 && ($ascii1 - $ascii3) == 0) {
                $msg = "密码不能有3位相同字符【" . $password[$i - 2] . $password[$i - 1] . $password[$i] . "】";
                return false;
            }

            if (($ascii1 - $ascii2) == 1 && ($ascii2 - $ascii3) == 1 && ($ascii1 - $ascii3) == 2) {
                $msg = "密码不能有3位连续字符【" . $password[$i - 2] . $password[$i - 1] . $password[$i] . "】";
                return false;
            }

            if (($ascii1 - $ascii2) == -1 && ($ascii2 - $ascii3) == -1 && ($ascii1 - $ascii3) == -2) {
                $msg = "密码不能有3位连续字符【" . $password[$i - 2] . $password[$i - 1] . $password[$i] . "】";
                return false;
            }

        }
        return true;
    }

}