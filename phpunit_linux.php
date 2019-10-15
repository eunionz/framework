<?php

/**
 * 基于上述配置进行自动化单元测试的类
 * 将递归扫描  PHP_PHPUNIT_CLASS_PATH 对应文件夹下所有.php单元测试类并执行单元测试，输出结果
 * Class AutoPhpUnitTest
 */
class AutoPhpUnitTest
{
    private static $foreground_colors = [
        'black' => '0;30', 'dark_gray' => '1;30', 'blue' => '0;34', 'light_blue' => '1;34', 'green' => '0;32',
        'light_green' => '1;32', 'cyan' => '0;36', 'light_cyan' => '1;36', 'red' => '0;31', 'light_red' => '1;31',
        'purple' => '0;35', 'light_purple' => '1;35', 'brown' => '0;33', 'yellow' => '1;33', 'light_gray' => '0;37', 'white' => '1;37',
    ];
    private static $background_colors = ['black' => '40', 'red' => '41', 'green' => '42', 'yellow' => '43', 'blue' => '44', 'magenta' => '45', 'cyan' => '46', 'light_gray' => '47',];


    const PHP_CLI_EXE_PATH = '/usr/local/share/php/bin/php';
    const PHP_PHPUNIT_EXE_PATHE_PATH = '/mnt/d/swoole/eunionz_ctx/framework/vendor/phpunit/phpunit/phpunit';
    const PHP_PHPUNIT_CLASS_PATH = '/mnt/d/swoole/eunionz_ctx/framework/app/package/tests';
    const PHP_PHPUNIT_CLASS_PREFIX_PATH = '/mnt/d/swoole/eunionz_ctx/framework/app/';
    const PHP_PHPUNIT_OUTPUT_PATH = '/mnt/d/swoole/eunionz_ctx/framework/storage/runtime/phpunit';

    const PHP_PHPUNIT_CLASS_EXCLUDE_FILENAMES = ['/mnt/d/swoole/eunionz_ctx/framework/app/package/tests/TestBase.php'];

    private $outputs = array();

    public function run()
    {
        $this->scanPhpUnitClass(self::PHP_PHPUNIT_CLASS_PATH);
        $all_filename_count = 0;
        $all_test_count = 0;
        $all_assertion_count = 0;
        $all_failure_count = 0;
        $failures = array();
        $all_filename_count = count($this->outputs);
        foreach ($this->outputs as $class => $item) {
            $all_test_count += $item['Tests'];
            $all_assertion_count += $item['Assertions'];
            $all_failure_count += $item['Failures'];
            if ($item['Failures'] > 0) {
                $failures[] = $item;
            }
        }
        $items = array();
        if ($all_failure_count <= 0) {
            $str = "OK (" . $all_filename_count . " TestClasses, " . $all_test_count . " tests, " . $all_assertion_count . " assertions)";
            self::consoleln($str, 0);
            $items[] = $str;
        } else {
            foreach ($failures as $item) {
                $FailuerTests = '';
                if ($item['FailuerTests']) {
                    $FailuerTests = implode(',', $item['FailuerTests']);
                }
                $str = "FailureTestClass: [" . $item['class'] . "],FailuerTests: [" . $FailuerTests . "] (Tests: " . $item['Tests'] . ", Assertions: " . $item['Assertions'] . ", Failures: " . $item['Failures'] . ")";
                self::consoleln($str, 1);
                $items[] = $str;
            }
            $str = "TestClasses: " . $all_filename_count . ", Tests: " . $all_test_count . ", Assertions: " . $all_assertion_count . ", Failures: " . $all_failure_count . ".";
            self::consoleln($str, 1);
            $items[] = $str;
        }
        if(!file_exists(self::PHP_PHPUNIT_OUTPUT_PATH)){
            mkdir(self::PHP_PHPUNIT_OUTPUT_PATH);
        }
        $filename = self::PHP_PHPUNIT_OUTPUT_PATH . '/' . date("YmdHi") . '.output.log';
        file_put_contents($filename , implode(PHP_EOL , $items));
    }

