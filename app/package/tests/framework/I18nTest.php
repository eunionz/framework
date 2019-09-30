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
use package\controller\home\Home;

require_once __DIR__ . '/../TestBase.php';

class I18nTest extends \PHPUnit\Framework\TestCase
{

    public function test_getLanguage()
    {
        $request = new Request();
        $request->setGet(
            [
                "APP_LANGUAGE" => "",
            ]
        );
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $i18n = new I18n();
        $APP_LANGUAGE = $i18n->getLanguage();

        $this->assertEquals($APP_LANGUAGE=='en_us', true);

        $request->setGet(
            [
                "APP_LANGUAGE" => "zh-cn",
            ]
        );
        ctx()->setRequest($request);
        $APP_LANGUAGE = $i18n->getLanguage();
        $this->assertEquals($APP_LANGUAGE=='zh_cn', true);


        $request->setGet(
            [
            ]
        );
        $request->setCookie(
            [
                "APP_LANGUAGE" => "ar-dr",
            ]
        );
        ctx()->setRequest($request);
        $APP_LANGUAGE = $i18n->getLanguage();
        $this->assertEquals($APP_LANGUAGE=='ar_dr', true);

        $request->setGet(
            [
            ]
        );
        $request->setCookie(
            [
            ]
        );
        ctx()->session("APP_LANGUAGE" , "rd-ar");
        ctx()->setRequest($request);
        $APP_LANGUAGE = $i18n->getLanguage();
        $this->assertEquals($APP_LANGUAGE=='rd_ar', true);

    }


    public function test_getDefaultLanguage(){
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $i18n = new I18n();
        $APP_LANGUAGE = $i18n->getDefaultLanguage();

        $this->assertEquals($APP_LANGUAGE=='en_us', true);

    }


    public function test_getLang(){
        $request = new Request();
        ctx()->setShopId(21212);
        ctx()->setRequest($request);
        ctx()->setResponse(new Response());
        ctx()->setSession(new Session());
        $i18n = new I18n();
        $i18n->getCoreLang('core');
        $langs = $i18n->getLang();

        $this->assertEquals($langs['app_output_trace_var_value']=='value', true);

        ctx()->session("APP_LANGUAGE" , "zh-cn");
        $i18n->getCoreLang('core');
        $langs = $i18n->getLang();
        $this->assertEquals($langs['app_output_trace_var_value']=='变量值', true);


        ctx()->session("APP_LANGUAGE" , "en-us");
        $i18n->getCoreLang('core');
        $i18n->getGlobalLang('global');

        $langs = $i18n->getLang();

        $this->assertEquals($langs['application_name']=='K+ShopMAX', true);

        ctx()->session("APP_LANGUAGE" , "zh-cn");
        $i18n->getCoreLang('core');
        $i18n->getGlobalLang('global');

        $langs = $i18n->getLang();

        $this->assertEquals($langs['application_name']=='K+云商MAX', true);


        ctx()->session("APP_LANGUAGE" , "en-us");
        $i18n->getCoreLang('core');
        $i18n->getGlobalLang('global');
        $i18n->getControllerLang(Home::class);

        $langs = $i18n->getLang();

        $this->assertEquals($langs['test']=='test56789yyyyyy', true);

        ctx()->session("APP_LANGUAGE" , "zh-cn");
        $i18n->getCoreLang('core');
        $i18n->getGlobalLang('global');
        $i18n->getControllerLang(Home::class);

        $langs = $i18n->getLang();

        $this->assertEquals($langs['test']=='测试7890yyyy', true);


        ctx()->session("APP_LANGUAGE" , "zh-cn");
        $i18n->getCoreLang('core');
        $i18n->getGlobalLang('global');
        $i18n->getControllerLang(Home::class);

        $rs = $i18n->getLang('error_hook_method_not_found',["a","b"]);
        $this->assertEquals($rs=='钩子类 a 成员方法 b 不存在。', true);

    }






}
