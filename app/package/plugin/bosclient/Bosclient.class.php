<?php
/**
 * EUnionZ PHP Framework Barcode Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\bosclient;

defined('APP_IN') or exit('Access Denied');



class Bosclient extends \com\eunionz\core\Plugin
{
    /**
     * 百度云存储配置
     * @var mixed
     */
    private  $bos_config;

    /**
     * 百度云存储 client 对象
     * @var \BaiduBce\Services\Bos\BosClient
     */
    private  $client;

    /**
     * 百度云存储日志对象
     * @var \BaiduBce\Log\LogFactory
     */
    private  $logger;

    private $bucketName='kshopx';
    private $key;
    private $filename;
    private $download;

	public function __construct()
    {
        require_once 'BaiduBce/index.php';
        $this->bucketName=$this->getConfig('global','BOS_BUCKET_NAME');

        error_reporting(-1);
        date_default_timezone_set('UTC');

        define('__BOS_CLIENT_ROOT', dirname(__DIR__));

        $this->bos_config = $this->getConfig('global','BOS_CONFIG');
        $STDERR = fopen('php://stderr', 'w+');
        $__handler = new \Monolog\Handler\StreamHandler($STDERR, \Monolog\Logger::DEBUG);
        $__handler->setFormatter(
            new \Monolog\Formatter\LineFormatter(null, null, false, true)
        );
        \BaiduBce\Log\LogFactory::setInstance(
            new \BaiduBce\Log\MonoLogFactory(array($__handler))
        );
        \BaiduBce\Log\LogFactory::setLogLevel(\Psr\Log\LogLevel::DEBUG);

        $this->client = new \BaiduBce\Services\Bos\BosClient($this->bos_config);
        $this->logger = \BaiduBce\Log\LogFactory::getLogger(get_class($this));


        //检查云商 Bucket 是否存在
        if(!$this->client->doesBucketExist($this->bucketName)){
            $this->client->createBucket($this->bucketName);
            if(!$this->client->doesBucketExist($this->bucketName)){
                throw new \Exception("百度云存储无法创建【{$this->bucketName}】");
            }
        }

    }


    /**
     * 向百度云存储中上传文件
     * @param $shop_id  店铺/平台id
     * @param $server_dir  服务器端文件基准文件夹名
     * @param $server_filename  服务器端文件名，只能为英文文件名，且路径必须在基准文件夹下
     * @return stdClass
     */
    public function upload($shop_id,$server_dir,$server_filename){
        $key=md5($shop_id.'::' . $server_filename);
        $server_filename = str_ireplace('\\','/', $server_dir . '/' . $server_filename);
        $server_filename = str_ireplace('//','/', $server_filename);
        $this->client->putObjectFromFile($this->bucketName, $key, $server_filename);
        return $this->client->generatePreSignedUrl($this->bucketName, $key);
    }


    /**
     * 从百度云存储中获取文件url
     * @param $shop_id  店铺/平台id
     * @param $server_filename  服务器端文件名，只能为英文文件名
     * @return url false--不存在
     */
    public function getUrl($shop_id,$server_filename){
        $key=md5($shop_id.'::' . $server_filename);
        return $this->client->generatePreSignedUrl($this->bucketName, $key);
    }


    /**
     * 从百度云存储中删除文件
     * @param $shop_id  店铺/平台id
     * @param $server_filename  服务器端文件名，只能为英文文件名
     * @return url false--不存在
     */
    public function delete($shop_id,$server_filename){
        $key=md5($shop_id.'::' . $server_filename);
        return $this->client->deleteObject($this->bucketName, $key);
    }

}