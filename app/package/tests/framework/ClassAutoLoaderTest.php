<?php

namespace package\tests\framework;

use cn\eunionz\core\ClassAutoLoader;

require_once __DIR__ . '/../TestBase.php';

class ClassAutoLoaderTest extends \PHPUnit\Framework\TestCase
{

    public function test_autoload()
    {
        $class = '\cn\eunionz\core\Component';
        $rs = ClassAutoLoader::autoload($class);
        $this->assertEquals($rs , true);
        $this->assertEquals(class_exists($class) , true);
    }

    public function test_autoload_1()
    {
        $class = '\cn\eunionz\core\Component1';
        $rs = ClassAutoLoader::autoload($class);
        $this->assertEquals($rs , false);
        $this->assertEquals(class_exists($class) , false);
    }

}
