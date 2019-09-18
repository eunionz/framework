<?php

namespace package\application;

use com\eunionz\component\grpcParser\GrpcParser;
use com\eunionz\core\Context;
use com\eunionz\core\I18n;
use com\eunionz\core\Request;
use com\eunionz\core\Response;
use com\eunionz\core\Session;
use com\eunionz\exception\ControllerNotFoundException;
use com\eunionz\exception\MethodNotFoundException;

/**
 * Grpc 服务器类
 * Class GrpcServer
 * @package package\application
 */
class GrpcServer extends \com\eunionz\core\Server
{
//    /**
//     * 主服务器/其它监听服务器有http/https请求时触发该事件
//     * 说明：
//     *       主服务器/其它服务器均可设置 onRequest 事件回调
//     *       主要用于对http/https web服务器新的http请求事件进行处理
//     *
//     * @param $request      当前请求对像
//     * @param $response     当前响应对像
//     * @param $cfg          当前服务器配置
//     */
//    public function onRequest($request, $response, $cfg)
//    {
//        @libxml_disable_entity_loader(true);
//
//        $path = $request->server['request_uri'];
//        if(strtolower($path) == '/favicon.ico') {
//            $response->end();
//            return;
//        }
//
//        if ($path == '/grpc.HelloService/SayHello') {
//            // decode, 获取 rpc 中的请求
//            $request_message = GrpcParser::deserializeMessage([\pacakge\grpc\Grpc\HiUser::class, null], $request->rawContent());
//
//            // encode, 返回 rpc 中的应答
//            $response_message = new \package\grpc\Grpc\HiReply();
//            $response_message->setMessage('Hello ' . $request_message->getName());
//            $response->header('content-type', 'application/grpc');
//            $response->header('trailer', 'grpc-status, grpc-message');
//            $trailer = [
//                "grpc-status" => "0",
//                "grpc-message" => ""
//            ];
//            foreach ($trailer as $trailer_name => $trailer_value) {
//                $response->trailer($trailer_name, $trailer_value);
//            }
//            $response->end(GrpcParser::serializeMessage($response_message));
//            return true;
//        }
//        $response->status(400);
//        $response->end('Bad Request');
//        return false;
//    }

}