<?php
declare(strict_types=1);

namespace S3bul\Builder;

class Director
{
    private RequestBuilderInterface $builder;

    public function setBuilder(RequestBuilderInterface $builder): void
    {
        $this->builder = $builder;
    }

    public function buildResponse(): void
    {
        $this->builder->produceHandle();
    }
}