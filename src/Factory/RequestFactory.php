<?php
declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use S3bul\CurlPsr7\Util\HttpMethod;

class RequestFactory extends MessageFactory
{
    /**
     * @param string $uri
     * @param array<string, string|int|null>|null $data
     * @return UriInterface|string
     */
    private static function createUri(string $uri, ?array $data): UriInterface|string
    {
        /** @var array<string, string|null> $_data */
        $_data = $data ?? [];
        return count($_data) > 0 ? Uri::withQueryValues(new Uri($uri), $_data) : $uri;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array<string, string|int|null>|null $data
     * @param StreamInterface|resource|array<string, string>|string|null $body
     * @param array<string, string|string[]>|null $headers
     * @param string|null $version
     * @return RequestInterface
     */
    public static function create(
        string $method,
        string $uri,
        ?array $data = [],
        mixed  $body = null,
        ?array $headers = [],
        string $version = null,
    ): RequestInterface
    {
        return new Request(
            $method,
            self::createUri($uri, $data),
            $headers ?? [],
            is_array($body) ? http_build_query($body) : $body,
            self::getHttpVersion($version),
        );
    }

    /**
     * @param string $uri
     * @param array<string, string|int|null> $data
     * @param array<string, string|string[]> $headers
     * @return RequestInterface
     */
    public static function get(string $uri, array $data = [], array $headers = []): RequestInterface
    {
        return self::create(HttpMethod::GET, $uri, $data, null, $headers);
    }

    /**
     * @param string $uri
     * @param StreamInterface|resource|array<string, string>|string|null $body
     * @param array<string, string|string[]> $headers
     * @return RequestInterface
     */
    public static function post(string $uri, mixed $body = null, array $headers = []): RequestInterface
    {
        return self::create(HttpMethod::POST, $uri, [], $body, $headers);
    }

    /**
     * @param string $uri
     * @param StreamInterface|resource|array<string, string>|string|null $body
     * @param array<string, string|string[]> $headers
     * @return RequestInterface
     */
    public static function put(string $uri, mixed $body = null, array $headers = []): RequestInterface
    {
        return self::create(HttpMethod::PUT, $uri, [], $body, $headers);
    }

    /**
     * @param string $uri
     * @param StreamInterface|resource|array<string, string>|string|null $body
     * @param array<string, string|string[]> $headers
     * @return RequestInterface
     */
    public static function patch(string $uri, mixed $body = null, array $headers = []): RequestInterface
    {
        return self::create(HttpMethod::PATCH, $uri, [], $body, $headers);
    }

    /**
     * @param string $uri
     * @param array<string, string|string[]> $headers
     * @return RequestInterface
     */
    public static function delete(string $uri, array $headers = []): RequestInterface
    {
        return self::create(HttpMethod::DELETE, $uri, [], null, $headers);
    }

}
