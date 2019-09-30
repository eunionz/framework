<?php

namespace package\tests\framework;

use cn\eunionz\component\cache\Cache;
use cn\eunionz\component\cdb\Cdb;
use cn\eunionz\component\db\Db;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Request;
use cn\eunionz\core\Response;
use cn\eunionz\core\Router;
use cn\eunionz\core\Session;

require_once __DIR__ . '/../TestBase.php';

class ContextTest extends \PHPUnit\Framework\TestCase
{

    public function test_setIsGrpcResponse()
    {
        ctx()->setIsGrpcResponse(true);
        $this->assertEquals(ctx()->isIsGrpcResponse(), true);
    }

    public function test_setRequest()
    {
        $request = new Request();
        ctx()->setRequest($request);
        $this->assertEquals(ctx()->getRequest() instanceof Request, true);
    }

    public function test_setResponse()
    {
        $request = new Response();
        ctx()->setResponse($request);
        $this->assertEquals(ctx()->getResponse() instanceof Response, true);
    }

    public function test_addTimeNode()
    {
        ctx()->addTimeNode("system_launch");
        $use = ctx()->execTimeElapsed("system_launch");
        consoleln($use);
        $this->assertEquals(!$use, false);
    }

    public function test_getUseSeconds()
    {
        ctx()->addTimeNode("system_launch");
        $use = ctx()->getUseSeconds();
        consoleln($use);
        $this->assertEquals(!$use, false);
    }

    public function test_get_shop_id()
    {
        $shop_id = ctx()->get_shop_id("www.xizangjiancai.cn");
        $this->assertEquals($shop_id == 10006, true);

        $shop_id = ctx()->get_shop_id("www.xizangjiancai.cn:8443");
        $this->assertEquals($shop_id == 10006, true);

        $shop_id = ctx()->get_shop_id("www.xizangjiancai.cn1");
        $this->assertEquals($shop_id == 10006, true);

        ctx()->setShopId(10000006);
        $shop_id = ctx()->get_shop_id();
        $this->assertEquals($shop_id == 10000006, true);
    }


    public function test_getPartitionName()
    {
        $route = ["controller" => "\\package\\controller\\home\\Home"];
        ctx()->setRouter($route);
        $partition_name = ctx()->getPartitionName();
        $this->assertEquals($partition_name == "home", true);

        $route = ["controller" => "\\package\\controller\\admin\\Home"];
        ctx()->setRouter($route);
        $partition_name = ctx()->getPartitionName();
        $this->assertEquals($partition_name == "admin", true);

        $route = ["controller" => "\\package\\controller\\tests\\Home"];
        ctx()->setRouter($route);
        $partition_name = ctx()->getPartitionName();
        $this->assertEquals($partition_name == "", true);

    }

    public function test_getSessionNameByPartition()
    {
        $partition_name = "home";
        $sessionName = ctx()->getSessionNameByPartition($partition_name);
        $this->assertEquals($sessionName == "frontsessionsid", true);

        $partition_name = "home1";
        $sessionName = ctx()->getSessionNameByPartition($partition_name);
        $this->assertEquals($sessionName == "frontsessionsid", true);

        $partition_name = "";
        $sessionName = ctx()->getSessionNameByPartition($partition_name);
        $this->assertEquals($sessionName == "frontsessionsid", true);

        $partition_name = "admin";
        $sessionName = ctx()->getSessionNameByPartition($partition_name);
        $this->assertEquals($sessionName == "adminsessionsid", true);

        $partition_name = "seller";
        $sessionName = ctx()->getSessionNameByPartition($partition_name);
        $this->assertEquals($sessionName == "sellersessionsid", true);

    }

    public function test_outputTrace()
    {
        ctx()->setRequest(new Request());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        $controller = new \stdClass();
        $rs = ctx()->outputTrace($controller, true);
        consoleln($rs);
        $this->assertEquals(strpos($rs, 'parent_page_trace_output'), true);

    }

    public function test_getTraceOutput()
    {
        $rs = ctx()->getTraceOutput();
        $this->assertEquals($rs, true);

        ctx()->setTraceOutput(false);
        $rs = ctx()->getTraceOutput();
        $this->assertEquals($rs, false);

        ctx()->setTraceOutput(true);
        $rs = ctx()->getTraceOutput();
        $this->assertEquals($rs, true);

        ctx()->closeTraceOutput();
        $rs = ctx()->getTraceOutput();
        $this->assertEquals($rs, false);

        ctx()->openTraceOutput();
        $rs = ctx()->getTraceOutput();
        $this->assertEquals($rs, true);
    }

    public function test_getTheme()
    {
        $route = ["controller" => "\\package\\controller\\home\\Home"];
        ctx()->setRequest(new Request());
        ctx()->setSession(new Session());
        ctx()->setRouter($route);
        $theme = ctx()->getTheme();
        $this->assertEquals($theme == "pc", true);

        $route = ["controller" => "\\package\\controller\\admin\\Home"];
        ctx()->setRouter($route);
        $theme = ctx()->getTheme();
        $this->assertEquals($theme == "admin", true);

        $route = ["controller" => "\\package\\controller\\tests\\Home"];
        ctx()->setRouter($route);
        $theme = ctx()->getTheme();
        $this->assertEquals($theme == "default", true);

        $route = ["controller" => "\\package\\controller\\seller\\Home"];
        ctx()->setRouter($route);
        $theme = ctx()->getTheme();
        $this->assertEquals($theme == "seller", true);

    }

