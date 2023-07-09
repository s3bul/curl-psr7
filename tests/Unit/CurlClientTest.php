<?php
declare(strict_types=1);

namespace S3bul\CurlPsr7\Tests\Unit;

use Codeception\Test\Unit;
use Codeception\Util\HttpCode;
use CurlHandle;
use GuzzleHttp\Psr7\Request;
use S3bul\CurlPsr7\Exception\CurlExecException;
use S3bul\CurlPsr7\Factory\CurlFactory;
use S3bul\CurlPsr7\Tests\Support\UnitTester;

class CurlClientTest extends Unit
{
    private string $testApiUri;

    protected UnitTester $tester;

    protected function _before(): void
    {
        $this->testApiUri = getenv('TEST_API_URI');
    }

    public function testWhenCreateClientExpectProperlyTypesAndValues(): void
    {
        $curl = CurlFactory::get($this->testApiUri, [
            'per_page' => 1,
        ], [
            'Content-Language' => 'pl-PL',
        ], [
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_RETURNTRANSFER => false
        ]);
        $curl->addOption(CURLOPT_SSL_VERIFYPEER, true)
            ->addOptions([CURLOPT_SSLVERSION => 3])
            ->addHeader('Content-Type', 'text/html')
            ->exec();

        $this->tester->assertInstanceOf(Request::class, $curl->request);
        $this->tester->assertInstanceOf(CurlHandle::class, $curl->getHandle());
        $this->tester->assertEquals($this->testApiUri, strval($curl->request->getUri()->withQuery('')));
        $this->tester->assertEquals('per_page=1', $curl->request->getUri()->getQuery());
        $this->tester->assertEquals('pl-PL', $curl->request->getHeaderLine('Content-Language'));
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
        $curl = CurlFactory::get('exception' . $this->testApiUri);
        $this->tester->expectThrowable(CurlExecException::class, function () use ($curl) {
            $curl->exec();
        });
    }

    public function testWhenGetNotExistsUsersExpectHttpCodeIsNotFound(): void
    {
        $curl = CurlFactory::get($this->testApiUri . '/0');
        $response = $curl->exec();

        $this->tester->assertEquals(HttpCode::NOT_FOUND, $response->getStatusCode());
    }

    public function testWhenGetUsersExpectJsonStructure(): void
    {
        $curl = CurlFactory::get($this->testApiUri);
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsArray($decoded);
    }

    /**
     * @return object[]
     */
    private function getListWithOneUser(): array
    {
        $curl = CurlFactory::get($this->testApiUri, [
            'page' => 1,
            'per_page' => 1,
        ]);
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->tester->assertIsArray($decoded);
        $this->tester->assertCount(1, $decoded);
        return $decoded;
    }

    private function testUserObject(mixed $user, string $email = null): void
    {
        $this->tester->assertIsObject($user);
        $this->tester->assertTrue(property_exists($user, 'id'));
        $this->tester->assertTrue(property_exists($user, 'email'));
        $this->tester->assertIsInt($user->id);
        $this->tester->assertIsString($user->email);
        if (!is_null($email)) {
            $this->tester->assertEquals($email, $user->email);
        }
    }

    /**
     * @return object
     */
    private function getOneUser(): object
    {
        $decoded = $this->getListWithOneUser();
        $firsElement = reset($decoded) ?: null;
        $this->testUserObject($firsElement);

        return $firsElement;
    }

    public function testWhenGetUsersWithFilterExpectJsonStructureAndOneElement(): void
    {
        $this->getListWithOneUser();
    }

    public function testWhenCreateUserExpectProperlyTypesAndValues(): void
    {
        $email = uniqid('curl_') . '@curl.pl';
        $curl = CurlFactory::post($this->testApiUri, [
            'email' => $email,
            'name' => 'Curl Client',
            'gender' => 'male',
            'status' => 'active',
        ])->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->testUserObject($decoded, $email);
    }

    public function testWhenUpdateUserExpectEmailProperlyValue(): void
    {
        $user = $this->getOneUser();
        $email = uniqid('curl_') . '@curl.pl';
        $curl = CurlFactory::put($this->testApiUri . "/$user->id", [
            'email' => $email,
        ])->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $json = $response->getBody()->getContents();
        $this->tester->assertJson($json);
        $decoded = json_decode($json);
        $this->testUserObject($decoded, $email);
    }

    public function testWhenDeleteUserExpectHttpCodeIsNoContent(): void
    {
        $user = $this->getOneUser();
        $curl = CurlFactory::delete($this->testApiUri . "/$user->id")
            ->setJwtToken(getenv('TEST_API_TOKEN'));
        $response = $curl->exec();

        $this->tester->assertEquals(HttpCode::NO_CONTENT, $response->getStatusCode());
    }

}
