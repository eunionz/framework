<?php

namespace package\tests\framework;

use cn\eunionz\component\cache\Cache;
use cn\eunionz\component\cdb\Cdb;
use cn\eunionz\component\db\Db;
use cn\eunionz\core\Controller;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Request;
use cn\eunionz\core\Response;
use cn\eunionz\core\Router;
use cn\eunionz\core\Session;

require_once __DIR__ . '/../TestBase.php';

class ControllerTest extends \PHPUnit\Framework\TestCase
{

    public function test_initialize()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $controller->initialize();

        consoleln(print_r($controller->viewData['all_langs'], true));
        $this->assertEquals(isset($controller->viewData['all_langs']), true);
        consoleln(print_r($controller->viewData['js_all_langs'], true));
        $this->assertEquals(isset($controller->viewData['js_all_langs']), true);
        consoleln(print_r($controller->viewData['js_headers'], true));
        $this->assertEquals(isset($controller->viewData['js_headers']), true);

    }

    public function test_is_post()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'post',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_post();
        $this->assertEquals($rs, true);
    }

    public function test_is_ajax()
    {
        $request = new Request();
        $request->setServer(
            [
                "HTTP_X_REQUESTED_WITH" => 'xmlhttprequest',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_ajax();
        $this->assertEquals($rs, true);
    }

    public function test_is_get()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'get',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_get();
        $this->assertEquals($rs, true);
    }

    public function test_is_put()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'put',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_put();
        $this->assertEquals($rs, true);
    }

    public function test_is_delete()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'delete',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_delete();
        $this->assertEquals($rs, true);
    }

    public function test_is_options()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'options',
            ]
        );
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());

        $controller = new Controller();
        $rs = $controller->is_options();
        $this->assertEquals($rs, true);
    }

    public function test_display()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'get',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        ctx()->setTheme("pc");

        $controller = new Controller();
        $rs = '';
        try {
            $rs = $controller->display(null, [], true);
        } catch (\Exception $err) {
            $rs = null;
        }
        $this->assertEquals($rs == null, true);

    }


    public function test_ajaxReturn()
    {
        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'get',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        ctx()->setTheme("pc");

        $controller = new Controller();
        $rs = $controller->ajaxReturn(["a"=>2], true);
        $rs = json_decode($rs , true);
        $this->assertEquals($rs['a'] == 2, true);
    }

    public function test_setcookie(){

        $request = new Request();
        $request->setServer(
            [
                "REQUEST_METHOD" => 'get',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        ctx()->setI18n(new I18n());
        ctx()->setTheme("pc");

        $controller = new Controller();
        $rs = $controller->setcookie("test", "23");
        $this->assertEquals($rs == "23", true);
    }


}
