<?php

namespace package\application;

use com\eunionz\core\Context;
use com\eunionz\core\I18n;
use com\eunionz\core\Request;
use com\eunionz\core\Session;

/**
 * Udp 服务器类
 * Class UdpServer
 * @package package\application
 */
class UdpServer extends \com\eunionz\core\Server
{
    public function onPacket($server, $data, $client_info, $cfg)
    {
        $server->sendto($client_info['address'], $client_info['port'], $data . ' world udp');
    }


}