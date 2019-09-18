<?php

/**
 * Eunionz PHP Framework Ftp Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\ftp;


use com\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');


/**
 * Ftp Component
 *
 */
class Ftp extends Component
{
    private $off;                          // 返回操作状态(成功/失败)
    private $conn_id=null;                // FTP连接
    private $mode=0;                       //0--正常模式，不使用任何ftp或共享文件夹    1--共享文件夹(NFS挂载或samba挂载)模式    2--FTP模式
    private $share_dir='';                //共享文件夹(NFS挂载或samba挂载点)路径

    private $ftp_host;
    private $ftp_port;
    private $ftp_user;
    private $ftp_pass;
    private $init_dir;

    private $curr_dir_path='';          //当前文件夹路径

    /**
     * 方法：FTP连接
     * @FTP_HOST -- FTP主机
     * @FTP_PORT -- 端口
     * @FTP_USER -- 用户名
     * @FTP_PASS -- 密码
     */
    public function __construct($UPLOAD_REOMTE_SHARE_DIR='',$FTP_HOST='',$FTP_PORT='',$FTP_USER='',$FTP_PASS='',$INIT_DIR='')
    {
        if(!$INIT_DIR){
            $this->init_dir= $this->getConfig('app','UPLOAD_REMOTE_INIT_DIR');
        }else{
            $this->init_dir=$INIT_DIR;
        }

        if($UPLOAD_REOMTE_SHARE_DIR || $this->getConfig('app','UPLOAD_REOMTE_SHARE_DIR')){
            $this->mode=1;
            $this->share_dir = $UPLOAD_REOMTE_SHARE_DIR?$UPLOAD_REOMTE_SHARE_DIR:$this->getConfig('app','UPLOAD_REOMTE_SHARE_DIR');
            $this->share_dir = str_ireplace("\\",'/',$this->share_dir);
            if(@chdir($this->share_dir)){
                $this->curr_dir_path = $this->share_dir;
                if($this->init_dir) $this->ftp_chdir($this->init_dir);
            }
        }else{
            if(!$FTP_HOST){
                $this->ftp_host=$this->getConfig('app','UPLOAD_REOMTE_FTP_SERVER');
            }else{
                $this->ftp_host=$FTP_HOST;
            }

            if(!$FTP_PORT){
                $this->ftp_port=$this->getConfig('app','UPLOAD_REOMTE_FTP_PORT');
            }else{
                $this->ftp_port=$FTP_PORT;
            }


            if(!$FTP_USER){
                $this->ftp_user=$this->getConfig('app','UPLOAD_REMOTE_FTP_USER');
            }else{
                $this->ftp_user=$FTP_USER;
            }


            if(!$FTP_PASS){
                $this->ftp_pass=$this->getConfig('app','UPLOAD_REMOTE_FTP_PASSWORD');
            }else{
                $this->ftp_pass=$FTP_PASS;
            }



            if($this->ftp_host){
                $this->conn_id = @ftp_connect($this->ftp_host,$this->ftp_port);
                if($this->conn_id){
                    if(@ftp_login($this->conn_id,$this->ftp_user,$this->ftp_pass)){
                        @ftp_pasv($this->conn_id,1); // 打开被动模拟
                        if($this->init_dir){
                            if(@ftp_chdir($this->conn_id,$this->init_dir)){
                                $this->curr_dir_path = $this->init_dir;
                            }
                        }
                        $this->mode=2;//FTP模式
                    }
                }
            }

        }
    }

    /**
     * @param $irectory  文件夹名称或路径
     *
     * @return bool
     */
    public function ftp_chdir($irectory){
        $irectory = str_ireplace("\\",'/',$irectory);
        switch($this->mode){
            case 0://普通模式,进入指定文件夹
                if(@chdir($irectory)){
                    $this->curr_dir_path = $irectory;
                    return true;
                }
                break;
            case 1://共享文件夹模式，进入共享文件夹根目录下指定目录
                if($this->startsWith($irectory,'/')){
                    if(@chdir($this->share_dir . $irectory)){
                        $this->curr_dir_path = $this->share_dir . $irectory;
                        return true;
                    }
                }else{
                    if(@chdir($this->share_dir . '/' . $irectory)){
                        $this->curr_dir_path = $this->share_dir . '/' . $irectory;
                        return true;
                    }
                }

                break;
            case 2://FTP模式，进入ftp根目录下指定目录
                if($this->conn_id){
                    if(@ftp_chdir($this->conn_id,$irectory)){
                        $this->curr_dir_path = $irectory;
                        return true;
                    }
                }
                break;
        }
        return false;
    }



