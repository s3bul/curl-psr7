<?php

declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use Psr\Http\Message\RequestInterface;
use S3bul\CurlPsr7\Client\CurlClient;

class CurlFactory
{
    public static function create(RequestInterface $request, array $options = []): CurlClient
    {
        return new CurlClient($request, $options);
    }

    public static function get(string $uri, ?array $data = [], ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::get($uri, $data ?? [], $headers ?? []);
        return self::create($request, $options);
    }

    public static function post(string $uri, mixed $body = null, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::post($uri, $body, $headers ?? []);
        return self::create($request, $options);
    }

    public static function delete(string $uri, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::delete($uri, $headers ?? []);
        return self::create($request, $options);
    }

}
