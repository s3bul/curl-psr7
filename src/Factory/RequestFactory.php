<?php
declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use S3bul\CurlPsr7\Util\HttpMethod;

class RequestFactory
{
    private static function createRequest(string $method, string $uri, array $data = [], array $headers = [], $body = null): RequestInterface
    {
        return new Request(
            $method,
            count($data) > 0 ? Uri::withQueryValues(new Uri($uri), $data) : $uri,
            $headers,
            is_array($body) ? http_build_query($body) : $body,
        );
    }

    public static function get(string $uri, array $data = [], array $headers = []): RequestInterface
    {
        return self::createRequest(HttpMethod::GET, $uri, $data, $headers);
    }

    public static function post(string $uri, $body = null, array $headers = []): RequestInterface
    {
        return self::createRequest(HttpMethod::POST, $uri, [], $headers, $body);
    }

    public static function delete(string $uri, array $headers = []): RequestInterface
    {
        return self::createRequest(HttpMethod::DELETE, $uri, [], $headers);
    }

}