    /**
     * 方法：上传文件
     * @path    -- 本地路径
     * @newpath -- 上传路径,如果为/打头的路径，表示将上传到当前目录下的指定目录下，如果是文件名，则直接上传到当前目录下
     * @del_local_file -- 在上传成功后是否删除本地文件  true--要删除， false--不删除
     */
    public function up_file($path,$newpath,$del_local_file=false)
    {
        $rs=false;
        switch($this->mode){
            case 0://普通模式,进入指定文件夹，不对web服务器下的文件进行任何操作，即不上传，也不删除原文件
                break;
            case 1://共享文件夹模式，将$path所对应的文件复制到当前文件夹，
                if(dirname($newpath))  @$this->dir_mkdirs(dirname($newpath));
                $rs=@copy($path,rtrim($this->curr_dir_path,'/') . '/' . ltrim($newpath,'/'));
                if($rs && $del_local_file){
                    @unlink($path);
                }
                break;
            case 2://FTP模式，进入ftp根目录下指定目录
                if($this->conn_id){
                    if(dirname($newpath)) @$this->dir_mkdirs(dirname($newpath));
                    if($this->startsWith($newpath,'/')){
                        $rs = @ftp_put($this->conn_id,$this->curr_dir_path . $newpath,$path,FTP_BINARY);
                    }else{
                        $rs = @ftp_put($this->conn_id,$this->curr_dir_path .'/' . $newpath,$path,FTP_BINARY);
                    }
                    if($rs && $del_local_file){
                        @unlink($path);
                    }
                }
                break;
        }
        return $rs;

    }

    /**
     * 方法：上传多文件，只能将多文件以文件名本身上传到当前文件夹下
     * @paths    -- 本地要上传文件路径数组
     * @del_local_file -- 在上传成功后是否删除本地文件  true--要删除， false--不删除
     * @return 返回数组，保存了每一个文件上传成功与否的值，true--成功,false--失败
     */
    public function up_files($paths,$del_local_file=true)
    {
        $arr=array();

        switch($this->mode){
            case 0://普通模式,不对web服务器下的文件进行任何操作，即不上传，也不删除原文件
                break;
            case 1://共享文件夹模式，将$paths所对应的所有文件以文件名本身复制到当前文件夹下，
                foreach($paths as $index=>$path){
                    $path =str_ireplace('//','/', str_ireplace("\\",'/',$path));
                    $app_real_path = str_ireplace('//','/', str_ireplace("\\",'/',APP_REAL_PATH));
                    $rel_path = str_ireplace($app_real_path,'/',$path);
                    $res_dir_path = $rel_path;
                    if($res_dir_path) {
                        $rs=$this->dir_mkdirs($res_dir_path);
                        if(!$rs){
                            $arr[$index]=0;
                            continue;
                        }
                    }
                    $arr[$index] = @copy($path,str_ireplace('//','/',$this->curr_dir_path . $rel_path));
                    if($arr[$index] && $del_local_file){//上传成功且要删除本地文件
                        @unlink($path);
                    }
                }
                break;
            case 2://FTP模式，进入ftp根目录下指定目录
                if($this->conn_id){
                    foreach($paths as $index=>$path){
                        $path =str_ireplace('//','/', str_ireplace("\\",'/',$path));
                        $app_real_path = str_ireplace('//','/', str_ireplace("\\",'/',APP_REAL_PATH));
                        $rel_path = str_ireplace($app_real_path,'/',$path);
                        $res_dir_path = $rel_path;
                        if($res_dir_path) {
                            $rs=$this->dir_mkdirs($res_dir_path);
                            if(!$rs){
                                $arr[$index]=0;
                                continue;
                            }
                        }
                        $arr[$index] = @ftp_put($this->conn_id,$this->curr_dir_path . $rel_path,$path,FTP_BINARY);
                        if($arr[$index] && $del_local_file){//上传成功且要删除本地文件
                            @unlink($path);
                        }
                    }
                }
                break;
        }


        return  $arr;
    }

