<?php

namespace package\application;

use cn\eunionz\core\Context;
use cn\eunionz\core\I18n;
use cn\eunionz\core\Request;
use cn\eunionz\core\Session;

/**
 * Tcp 服务器类
 * Class TcpServer
 * @package package\application
 */
class TcpServer extends \cn\eunionz\core\Server
{
    public function onReceive($server, $fd, $reactor_id, $data, $cfg)
    {
//        self::consoleln($data,APP_INFO);
        $server->send($fd, $data . ' world - tcp');

    }
}