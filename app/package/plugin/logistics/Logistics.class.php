<?php
/**
 * EUnionZ PHP Framework Logistics Plugin class
 * 物流查询插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */
namespace package\plugin\logistics;

defined('APP_IN') or exit('Access Denied');

class Logistics extends \com\eunionz\core\Plugin
{
    /*
     * {
	"com":"顺丰",
	"no":"sf"
	},
	{
	"com":"申通",
	"no":"sto"
	},
	{
	"com":"圆通",
	"no":"yt"
	},
	{
	"com":"韵达",
	"no":"yd"
	},
	{
	"com":"天天",
	"no":"tt"
	},
	{
	"com":"EMS",
	"no":"ems"
	},
	{
	"com":"中通",
	"no":"zto"
	},
	{
	"com":"汇通",
	"no":"ht"
	}
     */

    function __construct()
    {
    }

    /**
     * 通过物流接口查询物流信息
     * @param $code  物流公司代码(转换之后的物流公司代码)
     * @param $logistics_no  运单号
     * @param $is_free  是否免费物流  0--收费(聚合物流)  1--免费(快递100)
     * @return  免费物流返回  url   收费物流返回 json
     */
    public function api_q($code, $logistics_no, $is_free = 1)
    {
        if ($is_free) {//免费
            $code = $this->logistics_com2free($code);
            $contents = file_get_contents("http://www.kuaidi100.com/applyurl?key=21e73de7adcd35da&com={$code}&nu={$logistics_no}");
            return $contents;
        } else {//收费
            $data = file_get_contents("http://v.juhe.cn/exp/index?key=8be82cd52d09727e5cea4c76782f6e02&com={$code}&no={$logistics_no}");
            $data = json_decode($data, true);
            return $data['result'];
        }
    }

    /**
     * 将收费物流公司代码转换为免费物流(快递100)公司代码
     * @param $code 收费物流代码
     * @return 免费物流代码
     */
    public function logistics_com2free($code)
    {
        $shipping_code_exchanges = array(
            'yd' => 'yunda',
            'yt' => 'yuantong',
            'sf' => 'shunfeng',
            'zto' => 'zhongtong',
            'tt' => 'tiantian',
            'ht' => 'huitongkuaidi',
        );
        return isset($shipping_code_exchanges[$code]) ? $shipping_code_exchanges[$code] : $code;
    }
}