    /**
     * 方法：删除多文件
     * @paths -- 要删除的多文件路径数组,$paths中每个元素或者仅包括文件名，或者是物理路径，如果仅包括文件名，则需要结合  $local_path进行路径组合
     * @local_path -- 非ftp或共享文件夹方式路径时，要删除的文件名所对应文件所在文件夹路径，不能/结束
     * @is_local -- true--本地删除  false--ftp删除
     * @return 返回数组，保存了每一个文件删除成功与否的值，true--成功,false--失败
     */
    public function del_files($paths,$local_path='', $is_local=false)
    {
        $app_real_path = str_ireplace('//','/', str_ireplace("\\",'/',APP_REAL_PATH));
        $local_path = str_ireplace('//','/', str_ireplace("\\",'/',$local_path));
        $arr=array();
        if($local_path && !$this->endsWith($local_path,'/')){
            $local_path=$local_path . '/';
        }
        if($is_local){
            //删除本地文件
            foreach($paths as $index=>$path){
                if($path && is_file($local_path . $path)){
                    $arr[$index] = @unlink($local_path . $path);
                }else{
                    $arr[$index] = false;
                }
            }

        }else{
            //删除远程文件
            switch($this->mode){
                case 0://普通模式,不对web服务器下的文件进行任何操作，不删除文件
                    foreach($paths as $index=>$path){
                        if($path && is_file($local_path . $path)){
                            $arr[$index] = @unlink($local_path . $path);
                        }else{
                            $arr[$index] = false;
                        }
                    }
                    break;
                case 1://共享文件夹模式，将$paths所对应的所有文件以文件名本身复制到当前文件夹下，
                    foreach($paths as $index=>$path){
                        $abs_path =rtrim($local_path,'/') . '/' . ltrim(str_ireplace('//','/', str_ireplace("\\",'/',$path)),'/');
                        $rel_path = str_ireplace($app_real_path,'/',$abs_path);
                        if($rel_path){
                            $arr[$index] = @unlink(rtrim($this->share_dir,'/') . '/' . ltrim($rel_path,'/'));
                        }else{
                            $arr[$index] = false;
                        }
                    }
                    break;
                case 2://FTP模式，进入ftp根目录下指定目录
                    if($this->conn_id){
                        foreach($paths as $index=>$path){
                            $abs_path =rtrim($local_path,'/') . '/' . ltrim(str_ireplace('//','/', str_ireplace("\\",'/',$path)),'/');
                            $rel_path = str_ireplace($app_real_path,'/',$abs_path);

                            if($rel_path){
                                $arr[$index] = @ftp_delete($this->conn_id, rtrim($this->share_dir,'/') . '/' . ltrim($rel_path,'/'));

                            }else{
                                $arr[$index] = false;
                            }
                        }
                    }
                    break;
            }

        }

        return  $arr;
    }



    /**
     * 方法：移动文件
     * @filename    -- 原文件名
     * @new_filename -- 新文件名
     * @local_path -- 仅普通模式时有效的原文件所在本地路径
     */
    public function move_file($filename,$new_filename,$local_path='')
    {
        switch($this->mode){
            case 0://普通模式,不对web服务器下的文件进行任何操作，不删除文件
                if($this->endsWith($local_path,'/')){
                    return @rename($local_path. $filename, $local_path . $new_filename);//对原长方形缩略图的原图更名
                }else{
                    return @rename($local_path. '/' . $filename, $local_path . '/' . $new_filename);//对原长方形缩略图的原图更名
                }
                break;
            case 1://共享文件夹模式，将将$paths所对应的所有文件以文件名本身复制到当前文件夹下，
                return @rename($this->curr_dir_path . '/'. $filename, $this->curr_dir_path . '/' . $new_filename);//对原长方形缩略图的原图更名
                break;
            case 2://FTP模式，进入ftp根目录下指定目录
                if($this->conn_id){
                    return @ftp_rename($this->conn_id,$this->curr_dir_path . '/'. $filename,$this->curr_dir_path . '/'. $new_filename);
                }
                break;
        }

        return false;
    }

