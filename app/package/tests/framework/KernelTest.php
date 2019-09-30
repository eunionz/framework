<?php

namespace package\tests\framework;

use cn\eunionz\component\activemq\Activemq;
use cn\eunionz\component\cache\Cache;
use cn\eunionz\component\cdb\Cdb;
use cn\eunionz\component\db\Db;
use cn\eunionz\core\Context;
use cn\eunionz\core\Controller;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Kernel;
use cn\eunionz\core\Request;
use cn\eunionz\core\Response;
use cn\eunionz\core\Router;
use cn\eunionz\core\Session;
use cn\eunionz\exception\FileNotFoundException;
use package\controller\home\Home;
use package\plugin\common\Common;
use package\service\sessions\Sessions;

require_once __DIR__ . '/../TestBase.php';

class KernelTest extends \PHPUnit\Framework\TestCase
{

    public function test_showLogo()
    {
        Kernel::showLogo();
        $this->assertEquals(true, true);
    }


    public function test_getColoredString()
    {
        $a = Kernel::getColoredString("a");
        $this->assertEquals(strpos($a, 'a') !== false, true);

    }


    public function test_loadCore()
    {
        $kernel = new Kernel();
        $Context = $kernel->loadCore('Context');
        $this->assertEquals($Context instanceof Context, true);

        $Context1 = $kernel->loadCore('Context');
        $this->assertEquals($Context === $Context1, true);

        $Context2 = $kernel->loadCore('Context', false);
        $this->assertEquals($Context !== $Context2, true);

    }


    public function test_loadComponent()
    {
        $kernel = new Kernel();
        $activemq = $kernel->loadComponent('activemq');
        $this->assertEquals($activemq instanceof Activemq, true);

        $activemq1 = $kernel->loadComponent('activemq');
        $this->assertEquals($activemq === $activemq1, true);

        $activemq2 = $kernel->loadComponent('activemq', false);
        $this->assertEquals($activemq !== $activemq2, true);

    }

    public function test_loadService()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $kernel = new Kernel();
        $sessions = $kernel->loadService('sessions');
        $this->assertEquals($sessions instanceof Sessions, true);

        $sessions1 = $kernel->loadService('sessions');
        $this->assertEquals($sessions === $sessions1, true);

