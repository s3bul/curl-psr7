<?php

declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use Psr\Http\Message\RequestInterface;
use S3bul\CurlPsr7\Client\CurlClient;

class CurlFactory
{
    /**
     * @param RequestInterface $request
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function create(RequestInterface $request, array $options = []): CurlClient
    {
        return new CurlClient($request, $options);
    }

    /**
     * @param string $uri
     * @param array<string, string|int|null>|null $data
     * @param array<string, string|string[]>|null $headers
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function get(string $uri, ?array $data = [], ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::get($uri, $data ?? [], $headers ?? []);
        return self::create($request, $options);
    }

    /**
     * @param string $uri
     * @param mixed|null $body
     * @param array<string, string|string[]>|null $headers
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function post(string $uri, mixed $body = null, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::post($uri, $body, $headers ?? []);
        return self::create($request, $options);
    }

    /**
     * @param string $uri
     * @param mixed|null $body
     * @param array<string, string|string[]>|null $headers
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function put(string $uri, mixed $body = null, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::put($uri, $body, $headers ?? []);
        return self::create($request, $options);
    }

    /**
     * @param string $uri
     * @param mixed|null $body
     * @param array<string, string|string[]>|null $headers
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function patch(string $uri, mixed $body = null, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::patch($uri, $body, $headers ?? []);
        return self::create($request, $options);
    }

    /**
     * @param string $uri
     * @param array<string, string|string[]>|null $headers
     * @param array<int, mixed> $options
     * @return CurlClient
     */
    public static function delete(string $uri, ?array $headers = [], array $options = []): CurlClient
    {
        $request = RequestFactory::delete($uri, $headers ?? []);
        return self::create($request, $options);
    }

}
