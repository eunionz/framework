<?php
/**
 * EUnionZ PHP Framework Validation Plugin class
 * 验证插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\validation;


defined('APP_IN') or exit('Access Denied');

class Validation extends \cn\eunionz\core\Plugin
{
    /**
     * 数字验证
     * @param $char
     * @return bool
     */
    function vNumber($int)
    {
        if (preg_match("/^[0-9]+$/", $int)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 字母验证
     * @param $char
     * @return bool
     */
    public function vLetter($char)
    {
        // 基础验证
        if ($char == "" || is_null($char)) {
            return false;
        }
        // 字母验证
        if (preg_match("/^[a-zA-Z]+$/", $char)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 中文验证
     * @param $char
     * @return bool
     */
    public function vChinese($char)
    {
        // 基础验证
        if ($char == "" || is_null($char)) {
            return false;
        }
        // 手机验证
        if (preg_match("/^[\x{4E00}-\x{9FA5}\x{F900}-\x{FA2D}]+$/u", $char)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 链接验证
     * @param $char
     * @return bool
     */
    public function vUrl($char)
    {
        // 基础验证
        if ($char == "" || is_null($char)) {
            return false;
        }
        // 手机验证
        if (preg_match("/^(http\:\/\/|https\:\/\/|\/)+[^\s]+$/", $char)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * //手机号码验证
     * @param $mobile
     * @return bool
     */
    public function vPhone($mobile)
    {
        // 基础验证
        if ($mobile == "" || is_null($mobile) || strlen($mobile) != 11) {
            return false;
        }
        // 手机验证
        //if (preg_match("/^(13[0-9]|15[012356789]|17[031678]|18[0-9]|14[57])[0-9]{8}$/", $mobile)) {
        if (preg_match("/^1[3-9]\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * //手机号码列表验证
     * @param $mobile
     * @return bool
     */
    public function vPhones($mobile)
    {
        // 基础验证
        if ($mobile == "" || is_null($mobile)) {
            return false;
        }
        // 手机验证
        //if (preg_match("/^(13[0-9]|15[012356789]|17[01678]|18[0-9]|14[57])[0-9]{8}(,(13[0-9]|15[012356789]|17[01678]|18[0-9]|14[57])[0-9]{8})*$/", $mobile)) {
        if (preg_match("/^1[3-9]\d{9}(,1[3-9]\d{9})*$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * //验证邮箱
     * @param $email
     * @return bool
     */
    public function vEmail($email)
    {
        //可以匹配中文邮箱格式
        $pregEmail = "/^([\x{4e00}-\x{9fa5}A-Za-z0-9]+[_|\_|\.\-&]*)+@([\x{4e00}-\x{9fa5}a-zA-Z0-9]+[_|\_|\.\-]?)*[\x{4e00}-\x{9fa5}a-zA-Z0-9]+\.[\x{4e00}-\x{9fa5}a-zA-Z0-9-]{2,20}$/u";
        return preg_match($pregEmail, $email);
    }

    /**
     * //验证身份证
     */
    function validation_filter_id_card($id_card)
    {
        if(!preg_match("/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[12])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/", $id_card)){
            return false;
        } elseif (strlen($id_card) == 18) {
            return $this->idcard_checksum18($id_card);
        } elseif ((strlen($id_card) == 15)) {
            $id_card = $this->idcard_15to18($id_card);
            return $this->idcard_checksum18($id_card);
        } else {
            return false;
        }
    }

    /**
     * //计算身份证校验码，根据国家标准GB 11643-1999
     * @param $idcard_base
     * @return bool
     */
    function idcard_verify_number($idcard_base)
    {
        if (strlen($idcard_base) != 17) {
            return false;
        }
        $city = array(11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古", 21 => "辽宁", 22 => "吉林", 23 => "黑龙江 ", 31 => "上海", 32 => "江苏", 33 => "浙江", 34 => "安徽", 35 => "福建", 36 => "江西", 37 => "山东", 41 => "河南", 42 => "湖北 ", 43 => "湖南", 44 => "广东", 45 => "广西", 46 => "海南", 50 => "重庆", 51 => "四川", 52 => "贵州", 53 => "云南", 54 => "西藏 ", 61 => "陕西", 62 => "甘肃", 63 => "青海", 64 => "宁夏", 65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外");
        if (!$city[substr($idcard_base, 0, 2)]) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($idcard_base); $i++) {
            $checksum += substr($idcard_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    /**
     * //将15位身份证升级到18位
     * @param $idcard
     * @return bool|string
     */
    function idcard_15to18($idcard)
    {
        if (strlen($idcard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($idcard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $idcard = substr($idcard, 0, 6) . '18' . substr($idcard, 6, 9);
            } else {
                $idcard = substr($idcard, 0, 6) . '19' . substr($idcard, 6, 9);
            }
        }
        $idcard = $idcard . $this->idcard_verify_number($idcard);
        return $idcard;
    }

    /**
     * //18位身份证校验码有效性检查
     * @param $idcard
     * @return bool
     */
    function idcard_checksum18($idcard)
    {
        if (strlen($idcard) != 18) {
            return false;
        }
        $idcard_base = substr($idcard, 0, 17);
        if ($this->idcard_verify_number($idcard_base) != strtoupper(substr($idcard, 17, 1))) {
            return false;
        } else {
            return true;
        }
    }

    function vf_date($date)
    {
        $k = explode('-', $date);
        if (checkdate($k[1], $k[2], $k[0]))
            return true;
        else
            return false;
    }

    function vf_float($float)
    {
        if (preg_match("/^(-?\d+)(.\d+)?$/", $float))
            return true;
        else
            return false;
    }

    function vf_int($int)
    {
        if (preg_match("/^\d+$/", $int)) {
            $val = intval($int);
            if (($val >= -2147483648) && ($val <= 2147483647))
                return true;
            else
                return false;
        } else
            return false;
    }

    function vf_smallint($short)
    {
        if (preg_match("/^\d+$/", $short)) {
            $val = intval($short);
            if (($val >= -32768) && ($val <= 32767))
                return true;
            else
                return false;
        } else
            return false;
    }

    function vf_tinyint($char)
    {
        if (preg_match("/^\d+$/", $char)) {
            $val = intval($char);
            if (($val >= 0) && ($val <= 255))
                return true;
            else
                return false;
        } else
            return false;
    }

    function vf_enum($val, $vals)
    {
        if (in_array($val, explode('|', $vals)))
            return true;
        else
            return false;
    }

    function vf_utf8($utf8Str)
    {
        //if ( preg_match( '//u', $utf8Str) )
        if (mb_check_encoding($utf8Str, 'utf-8'))
            return true;
        else
            return false;
    }

    function vf_hex($hex)
    {
        if (preg_match("/^[0-9a-fA-F]{2,}$/", $hex))
            return true;
        else
            return false;
    }

    function vf_phone($phone)
    {
        if (preg_match("/^1[0-9]{10}$/", $phone))
            return true;
        else
            return false;
    }


    /*
     * fahai:按配置验证多个参数，配置格式：
     * array(
     *  'if_name'=>array(
     *      'nullable'=>0,
     *      'para_type'=>'int',
     *
     * ))
     */
    function validPara($data)
    {
        $err = "";
        $check_rule = self::getConfig('api', $this->getRouter('path'));
        $act = $this->getRouter('act');
        if (isset($check_rule[$act])) {
            $rules = $check_rule[$act];
            $err = $this->checkData($data, $rules);
        }

        return $err;
    }

    function checkData($data, $rules, $upk = null)
    {
        static $err = "";
        static $upks = [];
        static $upks_r = [];

        $uks = '';
        $uks_r = '';
        if (is_int($upk)) {
            $upkey = 'index';
        } else {
            $upkey = $upk;
        }
        if (!empty($upkey)) {
            array_push($upks, $upkey);
            array_push($upks_r, $upk);
            foreach ($upks as $uk) {
                $uks .= "['$uk']";
            }
//            $uks_r = implode("->", $upks_r);
            foreach ($upks_r as $uk) {
                $uks_r .= "[$uk]";
            }
        }

        if (empty($rules))
            return $err;

        $str = "foreach ( \$rules$uks as \$key=>\$rule ) {
                    if (is_array(\$rules$uks" . "[\$key])) {
                        \$rules$uks" . "[\$key]['found'] = 0;
                    }
            }";
        eval($str);

        foreach ($data as $key => $para) {
            if (is_array($para)) {
                $err = $this->checkData($para, $rules, $key);
            } else {
                $rule = null;
                $str = "if ( \$key && isset(\$rules$uks" . "['$key'] ))
                {
                    \$rule = \$rules$uks" . "['$key'];
                    \$rules$uks" . "['$key']['found'] = 1;
                }";
                eval($str);
                if ($rule == null) {
                    continue;
                }

                if (is_array($rule[1])) {
                    foreach ($rule[1] as $checker) {
                        if (is_array($checker) && $checker[0] == 'vf_enum') {
                            if (!$this->vf_enum($para, $checker[1])) {
                                $err = "参数 $key=$para 取值无效. 取值应该在 [$checker[1]]之内.";
                            }
                        } else {
                            $str = <<<EOT

                            if ( method_exists( \$this, '$checker' ) ){
                                \$chkObj = \$this;
                            } else {
                                \$chkObj = \$this->loadComponent('validation');
                                if ( !method_exists( \$chkObj, '$checker' ) ) {
                                    \$err = "参数 $key 校验配置无效，方法 $checker 找不到.";
                                }
                            }

                            if ( !\$chkObj->$checker( \$para ) ) {
                                \$err = "$checker 判断参数 $key=$para 取值无效.";
                            }

EOT;
                            eval($str);
                        }
                        if ($err != "")
                            break;
                    }
                } else {
                    $err = "参数 $key 校验配置无效，方法集应为数组.";
                    break;
                }
            }
            if ($err != "")
                break;
        }

        if ($err == "") {
            $str = <<<EOT
            foreach ( \$rules$uks as \$key=>\$rule ) {
                if (isset(\$rule[0])) {
                    if (( \$rule[0] == 1 ) && (\$rule['found'] != 1)) {
                        \$err = "data$uks_r 必须给出参数 \$key";
                    }
                }
            }
EOT;
            eval($str);
        }
        array_pop($upks);
        array_pop($upks_r);
        return $err;
    }
}