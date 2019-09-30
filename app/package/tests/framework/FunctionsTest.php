<?php

namespace package\tests\framework;

use cn\eunionz\component\db\Db;

require_once __DIR__ . '/../TestBase.php';

class FunctionsTest extends \PHPUnit\Framework\TestCase
{

    public function test_C()
    {
        $db = C("db", false);
        $this->assertEquals($db instanceof Db , true);
    }


    public function test_L()
    {
        $rs = L(APP_ERROR , "423343434343434343434343434343434343434343434343434343434343434343434343434343434343434343434343434343434343455" , 'a');
        consoleln("aaaa");
        consoleln("aaaa",APP_ERROR);
        consoleln("aaaa",APP_WARNING);
        consoleln("aaaa",APP_DEBUG);
        consoleln("aaaa",APP_INFO);
        $this->assertEquals($rs , true);
    }

    public function test_getConfig(){
        $rs = getConfig("app" , "APP_LOG_MAXSIZE");

        $this->assertEquals($rs == 204800, true);
    }

    public function test_getConfig_1(){
        $rs = getConfig("app");

        $this->assertEquals(is_array($rs), true);
    }

    public function test_truncate_number(){

        $rs = truncate_number("1.25",1);
        consoleln($rs);
        $this->assertEquals($rs == 1.2, true);
    }

    public function test_truncate_number_1(){

        $rs = truncate_number("1.25",0);
        consoleln($rs);
        $this->assertEquals($rs == 1, true);
    }


    public function test_endsWith_1(){
        $this->assertEquals(endsWith("1.php" , ".php"), true);
    }

    public function test_endsWith_2(){
        $this->assertEquals(endsWith("1.php" , ".Php"), false);
    }

    public function test_startsWith_1(){
        $this->assertEquals(startsWith("1.phpa" , "1.php"), true);
    }

    public function test_startsWith_2(){
        $this->assertEquals(startsWith("1.phpa" , "1.Php"), false);
    }

    public function test_loadConstrants(){
        loadConstrants();
        $this->assertEquals(defined("GRPC_ERROR_NO_RESPONSE"), true);
    }


}
