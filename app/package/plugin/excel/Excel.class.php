<?php
/**
 * EUnionZ PHP Framework Excel Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\excel;


defined('APP_IN') or exit('Access Denied');

class Excel extends \com\eunionz\core\Plugin
{
    public function __construct()
    {
        require 'PHPExcel.php';
        require 'PHPExcel/IOFactory.php';
    }

    public function read($filename, $encode, $file_type)
    {
        if (strtolower($file_type) == 'xls') //判断excel表类型为2003还是2007
        {
            require 'PHPExcel/Reader/Excel5.php';
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        } elseif (strtolower($file_type) == 'xlsx') {
            require 'PHPExcel/Reader/Excel2007.php';
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        }
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }

        return $excelData;
    }


    /**
     * 优惠券卡密导出
     * @param $data
     * @throws \PHPExcel_Reader_Exception
     */
    public function volumes($data, $filename)
    {

        require 'PHPExcel/Writer/IWriter.php';
        require 'PHPExcel/Writer/Excel5.php';
        error_reporting(0);
        $obj_phpexcel = new \PHPExcel();
        $obj_phpexcel->getActiveSheet()->setCellValue('a1', '卡号');
        $obj_phpexcel->getActiveSheet()->setCellValue('b1', '密码');
        if ($data) {
            $i = 2;
            foreach ($data as $key => $value) {
                $obj_phpexcel->getActiveSheet()->setCellValue('a' . $i, ' ' . $value['usvol_no'], \PHPExcel_Cell_DataType::TYPE_STRING);
                $obj_phpexcel->getActiveSheet()->setCellValue('b' . $i, ' ' . $value['usvol_password'], \PHPExcel_Cell_DataType::TYPE_STRING);
                $i++;
            }
        }

        $obj_Writer = \PHPExcel_IOFactory::createWriter($obj_phpexcel, 'Excel5');
        $filename = $filename . '_' . date('YmdHis') . '.xls';

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $obj_Writer->save('php://output');
    }

    public function export_form($key, $rs, $filename)
    {
        require 'PHPExcel/Writer/IWriter.php';
        require 'PHPExcel/Writer/Excel5.php';
        $col = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k','l','m','n','o','p','q','r','s','t','v','u','w','x','y','z'];
        error_reporting(0);
        $obj_phpexcel = new \PHPExcel();
        $i = 0;
        $j = 0;
        foreach ($key as $kk => $vv) {
            $row = floor(($i) / 25) - 1;
            $rowchar = '';
            if ($row >= 0) {
                $rowchar = $col[$row];
            }
            if ($j > 25) {
                $j = 0;
            }
            $obj_phpexcel->getActiveSheet()->setCellValue($rowchar.$col[$j] . '1', $vv);
            $i++;
            $j++;
        }
        if ($rs) {
            $i = 2;
            foreach ($rs as $keys => $info) {
                $j = 0;
                $k = 0;
                foreach ($key as $kk => $vv) {
                    $row = floor(($k) / 25) - 1;
                    $rowchar = '';
                    if ($row >= 0) {
                        $rowchar = $col[$row];
                    }
                    if ($j > 25) {
                        $j = 0;
                    }
                    $obj_phpexcel->getActiveSheet()->getColumnDimension($rowchar.$col[$j])->setAutoSize(true);
                    $obj_phpexcel->getActiveSheet()->setCellValue($rowchar.$col[$j] . $i, ' ' . $info[$kk], \PHPExcel_Cell_DataType::TYPE_STRING);
                    $j++;
                    $k++;
                }
                $i++;
            }
        }
        $obj_Writer = \PHPExcel_IOFactory::createWriter($obj_phpexcel, 'Excel5');
        $filename = $filename . '_' . date('YmdHis') . '.xls';

        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        $obj_Writer->save('php://output');
    }
}

?>