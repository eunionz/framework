<?php
/**
 * Eunionz PHP Framework Upload component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\upload;


defined('APP_IN') or exit('Access Denied');

/**
 * 上传
 * 
 * 实现文件上传
 * 依赖file组件
 *
 */

class Upload extends \cn\eunionz\core\Component
{
	// 实例
	static protected $_instance;
	
	private $_path = array();

	/**
	 * 上传处理
	 * 
	 * 将上传后的文件按照规则处理
	 * @qrcode
	 * $this->move($_FILES, 'temp/uploadfiles/','date','jpg',1024*1024)
	 * @endcode
	 *
	 * @param	array 上传文件$_FILES信息数组
	 * @param	string 存储目录
	 * @param	string 命名规则[null][md5|date]，默认不更改名字。
	 * @param	string 允许扩展名[jpg,gif,png]，默认不限制。
	 * @param	integer 文件大小上限，以KB为单位，默认不限制。
     * @param	string  提示类型，js--js提示   pop--弹窗提示
	 * @return	bool
	 */
	public function move($data, $savePath, $nameRules = null, $extension = null, $maxSize = null,$prompt_type="js")
	{
		$this->_path = array();
		if (empty($data) || !is_array($data))
		{
			return false;
		}
        $prompt_type = strtolower($prompt_type);
        if($data['error']>0 && $data['error']!=UPLOAD_ERR_NO_FILE)
        {
            if($prompt_type=="js"){
                header("Content-Type:text/html;charset=utf-8");
                echo '<script>alert("上传文件大小超出最大文件大小【' . $maxSize .'】字节，文件上传失败！");history.back();</script>';
                exit;
            }elseif($prompt_type=="pop"){
                $this->loadPlugin('common')->write_message('上传文件大小超出最大文件大小【' . $maxSize .'】字节，文件上传失败！');
            }
        }elseif($data['error']==UPLOAD_ERR_NO_FILE){
            return false;
        }

		$data = $this->dealFiles($data);
		
		foreach ( $data as $file )
		{
			// 跳过无效上传
			if (empty($file['name']))
			{
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_filename',array($file['name'])));
			}

		    // 上传文件的扩展信息
			$file['extension'] = $this->getExtension($file['name']);
			$file['savepath'] = $savePath;
			$file['savename'] = $this->getSaveName($file['name'], $nameRules);
			
			// 检查文件大小
			if (!$this->checkSize($file['size'], $maxSize))
			{
				@unlink($file['tmp_name']);
				return false;
			}
			
			// 检查文件类型
			if (!$this->checkExt($file['extension'], $extension,$prompt_type))
			{
				@unlink($file['tmp_name']);
				return false;
			}
			
			// 检查是否合法上传
			if (!$this->checkUpload($file['tmp_name']))
			{
				@unlink($file['tmp_name']);
				return false;
			}
			
			// 上传文件

			$_path = $this->save($file);
			
			if (false === $_path)
				return false;
			
			$this->_path[] = $_path;
			unset($_path);
		}
		
		return true;
	}


    /**
     * 上传处理
     *
     * 将上传后的文件按照规则处理 <input type='file' name='myfile[]' />
     * @qrcode
     * $this->moves($_FILES['myfile'], 'temp/uploadfiles/','date','jpg',1024*1024)
     * @endcode
     *
     * @param	array 上传文件$_FILES信息数组
     * @param	string 存储目录
     * @param	string 命名规则[null][md5|date]，默认不更改名字。
     * @param	string 允许扩展名[jpg,gif,png]，默认不限制。
     * @param	integer 文件大小上限，以KB为单位，默认不限制。
     * @return	bool
     */
    public function moves($data, $savePath, $nameRules = null, $extension = null, $maxSize = null)
    {
        $this->_path = array();

        if (empty($data) || !is_array($data))
        {
            throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_files'));
        }

        foreach($data['name'] as $key => $val){
            if($val){
                // 上传文件的扩展信息
                $file['extension'] = $this->getExtension($val);
                $file['savepath'] = $savePath;
                $file['savename'] = $this->getSaveName($val, $nameRules);
                // 检查文件大小
                if (!$this->checkSize($data['size'][$key]  , $maxSize))
                {
                    @unlink($data['tmp_name'][$key]);
                    return false;
                }
                // 检查文件类型
                if (!$this->checkExt($file['extension'], $extension))
                {
                    @unlink($data['tmp_name'][$key]);
                    return false;
                }


                // 检查是否合法上传
                if (!$this->checkUpload($data['tmp_name'][$key]))
                {
                    @unlink($data['tmp_name'][$key]);
                    return false;
                }

                $file['tmp_name'] = $data['tmp_name'][$key];

                // 上传文件
                $_path = $this->save($file);

                if (false === $_path)
                    return false;

                $this->_path[] = $_path;
                unset($_path);


            }
        }

        return true;
    }


