<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/16
 * Time: 19:30
 */

namespace package\controller\home;


use cn\eunionz\core\Controller;

class Test extends Controller
{

    public function _index(){

        $this->session("a","123");

        $opts=[];
        $rs = $this->loadModel('sessions');

        $rs =$rs->find($opts);
        $this->write(print_r($rs,true));
        $this->write(print_r($this->session(),true));
        $this->write(print_r($this->cookie(),true));

    }


    public function _a(){
//        ctx()->closeTraceOutput();
        $this->write("ddssdds");
    }

}