<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\elasticsearch;


use cn\eunionz\core\Component;
use Elasticsearch\ClientBuilder;

defined('APP_IN') or exit('Access Denied');

/**
 * Elasticsearch 类，工具类
 * Class Elasticsearch
 */
class Elasticsearch extends Component
{
    /**
     * Elasticsearch 连接主机集
     * @var
     */
    private $hosts;

    /**
     * 客户端
     * @var null
     */
    private $client = null;

    /**
     * 创建Elasticsearch 客户端
     * @return void
     */
    public function __construct($hosts = null)
    {
        if($hosts){
            $this->hosts = $hosts;
        }else{
            $this->hosts = self::getConfig('elasticsearch', 'hosts');
        }

        $this->client = ClientBuilder::create()->setHosts($this->hosts)->build();
        if (!$this->client) {
            throw new \Exception("Connect Elasticsearch Servers failure.");
        }
    }


    /*************************************************************
    /**
     * 索引一个文档
     * 说明：索引没有被创建时会自动创建索引
     */
    public function addOne($params)
    {
//        $params = [];
//        $params['index'] = 'xiaochuan';
//        $params['type']  = 'cat';
//        $params['id']  = '20180407001';  # 不指定就是es自动分配
//        $params['body']  = array('name' => '小川编程');
        return $this->client->index($params);
    }


    /**
     * 索引多个文档
     * 说明：索引没有被创建时会自动创建索引
     */
    public function addAll($params)
    {
//        $params = [];
//        for($i = 1; $i < 21; $i++) {
//            $params['body'][] = [
//                'index' => [
//                    '_index' => 'test_index'.$i,
//                    '_type'  => 'cat_test',
//                    '_id'    => $i,
//                ]
//            ];
//            $params['body'][] = [
//                'name' => '小川编程'.$i,
//                'content' => '内容'.$i
//            ];
//        }
        return $this->client->bulk($params);
    }

    /**
     * 获取一个文档
     */
    public function getOne($params)
    {
//        $params = [];
//        $params['index'] = 'xiaochuan';
//        $params['type']  = 'cat';
//        $params['id']    = '20180407001';
        return $this->client->get($params);
    }


    /**
     * 搜索文档
     */
    public function search($params)
    {
//        $params = [];
//        $params['index'] = 'xiaochuan';
//        $params['type']  = 'cat';
//        $params['body']['query']['match']['name'] = '小川编程';
        $rs =  $this->client->search($params);
        $result = [];
        if(isset($rs['hits']['hits']) && $rs['hits']['hits']){
            foreach ($rs['hits']['hits'] as $index => $row){
                $result[$index]   = $row['_source'];
            }
        }
        return $result;
    }


    /**
     * 删除文档
     * 说明：文档删除后，不会删除对应索引。
     */
    public function delete($params)
    {
//        $params = [];
//        $params['index'] = 'xiaochuan';
//        $params['type'] = 'cat';
//        $params['id'] = '20180407001';
        return $this->client->delete($params);
    }

    /*************************************************************
    /**
     * 创建索引
     */
    public function createIndex($params)
    {
//        $params = [];
//        $params['index']  = 'xiaochuan';
        return $this->client->indices()->create($params);
    }

    /**
     * 删除索引：匹配单个 | 匹配多个
     * 说明： 索引删除后，索引下的所有文档也会被删除
     */
    public function deleteIndex($params)
    {
//        $params = [];
//        $params['index'] = 'test_index';  # 删除test_index单个索引
//        #$params['index'] = 'test_index*'; # 删除以test_index开始的所有索引
        try{
            return $this->client->indices()->delete($params);
        }catch (\Exception $err){
            return false;
        }
    }

    /*************************************************************
    /**
     * 设置索引配置
     */
    public function setIndexConfig($params)
    {
//        $params = [];
//        $params['index'] = 'xiaochuan';
//        $params['body']['index']['number_of_replicas'] = 0;
//        $params['body']['index']['refresh_interval'] = -1;
        return $this->client->indices()->putSettings($params);
    }

    /**
     * 获取索引配置
     */
    public function getIndexConfig($params)
    {
//        # 单个获取条件写法
//        $params['index'] = 'xiaochuan';
//        # 多个获取条件写法
//        //$params['index'] = ['xiaochuan', 'test_index'];
        return $this->client->indices()->getSettings($params);
    }

    /**
     * 设置索引映射
     */
    public function setIndexMapping($params)
    {
////        #  设置索引和类型
////        $params['index'] = 'xiaochuan';
////        $params['type']  = 'cat';
//
//        #  向现有索引添加新类型
//        $myTypeMapping = array(
//            '_source' => array(
//                'enabled' => true
//            ),
//            'properties' => array(
//                'first_name' => array(
//                    'type' => 'string',
//                    'analyzer' => 'standard'
//                ),
//                'age' => array(
//                    'type' => 'integer'
//                )
//            )
//        );
//        $params['body']['cat'] = $myTypeMapping;

        #  更新索引映射
        $this->client->indices()->putMapping($params);
    }

    /**
     * 获取索引映射
     */
    public function getIndexMapping()
    {
        #  获取所有索引和类型的映射
        $ret = $this->client->indices()->getMapping();

        /*
        #  获取索引为：xiaochuan的映射
        $params['index'] = 'xiaochuan';
        $ret = $this->api->indices()->getMapping($params);

        #  获取类型为：cat的映射
        $params['type'] = 'cat';
        $ret = $this->api->indices()->getMapping($params);

        #  获取（索引为：xiaochuan和 类型为：cat）的映射
        $params['index'] = 'xiaochuan';
        $params['type']  = 'cat'
        $ret = $this->api->indices()->getMapping($params);

        #  获取索引为：xiaochuan和test_index的映射
        $params['index'] = ['xiaochuan', 'test_index'];
        $ret = $this->api->indices()->getMapping($params);
        */

        return $ret;
    }

    public function __destruct()
    {
    }
}
