<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-30
 * Time: 下午2:48
 */

namespace package\controller\websocket;

use com\eunionz\core\Controller;

/**
 * Class Home
 * @package package\controller\websocket
 * @WEBSOCKET_CLASS
 */
class Home extends \com\eunionz\core\Controller
{
    public $accept_data = null;
    public $header = null;
    public $url = null;

    /**
     * @param $a
     * @param $b
     * @WEBSOCKET_METHOD
     * @return string
     */
    public function _index($a, $b)
    {
        $this->session('login_user111', array('admin_websocket', 'websocket ddd'));
        return ["aaaaa : " => ($a + $b),
            ' : ' . $this->getLang('test1') . ' : ' => $this->getLang('app_output_trace_var_value'),
            'accept_data' => $this->accept_data,
            'header' => $this->header,
            'get' => $this->get(),
            'url' => $this->url,
            ];
    }


}
