<?php
/**
 * EUnionZ PHP Framework mysqldump Plugin class
 * Mysql dump class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\mysqldump;


defined('APP_IN') or exit('Access Denied');

/**
 * * Mysql dump class
 * Class Mysqldump
 * @package plugin\mysqldump
 */
class Mysqldump extends \cn\eunionz\core\Plugin
{

    public $is_debug=true;
    public $format_space='&nbsp;&nbsp;&nbsp;&nbsp;';

    /**
     * 要导出记录的表，导出所有记录有key的仅导致该key对应的字段值为0的所有记录
     * @var array
     */
    public $dump_data_tables;

    public function db_dump($host,$user,$pwd,$db,$filename) {
        $this->dump_data_tables = $this->getConfig('global','dump_data_tables');
        set_time_limit(0);
        $mysqlconlink = mysqli_connect($host,$user,$pwd , $db);
        if (!$mysqlconlink){
            if($this->is_debug){
                echo sprintf($this->format_space . '<span class="fred">No MySQL connection: %s',mysqli_error($mysqlconlink))."</span><br/>";
                flush();
                return false;
            }

        }

        mysqli_set_charset($mysqlconlink, 'utf8' );
        $mysqldblink = mysqli_select_db($mysqlconlink,$db);
        if (!$mysqldblink){
            if($this->is_debug){
                echo sprintf($this->format_space . '<span class="fred">No MySQL connection to database: %s',mysqli_error($mysqlconlink))."</span><br/>";
                flush();
                return false;
            }
        }
        $tabelstobackup=array();
        $result=mysqli_query($mysqlconlink,"SHOW TABLES FROM `$db`");
        if (!$result){
            if($this->is_debug){
                echo sprintf($this->format_space . '<span class="fred">Database error %1$s for query %2$s', mysqli_error($mysqlconlink), "SHOW TABLE STATUS FROM `$db`;")."</span><br/>";
                flush();
                return false;
            }
        }

        while ($data = mysqli_fetch_row($result)) {
            $tabelstobackup[]=$data[0];
        }
        if (count($tabelstobackup)>0) {
            $result=mysqli_query($mysqlconlink,"SHOW TABLE STATUS FROM `$db`");
            if (!$result){
                if($this->is_debug){
                    echo sprintf($this->format_space . '<span class="fred">Database error %1$s for query %2$s', mysqli_error($mysqlconlink), "SHOW TABLE STATUS FROM `$db`;")."</span><br/>";
                    flush();
                    return false;
                }
            }
            while ($data = mysqli_fetch_assoc($result)) {
                $status[$data['Name']]=$data;
            }
            if ($file = fopen($filename, 'wb')) {
                //fwrite($file, "-- ---------------------------------------------------------\n");
                //fwrite($file, "-- Database Name: $db\n");
                //fwrite($file, "-- ---------------------------------------------------------\n\n");
                fwrite($file, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;#;;;;\n");
                fwrite($file, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;#;;;;\n");
                fwrite($file, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;#;;;;\n");
                fwrite($file, "/*!40101 SET NAMES 'utf8' */;#;;;;\n");
                fwrite($file, "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;#;;;;\n");
                fwrite($file, "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;#;;;;\n");
                fwrite($file, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;#;;;;\n");
                fwrite($file, "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;#;;;;\n");
                fwrite($file, "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;#;;;;\n\n");
                foreach($tabelstobackup as $table) {
                    if($this->is_debug){
                        echo sprintf($this->format_space . '<span class="fblack">Dump database table "%s"',$table)."</span><br/>";
                        flush();
                    }
                    $this->need_free_memory(($status[$table]['Data_length']+$status[$table]['Index_length'])*3);
                    $this->_db_dump_table($table,$status[$table],$file,$mysqlconlink);
                }
//                fwrite($file, "\n");
                fwrite($file, "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;#;;;;\n");
                fwrite($file, "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;#;;;;\n");
                fwrite($file, "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;#;;;;\n");
                fwrite($file, "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;#;;;;\n");
                fwrite($file, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;#;;;;\n");
                fwrite($file, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;#;;;;\n");
                fwrite($file, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;#;;;;\n");
                fwrite($file, "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;#;;;;\n");
                fclose($file);
                if($this->is_debug){
                    echo $this->format_space . '<span class="fblack">Database dump done!</span><br/>';
                    flush();
                }
            } else {
                if($this->is_debug){
                    echo $this->format_space . '<span class="fred">Can not create database dump!</span><br/>';
                    flush();
                    return false;
                }
            }
        } else {
            if($this->is_debug){
                echo $this->format_space . '<span class="fred">No tables to dump!</span><br/>';
                flush();
            }
        }
        return true;
    }

    public function _db_dump_table($table,$status,$file,&$mysqlconlink) {
//        fwrite($file, "\n");
        //fwrite($file, "--\n");
        //fwrite($file, "-- Table structure for table $table\n");
        //fwrite($file, "--\n\n");
        fwrite($file, "DROP TABLE IF EXISTS `" . $table .  "`;#;;;;\n");
        fwrite($file, "/*!40101 SET @saved_cs_client     = @@character_set_client */;#;;;;\n");
        fwrite($file, "/*!40101 SET character_set_client = 'utf8' */;#;;;;\n");
        $result=mysqli_query($mysqlconlink,"SHOW CREATE TABLE `".$table."`");
        if (!$result) {
            if($this->is_debug){
                echo sprintf($this->format_space . '<span class="fred">Database error %1$s for query %2$s', mysqli_error($mysqlconlink), "SHOW CREATE TABLE `".$table."`")."</span><br/>";
                flush();
            }
            return false;
        }
        $tablestruc=mysqli_fetch_assoc($result);
        fwrite($file, $tablestruc['Create Table'].";#;;;;\n");
        fwrite($file, "/*!40101 SET character_set_client = @saved_cs_client */;#;;;;\n");
        if(in_array($table,$this->dump_data_tables)){
            $where="";
            foreach ($this->dump_data_tables as $t_field=>$t_table){
                if($t_table==$table){
                    if(!is_numeric($t_field)){
                        $where=" WHERE `{$t_field}`=0";
                    }
                    break;
                }
            }
            $result=mysqli_query($mysqlconlink,"SELECT * FROM `".$table."`".$where);
            if (!$result) {
                if($this->is_debug){
                    echo sprintf($this->format_space . '<span class="fred">Database error %1$s for query %2$s', mysqli_error($mysqlconlink), "SELECT * FROM `".$table."`")."</span><br/>";
                    flush();
                }
                return false;
            }
//        fwrite($file, "--\n");
//        fwrite($file, "-- Dumping data for table $table\n");
//        fwrite($file, "--\n\n");
            if ($status['Engine']=='MyISAM')
                fwrite($file, "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;#;;;;\n");
            while ($data = mysqli_fetch_assoc($result)) {
                $keys = array();
                $values = array();
                foreach($data as $key => $value) {
                    if($value === NULL)
                        $value = "NULL";
                    elseif($value === "" or $value === false)
                        $value = "''";
                    elseif(!is_numeric($value))
                        $value = "'".mysqli_real_escape_string($mysqlconlink,$value)."'";
                    $values[] = $value;
                }
                fwrite($file, "INSERT INTO `".$table."` VALUES ( ".implode(", ",$values)." );#;;;;\n");
            }
        }
        if ($status['Engine']=='MyISAM')
            fwrite($file, "/*!40000 ALTER TABLE ".$table." ENABLE KEYS */;#;;;;\n");
    }


    public function need_free_memory($memneed) {
        if (!function_exists('memory_get_usage'))
            return;
        $needmemory=@memory_get_usage(true)+$this->inbytes($memneed);
        if ($needmemory>$this->inbytes(ini_get('memory_limit'))) {
            $newmemory=round($needmemory/1024/1024)+1 .'M';
            if ($needmemory>=1073741824)
                $newmemory=round($needmemory/1024/1024/1024) .'G';
            if ($oldmem=@ini_set('memory_limit', $newmemory)){
                if($this->is_debug){
                    echo sprintf($this->format_space . '<span class="fblack">Memory increased from %1$s to %2$s','backwpup',$oldmem,@ini_get('memory_limit'))."</span><br/>";
                    flush();
                }
            }
            else{
                if($this->is_debug){
                    echo sprintf($this->format_space . '<span class="fblack">Can not increase memory limit is %1$s','backwpup',@ini_get('memory_limit'))."</span><br/>";
                    flush();
                }

            }
        }
    }

    public function inbytes($value) {
        $multi=strtoupper(substr(trim($value),-1));
        $bytes=abs(intval(trim($value)));
        if ($multi=='G')
            $bytes=$bytes*1024*1024*1024;
        if ($multi=='M')
            $bytes=$bytes*1024*1024;
        if ($multi=='K')
            $bytes=$bytes*1024;
        return $bytes;
    }



}
