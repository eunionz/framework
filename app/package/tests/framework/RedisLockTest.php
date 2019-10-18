<?php

namespace package\tests\framework;


use cn\eunionz\core\RedisLock;

require_once __DIR__ . '/../TestBase.php';

class RedisLockTest extends \PHPUnit\Framework\TestCase
{


    public function test_lock()
    {
        $redislock = new RedisLock();

        $rs = $redislock->redis_lock("a");

        $this->assertEquals($rs, true);

        $rs = $redislock->redis_isLocking("a");

        $this->assertEquals($rs > 0, true);

        $rs = $redislock->redis_expire("a" , 50);

        $this->assertEquals($rs, true);

        $rs =  $redislock->redis_unlock("a");
        $this->assertEquals($rs, true);

    }


}
