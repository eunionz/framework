<?php
/**
 * Eunionz PHP Framework Image component class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\image;

defined('APP_IN') or exit('Access Denied');

/**
 * 图像处理
 * 
 * 读取输出图像、创建图像、水印文字与图像、旋转、锐化、缩放图像。
 * 依赖PHP GD库
 * 
 */
class Image extends \com\eunionz\core\Component
{
	// 源图像路径
	private $_src;
	
	// 源图像句柄
	private $_srcIm;
	
	// 源图像宽度
	private $_srcWidth;
	
	// 源图像高度
	private $_srcHeight;

	// 源图像类型
	private $_srcType;

    private  $_fill_color=[255,255,255];

	/**
	 * 加载图像
	 * 从源图创建新图像时，提供图像加载
	 * 加载成功返回true，并创建图像句柄、宽、高和图像类型，否则返回false
	 * 
	 * @qrcode
	 * $imgSrc = dirname(__FILE__) . '/img/test.jpg';
	 * //加载指定的源图像
	 * $this->load($imgSrc);
	 * $this->render();
	 * @endcode
	 * 
	 * @param	string 图像路径
	 * @return	bool
	 */
	public function load($imageSrc)
	{
		$this->_src = $imageSrc;
		
		// 从源文件创建图像
		$result = $this->createImgFromSourceFile($imageSrc);
		
		if (!is_array($result) || empty($result))
			return false;
		
		list($this->_srcIm, $this->_srcWidth, $this->_srcHeight, $this->_srcType) = $result;
		
		unset($result);
		
		return true;
	}

	/**
	 * 创建图像
	 * 创建空白画布
	 * 
	 * @qrcode
	 * //创建一个指定背景色为黑色，宽:300px，高:200px的png类型的空白画布
	 * $this->create(300, 200, 'png', array(0, 0, 0));
	 * $this->render();
	 * @endcode
	 * 
	 * @param	integer 宽度
	 * @param	integer 高度
	 * @param	string  图片类型 [jpg|png|gif]
	 * @param	array   背景色,默认随机，其RGB数组格式如：array(255, 255, 255)
	 * @return	bool
	 */
	public function create($width, $height, $type = 'gif', array $color = array())
	{
		$this->_src = 'create_temp.' . $type;
		$this->_srcWidth = $width;
		$this->_srcHeight = $height;
		$this->_srcIm = $this->createTargetImgIdentifier($width, $height);
		
		if (empty($color))
		{
			$color = array(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
			
			imagefill($this->_srcIm, 0, 0, imagecolorallocate($this->_srcIm, $color[0], $color[1], $color[2]));
			
			for ($i = 0; $i < 3; $i++)
				imagerectangle($this->_srcIm, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), imagecolorallocate($this->_srcIm, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
			
			for ($i = 0; $i < 50; $i++)
				imagesetpixel($this->_srcIm, mt_rand(0, $width), mt_rand(0, $height), imagecolorallocate($this->_srcIm, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
		}
		else
		{
			imagefill($this->_srcIm, 0, 0, imagecolorallocate($this->_srcIm, $color[0], $color[1], $color[2]));
		}

		return true;
	}

	/**
	 * 通过源文件创建新图像
	 * 
	 * @param	string 图像路径
	 * @return	array 返回图像资源与图像信息
	 */
	private function createImgFromSourceFile($src)
	{
		if (!is_file($src) || !file_exists($src) || !is_readable($src))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_read',array($src)));
		}
		elseif (!function_exists('imagecreatefromgif') || !function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng'))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_gd'));
		}
		elseif (!list($sourceImageWidth, $sourceImageHeight, $sourceImageType) = @getimagesize($src))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_read',array($src)));
		}
		else
		{

            try{
                // 检查扩展名
                switch ($sourceImageType)
                {
                    // gif
                    case 1 :

                        // 获取GIF透明色
                        $fp = fopen($src, "rb");
                        $result = fread($fp, 13);
                        $colorFlag = ord(substr($result, 10, 1)) >> 7;
                        $background = ord(substr($result, 11));

                        if ($colorFlag)
                        {
                            $tableSizeNeeded = ($background + 1) * 3;
                            $result = fread($fp, $tableSizeNeeded);
                            $this->transparentColorRed = ord(substr($result, $background * 3, 1));
                            $this->transparentColorGreen = ord(substr($result, $background * 3 + 1, 1));
                            $this->transparentColorBlue = ord(substr($result, $background * 3 + 2, 1));
                        }

                        fclose($fp);

                        $sourceImageIdentifier = imagecreatefromgif($src);
                        break;

                    // jpg
                    case 2 :
                        $sourceImageIdentifier = imagecreatefromjpeg($src);
                        break;

                    // png
                    case 3 :
                        $sourceImageIdentifier = imagecreatefrompng($src);
                        break;

                    default :
                        /*$error = "不支持的文件格式[$src]";
                        $this->setError($error);
                        return false;*/
                        return false;
                }

            }catch (\Exception $err){
                return false;
            }

		}
		
		// 返回图像资源与图像信息
		return array($sourceImageIdentifier, $sourceImageWidth, $sourceImageHeight, $sourceImageType);
	
	}

