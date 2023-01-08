<?php
declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;
use GuzzleHttp\Psr7\Request;
use S3bul\CurlPsr7\Client\CurlClient;
use S3bul\CurlPsr7\Factory\RequestFactory;
use Tests\Support\UnitTester;

class CurlClientTest extends Unit
{
    const SERVICE_URI = 'https://gorest.co.in/public/v2/users';

    protected UnitTester $tester;

    protected function _before()
    {
    }

    public function testWhenCreateClientExpectProperlyTypesAndValues(): void
    {
        $request = RequestFactory::get(self::SERVICE_URI);
        $curl = new CurlClient($request, [CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_RETURNTRANSFER => false]);
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
            ->addOptions([CURLOPT_HEADER => ['Content-Type: text']]);
        $this->tester->assertInstanceOf(Request::class, $curl->request);
        $this->tester->assertEquals(self::SERVICE_URI, strval($curl->request->getUri()));
        $this->tester->assertArrayHasKey(CURLOPT_SSL_VERIFYHOST, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_RETURNTRANSFER, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_SSL_VERIFYPEER, $curl->options);
        $this->tester->assertArrayHasKey(CURLOPT_HEADER, $curl->options);
        $this->tester->assertEquals(2, $curl->getOption(CURLOPT_SSL_VERIFYHOST));
        $this->tester->assertEquals(false, $curl->getOption(CURLOPT_RETURNTRANSFER));
        $this->tester->assertEquals(true, $curl->getOption(CURLOPT_SSL_VERIFYPEER));
        $this->tester->assertEquals(['Content-Type: text'], $curl->getOption(CURLOPT_HEADER));
    }

    public function testWhenGetNotExistsUsersExpectHttpCodeIsNotFound(): void
    {
        $request = RequestFactory::get(self::SERVICE_URI . '/0');
        $curl = new CurlClient($request);
        $response = $curl->exec();
        $this->tester->assertEquals(HttpCode::NOT_FOUND, $response->getStatusCode());
    }

    public function testWhenGetUsersExpectJsonStructure(): void
    {
        $request = RequestFactory::get(self::SERVICE_URI);
        $curl = new CurlClient($request);
        $response = $curl->exec();
        $json = $response->getBody()->getContents();

        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsArray($decoded);
    }

    public function testWhenGetUsersWithFilterExpectJsonStructureAndOneElement(): void
    {
        $request = RequestFactory::get(self::SERVICE_URI, [
            'page' => 1,
            'per_page' => 1,
        ]);
        $curl = new CurlClient($request);
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
        $request = RequestFactory::post(self::SERVICE_URI, [
            'email' => $email,
            'name' => 'Curl Client',
            'gender' => 'male',
            'status' => 'active',
        ], [
            'Authorization' => 'Bearer ' . getenv('TEST_API_TOKEN'),
//            'Content-Type' => 'application/json',
        ]);
        $curl = new CurlClient($request);
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

    private function whenDeleteUserExpectHttpCodeIsNoContent(int $userId): void
    {
        $request = RequestFactory::delete(self::SERVICE_URI . "/$userId", [
            'Authorization' => 'Bearer ' . getenv('TEST_API_TOKEN'),
        ]);
        $curl = new CurlClient($request);
        $response = $curl->exec();

        $this->tester->assertEquals(HttpCode::NO_CONTENT, $response->getStatusCode());
    }

    public function testWhenCreateAndDeleteUserExpectEmailAsTheSameAfterCreateAndHttpCodeIsNoContentAfterDelete(): void
    {
        $userId = $this->whenCreateUserExpectEmailAsTheSame();
        $this->whenDeleteUserExpectHttpCodeIsNoContent($userId);
    }

}
