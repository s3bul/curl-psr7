<?php
declare(strict_types=1);

namespace S3bul\Client;

use CurlHandle;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use S3bul\Exception\CurlExecException;

class CurlClient
{
    private const DEFAULT_OPTIONS = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
    ];

    private ?CurlHandle $handle = null;

    public function __construct(
        public RequestInterface $request,
        public array            $options = [],
    )
    {
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    /**
     * @return CurlHandle|null
     */
    public function getHandle(): ?CurlHandle
    {
        return $this->handle;
    }

    /**
     * @param int $option
     * @return mixed
     */
    public function getOption(int $option): mixed
    {
        return $this->options[$option] ?? null;
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function addOption(int $option, mixed $value): self
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function addOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->addOption($option, $value);
        }
        return $this;
    }

    private function getCurlInfo(int $option = null): mixed
    {
        return curl_getinfo($this->handle, $option);
    }

    private function getCurlInfoHttpCode(): int
    {
        return $this->getCurlInfo(CURLINFO_HTTP_CODE);
    }

    private function setCurlOption(int $option, mixed $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    private function convertHeaderToCurlOpt(): array
    {
        $result = [];

        foreach ($this->request->getHeaders() as $header => $value) {
            $headerLine = $this->request->getHeaderLine($header);
            $result[] = "$header: $headerLine";
        }

        return $result;
    }

    private function curlInit(): void
    {
        $this->handle = curl_init();
        $_options = [
            CURLOPT_URL => strval($this->request->getUri()),
            CURLOPT_CUSTOMREQUEST => $this->request->getMethod(),
            CURLOPT_HTTPHEADER => $this->convertHeaderToCurlOpt(),
        ];

        if ($this->request->getBody()->getSize() > 0) {
            $_options[CURLOPT_POST] = true;
            $_options[CURLOPT_POSTFIELDS] = $this->request->getBody()->getContents();
        }

        curl_setopt_array($this->handle, $_options + $this->options);
    }

    private function convertHeaderToArray(string $header): array
    {
        $result = [];

        $headers = explode("\r\n", $header);
        foreach ($headers as $row) {
            if (preg_match('/^\S+:/', $row) === 1) {
                $name = substr($row, 0, strpos($row, ':'));
                $value = substr($row, strpos($row, ':') + 1);
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function curlExec(): ResponseInterface
    {
        $this->curlInit();

        $result = $header = $body = curl_exec($this->handle);

        $errno = curl_errno($this->handle);
        if ($errno !== 0) {
            throw new CurlExecException(curl_error($this->handle), $errno);
        }
        if ($this->getOption(CURLOPT_HEADER)) {
            $headerSize = $this->getCurlInfo(CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $headerSize);
            $body = substr($result, $headerSize);
        }

        return new Response(
            $this->getCurlInfoHttpCode(),
            $this->getOption(CURLOPT_HEADER) ? $this->convertHeaderToArray($header) : [],
            $body,
        );
    }

}