<?php
declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;
use CurlHandle;
use GuzzleHttp\Psr7\Request;
use S3bul\CurlPsr7\Exception\CurlExecException;
use S3bul\CurlPsr7\Factory\CurlFactory;
use Tests\Support\UnitTester;

class CurlClientTest extends Unit
{
    const SERVICE_URI = 'https://gorest.co.in/public/v2/users';

    protected UnitTester $tester;

    protected function _before(): void
    {
    }

    public function testWhenCreateClientExpectProperlyTypesAndValues(): void
    {
        $curl = CurlFactory::get(self::SERVICE_URI, null, null, [CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_RETURNTRANSFER => false]);
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
            ->addOptions([CURLOPT_SSLVERSION => 3])
            ->addHeader('Content-Type', 'text/html')
            ->exec();

        $this->tester->assertInstanceOf(Request::class, $curl->request);
        $this->tester->assertInstanceOf(CurlHandle::class, $curl->getHandle());
        $this->tester->assertEquals(self::SERVICE_URI, strval($curl->request->getUri()));
        $this->tester->assertEquals('text/html', $curl->request->getHeaderLine('Content-Type'));
        $this->tester->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_SSLVERSION, $curl->options);
        $this->tester->assertEquals(2, $curl->getOption(CURLOPT_SSL_VERIFYHOST));
        $this->tester->assertEquals(false, $curl->getOption(CURLOPT_RETURNTRANSFER));
        $this->tester->assertEquals(true, $curl->getOption(CURLOPT_SSL_VERIFYPEER));
        $this->tester->assertEquals(3, $curl->getOption(CURLOPT_SSLVERSION));
    }

    public function testWhenSendRequestToWrongHostExpectThrowCurlException(): void
    {
        $curl = CurlFactory::get('https://go99rest.co.in/public/v2/users');
        $this->tester->expectThrowable(CurlExecException::class, function () use ($curl) {
            $curl->exec();
        });
    }

    public function testWhenGetNotExistsUsersExpectHttpCodeIsNotFound(): void
    {
        $curl = CurlFactory::get(self::SERVICE_URI . '/0');
        $response = $curl->exec();

        $this->tester->assertEquals(HttpCode::NOT_FOUND, $response->getStatusCode());
    }

    public function testWhenGetUsersExpectJsonStructure(): void
    {
        $curl = CurlFactory::get(self::SERVICE_URI);
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsArray($decoded);
    }

    public function testWhenGetUsersWithFilterExpectJsonStructureAndOneElement(): void
    {
        $curl = CurlFactory::get(self::SERVICE_URI, [
            'page' => 1,
            'per_page' => 1,
        ]);
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsArray($decoded);
        $this->tester->assertCount(1, $decoded);
    }

    private function whenCreateUserExpectEmailAsTheSame(): int
    {
        $email = uniqid('curl_') . '@curl.pl';
        $curl = CurlFactory::post(self::SERVICE_URI, [
            'email' => $email,
            'name' => 'Curl Client',
            'gender' => 'male',
            'status' => 'active',
        ])->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsObject($decoded);
        $this->tester->assertObjectHasAttribute('id', $decoded);
        $this->tester->assertObjectHasAttribute('email', $decoded);
        $userId = $decoded->id;
        $this->tester->assertIsInt($userId);
        $this->tester->assertEquals($email, $decoded->email);
        return $userId;
    }

    private function whenUpdateUserExpectEmailProperlyValue(int $userId): void
    {
        $email = uniqid('curl_') . '@curl.pl';
        $curl = CurlFactory::put(self::SERVICE_URI . "/$userId", [
            'email' => $email,
        ])->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsObject($decoded);
        $this->tester->assertObjectHasAttribute('id', $decoded);
        $this->tester->assertObjectHasAttribute('email', $decoded);
        $this->tester->assertEquals($email, $decoded->email);
    }

    private function whenDeleteUserExpectHttpCodeIsNoContent(int $userId): void
    {
        $curl = CurlFactory::delete(self::SERVICE_URI . "/$userId")
            ->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $this->tester->assertEquals(HttpCode::NO_CONTENT, $response->getStatusCode());
    }

    public function testWhenCreateAndDeleteUserExpectEmailAsTheSameAfterCreateAndHttpCodeIsNoContentAfterDelete(): void
    {
        $userId = $this->whenCreateUserExpectEmailAsTheSame();
        $this->whenUpdateUserExpectEmailProperlyValue($userId);
        $this->whenDeleteUserExpectHttpCodeIsNoContent($userId);
    }

}
