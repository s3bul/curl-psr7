<?php
declare(strict_types=1);

namespace S3bul\Builder;


use Psr\Http\Message\ResponseInterface;

interface RequestBuilderInterface
{
    public function produceHandle(): void;

    public function getResponse(): ResponseInterface;
}