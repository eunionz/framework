<?php

namespace package\tests\framework;


use cn\eunionz\component\tokenBucket\TokenBucket;

require_once __DIR__ . '/../TestBase.php';

class TokenBucketTest extends \PHPUnit\Framework\TestCase
{
    public function test_set()
    {
        $tokenbucket = new TokenBucket(getConfig('cache', 'cache_driver_data')['redis_servers'], "tokenbucket_default", 5);
        $this->assertEquals($tokenbucket instanceof TokenBucket, true);

        $tokenbucket->token_reset();
        // 循环获取令牌，令牌桶内只有5个令牌，因此最后3次获取失败
        for ($i = 0; $i < 8; $i++) {
            $rs = $tokenbucket->token_get();
            if($rs==false) break;
            consoleln($rs);
        }
        consoleln();

        $this->assertEquals($i == 5, true);


        // 加入10个令牌，最大令牌为5，因此只能加入5个
        $add_num = $tokenbucket->token_add(10);
        consoleln($add_num);
        consoleln();
        $this->assertEquals($add_num == 5, true);
        // 循环获取令牌，令牌桶内只有5个令牌，因此最后1次获取失败
        for($i=0; $i<6; $i++){
            $rs = $tokenbucket->token_get();
            if($rs==false) break;
            consoleln($rs);
        }
        consoleln();
        $this->assertEquals($i == 5, true);
    }


}
