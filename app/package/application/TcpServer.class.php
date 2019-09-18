<?php

namespace package\application;

use com\eunionz\core\Context;
use com\eunionz\core\I18n;
use com\eunionz\core\Request;
use com\eunionz\core\Session;

/**
 * Tcp 服务器类
 * Class TcpServer
 * @package package\application
 */
class TcpServer extends \com\eunionz\core\Server
{
    public function onReceive($server, $fd, $reactor_id, $data, $cfg)
    {
//        self::consoleln($data,APP_INFO);
        $server->send($fd, $data . ' world - tcp');

    }
}