<?php

declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory extends MessageFactory
{

    public static function create(
        mixed      $body = null,
        int        $status = 200,
        ?array      $headers = [],
        string|int $version = null,
        string     $reason = null,
    ): ResponseInterface
    {
        return new Response($status, $headers ?? [], $body, self::getHttpVersion($version), $reason);
    }

}