	/**
	 * 创建空白图像
	 *
	 */
	private function createTargetImgIdentifier($width, $height)
	{
		if (!function_exists('imagecreatetruecolor'))
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_gd'));

		// 创建空白图像
		$targetImageIdentifier = imagecreatetruecolor((int)$width <= 0 ? 1 : (int)$width, (int)$height <= 0 ? 1 : (int)$height);
		
		// 返回图像标示符
		return $targetImageIdentifier;
	
	}

	/**
	 * 输出图像
	 * 
	 * @param	string  图像存储路径
	 * @param	integer 图像质量
	 * @param	bool    是否立即输出到浏览器
	 */
	private function outputTargetImg($target, $quality = 100, $render = false)
	{
		if (!is_resource($this->_srcIm))
		{
			/*$error = "未加载图像";
			$this->setError($error);
			return false;*/
			exit();
		}
		
		// 获取目标文件扩展名
		$type = strtolower(substr($target, strrpos($target, ".") + 1));
		
		if (!in_array($type, array('gif', 'jpg', 'jpeg', 'png')))
		{
			/*$error = "不支持的文件扩展名[$type]";
			$this->setError($error);
			return false;*/
			exit();
		}
		
		$type = ('jpg' == $type) ? 'jpeg' : $type;
		
		$imgFun = 'image' . $type;


		if (!function_exists($imgFun))
		{
			/*$error = "不支持的文件扩展名[$type]";
			$this->setError($error);
			return false;*/
			exit();
		}
		
		if (true === $render)
		{
			header('Content-type: image/' . $type);
			$imgFun($this->_srcIm);
		}
		else
		{
            if(version_compare(PHP_VERSION, '5.1.2', '<')){
                $quality = min(100, max($quality, 1));
            }else{
                $quality = min(9, max($quality, 1));
            }
			$imgFun($this->_srcIm, $target, $quality);
		}
		
		// 设置文件读写权限
		//chmod($this->targetFile, intval(0755, 8));
		

		// 目标文件时间与源文件相同
		//if ($this->preserveSourceFileTime)
		//@touch($this->targetFile, $this->sourceFileTime);
		

		//imagedestroy($this->_srcIm);
		//$this->_srcIm = NULL;
		

		return true;
	}

	/**
	 * 图片水印
	 * 
	 * @param    source  源图片标示符
	 * @param    source  水印图片标示符
	 * @param    integer 原图片X坐标
	 * @param    integer 原图片Y坐标
	 * @param    integer 水印图片X坐标
	 * @param    integer 水印图片Y坐标
	 * @param    integer 水印图片宽度
	 * @param    integer 水印图片高度
	 */
	private function createImgFromIdentifier(&$SourceIdentifier, &$targetIdentifier, $x, $y, $s_x, $s_y, $sWidth, $sHeight)
	{
		// 复制图片
		return imagecopy($SourceIdentifier, $targetIdentifier, $x, $y, $s_x, $s_y, $sWidth, $sHeight);
	}

