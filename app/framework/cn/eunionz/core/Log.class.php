<?php
declare(strict_types=1);
/**
 * Eunionz PHP Framework Log class (Log record )
 * Created by PhpStorm.
 * User: liulin (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午11:55
 */

namespace cn\eunionz\core;

use cn\eunionz\exception\LogException;

defined('APP_IN') or exit('Access Denied');

class Log extends Kernel
{
    // log write on-off
    public $_log_enable = true;

    // log path
    public $_log_path = 'log';

    // log write level
    public $_log_level = 1;

    // log date format
    public $_date_format = 'Y-m-d H:i:s';

    public function __construct()
    {
        $this->_log_enable = self::getConfig('app', 'APP_LOG');
        if (!ctx()) {
            $this->_log_path = $GLOBALS['APP_RUNTIME_REAL_PATH'] . self::getConfig('app', 'APP_LOG_DIR') . APP_DS;
        } else {
            if(ctx()->getAppRuntimeRealPath()){
                $this->_log_path = ctx()->getAppRuntimeRealPath() . self::getConfig('app', 'APP_LOG_DIR') . APP_DS;
            }else{
                $this->_log_path = $GLOBALS['APP_RUNTIME_REAL_PATH'] . self::getConfig('app', 'APP_LOG_DIR') . APP_DS;
            }
        }
        $this->_log_level = self::getConfig('app', 'APP_LOG_LEVEL');
    }



    /**
     * Write Log
     *
     * @param int $level 1--ERROR  2--WARNING  3--DEBUG  4--INFO   5--ALL
     * @param string $message
     * @param string $filename
     * @return bool
     * @throws LogException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function log(int $level = APP_ERROR,string $message, string $filename = '') : bool
    {

        if ($this->_log_enable === false)
            return false;

        if ($level == APP_ERROR)
            $level_text = 'ERROR';
        else if ($level == APP_WARNING)
            $level_text = 'WARNING';
        else if ($level == APP_DEBUG)
            $level_text = 'DEBUG';
        else if ($level == APP_INFO)
            $level_text = 'INFO';
        else if ($level == APP_ALL)
            $level_text = 'ALL';
        else
            return false;

        if ($level > $this->_log_level)
            return false;

        if (empty($filename)) {
            $file_path = $this->_log_path . date('Y_m_d') . '.log.php';
        } else {
            $file_path = $this->_log_path . $filename . '.log.php';
        }

        $message_str = '';

        if (!file_exists($this->_log_path))
            @mkdir($this->_log_path, 0777, true);

        if (!file_exists($file_path))
            $message_str = "<?php  defined('APP_IN') or exit('Access Denied'); ?>" . PHP_EOL . PHP_EOL;

        if (file_exists($file_path) && @filesize($file_path) > self::getConfig('app', 'APP_LOG_MAXSIZE')) {
            @unlink($file_path);
            $message_str = "<?php  defined('APP_IN') or exit('Access Denied'); ?>" . PHP_EOL . PHP_EOL;
        }


        if (!$fp = @fopen($file_path, 'ab'))
            throw new LogException(ctx()->getI18n()->getLang('error_log_write_file_title'), ctx()->getI18n()->getLang('error_log_write_file'));

        $message_str .= $level_text . ' ' . (($level == APP_INFO) ? ' -' : '-') . ' ' . date($this->_date_format) . ' --> ' . $message . PHP_EOL;

        flock($fp, LOCK_EX);
        fwrite($fp, $message_str);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($file_path, 0666);
        return  true;
    }

    /**
     * Write error Log
     * @param string $message
     * @param string $filename
     * @return bool
     * @throws LogException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function error(string $message,string $filename = '')
    {
        return $this->log(APP_ERROR , $message, $filename);
    }

    /**
     * Write warn Log
     * @param string $message
     * @param string $filename
     * @return bool
     * @throws LogException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function warn(string $message,string $filename = '')
    {
        return $this->log(APP_WARNING , $message, $filename);
    }

    /**
     * Write debug Log
     * @param string $message
     * @param string $filename
     * @return bool
     * @throws LogException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */

    public function debug(string $message,string $filename = '')
    {
        return $this->log(APP_DEBUG , $message, $filename);
    }

    /**
     * Write info Log
     * @param string $message
     * @param string $filename
     * @return bool
     * @throws LogException
     * @throws \cn\eunionz\exception\FileNotFoundException
     */
    public function info(string $message,string $filename = '')
    {
        return $this->log(APP_INFO , $message, $filename);
    }
}