    public function test_setTheme()
    {
        $route = ["controller" => "\\package\\controller\\home\\Home"];
        ctx()->setRequest(new Request());
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setRouter($route);
        ctx()->setTheme("pc");
        $theme = ctx()->getTheme();
        consoleln($theme);
        $this->assertEquals($theme == "pc", true);
    }


    public function test_setRouter()
    {
        $route = ["controller" => "\\package\\controller\\home\\Home"];
        ctx()->clearRouter();
        ctx()->setRouter($route);
        $route1 = ctx()->getRouter();
        $this->assertEquals($route1['controller'] == $route['controller'], true);
        $this->assertEquals(ctx()->getRouter() != false, true);
        $route1 = ctx()->getRouter(true);
        $this->assertEquals($route1['controller'] == $route['controller'], true);
        $r2 = ctx()->getRouter();
        $this->assertEquals($r2 == null, true);

    }

    public function test_getControllerClass()
    {
        $router = new Router();
        $route = $router->parse_controller("/home/index.shtml");
        ctx()->setRequest(new Request());
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        ctx()->setRouter($route);

        $controller = ctx()->getControllerClass();
        $this->assertEquals($controller == "\\package\\controller\\home\\Home", true);

        $act = ctx()->getAction();
        $this->assertEquals($act == "index", true);
    }

    public function test_getClinetType()
    {
        $request = new Request();
        $request->setHeader([
            'clienttype' => 'pc'
        ]);

        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $clinetType = ctx()->getClinetType();
        $this->assertEquals($clinetType == "pc", true);

        $request->setHeader([
        ]);
        $request->setGet([
            'clienttype' => 'wap'
        ]);

        ctx()->setRequest($request);

        $clinetType = ctx()->getClinetType();
        $this->assertEquals($clinetType == "wap", true);

        $request->setHeader([
        ]);
        $request->setGet([

        ]);
        $request->setCookie([
            'clienttype' => 'wx'
        ]);
        ctx()->setRequest($request);

        $clinetType = ctx()->getClinetType();
        $this->assertEquals($clinetType == "wx", true);

        $request->setHeader([
        ]);
        $request->setGet([

        ]);
        $request->setCookie([

        ]);
        ctx()->setRequest($request);

        $clinetType = ctx()->getClinetType();
        $this->assertEquals($clinetType == "pc", true);
    }

    public function test_get_split_database_config()
    {
        ctx()->setShopId(110000000001);
        $db = ctx()->get_split_database_config();
        $this->assertEquals($db == "db", true);

        ctx()->setShopId(111000000002);
        $db = ctx()->get_split_database_config();
        $this->assertEquals($db == "db1", true);

        ctx()->setShopId(112000000003);
        $db = ctx()->get_split_database_config();
        $this->assertEquals($db == "db2", true);

    }

    public function test_db()
    {
        $request = new Request();
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        $db = ctx()->db();
        $this->assertEquals($db instanceof Db, true);

        $db_cluster_name = 'test';
        $db1 = ctx()->db($db_cluster_name);
        $this->assertEquals($db1 instanceof Db, true);


        $db_cluster_name = 'test';
        $db_name = 'db1';
        $db2 = ctx()->db($db_cluster_name, $db_name);
        $this->assertEquals($db2 instanceof Db, true);
    }

    public function test_cdb()
    {
        $request = new Request();
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        $db = ctx()->cdb();
        $this->assertEquals($db instanceof Cdb, true);

        $db_cluster_name = 'test';
        $db1 = ctx()->cdb($db_cluster_name);
        $this->assertEquals($db1 instanceof Cdb, true);


        $db_cluster_name = 'test';
        $db_name = 'db1';
        $db2 = ctx()->cdb($db_cluster_name, $db_name);
        $this->assertEquals($db2 instanceof Cdb, true);
    }


    public function test_cache()
    {
        $request = new Request();
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        $cache = ctx()->cache();
        $this->assertEquals($cache instanceof Cache, true);

        ctx()->cache('prefix', array(1, 2, 3), 56, 10);

        $rs = ctx()->cache('prefix', array(1, 2, 3));

        $this->assertEquals($rs == 56, true);


        ctx()->cache('prefix', array(1, 2, 3, 4), array(56), 10);

        $rs = ctx()->cache('prefix', array(1, 2, 3, 4));

        $this->assertEquals($rs[0] == 56, true);


//        ctx()->cache('prefix', array(1, 2, 3, 4, 5), array(56), 10);
//
//        sleep(15);
//        $rs = ctx()->cache('prefix', array(1, 2, 3, 4, 5));
//
//        $this->assertEquals($rs == null, true);

    }


}
