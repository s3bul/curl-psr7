<?php
declare(strict_types=1);

namespace S3bul\CurlPsr7\Client;

use CurlHandle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use S3bul\CurlPsr7\Exception\CurlExecException;
use S3bul\CurlPsr7\Factory\ResponseFactory;
use S3bul\CurlPsr7\Util\HttpHeader;

class CurlClient
{
    private const DEFAULT_OPTIONS = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
    ];

    private CurlHandle $handle;

    /**
     * @param RequestInterface $request
     * @param array<int, mixed> $options
     */
    public function __construct(
        public RequestInterface $request,
        public array            $options = [],
    )
    {
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    /**
     * @return CurlHandle
     */
    public function getHandle(): CurlHandle
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
     * @return $this
     */
    public function removeOption(int $option): self
    {
        unset($this->options[$option]);
        return $this;
    }

    /**
     * @param int $option
     * @param mixed $value
     * @return $this
     */
    public function addOption(int $option, mixed $value): self
    {
        if (is_null($value)) {
            return $this->removeOption($option);
        }
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * @param array<int, mixed> $options
     * @return $this
     */
    public function addOptions(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->addOption($option, $value);
        }
        return $this;
    }

    /**
     * @param string $header
     * @return $this
     */
    public function removeHeader(string $header): self
    {
        $this->request = $this->request->withoutHeader($header);
        return $this;
    }

    /**
     * @param string $header
     * @param string|string[] $value
     * @return $this
     */
    public function addHeader(string $header, string|array $value): self
    {
        $this->request = $this->request->withHeader($header, $value);
        return $this;
    }

    /**
     * @param array<string, string|string[]> $headers
     * @return $this
     */
    public function addHeaders(array $headers): self
    {
        foreach ($headers as $header => $value) {
            $this->addHeader($header, $value);
        }
        return $this;
    }

    /**
     * @param int|null $httpauth
     * @return $this
     */
    public function setHttpAuth(?int $httpauth): self
    {
        $this->addOption(CURLOPT_HTTPAUTH, $httpauth);
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setBasicAuthentication(string $username, string $password): self
    {
        $this->setHttpAuth(CURLAUTH_BASIC);
        $this->addOption(CURLOPT_USERPWD, "$username:$password");
        return $this;
    }

    /**
     * @param string $authorization
     * @return $this
     */
    public function setAuthorization(string $authorization): self
    {
        return $this->addHeader(HttpHeader::AUTHORIZATION, $authorization);
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setJwtToken(string $token): self
    {
        return $this->setAuthorization('Bearer ' . $token);
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setContentType(string $type): self
    {
        return $this->addHeader(HttpHeader::CONTENT_TYPE, $type);
    }

    /**
     * @param int|null $option
     * @return mixed
     */
    private function getCurlInfo(int $option = null): mixed
    {
        return curl_getinfo($this->handle, $option);
    }

    /**
     * @return int
     */
    private function getCurlInfoHttpCode(): int
    {
        return intval($this->getCurlInfo(CURLINFO_HTTP_CODE));
    }

    /**
     * @return int
     */
    private function getCurlInfoHttpVersion(): int
    {
        return intval($this->getCurlInfo(CURLINFO_HTTP_VERSION));
    }

    /**
     * @return int
     */
    private function getCurlInfoHeaderSize(): int
    {
        return intval($this->getCurlInfo(CURLINFO_HEADER_SIZE));
    }

    /**
     * @return string[]
     */
    private function convertHeaderToCurlOpt(): array
    {
        $result = [];

        foreach ($this->request->getHeaders() as $header => $value) {
            $headerLine = $this->request->getHeaderLine($header);
            $result[] = "$header: $headerLine";
        }

        return $result;
    }

    /**
     * @return void
     */
    private function init(): void
    {
        $this->handle = curl_init();
        $options = [
            CURLOPT_URL => strval($this->request->getUri()),
            CURLOPT_CUSTOMREQUEST => $this->request->getMethod(),
            CURLOPT_HTTPHEADER => $this->convertHeaderToCurlOpt(),
        ];

        if ($this->request->getBody()->getSize() > 0) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $this->request->getBody()->getContents();
        }

        curl_setopt_array($this->handle, $options + $this->options);
    }

    /**
     * @param string $header
     * @return array<string, string>
     */
    private function convertHeaderToArray(string $header): array
    {
        $result = [];

        $headers = explode("\r\n", $header);
        foreach ($headers as $row) {
            if (preg_match('/^\S+:/', $row) === 1) {
                $strPos = strpos($row, ':');
                if ($strPos !== false) {
                    $name = substr($row, 0, $strPos);
                    $value = substr($row, $strPos + 1);
                    $result[$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param string|bool $response
     * @return string|null
     */
    private function getResponseBody(string|bool $response): ?string
    {
        $result = null;
        if (is_string($response)) {
            $result = $this->getOption(CURLOPT_HEADER) ?
                (substr($response, $this->getCurlInfoHeaderSize()) ?: null) :
                $response;
        }

        return $result;
    }

    /**
     * @param string|bool $response
     * @return array<string, string>|null
     */
    private function getResponseHeader(string|bool $response): ?array
    {
        $result = null;
        if (is_string($response)) {
            $result = $this->getOption(CURLOPT_HEADER) ?
                $this->convertHeaderToArray(
                    substr($response, 0, $this->getCurlInfoHeaderSize()) ?: ''
                ) : [];
        }

        return $result;
    }

    /**
     * @return ResponseInterface
     * @throws CurlExecException
     */
    public function exec(): ResponseInterface
    {
        $this->init();

        $result = curl_exec($this->handle);

        $errno = curl_errno($this->handle);
        if ($errno !== 0) {
            throw new CurlExecException(curl_error($this->handle), $errno);
        }

        return ResponseFactory::create(
            $this->getResponseBody($result),
            $this->getCurlInfoHttpCode(),
            $this->getResponseHeader($result),
            $this->getCurlInfoHttpVersion(),
        );
    }

}
