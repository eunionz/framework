<?php
/**
 * EUnionZ PHP Framework Logistics Plugin class
 * 生成二维码的插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\phpqrcode;


defined('APP_IN') or exit('Access Denied');

class Phpqrcode extends \com\eunionz\core\Plugin
{
    public function __construct()
    {
        require_once('phpqrcode.php');
    }

    /**
     * 生成二维码
     * @param $text   生成二维码的图片
     * @param $outfile   输出二维码图片文件
     * @param $logo   二维码中间的logo图片
     * @param $errorCorrectionLevel   容错级别  L（QR_ECLEVEL_L，7%），M（QR_ECLEVEL_M，15%），Q（QR_ECLEVEL_Q，25%），H（QR_ECLEVEL_H，30%）
     * @param $matrixPointSize  生成图片大小  1-20
     */
    public function create($text,$outfile,$logo=false,$errorCorrectionLevel='L',$overwrite=false,$matrixPointSize=10,$response_page=true){

        if(!$overwrite && $outfile && file_exists($outfile)){
            if($response_page){
                $QR = imagecreatefromstring(file_get_contents($outfile));
                header("Content-Type: image/png");
                imagepng($QR);
                imagedestroy($QR);
                exit;
            }
        }
        if($outfile && file_exists($outfile)) @unlink($outfile);
        \QRcode::png($text,$outfile, $errorCorrectionLevel, $matrixPointSize, 2);

        if($outfile){

            $QR = $outfile;//已经生成的原始二维码图
            if ($logo !== FALSE) {
                $QR = imagecreatefromstring(file_get_contents($QR));
                $logo = imagecreatefromstring(file_get_contents($logo));
                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度

                switch($errorCorrectionLevel){
                    case "M":
                        $Grade=8;
                        break;
                    case "Q":
                        $Grade=8;
                        break;
                    case "H":
                        $Grade=8;
                        break;
                    default:
                        $Grade=8;
                }

                $logo_qr_width = $QR_width / $Grade;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;


                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                    $logo_qr_height, $logo_width, $logo_height);

                //输出图片
                imagepng($QR, $outfile);
                if($response_page){
                    header("Content-Type: image/png");
                    imagepng($QR);
                }
                imagedestroy($QR);
                imagedestroy($logo);
            }else{
                if($response_page){
                    header("Content-Type: image/png");
                    $QR = imagecreatefromstring(file_get_contents($QR));
                    imagepng($QR);
                    imagedestroy($QR);
                }
            }
        }
   }

    public function create_path($text,$outfile,$logo=false,$errorCorrectionLevel='L',$overwrite=false,$matrixPointSize=10,$response_page=true){
        if($outfile && file_exists($outfile)) @unlink($outfile);
        \QRcode::png($text,$outfile, $errorCorrectionLevel, $matrixPointSize, 2);

        if($outfile){

            $QR = $outfile;//已经生成的原始二维码图
            if ($logo !== FALSE) {
                $QR = imagecreatefromstring(file_get_contents($QR));
                $logo = imagecreatefromstring(file_get_contents($logo));
                $QR_width = imagesx($QR);//二维码图片宽度
                $QR_height = imagesy($QR);//二维码图片高度
                $logo_width = imagesx($logo);//logo图片宽度
                $logo_height = imagesy($logo);//logo图片高度

                switch($errorCorrectionLevel){
                    case "M":
                        $Grade=8;
                        break;
                    case "Q":
                        $Grade=8;
                        break;
                    case "H":
                        $Grade=8;
                        break;
                    default:
                        $Grade=8;
                }

                $logo_qr_width = $QR_width / $Grade;
                $scale = $logo_width/$logo_qr_width;
                $logo_qr_height = $logo_height/$scale;


                $from_width = ($QR_width - $logo_qr_width) / 2;
                //重新组合图片并调整大小
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,
                    $logo_qr_height, $logo_width, $logo_height);

                //输出图片
                imagepng($QR, $outfile);
                if($response_page){
                    header("Content-Type: image/png");
                    imagepng($QR);
                }
                imagedestroy($QR);
                imagedestroy($logo);
            }
        }
    }

}