<?php
/**
 * EUnionZ PHP Framework Pinyin Plugin class
 * 微信高级插件
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace package\plugin\weixinadv;


defined('APP_IN') or exit('Access Denied');


class Weixinadv extends \cn\eunionz\core\Plugin
{

    public $appid = '';//微信公众号APPID
    public $appsecret = '';//微信公众号安全码


    public function init($appid, $appsecret)
    {
        $this->appid = $appid;
        $this->appsecret = $appsecret;

        //hardcode
        $weixin_expired = false;
        $SESSION = ctx()->session();
        if (!isset($SESSION['weixin_lasttime']) || empty($SESSION['weixin_lasttime'])) {
            $weixin_expired = true;
        } else {
            $this->access_token = $SESSION['weixin_access_token'];
            $this->lasttime = $SESSION['weixin_lasttime'];
            if (time() > ($this->lasttime + 7200)) {
                $weixin_expired = true;
            }
        }

        if ($weixin_expired) {
            $url = APP_WEIXIN_ACCESS_TOKEN_AGENT_URL . urlencode("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->appsecret);
            $res = $this->https_request($url);
            $result = json_decode($res, true);
            //save to Database or Memcache
            $this->access_token = $result["access_token"];
            $this->lasttime = time();

            ctx()->session('weixin_lasttime', $this->lasttime);
            ctx()->session('weixin_access_token', $this->access_token);
        }

        return $this;
    }

    //构造函数
    public function __construct()
    {


    }

    //获取关注者列表
    public function get_user_list($next_openid = NULL)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=" . $this->access_token . "&next_openid=" . ($next_openid ? $next_openid : '');
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //获取用户基本信息
    public function get_user_info($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $this->access_token . "&openid=" . $openid . "&lang=zh_CN";
        $res = $this->https_request($url);
        return json_decode($res, true);
    }

    //创建菜单
    public function create_menu($data)
    {

        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this->access_token;

        $res = $this->https_request($url, $data);

        return json_decode($res, true);
    }

    //发送客服消息，已实现发送文本，其他类型可扩展
    public function send_custom_message($touser, $type, $data)
    {
        $msg = array('touser' => $touser);
        switch ($type) {
            case 'text':
                $msg['msgtype'] = 'text';
                $msg['text'] = array('content' => urlencode($data));
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $this->access_token;
        return $this->https_request($url, urldecode(json_encode($msg)));
    }

    //生成参数二维码
    public function create_qrcode($scene_type, $scene_id)
    {
        switch ($scene_type) {
            case 'QR_LIMIT_SCENE': //永久
                $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
                break;
            case 'QR_SCENE':       //临时
                $data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $scene_id . '}}}';
                break;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        $result = json_decode($res, true);
        return $result["ticket"];
        //return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($result["ticket"]);
    }

    //创建分组
    public function create_group($name)
    {
        $data = '{"group": {"name": "' . $name . '"}}';
        $url = "https://api.weixin.qq.com/cgi-bin/groups/create?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //移动用户分组
    public function update_group($openid, $to_groupid)
    {
        $data = '{"openid":"' . $openid . '","to_groupid":' . $to_groupid . '}';
        $url = "https://api.weixin.qq.com/cgi-bin/groups/members/update?access_token=" . $this->access_token;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //上传多媒体文件
    public function upload_media($type, $file)
    {
        $data = array("media" => "@" . dirname(__FILE__) . '\\' . $file);
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $this->access_token . "&type=" . $type;
        $res = $this->https_request($url, $data);
        return json_decode($res, true);
    }

    //https请求（支持GET和POST）
    public function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


}