	/**
	 * 添加图片水印
	 * 为指定图片添加水印
	 * $this->create(300, 200, 'jpg', array(125,125,125));
	 * $srcImg = 'test.png';
	 * $this->drawImage($srcImg, 10, 10 , 20 , 20, 50, 50);
	 * $this->render();
	 * 
	 * @param    string 水印图片路径
	 * @param    integer 目标X坐标
	 * @param    integer 目标Y坐标
	 * @param    integer 源X坐标
	 * @param    integer 源Y坐标
	 * @param    integer 源宽度
	 * @param    integer 源高度
	 * @return    bool
	 */
	public function drawImage($imageSrc, $x, $y, $srcX, $srcY, $sWidth, $sHeight)
	{
		// 从源文件创建图像
		$result = $this->createImgFromSourceFile($imageSrc);
		
		if (!is_array($result) || empty($result))
		{
			/*$error = "读取水印图像错误";
			$this->setError($error);
			return false;*/
			exit();
		}
		
		list($srcIm, $srcWidth, $srcHeight) = $result;
		
		$this->createImgFromIdentifier($this->_srcIm, $srcIm, $x, $y, $srcX, $srcY, $sWidth, $sHeight);
		
		imagedestroy($srcIm);
		unset($srcIm);
		
		return true;
	}

	/**
	 * 添加文字水印
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'jpg', array(125,125,125));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->render();
	 * @endcode
	 * 
	 * @param    string  文字
	 * @param    integer 目标X坐标
	 * @param    integer 目标Y坐标
	 * @param    integer 文字大小
	 * @param    array   文字颜色,默认随机，其RGB数组格式如：array(255, 255, 255)
	 */
	public function drawText($_fontFile, $string, $x, $y, $size, array $color = array())
	{
		// 设置颜色
		if (empty($color))
			$color = array(mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
		
		$color = imagecolorallocate($this->_srcIm, $color[0], $color[1], $color[2]);
		
		// 写入文字
		imagettftext($this->_srcIm, $size, 0, $x, $y + $size + 4, $color, $_fontFile, $string);
		
		unset($color);
	}

	/**
	 * 锐化图片
	 * 
	 * 锐化计算速度较慢请谨用
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'png', array(125,125,125));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->sharp(1);
	 * $this->render();
	 * @endcode
	 * 
	 * @param    integer 锐化度[0.1-1]
	 * @return    void
	 */
	public function sharp($degree)
	{
		if (!is_resource($this->_srcIm))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_load'));
		}
		
		$degree = min(1, max($degree, 0.1));
		
		$cnt = 0;
		
		for ($x = 1; $x < $this->_srcWidth; $x++)
			for ($y = 1; $y < $this->_srcHeight; $y++)
			{
				$src_clr1 = imagecolorsforindex($this->_srcIm, imagecolorat($this->_srcIm, $x - 1, $y - 1));
				$src_clr2 = imagecolorsforindex($this->_srcIm, imagecolorat($this->_srcIm, $x, $y));
				$r = intval($src_clr2["red"] + $degree * ($src_clr2["red"] - $src_clr1["red"]));
				$g = intval($src_clr2["green"] + $degree * ($src_clr2["green"] - $src_clr1["green"]));
				$b = intval($src_clr2["blue"] + $degree * ($src_clr2["blue"] - $src_clr1["blue"]));
				$r = min(255, max($r, 0));
				$g = min(255, max($g, 0));
				$b = min(255, max($b, 0));
				
				if (($dst_clr = imagecolorexact($this->_srcIm, $r, $g, $b)) == -1)
					$dst_clr = Imagecolorallocate($this->_srcIm, $r, $g, $b);
				
				$cnt++;
				
				imagesetpixel($this->_srcIm, $x, $y, $dst_clr);
			}
		
		unset($dst_clr);
	}

	/**
	 * 将图片转换为灰度
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,124));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->gray();
	 * $this->render();
	 * @endcode
	 * 
	 * @return    void
	 */
	public function gray()
	{
		if (!is_resource($this->_srcIm))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_load'));
		}
		
