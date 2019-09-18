<?php
/**
 * 统计目录下的文件行数及总文件数··去除注释
  * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\codelinecount;


defined('APP_IN') or exit('Access Denied');


/**
 * 执行目录中文件的统计（包括文件数及总行数
 *
 * 1、跳过文件的时候：
 * 匹配的规则只是从文件名上着手，匹配的规则也仅限在开头。
 * 2、跳过文件中的注释行：
 * 匹配的规则只是从注释段落的头部匹配，如果出现// 及 *及 #及/*开头的行及空行会被跳过。所以类似/*这种多汗注释，每行的开头都必须加上*号，否则无法匹配到这种的注释。
 * 3、目录过滤：
 * 匹配的规则是从目录名的全名匹配
 */
class Codelinecount extends \cn\eunionz\core\Plugin{
    /**
     * 统计的后缀
     */
    private $ext = ".php";
    /**
     * 是否显示每个文件的统计数
     */
    private $showEveryFile = true;
    /**
     * 文件的的跳过规则
     */
    private $fileSkip = array();
    /**
     * 统计的跳过行规则
     */
    private $lineSkip = array("*", "/*", "//", "#");
    /**
     * 统计跳过的目录规则
     */
    private $dirSkip = array(".", "..", '.svn');

    public function __construct($ext = '', $dir = '', $showEveryFile = true, $dirSkip = array(), $lineSkip = array(), $fileSkip = array()) {
        $this->setExt($ext);
        $this->setDirSkip($dirSkip);
        $this->setFileSkip($fileSkip);
        $this->setLineSkip($lineSkip);
        $this->setShowFlag($showEveryFile);
        $this->run($dir);
    }

    public function setExt($ext) {
        trim($ext) && $this->ext = strtolower(trim($ext));
    }
    public function setShowFlag($flag = true) {
        $this->showEveryFile = $flag;
    }
    public function setDirSkip($dirSkip) {
        $dirSkip && is_array($dirSkip) && $this->dirSkip = $dirSkip;
    }
    public function setFileSkip($fileSkip) {
        $this->fileSkip = $fileSkip;
    }
    public function setLineSkip($lineSkip) {
        $lineSkip && is_array($lineSkip) && $this->lineSkip = array_merge($this->lineSkip, $lineSkip);
    }
    /**
     * 执行统计
     * @param string $dir 统计的目录
     */
    public function run($dir = '') {
        if ($dir == '') return;
        if (!is_dir($dir)) exit('Path error!');
        $this->dump($dir, $this->readDir($dir));
    }

    /**
     * 显示统计结果
     * @param string $dir 目录
     * @param array $result 统计结果（包含总行数，有效函数，总文件数
     */
    private function dump($dir, $result) {
        $totalLine = $result['totalLine'];
        $lineNum = $result['lineNum'];
        $fileNum = $result['fileNum'];
        echo "*************************************************************\r\n<br/>";
        echo $dir . ":\r\n<br/>";
        echo "TotalLine: " . $totalLine . "\r\n<br/>";
        echo "TotalLine with no comment and empty: " . $lineNum . "\r\n<br/>";
        echo 'TotalFiles:' . $fileNum . "\r\n<br/>";
    }

    /**
     * 读取目录
     * @param string $dir 目录
     */
    private function readDir($dir) {
        $num = array('totalLine' => 0, 'lineNum' => 0, 'fileNum' => 0);
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($this->skipDir($file)) continue;
                if (is_dir($dir . '/' . $file)) {
                    $result = $this->readDir($dir . '/' . $file);
                    $num['totalLine'] += $result['totalLine'];
                    $num['lineNum'] += $result['lineNum'];
                    $num['fileNum'] += $result['fileNum'];
                } else {
                    if ($this->skipFile($file)) continue;
                    list($num1, $num2) = $this->readfiles($dir . '/' . $file);
                    $num['totalLine'] += $num1;
                    $num['lineNum'] += $num2;
                    $num['fileNum']++;
                }
            }
            closedir($dh);
        } else {
            echo 'open dir <' . $dir . '> error!' . "\r";
        }
        return $num;
    }

    /**
     * 读取文件
     * @param string $file 文件
     */
    private function readfiles($file) {
        $str = file($file);
        $linenum = 0;
        foreach ($str as $value) {
            if ($this->skipLine(trim($value))) continue;
            $linenum++;
        }
        $totalnum = count(file($file));
        if (!$this->showEveryFile) return array($totalnum, $linenum);
        echo $file . "\r\n";
        echo 'TotalLine in the file:' . $totalnum . "\r\n";
        echo 'TotalLine with no comment and empty in the file:' . $linenum . "\r\n";
        return array($totalnum, $linenum);
    }

    /**
     * 执行跳过的目录规则
     * @param string $dir 目录名
     */
    private function skipDir($dir) {
        if (in_array($dir, $this->dirSkip)) return true;
        return false;
    }

    /**
     * 执行跳过的文件规则
     * @param string $file 文件名
     */
    private function skipFile($file) {
        if (strtolower(strrchr($file, '.')) != $this->ext) return true;
        if (!$this->fileSkip) return false;
        foreach ($this->fileSkip as $skip) {
            if (strpos($file, $skip) === 0) return true;
        }
        return false;
    }

    /**
     * 执行文件中行的跳过规则
     * @param string $string 行内容
     */
    private function skipLine($string) {
        if ($string == '') return true;
        foreach ($this->lineSkip as $tag) {
            if (strpos($string, $tag) === 0) return true;
        }
        return false;
    }

    //
    //$obj = new Codelinecount();
    ////如果设置为false,这不会显示每个文件的信息，否则显示
    //$obj->setShowFlag(false);
    ////会跳过所有All开头的文件
    //$obj->setFileSkip(array('All'));
    //$obj->run("D:\PHPAPP\php\_tests");
    //
    ////所有文件，（默认格式为.php)
    //$obj->setFileSkip(array());
    //$obj->run("D:\PHPAPP\php");
    //
    //$obj->setShowFlag(true);
    ////跳过所有I和A开头的文件，（比如接口和抽象类开头）
    //$obj->setFileSkip(array('I', 'A'));
    //$obj->run("D:\PHPAPP\php");

}