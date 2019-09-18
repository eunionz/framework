<?php
/**
 * EUnionZ PHP Framework Wordsplit Plugin class
 * 分词插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\wordsplit;


defined('APP_IN') or exit('Access Denied');

class Wordsplit extends \com\eunionz\core\Plugin
{
    /**
     * 中文分词方法
     * @param $word  要分词的句子
     * @param $do_fork  岐义处理
     * @param $do_unit  新词识别
     * @param $do_multi  多元切分
     * @param $do_prop  词性标注
     * @param $pri_dict  是否预先载入全部词条
     */
     public function split($word,$do_fork=false,$do_unit=false,$do_multi=false,$do_prop=false,$pri_dict=false){
         require_once('phpanalysis.class.php');
         //初始化类
         \PhpAnalysis::$loadInit = false;
         $pa = new \PhpAnalysis('utf-8', 'utf-8', $pri_dict);
         //载入词典
         $pa->LoadDict();

         //执行分词
         $pa->SetSource($word);
         $pa->differMax = $do_multi;
         $pa->unitWord = $do_unit;

         $pa->StartAnalysis( $do_fork );

         $okresult = $pa->GetFinallyResult(' ', $do_prop);

         return $this->handle_special_char($okresult);
     }

    private function handle_special_char($words){
        $special_chars=array('`','~','1','!','2','@','3','#','4','$','5','%','6','^','7','&','8','*','9','(','0',')','-',',','_','=','+','{','[','}',']','|','\\',':',';','"','\'','<','>','.','/','?','｀','～','１','！','２','＠','３','＃','４','＄','５','％','６','＾','７','＆','８','＊','９','（','０','）','－','＿','＝','＋','｛','［','｝','］','｜','＼','：','；','＂','＇','＜','，','＞','．','？','／',')','　','·','！','￥','×','、','。','，','；','：','“','”','‘','’','【','】','｛','｝');
        $rs=$words;
        foreach($special_chars as $word){
            if($word) $rs=str_replace($word,"",$rs);
        }
        $arr= explode(' ',$rs);
        foreach($arr as $k=>$v) if(strlen(trim($v))==0) unset($arr[$k]);
        return $arr;
    }
}