    public function scanPhpUnitClass($path)
    {
        $dir = opendir($path);
        if ($dir) {
            while ($file = readdir($dir)) {
                if ($file != "." && $file != "..") {
                    $fullpath = $path . "/" . $file;
                    if (in_array($fullpath, self::PHP_PHPUNIT_CLASS_EXCLUDE_FILENAMES)) {
                        continue;
                    }
                    if (is_file($fullpath) && $this->endsWith($fullpath, ".php")) {
                        $class = str_replace(".php", "", str_replace("/", "\\", str_replace(self::PHP_PHPUNIT_CLASS_PREFIX_PATH, "", $fullpath)));
                        $cmd = self::PHP_CLI_EXE_PATH . ' ' . self::PHP_PHPUNIT_EXE_PATHE_PATH . ' --no-configuration ' . $class . ' ' . $fullpath . ' --teamcity';
                        $output = '';
                        $r = exec($cmd, $output, $return_var);
                        $Tests = 0;
                        $Assertions = 0;
                        $Failures = 0;
                        $result = '';
                        $FailuerTests = [];
                        if (is_array($output)) {
                            $result = end($output);
                            if (strpos($result, 'OK') !== false) {
                                //单元测试成功 OK (2 tests, 2 assertions)
                                if (preg_match("/([0-9]+) *tests/", $result, $arr)) {
                                    $Tests = intval($arr[1]);
                                }

                                if (preg_match("/([0-9]+) *assertions/", $result, $arr)) {
                                    $Assertions = intval($arr[1]);
                                }
                            } else {
                                //单元测试失败 Tests: 2, Assertions: 4, Failures: 1.
                                if (preg_match("/Tests: *([0-9]+) *,/", $result, $arr)) {
                                    $Tests = intval($arr[1]);
                                }
                                if (preg_match("/Assertions: *([0-9]+) *,/", $result, $arr)) {
                                    $Assertions = intval($arr[1]);
                                }
                                if (preg_match("/Failures: *([0-9]+) *./", $result, $arr)) {
                                    $Failures = intval($arr[1]);
                                }

                                foreach ($output as $item) {
                                    if(strpos($item , 'testFailed')!==false){
                                        if(preg_match("/name='(.+?)'/", $item, $arr)){
                                            $FailuerTests[] = $arr[1];
                                        }
                                    }
                                }
                            }
                        }
                        $FailuerTests = array_unique($FailuerTests);

                        $this->outputs[$class] = array(
                            'class' => $class,
                            'filename' => $fullpath,
                            'Tests' => $Tests,
                            'Assertions' => $Assertions,
                            'Failures' => $Failures,
                            'FailuerTests' => $FailuerTests,
                        );
                    } elseif (is_dir($fullpath)) {
                        $this->scanPhpUnitClass($fullpath);
                    }
                }
            }
            closedir($dir);
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

    // Returns colored string
    public static function getColoredString(string $string, string $foreground_color = 'light_green', string $background_color = null): string
    {
        $colored_string = "";

        // Check if given foreground color found
        if (isset(self::$foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset(self::$background_colors[$background_color])) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .= $string . "\033[0m";

        return $colored_string;
    }

    /**
     * 向控制台输入调试信息不换行
     * @param int $level
     * @param $msg
     */
    public static function console(string $msg, int $level = 0, string $is_br = ''): string
    {
        switch ($level) {
            case 1:
                $title = '';
                $color = 'light_red';
                break;

            case 2:
                $title = '';
                $color = 'yellow';
                break;

            case 3:
                $title = '';
                $color = 'light_cyan';
                break;

            case 4:
                $title = '';
                $color = '';
                break;
            case 0:
                $title = '';
                $color = 'light_green';
                break;
            default:
                $title = '';
                $color = '';
                break;
        }

        $str = self::getColoredString($title . $msg, $color);
        @file_put_contents("php://stdout", $str . $is_br);
        return $str . $is_br;
    }

    /**
     * 向控制台输入调试信息不换行
     * @param int $level
     * @param $msg
     */
    public static function consoleln(string $msg, int $level = 0): string
    {
        return self::console($msg, $level, PHP_EOL);
    }
}

(new AutoPhpUnitTest)->run();
