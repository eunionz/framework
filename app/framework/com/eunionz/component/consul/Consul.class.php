<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace com\eunionz\component\consul;


use com\eunionz\core\Component;

defined('APP_IN') or exit('Access Denied');

/**
 * Consul 客户端类，工具类，用于实现基于Consul的服务发现与注册
 * Class Consul
 */
class Consul extends Component
{

    /**
     * Consul leader Host
     * @var string
     */
    private $sechma = 'http';


    /**
     * Consul leader Host
     * @var string
     */
    private $host = '127.0.0.1';

    /**
     * Consul leader port
     * @var int
     */
    private $port = 8500;

    /**
     * 协程客户端
     * @var null
     */
    private $client = null;

    /**
     * Consul constructor.
     * @param array $cfg ['host' => ,'port'=>]
     */
    public function __construct($cfg = [])
    {
        if (empty($cfg)) {
            $cfg = self::getConfig('consul');
        }
        $this->sechma = trim(strtolower($cfg['sechma']));
        $this->host = $cfg['host'];
        $this->port = $cfg['port'];
    }


    /**
     * 注销服务
     * @param $service_id  服务ID
     */
    public function service_deregister($service_id)
    {
        $url = $this->sechma . '://' . $this->host . ($this->port == 80 ? '' : ':' . $this->port) . '/v1/agent/service/deregister/' . $service_id;
        $response = $this->do_Put($url, []);
        return empty($response) ? true : false;
    }

    /**
     * 注册服务
     * @param $service_id  服务ID
     */
    public function service_register($service_id, $service_name, $service_address, $service_port, $tags = [], $meta = [], $check = [], $weights = [])
    {
        $url = $this->sechma . '://' . $this->host . ($this->port == 80 ? '' : ':' . $this->port) . '/v1/agent/service/register';
        $service = [
            'ID' => $service_id,
            'Name' => $service_name,
            'Tags' => $tags,
            'Address' => $service_address,
            'Port' => $service_port,
            'Meta' => $meta,
            "EnableTagOverride" => false,
            'Check' => $check,
            'Weights' => $weights,
        ];
        $response = $this->do_Put($url, json_encode($service));
        return empty($response) ? true : false;
    }


    /**
     * 服务发现
     * @param $service_name  服务名称
     * @param $tag  服务标签
     */
    public function service_list($service_name, $tag = '')
    {

        $url = $this->sechma . '://' . $this->host . ($this->port == 80 ? '' : ':' . $this->port) . '/v1/health/service/' . $service_name . '?passing=true';
        $response = $this->do_Get($url);
        $services = [];
        if ($response) {
            $rs = json_decode($response, true);
            foreach ($rs as $item) {
                if ($item) {
                    if (empty($tag) || ($tag && in_array($tag, $item['Service']['Tags']))) {
                        $services[] = [
                            'ID' => $item['Service']['ID'],
                            'Service' => $item['Service']['Service'],
                            'Address' => $item['Service']['Address'],
                            'Port' => $item['Service']['Port'],
                            'Tags' => $item['Service']['Tags'],
                            'Weights' => $item['Service']['Weights'],
                        ];
                    }
                }
            }
        }
        return $services;
    }


    /**
     * 获取一个可用服务
     * @param $service_name  服务名称
     */
    public function get_service($service_name, $tag = '')
    {
        $services = $this->service_list($service_name, $tag);
        if ($services) {
            $index = mt_rand(0, count($services) - 1);
            return $services[$index] ?? null;
        }

        return null;
    }


    /**
     * 离线服务
     * @param $service_id  服务ID
     */
    public function service_offline($service_id, $reason = 'offline')
    {
        $url = $this->sechma . '://' . $this->host . ($this->port == 80 ? '' : ':' . $this->port) . '/v1/agent/service/maintenance/' . $service_id . '?enable=true&reason=' . urlencode($reason);
        $response = $this->do_Put($url, []);
        return empty($response) ? true : false;
    }

    /**
     * 上线服务
     * @param $service_id  服务ID
     */
    public function service_online($service_id, $reason = 'online')
    {
        $url = $this->sechma . '://' . $this->host . ($this->port == 80 ? '' : ':' . $this->port) . '/v1/agent/service/maintenance/' . $service_id . '?enable=false&reason=' . urlencode($reason);
        $response = $this->do_Put($url, []);
        return empty($response) ? true : false;
    }

    function do_Put($url, $fields, $extraheader = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($fields) curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        //curl_setopt($ch, CURLOPT_ENCODING, '');
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    function do_Get($url, $extraheader = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraheader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回:
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}