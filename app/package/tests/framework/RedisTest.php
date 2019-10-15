<?php

namespace package\tests\framework;



use cn\eunionz\component\redis\Redis;

require_once __DIR__ . '/../TestBase.php';

class RedisTest extends \PHPUnit\Framework\TestCase
{


    public function test_set(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);
        $this->assertEquals($redis instanceof Redis , true);
        $redis->redis_set("a1" , "33");
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == "33" , true);


        $redis->redis_set("a2" , "333" , 1);
        sleep(1);
        $a2 = $redis->redis_get("a2");
        $this->assertEquals($a2 != "333" , true);

        $redis->redis_set("a3" , "333" , 5);
        sleep(1);
        $a3 = $redis->redis_get("a3");
        $this->assertEquals($a3 == "333" , true);


        $redis->redis_set("a4" , "444");
        $as = $redis->redis_get(["a1","a4"]);
        consoleln(print_r($as ,true));
        $this->assertEquals(is_array($as) , true);
        $this->assertEquals($as[0]=="33" , true);
        $this->assertEquals($as[1]=="444" , true);
    }

    public function test_setnx(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_set("a1" , "33");
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == "33" , true);

        $rs =  $redis->redis_setnx("a1" , "334");
        $this->assertEquals($rs , false);

        $redis->redis_remove("a2");
        $rs =  $redis->redis_setnx("a2" , "334");
        $this->assertEquals($rs , true);
        $a2 = $redis->redis_get("a2");
        $this->assertEquals($a2 == "334" , true);
    }


    public function test_remove(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_set("a1" , "33");
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == "33" , true);

        $rs = $redis->redis_remove("a1");
        $this->assertEquals($rs , true);

        $rs = $redis->redis_remove("a1");
        $this->assertEquals($rs , false);
    }

    public function test_incr(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_set("a1" , 1);
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 1 , true);

        $redis->redis_incr("a1");
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 2 , true);
    }

    public function test_decr(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_set("a1" , 10);
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 10 , true);

        $redis->redis_decr("a1");
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 9 , true);
    }

    public function test_clear(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_set("a1" , 10);
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 10 , true);

        $redis->redis_clear();
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == null , true);
    }

    public function test_lpush(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_lpush("list1" , 1);
        $redis->redis_lpush("list1" , 2);
        $num = $redis->redis_lsize("list1");
        $this->assertEquals($num > 0 , true);

        $value = $redis->redis_lpop("list1");
        $this->assertEquals($value == 2 , true);

        $rs = $redis->redis_remove("list1");
        $this->assertEquals($rs , true);

        $num = $redis->redis_lsize("list1");
        $this->assertEquals($num <= 0 , true);

        $redis->redis_lpush("list1" , 1);
        $redis->redis_lpush("list1" , 2);
        $redis->redis_lpush("list1" , 3);
        $redis->redis_lpush("list1" , 4);
        $redis->redis_lpush("list1" , 5);
        $num = $redis->redis_lsize("list1");
        $this->assertEquals($num == 5 , true);

        $arr = $redis->redis_lrange("list1" , 1 ,3);
        $this->assertEquals(count($arr) == 3 , true);
        consoleln(print_r($arr , true));
        $this->assertEquals($arr[0] == 4 , true);

    }


    public function test_hset(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_hset("hash1" , "a","ddd");
        $value = $redis->redis_hget("hash1" , "a");
        $this->assertEquals($value == "ddd" , true);


        $rs = $redis->redis_hdel("hash1" , "a");
        $this->assertEquals($rs , true);

        $value = $redis->redis_hget("hash1" , "a");
        $this->assertEquals($value == null , true);

        $redis->redis_hset("hash1" , "a","ddd1");
        $redis->redis_hset("hash1" , "b","ddd2");
        $redis->redis_hset("hash1" , "c","ddd3");
        $arr = $redis->redis_hgetAll("hash1");
        consoleln(print_r($arr,true));
        $this->assertEquals($arr['a'] == "ddd1" , true);
        $this->assertEquals($arr['b'] == "ddd2" , true);
        $this->assertEquals($arr['c'] == "ddd3" , true);


    }

    public function test_multi(){
        $redis = new Redis(getConfig('cache','cache_driver_data')['redis_servers']);

        $redis->redis_clear();
        $redis->redis_multi();
        $redis->redis_set("a1" , 10);
        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == null , true);

        $redis->redis_lpush("list1" , 1);
        $redis->redis_hset("hash1" , "a","ddd1");
        sleep(3);
        $redis->redis_exec();

        $a1 = $redis->redis_get("a1");
        $this->assertEquals($a1 == 10 , true);

    }

}