    /**
     * 方法：删除文件
     * @filename -- 要删除的ftp服务器当前文件夹下的文件名
     * @local_path -- 非ftp方式路径时，要删除的文件名所对应文件所在文件夹路径，不能/结束
     * @is_local -- true--本地删除  false--ftp删除
     */
    public function del_file($filename,$local_path='',$is_local=false)
    {
        if($local_path && !$this->endsWith($local_path,'/')){
            $local_path=$local_path . '/';
        }
        if($is_local){
            if($filename && is_file($local_path . $filename)){
                return @unlink($local_path . $filename );
            }
        }else{
            switch($this->mode){
                case 0://普通模式,不对web服务器下的文件进行任何操作
                    if($filename && is_file($local_path . $filename)){
                        return @unlink($local_path . $filename );
                    }
                    break;
                case 1://共享文件夹模式
                    if($filename && is_file($this->curr_dir_path . '/' . $filename)){
                        return @unlink($this->curr_dir_path . '/' . $filename );
                    }
                    break;
                case 2://FTP模式，进入ftp根目录下指定目录
                    if($this->conn_id){
                        return @ftp_delete($this->conn_id,$this->curr_dir_path . '/'. $filename);
                    }
                    break;
            }

        }
        return false;
    }

    /**
     * 方法：生成目录
     * @path -- 包含文件名的文件完整路径，以/打头
     */
    public function dir_mkdirs($path)
    {

        switch($this->mode){
            case 0://普通模式
                return @mkdir($path,0777,true);
                break;
            case 1://共享文件夹模式
                $dir_path =str_ireplace('\\','/', rtrim($this->curr_dir_path , '/') . '/' . ltrim(dirname($path),'/'));
                $dir_path =str_ireplace('//','/', $dir_path);
                if(!file_exists($dir_path))  return @mkdir($dir_path,0777,true);
                return true;
                break;
            case 2://FTP模式
                if($this->conn_id){
                    $path_arr  = explode('/',$path);              // 取目录数组
                    $file_name = array_pop($path_arr);            // 弹出文件名
                    $path_div  = count($path_arr);                // 取层数


                    foreach($path_arr as $val)                    // 创建目录
                    {
                        if(empty($val)) {
                            @ftp_chdir($this->conn_id,'/');
                            continue;
                        }
                        if(@ftp_chdir($this->conn_id,$val) == FALSE)
                        {
                            $tmp = @ftp_mkdir($this->conn_id,$val);
//                            if($tmp == FALSE)
//                            {
//                                return false;
//                            }
                            @ftp_chdir($this->conn_id,$val);
                        }
                    }

                    if($this->init_dir){
                        @ftp_chdir($this->conn_id,$this->init_dir);
                    }else{
                        for($i=1;$i<=$path_div;$i++)                  // 回退到根
                        {
                            @ftp_cdup($this->conn_id);
                        }
                    }
                    if($this->init_dir) @ftp_chdir($this->conn_id,$this->init_dir);
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * 方法：关闭FTP连接
     */
    public function close()
    {
        if($this->conn_id){
            return @ftp_close($this->conn_id);
        }else{
            return false;
        }
    }


    public  function endsWith($string1,$string2){
        if(strlen($string1)<strlen($string2)){  //若第一个字符串长度小于第二个的时候，必须指定返回false，
            return false;                                   //否则substr_compare遇到这种情况会返回0（即相当，与事实不符合）
        }else{
            return !substr_compare($string1,$string2,strlen($string1)-strlen($string2),strlen($string2));//从第一个字符串减去第二个字符串长度处开始判断
        }
    }

    public function startsWith($str, $needle) {
        return strpos($str, $needle) === 0;
    }




    /************************************** 测试 ***********************************
    $ftp = new ftp('222.13.67.42',21,'hlj','123456');          // 打开FTP连接
    $ftp->up_file('aa.wav','test/13548957217/bb.wav');         // 上传文件
    //$ftp->move_file('aaa/aaa.php','aaa.php');                // 移动文件
    //$ftp->copy_file('aaa.php','aaa/aaa.php');                // 复制文件
    //$ftp->del_file('aaa.php');                               // 删除文件
    $ftp->close();                                             // 关闭FTP连接
    //******************************************************************************/


}
