<?php
/**
 * EUnionZ PHP Framework Barcode Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\barcode;

defined('APP_IN') or exit('Access Denied');


class Barcode extends \com\eunionz\core\Plugin
{
	var $codebar='BCGcode128';
	public function __construct()
    {
        require('class/BCGFont.php');
		require('class/BCGColor.php');
		require('class/BCGDrawing.php'); 
		include('class/BCGcode128.barcode.php');
    }
	
	public function create($text){
		$font = new \BCGFont( realpath(dirname(__FILE__)).'/class/font/Arial.ttf', 10);
	 	$color_black = new \BCGColor(0, 0, 0);
		$color_white = new \BCGColor(255, 255, 255);
		
		$code = new \BCGcode128;
		$code->setScale(2); // Resolution
		$code->setThickness(40); // Thickness
		$code->setForegroundColor($color_black); // Color of bars
		$code->setBackgroundColor($color_white); // Color of spaces
		$code->setFont($font); // Font (or 0)
		$code->parse($text); 
		
		/* Here is the list of the arguments
		1 - Filename (empty : display on screen)
		2 - Background color */
		$drawing = new \BCGDrawing('', $color_white);
		$drawing->setBarcode($code);
		$drawing->draw();		
		// Header that says it is an image (remove it if you save the barcode to a file)
		header('Content-Type: image/png');		
		// Draw (or save) the image into PNG format.
		$drawing->finish(\BCGDrawing::IMG_FORMAT_PNG);
	 }
	
}