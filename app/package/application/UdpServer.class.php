<?php

namespace package\application;

use cn\eunionz\core\Context;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Request;
use cn\eunionz\core\Session;

/**
 * Udp 服务器类
 * Class UdpServer
 * @package package\application
 */
class UdpServer extends \cn\eunionz\core\Server
{
    public function onPacket($server, $data, $client_info, $cfg)
    {
        $server->sendto($client_info['address'], $client_info['port'], $data . ' world udp');
    }


}