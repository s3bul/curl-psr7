<?php

declare(strict_types=1);

namespace S3bul\CurlPsr7\Factory;

use S3bul\CurlPsr7\Util\HttpVersion;

abstract class MessageFactory
{
    protected static function getHttpVersion(string|int|null $version): string
    {
        switch ($version) {
            case CURL_HTTP_VERSION_1_0:
            {
                $result = HttpVersion::VERSION_1_0;
                break;
            }
            case CURL_HTTP_VERSION_1_1:
            {
                $result = HttpVersion::VERSION_1_1;
                break;
            }
            case CURL_HTTP_VERSION_2_0:
            {
                $result = HttpVersion::VERSION_2_0;
                break;
            }
            default:
            {
                $result = is_string($version) ? $version : HttpVersion::VERSION_1_1;
            }
        }

        return $result;
    }
}
