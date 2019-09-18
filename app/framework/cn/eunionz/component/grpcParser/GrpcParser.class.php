<?php
/**
 * Eunionz PHP Framework Cache Plugin class
 * Created by PhpStorm.
 * User: liulin  (84611913@qq.com)
 * Date: 15-4-30
 * Time: 上午10:16
 */

namespace cn\eunionz\component\grpcParser;


use cn\eunionz\core\Component;
use Google\Protobuf\Internal\Message;

defined('APP_IN') or exit('Access Denied');

/**
 * GrpcParser类，工具类
 * Class GrpcParser
 */
class GrpcParser extends Component
{

    const GRPC_ERROR_NO_RESPONSE = -1;

    public static function pack(string $data): string
    {
        return $data = pack('CN', 0, strlen($data)) . $data;
    }

    public static function unpack(string $data): string
    {
        // it's the way to verify the package length
        // 1 + 4 + data
        // $len = unpack('N', substr($data, 1, 4))[1];
        // assert(strlen($data) - 5 === $len);
        return $data = substr($data, 5);
    }

    public static function serializeMessage($data)
    {
        if (method_exists($data, 'encode')) {
            $data = $data->encode();
        } elseif (method_exists($data, 'serializeToString')) {
            $data = $data->serializeToString();
        } elseif (method_exists($data, 'serialize')) {
            /** @noinspection PhpUndefinedMethodInspection */
            $data = $data->serialize();
        }
        return self::pack((string) $data);
    }

    public static function deserializeMessage($deserialize, string $value)
    {
        if (empty($value)) {
            return null;
        }
        $value = self::unpack($value);

        if (is_array($deserialize)) {
            [$className, $deserializeFunc] = $deserialize;
            /** @var \Google\Protobuf\Internal\Message $object */
            $object = new $className();
            if ($deserializeFunc && method_exists($object, $deserializeFunc)) {
                $object->{$deserializeFunc}($value);
            } else {
                // @noinspection PhpUndefinedMethodInspection
                $object->mergeFromString($value);
            }
            return $object;
        }
        return call_user_func($deserialize, $value);
    }

    /**
     * @param null|\swoole_http2_response $response
     * @param $deserialize
     * @return \Grpc\StringifyAble[]|Message[]|\swoole_http2_response[]
     */
    public static function parseResponse($response, $deserialize): array
    {
        if (! $response) {
            return ['No response', self::GRPC_ERROR_NO_RESPONSE, $response];
        }
        if ($response->statusCode !== 200) {
            return ['Http status Error', $response->errCode ?: $response->statusCode, $response];
        }
        $grpc_status = (int) ($response->headers['grpc-status'] ?? 0);
        if ($grpc_status !== 0) {
            return [$response->headers['grpc-message'] ?? 'Unknown error', $grpc_status, $response];
        }
        $data = $response->data;
        $reply = self::deserializeMessage($deserialize, $data);
        $status = (int) ($response->headers['grpc-status'] ?? 0 ?: 0);
        return [$reply, $status, $response];
    }
}