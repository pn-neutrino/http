<?php

namespace Test\Provider;

use Neutrino\Http\Standard\Method;
use Neutrino\Http\Standard\StatusCode;

/**
 * Class TestCase
 *
 * @package     Test\Provider
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    public static function makeDataCall($method, $code, $status = null, $params = [], $json = false)
    {
        $statusMessage = is_null($status) ? StatusCode::message($code) : $status;
        $statusCode    = $code . (empty($statusMessage) ? '' : ' ' . $statusMessage);

        $expected = [
            'code'    => $code,
            'status'  => $statusMessage,
            'body'    => '',
            'headers' => [
                'Status-Code'    => $statusCode,
                'Request-Method' => $method
            ]
        ];

        $header_send = [
            'Host'   => '127.0.0.1:8000',
        ];

        if ($method === Method::POST || $method === Method::PATCH || $method === Method::PUT) {
            $header_send['Content-Type']   = $json ? 'application/json' : 'application/x-www-form-urlencoded';
            $header_send['Content-Length'] = '' . strlen($json ? json_encode($params) : http_build_query($params));
        }

        if ($method !== Method::HEAD) {
            $expected['body'] = json_encode([
                'header_send' => $header_send,
                'query'       => $params
            ]);
        }

        return [$expected, $method, "/$code" . (!empty($status) ? "/" . trim($status) : ''), $params, $json];
    }
}
