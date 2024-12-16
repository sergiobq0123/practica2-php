<?php


namespace App\Tests\Controller;

use App\Entity\Results;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Class ApiResultsControllerTest
 *
 * @package App\Tests\Controller
 * @group   controllers
 *
 * @coversDefaultClass \App\Controller\ApiResultsQueryController
 */
class ApiResultsControllerTest extends BaseTestCase
{
    private const RUTA_API = '/api/results';

    /** @var array<string,string> $userHeaders */
    private static array $userHeaders;

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;

    /**
     * Test OPTIONS /results and /results/{id}
     *
     * @covers ::optionsAction
     */
    public function testOptionsResultAction204NoContent(): void
    {
        // OPTIONS /api/results
        self::$client->request(Request::METHOD_OPTIONS, self::RUTA_API);
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/results/{id}
        self::$client->request(Request::METHOD_OPTIONS, self::RUTA_API . '/1');
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @covers ::postAction
     */
    public function testPostResultAction201Created(): array
    {
        $data = [
            'result' => 'Nuevo resultado',
            'time' => (new \DateTime())->format(\DateTime::ATOM),
        ];
        self::$userHeaders = $this->getTokenHeaders(
            self::$role_user['email'],
            self::$role_user['password']
        );

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$userHeaders,
            json_encode($data)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertJson($response->getContent());
        $result = json_decode($response->getContent(), true)['result'];
        self::assertNotEmpty($result['id']);
        self::assertSame($data['result'], $result['result']);

        return $result;
    }

    /**
     * Test GET /results 200 Ok
     *
     * @covers ::cgetAction
     * @depends testPostResultAction201Created
     */
    public function testCGetResultAction200Ok(): void
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$userHeaders);
        $response = self::$client->getResponse();

        self::assertTrue($response->isSuccessful());
        self::assertJson($response->getContent());
        $results = json_decode($response->getContent(), true)['results'];
        self::assertIsArray($results);
    }

    /**
     * Test GET /results/{id} 200 Ok
     *
     * @covers ::getAction
     * @depends testPostResultAction201Created
     */
    public function testGetResultAction200Ok(array $result): void
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$userHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson($response->getContent());
        $fetchedResult = json_decode($response->getContent(), true)['result'];
        self::assertSame($result['id'], $fetchedResult['id']);
    }

    /**
     * Test PUT /results/{id} 200 Ok
     *
     * @covers ::putAction
     * @depends testPostResultAction201Created
     */
    public function testPutResultAction200Ok(array $result): void
    {
        $updatedData = [
            'result' => 'Resultado actualizado',
            'time' => (new \DateTime('+1 hour'))->format(\DateTime::ATOM),
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$userHeaders,
            json_encode($updatedData)
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJson($response->getContent());
        $updatedResult = json_decode($response->getContent(), true)['result'];
        self::assertSame($updatedData['result'], $updatedResult['result']);
    }

    /**
     * Test DELETE /results/{id} 204 No Content
     *
     * @covers ::deleteAction
     * @depends testPostResultAction201Created
     */
    public function testDeleteResultAction204NoContent(array $result): void
    {
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$userHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertEmpty($response->getContent());
    }
}