        $sessions2 = $kernel->loadService('sessions', false);
        $this->assertEquals($sessions !== $sessions2, true);

    }

    public function test_loadPlugin()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $kernel = new Kernel();
        $common = $kernel->loadPlugin('common');
        $this->assertEquals($common instanceof Common, true);

        $common1 = $kernel->loadPlugin('common');
        $this->assertEquals($common === $common1, true);

        $common2 = $kernel->loadPlugin('common', false);
        $this->assertEquals($common !== $common2, true);

    }

    public function test_loadModel()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $kernel = new Kernel();
        $sessions = $kernel->loadModel('sessions');
        $this->assertEquals($sessions instanceof \package\model\Sessions, true);

        $sessions1 = $kernel->loadModel('sessions');
        $this->assertEquals($sessions === $sessions1, true);

        $sessions2 = $kernel->loadModel('sessions', false);
        $this->assertEquals($sessions !== $sessions2, true);

    }


    public function test_loadConstrants()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $this->assertEquals(defined("APP_PATH") && APP_PATH == '/', true);
    }

    public function test_getServer()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $server = Kernel::getServer();
        $this->assertEquals($server == null, true);

        $server1 = Kernel::getServer("main");
        $this->assertEquals($server1 == null, true);
    }

    public function test_getConnections()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $conns = Kernel::getConnections();
        $this->assertEquals($conns == [], true);

        $conns1 = Kernel::getConnections("main");
        $this->assertEquals($conns1 == [], true);
    }

    public function test_getCurrentWorkerPid()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $pid = Kernel::getCurrentWorkerPid();
        $this->assertEquals($pid == 1, true);
    }

    public function test_getMasterPid()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $pid = Kernel::getMasterPid();
        $this->assertEquals($pid == 1, true);
    }

    public function test_getManagerPid()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $pid = Kernel::getManagerPid();
        $this->assertEquals($pid == 1, true);
    }

    public function test_getCurrentWorkerId()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $pid = Kernel::getCurrentWorkerId();
        $this->assertEquals($pid == 1, true);
    }

    public function test_getRequestUniqueId()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $pid = Kernel::getRequestUniqueId();
        $this->assertEquals($pid == "1", true);
    }

    public function test_setRequestFd()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        Kernel::setRequestFd(1);
        $fd = Kernel::getRequestFd();
        $this->assertEquals($fd == 1, true);
    }

    public function test_setConfig()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        Kernel::setConfig("app", "AAA", "32323232");
        $v = Kernel::getConfig("app", "AAA");
        $this->assertEquals($v == "32323232", true);
    }

    public function test_getConfig()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        try {
            $apps = Kernel::getConfig("app1");
        } catch (\Exception $err) {
            $this->assertEquals($err instanceof FileNotFoundException, true);
        }

        $apps = Kernel::getConfig("app");
        $this->assertEquals($apps['APP_DEFAULT_TIMEZONE'] == "PRC", true);

        $APP_DEFAULT_TIMEZONE = Kernel::getConfig("app", 'APP_DEFAULT_TIMEZONE');
        $this->assertEquals($APP_DEFAULT_TIMEZONE == "PRC", true);
    }

    public function test_reloadConfig()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $APP_DEFAULT_TIMEZONE = Kernel::getConfig("app", 'APP_DEFAULT_TIMEZONE');
        $this->assertEquals($APP_DEFAULT_TIMEZONE == "PRC", true);

        Kernel::setConfig("app", 'APP_DEFAULT_TIMEZONE', '111');
        $APP_DEFAULT_TIMEZONE1 = Kernel::getConfig("app", 'APP_DEFAULT_TIMEZONE');
        $this->assertEquals($APP_DEFAULT_TIMEZONE1 == "111", true);

        Kernel::reloadConfig("app");
        $APP_DEFAULT_TIMEZONE2 = Kernel::getConfig("app", 'APP_DEFAULT_TIMEZONE');
        $this->assertEquals($APP_DEFAULT_TIMEZONE2 == "PRC", true);

    }

    public function test_getRouterPathByControllerClass()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $path = Kernel::getRouterPathByControllerClass(Home::class);

        consoleln($path);
        $this->assertEquals($path == "home/home", true);
    }

    public function test_checkProcessExistsByName()
    {
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());

        $rs = Kernel::checkProcessExistsByName("aaaa");
        $this->assertEquals($rs == false, true);
    }


    public function test_session()
    {
        $request = new Request();
        Kernel::setConfig('app', 'APP_SESSION_MODE', 'redis');
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $session = new Session();
        $session_id = $session->makeSessionId();
        ctx()->setSession($session);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $kernel->session('a', 2);
        $a = $kernel->session('a');
        $this->assertEquals($a == 2, true);

        $kernel->session('b', array('a' => 34, 'b' => 56));
        $b = $kernel->session('b');
        $this->assertEquals($b['a'] == 34 && $b['b'] == 56, true);


        $request = new Request();
        Kernel::setConfig('app', 'APP_SESSION_MODE', 'file');
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $session = new Session();
        $session_id = $session->makeSessionId();
        ctx()->setSession($session);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $kernel->session('a', 2);
        $a = $kernel->session('a');
        $this->assertEquals($a == 2, true);

        $kernel->session('b', array('a' => 34, 'b' => 56));
        $b = $kernel->session('b');
        $this->assertEquals($b['a'] == 34 && $b['b'] == 56, true);

        $request = new Request();
        Kernel::setConfig('app', 'APP_SESSION_MODE', 'sql');
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $session = new Session();
        $session_id = $session->makeSessionId();
        ctx()->setSession($session);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $kernel->session('a', 2);
        $a = $kernel->session('a');
        $this->assertEquals($a == 2, true);

        $kernel->session('b', array('a' => 34, 'b' => 56));
        $b = $kernel->session('b');
        $this->assertEquals($b['a'] == 34 && $b['b'] == 56, true);
    }

    public function test_server()
    {
        $request = new Request();
        $request->setServer(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->server('aa');
        $this->assertEquals($aa == '333', true);


        $kernel->server('bb', 45);
        $bb = $kernel->server('bb');
        $this->assertEquals($bb == 45, true);

    }

    public function test_get()
    {
        $request = new Request();
        $request->setGet(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->get('aa');
        $this->assertEquals($aa == '333', true);


        $kernel->get('bb', [45]);
        $bb = $kernel->get('bb');
        $this->assertEquals($bb[0] == 45, true);

    }

    public function test_post()
    {
        $request = new Request();
        $request->setPost(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->post('aa');
        $this->assertEquals($aa == '333', true);


        $kernel->post('bb', [45]);
        $bb = $kernel->post('bb');
        $this->assertEquals($bb[0] == 45, true);

    }

    public function test_cookie()
    {
        $request = new Request();
        $request->setCookie(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->cookie('aa');
        $this->assertEquals($aa == '333', true);


        $kernel->cookie('bb', '45');
        $bb = $kernel->cookie('bb');
        $this->assertEquals($bb == 45, true);

    }

    public function test_request()
    {
        $request = new Request();
        $request->setCookie(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->request('aa');
        $this->assertEquals($aa == '333', true);

        $kernel->request('bb', '45');
        $bb = $kernel->request('bb');
        $this->assertEquals($bb == 45, true);

    }

    public function test_header()
    {
        $request = new Request();
        $request->setHeader(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $aa = $kernel->header('aa');
        $this->assertEquals($aa == '333', true);

        $headers = $kernel->getallheaders();
        $this->assertEquals($headers['aa'] == '333', true);

        $bb = $kernel->getallheaders('aa');
        $this->assertEquals($bb == '333', true);

    }

    public function test_files()
    {
        $request = new Request();
        $request->setFiles(
            [
                'aa' => '333',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $files = $kernel->files();
        $this->assertEquals($files['aa'] == '333', true);


    }

    public function test_getClinetVersion()
    {
        $request = new Request();
        $request->setHeader(
            [
                'clientversion' => '2.1',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $clientversion = $kernel->getClinetVersion();
        $this->assertEquals($clientversion == '2.1', true);


        $request->setHeader(
            [
             //   'clientversion' => '2.1',
            ]
        );
        $request->setGet(
            [
                'clientversion' => '2.2',
            ]
        );
        $request->setCookie(
            [
                'clientversion' => '2.3',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $clientversion = $kernel->getClinetVersion();
        $this->assertEquals($clientversion == '2.2', true);

        $request->setHeader(
            [
                //   'clientversion' => '2.1',
            ]
        );
        $request->setGet(
            [
                //'clientversion' => '2.2',
            ]
        );
        $request->setCookie(
            [
                'clientversion' => '2.3',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $clientversion = $kernel->getClinetVersion();
        $this->assertEquals($clientversion == '2.3', true);


        $request->setHeader(
            [
                //   'clientversion' => '2.1',
            ]
        );
        $request->setGet(
            [
                //'clientversion' => '2.2',
            ]
        );
        $request->setCookie(
            [
//                'clientversion' => '2.3',
            ]
        );
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $clientversion = $kernel->getClinetVersion();
        $this->assertEquals($clientversion == '0', true);

    }

    public function test_csrftoken(){
        $request = new Request();
        ctx()->setShopId(10000006);
        ctx()->setResponse(new Response());
        ctx()->setAppRuntimePath(APP_PATH . APP_STORAGE_NAME . '/' . ctx()->getShopId() . '/' . APP_RUNTIME_NAME . '/');
        ctx()->setAppRuntimeRealPath(APP_STORAGE_REAL_PATH . ctx()->getShopId() . APP_DS . APP_RUNTIME_NAME . APP_DS);
        $request->initialize();
        ctx()->setRequest($request);
        $i18n = new I18n();
        $kernel = new Kernel();
        $csrftoken = $kernel->csrftoken();
        $this->assertEquals($csrftoken == $request->cookie('_csrftoken'), true);
    }
}