		for ($y = 0; $y < $this->_srcHeight; $y++)
		{
			for ($x = 0; $x < $this->_srcWidth; $x++)
			{
				$gray = (ImageColorAt($this->_srcIm, $x, $y) >> 8) & 0xFF;
				imagesetpixel($this->_srcIm, $x, $y, ImageColorAllocate($this->_srcIm, $gray, $gray, $gray));
			}
		}
	}

	/**
	 * 设置图像大小
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,125));
	 * $this->resize(true, 100); //修改图片宽、高
	 * $this->render();
	 * @endcode
	 * 
	 * @param    bool   是否保持宽高比 [true]
	 * @param    integer 目标宽度
	 * @param    integer 目标高度
     * @param    integer 目标质量[100][1-100]
     * @param    string 缩略图路径
     * @param    bool false--不增加水印  true--增加水印
     * @param  $is_padding  bool false--是否保持目标宽高不变，左右或者两边填充空白
	 * @return    bool
	 */
	public function resize($isAspectRatio = true, $width = NULL, $height = NULL, $quality = 100,$path="",$add_water=true,$overwrite_source_path="",$offset_x=0,$offset_y=0,$water_path='',$is_padding=true)
	{

        if (!is_resource($this->_srcIm))
        {
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_load'));
        }

        $sourceImageWidth = $this->_srcWidth;
        $sourceImageHeight = $this->_srcHeight;



        $gd = $this->gd_version(); //获取 GD 版本。0 表示没有 GD 库，1 表示 GD 1.x，2 表示 GD 2.x
        if ($gd == 0)
        {
            return false;
        }
        /* 检查缩略图宽度和高度是否合法 */
        if ($width == 0 && $height == 0)
        {
            return true;
        }

        if($add_water){
            

            
            if($water_path){
                $WATER_MARK_IMAGE= $water_path;
            }else{
                $WATER_MARK_IMAGE= APP_REAL_PATH . $this->getConfig('params','WATER_MARK_IMAGE');
            }
            $WATER_MARK_IMAGE_POSITION=$this->getConfig('params','WATER_MARK_IMAGE_POSITION');
            $WATER_MARK_IMAGE_TRANSPARENCY=$this->getConfig('params','WATER_MARK_IMAGE_TRANSPARENCY');
            if($WATER_MARK_IMAGE && $WATER_MARK_IMAGE_POSITION>0){
                //有水印图，且水印位置不为无，则添加水印
                $this->add_image_watermark($WATER_MARK_IMAGE,$WATER_MARK_IMAGE_POSITION,$WATER_MARK_IMAGE_TRANSPARENCY /100,$offset_x,$offset_y);
                if($overwrite_source_path){
                    imagejpeg($this->_srcIm, $overwrite_source_path,$quality);
                }
            }
        }


        $old_width=$width;
        $old_height=$height;



        /* 原始图片以及缩略图的尺寸比例 */
        $scale_org      =$sourceImageWidth / $sourceImageHeight;
        /* 处理只有缩略图宽和高有一个为0的情况，这时背景和缩略图一样大 */
        if ($width == 0)
        {
            $width = $width * $scale_org;
        }
        if ($height == 0)
        {
            $height = $height / $scale_org;
        }

        if ($sourceImageWidth / $width > $sourceImageHeight / $height)
        {
            $lessen_width  = $width;
            $lessen_height  = $width / $scale_org;
        }
        else
        {
            /* 原始图片比较高，则以高度为准 */
            $lessen_width  = $height * $scale_org;
            $lessen_height = $height;
        }

        if($lessen_width==$old_width){
            //宽相等，则高可能缩放
            $my_x=0;
            $my_y=($old_height-$lessen_height)/2;
        }else{
            //高度相等，宽度可能缩放
            $my_y=0;
            $my_x=($old_width-$lessen_width)/2;
        }

        /* 创建缩略图的标志符 */
        if ($gd == 2)
        {
            if($is_padding){
                $img_thumb  = imagecreatetruecolor($width, $height);
                imagefill($img_thumb,1,1,imagecolorallocate($img_thumb,$this->_fill_color[0],$this->_fill_color[1],$this->_fill_color[2]));
            }else{
                $img_thumb  = imagecreatetruecolor($lessen_width, $lessen_height);
            }
        }
        else
        {
            if($is_padding){
                $img_thumb  = imagecreate($width, $height);
                imagefill($img_thumb,1,1,imagecolorallocate($img_thumb,$this->_fill_color[0],$this->_fill_color[1],$this->_fill_color[2]));
            }else{
                $img_thumb  = imagecreate($lessen_width, $lessen_height);
            }
        }


        $dst_x = 0;
        $dst_y = 0;
        if($is_padding){
            $dst_x = $my_x;
            $dst_y = $my_y;
        }

        /* 将原始图片进行缩放处理 */
        if ($gd == 2)
        {
            imagecopyresampled($img_thumb, $this->_srcIm, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $sourceImageWidth, $sourceImageHeight);
        }
        else
        {
            imagecopyresized($img_thumb, $this->_srcIm, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $sourceImageWidth, $sourceImageHeight);
        }
        imagejpeg($img_thumb, $path,$quality);

        imagedestroy($img_thumb);
        imagedestroy($this->_srcIm);

        //确认文件是否生成
        if (file_exists($path))
        {
            return $path;
        }
        else
        {
            return false;
        }
        $this->_srcIm = NULL;
        return true;
	}



	/**
	 * 剪切图像
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,124));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->crop(10,10, 100, 100);
	 * $this->render();
	 * @endcode
	 * 
	 * @param    integer 源X坐标
	 * @param    integer 源Y坐标
	 * @param    integer 源宽度
	 * @param    integer 源高度
	 * @return    bool
	 */
	public function crop($srcX, $srcY, $dstX = NULL, $dstY = NULL)
	{
		if (!is_resource($this->_srcIm))
		{
			/*$error = "未加载图像";
			$this->setError($error);
			return false;*/
			exit();
		}
		
		$srcX = $srcX ? $srcX : 0;
		$srcY = $srcY ? $srcY : 0;
		$dstX = $dstX ? $dstX : $this->_srcWidth;
		$dstY = $dstY ? $dstY : $this->_srcHeight;
		
		// 创建目标图像
		$target = $this->createTargetImgIdentifier($dstX - $srcX, $dstY - $srcY);
		
		// 剪切图像
		imagecopyresampled($target, $this->_srcIm, 0, 0, $srcX, $srcY, $dstX - $srcX, $dstY - $srcY, $dstX - $srcX, $dstY - $srcY);
		
		imagedestroy($this->_srcIm);
		$this->_srcIm = NULL;
		$this->_srcIm = & $target;
		
		return true;
	}

	/**
	 * 旋转图像
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,124));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->rotate(30);
	 * $this->render();
	 * @endcode
	 * 
	 * @warning
	 * 该函数与其他函数混合使用时请将顺序滞后以免发生错误
	 * 
	 * @param    integer 旋转角度[0][0-360]
	 * @param    string  空白区域填充色[0xFFFFFF],旋转时产生的空白区域的颜色，十六进制RGB值。
	 * @return    void
	 */
	public function rotate($angle = 0, $bgColor = '0xFFFFFF')
	{
		if (!is_resource($this->_srcIm))
		{
            throw new \com\eunionz\exception\ImageException($this->getLang('error_image_title'),$this->getLang('error_image_load'));
        }
		
		// 旋转图像
		$target = imagerotate($this->_srcIm, $angle, $bgColor);
		
		imagedestroy($this->_srcIm);
		$this->_srcIm = NULL;
		$this->_srcIm = & $target;
	}

	/**
	 * 保存图像
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,124));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->save('./test.gif');
	 * @endcode
	 * 
	 * @param    string  图像路径
	 * @param    integer 图像质量[100][0-100]
	 * @return    bool
	 */
	public function save($target, $quality = 100)
	{
		return $this->outputTargetImg($target, $quality, false);
	}

	/**
	 * 输出图像
	 * 
	 * @qrcode
	 * $this->create(300, 200, 'gif', array(125,125,124));
	 * $this->drawText('文字水印', 6, 2, 16);
	 * $this->render();
	 * @endcode
	 * 
	 * @param    integer 图像质量[100][0-100]
	 * @return    bool
	 */
	public function render($quality = 100)
	{
		if ($this->outputTargetImg($this->_src, $quality, true))
		{
			$this->release();
			return true;
		}
		
		return false;
	}

	/**
	 * 释放内存
	 */
	protected function release()
	{
		imagedestroy($this->_srcIm);
		$this->_srcIm = NULL;
	}



    /**
     * 为图片对象增加水印
     *
     * @access      public
     * @param       string      $watermark          水印完整路径
     * @param       int         $watermark_place    水印位置代码
     * @return      mix         true -- 成功  false--失败
     */
    function add_image_watermark($watermark='', $watermark_place='', $watermark_alpha = 0.65,$offset_x=0,$offset_y=0)
    {
        // 是否安装了GD,则直接返回
        $gd = $this->gd_version();
        if ($gd == 0)
        {
            return false;
        }



        /* 如果水印的位置为0，则直接返回 */
        if ($watermark_place == 0 || empty($watermark))
        {
            return false;
        }

        if (!$this->validate_image($watermark))
        {
            /* 已经记录了错误信息 */
            return false;
        }

        // 获得水印文件以及源文件的信息
        $watermark_info     = @getimagesize($watermark);
        $watermark_handle   = $this->img_resource($watermark, $watermark_info[2]);

        if (!$watermark_handle)
        {
            return false;
        }

        // 根据文件类型获得原始图片的操作句柄
        $source_info    =array($this->_srcWidth,$this->_srcHeight,$this->_srcType);
        $source_handle  =& $this->_srcIm;
        if (!$source_handle)
        {
            return false;
        }

        // 根据系统设置获得水印的位置
        switch ($watermark_place)
        {
            case '1':
                $x = 0+$offset_x;
                $y = 0+$offset_y;
                break;
            case '2':
                $x = $source_info[0] - $watermark_info[0]+$offset_x;
                $y = 0+$offset_y;
                break;
            case '4':
                $x = 0+$offset_x;
                $y = $source_info[1] - $watermark_info[1]+$offset_y;
                break;
            case '5':
                $x = $source_info[0] - $watermark_info[0]-2+$offset_x;
                $y = $source_info[1] - $watermark_info[1]+2+$offset_y;
                break;
            default:
                $x = $source_info[0]/2 - $watermark_info[0]/2+$offset_x;
                $y = $source_info[1]/2 - $watermark_info[1]/2+$offset_y;
        }

        if (strpos(strtolower($watermark_info['mime']), 'png') !== false)
        {
            imagealphablending($source_handle,true);
            imagealphablending($watermark_handle,true);
            imagecopy($source_handle, $watermark_handle, $x, $y, 0, 0,$watermark_info[0], $watermark_info[1]);




        }
        else
        {
            imagecopymerge($source_handle, $watermark_handle, $x, $y, 0, 0,$watermark_info[0], $watermark_info[1], $watermark_alpha);
        }

        return true;
    }



    /**
     * 为图片增加水印
     *
     * @access      public
     * @param       string      filename            原始图片文件名，包含完整路径
     * @param       string      target_file         需要加水印的图片文件名，包含完整路径。如果为空则覆盖源文件
     * @param       string      $watermark          水印完整路径
     * @param       int         $watermark_place    水印位置代码
     * @return      mix         如果成功则返回文件路径，否则返回false
     */
    function add_watermark($filename, $target_file='', $watermark='', $watermark_place='', $watermark_alpha = 0.65)
    {
        // 是否安装了GD
        $gd = $this->gd_version();
        if ($gd == 0)
        {
            return false;
        }

        // 文件是否存在
        if ((!file_exists($filename)) || (!is_file($filename)))
        {
            return false;
        }

        /* 如果水印的位置为0，则返回原图 */
        if ($watermark_place == 0 || empty($watermark))
        {
            return $filename;
        }

        if (!$this->validate_image($watermark))
        {
            /* 已经记录了错误信息 */
            return false;
        }

        // 获得水印文件以及源文件的信息
        $watermark_info     = @getimagesize($watermark);
        $watermark_handle   = $this->img_resource($watermark, $watermark_info[2]);

        if (!$watermark_handle)
        {
            return false;
        }

        // 根据文件类型获得原始图片的操作句柄
        $source_info    = @getimagesize($filename);
        $source_handle  = $this->img_resource($filename, $source_info[2]);
        if (!$source_handle)
        {
            return false;
        }

        // 根据系统设置获得水印的位置
        switch ($watermark_place)
        {
            case '1':
                $x = 0;
                $y = 0;
                break;
            case '2':
                $x = $source_info[0] - $watermark_info[0];
                $y = 0;
                break;
            case '4':
                $x = 0;
                $y = $source_info[1] - $watermark_info[1];
                break;
            case '5':
                $x = $source_info[0] - $watermark_info[0];
                $y = $source_info[1] - $watermark_info[1];
                break;
            default:
                $x = $source_info[0]/2 - $watermark_info[0]/2;
                $y = $source_info[1]/2 - $watermark_info[1]/2;
        }

        if (strpos(strtolower($watermark_info['mime']), 'png') !== false)
        {
            imageAlphaBlending($watermark_handle, true);
            imagecopy($source_handle, $watermark_handle, $x, $y, 0, 0,$watermark_info[0], $watermark_info[1]);
        }
        else
        {
            imagecopymerge($source_handle, $watermark_handle, $x, $y, 0, 0,$watermark_info[0], $watermark_info[1], $watermark_alpha);
        }
        $target = empty($target_file) ? $filename : $target_file;

        switch ($source_info[2] )
        {
            case 'image/gif':
            case 1:
                imagegif($source_handle,  $target);
                break;

            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                imagejpeg($source_handle, $target,95);
                break;

            case 'image/x-png':
            case 'image/png':
            case 3:
                imagepng($source_handle,  $target,95);
                break;

            default:
                return false;
        }

        imagedestroy($source_handle);

        $path = realpath($target);
        if ($path)
        {
            return $path;
        }
        else
        {
            return false;
        }
    }

    /**
     *  检查水印图片是否合法
     *
     * @access  public
     * @param   string      $path       图片路径
     *
     * @return boolen
     */
    function validate_image($path)
    {
        if (empty($path))
        {
            return false;
        }

        /* 文件是否存在 */
        if (!file_exists($path))
        {
            return false;
        }

        // 获得文件以及源文件的信息
        $image_info     = @getimagesize($path);

        if (!$image_info)
        {
            return false;
        }

        /* 检查处理函数是否存在 */
        if (!$this->check_img_function($image_info[2]))
        {
            return false;
        }

        return true;
    }


    /**
     * 检查图片处理能力
     *
     * @access  public
     * @param   string  $img_type   图片类型
     * @return  void
     */
    function check_img_function($img_type)
    {
        switch ($img_type)
        {
            case 'image/gif':
            case 1:

                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromgif');
                }
                else
                {
                    return (imagetypes() & IMG_GIF) > 0;
                }
                break;

            case 'image/pjpeg':
            case 'image/jpeg':
            case 2:
                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefromjpeg');
                }
                else
                {
                    return (imagetypes() & IMG_JPG) > 0;
                }
                break;

            case 'image/x-png':
            case 'image/png':
            case 3:
                if (PHP_VERSION >= '4.3')
                {
                    return function_exists('imagecreatefrompng');
                }
                else
                {
                    return (imagetypes() & IMG_PNG) > 0;
                }
                break;

            default:
                return false;
        }
    }


    /**
     * 根据来源文件的文件类型创建一个图像操作的标识符
     *
     * @access  public
     * @param   string      $img_file   图片文件的路径
     * @param   string      $mime_type  图片文件的文件类型
     * @return  resource    如果成功则返回图像操作标志符，反之则返回错误代码
     */
    function img_resource($img_file, $mime_type)
    {
        switch ($mime_type)
        {
            case 1:
            case 'image/gif':
                $res = imagecreatefromgif($img_file);
                break;

            case 2:
            case 'image/pjpeg':
            case 'image/jpeg':
                $res = imagecreatefromjpeg($img_file);
                break;

            case 3:
            case 'image/x-png':
            case 'image/png':
                $res = imagecreatefrompng($img_file);
                break;

            default:
                return false;
        }

        return $res;
    }


    /**
     * 获得服务器上的 GD 版本
     *
     * @access      public
     * @return      int         可能的值为0，1，2
     */
    function gd_version()
    {
        static $version = -1;

        if ($version >= 0)
        {
            return $version;
        }

        if (!extension_loaded('gd'))
        {
            $version = 0;
        }
        else
        {
            // 尝试使用gd_info函数
            if (PHP_VERSION >= '4.3')
            {
                if (function_exists('gd_info'))
                {
                    $ver_info = gd_info();
                    preg_match('/\d/', $ver_info['GD Version'], $match);
                    $version = $match[0];
                }
                else
                {
                    if (function_exists('imagecreatetruecolor'))
                    {
                        $version = 2;
                    }
                    elseif (function_exists('imagecreate'))
                    {
                        $version = 1;
                    }
                }
            }
            else
            {
                if (preg_match('/phpinfo/', ini_get('disable_functions')))
                {
                    /* 如果phpinfo被禁用，无法确定gd版本 */
                    $version = 1;
                }
                else
                {
                    // 使用phpinfo函数
                    ob_start();
                    phpinfo(8);
                    $info = ob_get_contents();
                    ob_end_clean();
                    $info = stristr($info, 'gd version');
                    preg_match('/\d/', $info, $match);
                    $version = $match[0];
                }
            }
        }

        return $version;
    }


    /**
     * 生成xs sm md lg xl5种缩略图
     * @param $upload_path  生成的缩略图保存位置
     * @param $original_img  原始图片物理路径
     * @param $oldfilename  要删除的原文件名，基于该文件名生成要删除的5种缩略图，后缀_xs.jpg _sm.jpg  _md.jpg  _lg.jpg  _xl.jpg
     * @param $filename  要生成缩略图的文件名，基于该文件名生成5种缩略图，，后缀_xs.jpg _sm.jpg  _md.jpg  _lg.jpg  _xl.jpg
     * @param $del_upfiles  返回要删除的5种缩略图
     * @param $upfiles  返回要上传的5种缩略图
     * @param int $SMALL_THUMB_QUALITY 缩略图生成品质，默认80,最高100
     * @param int $IS_WATER_MARK  是否加水印  0--不加  1--要加
     */
    public function makeSmall($upload_path,$original_img,$oldfilename,$filename,&$del_upfiles,&$upfiles,$SMALL_THUMB_QUALITY=80,$IS_WATER_MARK=1){
        if($oldfilename){
            $del_upfiles[]=$oldfilename . '_xs.jpg';
            $del_upfiles[]=$oldfilename . '_sm.jpg';
            $del_upfiles[]=$oldfilename . '_md.jpg';
            $del_upfiles[]=$oldfilename . '_lg.jpg';
            $del_upfiles[]=$oldfilename . '_xl.jpg';
        }
        $this->loadComponent('image')->load($original_img);
        $this->loadComponent('image')->resize(true,60,60,$this->getConfig('params','SMALL_THUMB_QUALITY'),$upload_path . '/' . $filename. '_xs.jpg',$IS_WATER_MARK);
        $upfiles[]=$upload_path . '/' . $filename.'_xs.jpg';

        $this->loadComponent('image')->load($original_img);
        $this->loadComponent('image')->resize(true,120,120,$this->getConfig('params','SMALL_THUMB_QUALITY'),$upload_path . '/' . $filename. '_sm.jpg',$IS_WATER_MARK);
        $upfiles[]=$upload_path . '/' . $filename. '_sm.jpg';

        $this->loadComponent('image')->load($original_img);
        $this->loadComponent('image')->resize(true,240,240,$this->getConfig('params','SMALL_THUMB_QUALITY'),$upload_path . '/' . $filename. '_md.jpg',$IS_WATER_MARK);
        $upfiles[]=$upload_path . '/' . $filename. '_md.jpg';

        $this->loadComponent('image')->load($original_img);
        $this->loadComponent('image')->resize(true,480,480,$this->getConfig('params','SMALL_THUMB_QUALITY'),$upload_path . '/' . $filename. '_lg.jpg',$IS_WATER_MARK);
        $upfiles[]=$upload_path . '/' . $filename. '_lg.jpg';

        $this->loadComponent('image')->load($original_img);
        $this->loadComponent('image')->resize(true,960,960,$this->getConfig('params','SMALL_THUMB_QUALITY'),$upload_path . '/' . $filename. '_xl.jpg',$IS_WATER_MARK);
        $upfiles[]=$upload_path . '/' . $filename . '_xl.jpg';

    }

}