	/**
	 * 本地存储
	 *
	 * @param	array $file  保存文件的相关参数
	 * @return	保存文件的url路径
	 */
	private function save($file)
	{
		// 获取app.config.php中的文件上传配置参数
        //1、如果是本地文件上传



        //2.如果是上传到本地共享文件夹中


        //3.如果是上传到远程ftp站点中


        //4.如果是上传到百度云存储中




		$file['savepath'] = $this->getDirectory($file['savepath']);
		$file_path = $file['savepath'] . $file['savename'] . '.' . $file['extension'];
		
		if (!move_uploaded_file($file['tmp_name'], iconv("UTF-8","gb2312",$file_path)))
		{
            throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_save'));
		}
		
		@chmod($file_path, 0777);
		
		return $file_path;
	}


	/**
	 * 格式化FIELS数组
	 *
	 * @param	array FILES数组
	 * @return	array 格式化后的数组
	 */
	private function dealFiles($files)
	{
		$fileArray = array();
		
		if (isset($files['name']) && is_string($files['name']))
		{
			$_files[] = $files;
			$files = $_files;
		}
		
		foreach ( $files as $file )
		{
			if (is_array($file['name']))
			{
				$keys = array_keys($file);
				$count = count($file['name']);
				
				for ($i = 0; $i < $count; $i++)
					foreach ( $keys as $key )
						$fileArray[$i][$key] = $file[$key][$i];
			}
			else
			{
				$fileArray = $files;
			}
		}
		
		return $fileArray;
	}

	/**
	 * 检查扩展名
	 *
	 * @param	string 文件扩展名
	 * @param	string 允许的扩展名列表
	 * 列表格式为'jpg,gif,exe'
	 * @return	bool
	 */
	private function checkExt($ext, $extension,$prompt_type="js")
	{
		if (!empty($extension))
		{
			$extension = str_replace(' ', '', $extension);
			$extension = explode(',', $extension);

			if (!in_array(strtolower($ext), $extension))
			{
                if(strtolower($prompt_type)=='js'){
                    header("Content-Type:text/html;charset=utf-8");
                    echo '<script>alert("' . $this->getLang('error_upload_extension',array($ext)) .'！");history.back();</script>';
                    exit;
                }elseif($prompt_type=="pop"){
                    $this->loadPlugin('common')->write_message($this->getLang('error_upload_extension',array($ext)).'！');
                }
                //throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_extension',array($ext)));
			}
		}
		
		return true;
	}

	/**
	 * 检查文件大小
	 *
	 * @param	integer 文件大小
	 * @param	integer 文件大小上限值
	 * @return	bool
	 */
	private function checkSize($size, $maxSize)
	{
		if (!empty($maxSize))
		{
			if ($size > $maxSize)
			{
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_filesize',array(ceil($maxSize / 1024))));
			}
		}
		
		return true;
	}

	/**
	 * 检查非法提交
	 *
	 * @param	string 文件名
	 * @return	bool
	 */
	private function checkUpload($filename)
	{
		if (!is_uploaded_file($filename))
		{
            throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_mode'));
		}
		
		return true;
	}

	/**
	 * 获取上传后的文件名
	 *
	 * @param	string 文件名
	 * @param	string 命名规则
	 * @return string 新文件名
	 */
	private function getSaveName($fileName, $fileNameRules)
	{
		if(!empty($fileNameRules))
		{
			switch (strtolower($fileNameRules))
			{
				case 'date' :
					$fileName = date('YmdHis') . '_' . rand(1, 1000000);
					break;
				case 'md5' :
					$fileName = md5(rand(1, 1000000) . microtime());
					break;
				default :
					if (!empty($fileNameRules))
						$fileName = $fileNameRules;
					break;
			}
		}else{
			$fileName = substr($fileName, 0, strrpos($fileName, '.'));
		}
		return $fileName;
	}

	/**
	 * 获取上传文件后缀
	 *
	 * @param	string 文件名
	 * @return string 后缀
	 */
	private function getExtension($filename)
	{
		$pathinfo = pathinfo($filename);
		return $pathinfo['extension'];
	}

	/**
	 * 获取存储路径
	 *
	 * @param	string 存储路径
	 * @return string 新的存储路径
	 */
	private function getDirectory($savePath)
	{
		if (!is_dir($savePath))
		{
			if (!mkdir($savePath, 0777, true))
			{
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_mkdir',array($savePath)));
			}
		}
		
		if (!is_writable($savePath))
		{
            throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_dir_write',array($savePath)));
		}
		
		if (substr($savePath, -1) != "/")
		{
			$savePath .= "/";
		}
		
		return $savePath;
	}

	/**
	 * 返回错误代码说明
	 *
	 * @param	integer 错误代码
	 * @return	bool
	 */
	private function getErrorCode($errorNo)
	{
		switch ($errorNo)
		{
			case 0 :
				return true;
				break;
			case 1 :
			case 2 :
			case 3 :
			case 4 :
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_' . $errorNo . '_code'));
				break;
			case 6 :
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_' . $errorNo . '_code'));
                break;
			case 7 :
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_' . $errorNo . '_code'));
				break;
			default :
                throw new \cn\eunionz\exception\UploadException($this->getLang('error_upload_title'),$this->getLang('error_upload_0_code'));

		}
		
		return true;
	}

	/**
	 * 返回路径
	 * 
	 * 返回上传后的文件路径信息
	 *
	 * @return	array
	 */
	public function getPath()
	{
		return count($this->_path) == 1 ? $this->_path[0] : $this->_path;
	}
}
