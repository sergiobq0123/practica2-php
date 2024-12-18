<?php


namespace App\Tests\Controller;

use App\Entity\Results;
use App\Entity\User;
use DateTime;
use Generator;
use JetBrains\PhpStorm\ArrayShape;
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
    private const RUTA_API = '/api/v1/results';

    /** @var array<string,string> $adminHeaders */
    private static array $adminHeaders;


    /**
     * Test OPTIONS /results[/resultId] 204 No Content
     *
     * @covers ::__construct
     * @covers ::optionsAction
     * @return void
     */
    public function testOptionsResultsAction204NoContent(): void
    {
        // OPTIONS /api/v1/results
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API
        );
        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));

        // OPTIONS /api/v1/results/{id}
        self::$client->request(
            Request::METHOD_OPTIONS,
            self::RUTA_API . '/' . self::$faker->numberBetween(1, 100)
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode()
        );
        self::assertNotEmpty($response->headers->get('Allow'));
    }

    /**
     * Test POST /results 201 Created
     *
     * @covers ::postAction
     * @return array<string, mixed>
     */
    public function testPostResultsAction201Created(): array
    {
        $p_data = [
            Results::RESULT_ATTR => self::$faker->randomFloat(2, 0, 100),
            Results::TIME_ATTR   => (new DateTime())->format('Y-m-d H:i:s'),
        ];

        self::$adminHeaders = $this->getTokenHeaders(
            self::$role_admin['email'],
            self::$role_admin['password']
        );

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );

        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->headers->get('Location'));
        self::assertJson(strval($response->getContent()));

        $result = json_decode(strval($response->getContent()), true)[Results::RESULTS_ATTR];

        self::assertNotEmpty($result['id']);
        self::assertSame($p_data[Results::RESULT_ATTR], $result[Results::RESULT_ATTR]);
        self::assertSame($p_data[Results::TIME_ATTR], $result[Results::TIME_ATTR]);

        return $result;
    }

    /**
     * Test GET /results 200 Ok
     *
     * @depends testPostResultsAction201Created
     *
     * @return string ETag header
     */
    public function testCGetResultsAction200Ok(): string
    {
        self::$client->request(Request::METHOD_GET, self::RUTA_API, [], [], self::$adminHeaders);
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertNotNull($response->getEtag());
        $r_body = strval($response->getContent());
        self::assertJson($r_body);
        $results = json_decode($r_body, true);
        self::assertArrayHasKey(Results::RESULTS_ATTR, $results);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /results 304 NOT MODIFIED
     *
     * @param string $etag returned by testCGetResultsAction200Ok
     *
     * @depends testCGetResultsAction200Ok
     */
    public function testCGetResultsAction304NotModified(string $etag): void
    {
        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            $headers
        );
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());
    }

    /**
     * Test GET /results 200 Ok (with XML header)
     *
     * @param   array<string,string> $results results returned by testPostResultsAction201Created()
     * @return  void
     * @depends testPostResultsAction201Created
     */
    public function testCGetResultsAction200XmlOk(array $results): void
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $results['id'] . '.xml',
            [],
            [],
            array_merge(
                self::$adminHeaders,
                [ 'HTTP_ACCEPT' => 'application/xml' ]
            )
        );
        $response = self::$client->getResponse();
        self::assertTrue($response->isSuccessful(), strval($response->getContent()));
        self::assertNotNull($response->getEtag());
        self::assertTrue($response->headers->contains('content-type', 'application/xml'));
    }

    /**
     * Test GET /results/{resultId} 200 Ok
     *
     * @param   array<string,string> $results Results returned by testPostResultsAction201()
     * @return  string ETag header
     * @depends testPostResultsAction201Created
     */
    public function testGetResultsAction200Ok(array $results): string
    {
        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API . '/' . $results['id'],
            [],
            [],
            self::$adminHeaders
        );
        $response = self::$client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotNull($response->getEtag());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $Results_aux = json_decode($r_body, true)[Results::RESULTS_ATTR];
        self::assertSame($results['id'], $Results_aux['id']);

        return (string) $response->getEtag();
    }

    /**
     * Test GET /results/{resultId} 304 NOT MODIFIED
     *
     * @param array<string,string> $Results Results returned by testPostResultsAction201Created()
     * @param string $etag returned by testGetResultsAction200Ok
     * @return string Entity Tag
     *
     * @depends testPostResultsAction201Created
     * @depends testGetResultsAction200Ok
     */
    public function testGetResultsAction304NotModified(array $Results, string $etag): string
    {

        $headers = array_merge(
            self::$adminHeaders,
            [ 'HTTP_If-None-Match' => [$etag] ]
        );
        self::$client->request(Request::METHOD_GET, self::RUTA_API . '/' . $Results['id'], [], [], $headers);
        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_NOT_MODIFIED, $response->getStatusCode());

        return $etag;
    }

    /**
     * Test POST /Results 400 Bad Request
     *
     * @param   array<string,string> $results Results returned by testPostResultsAction201Created()
     * @return  array<string,string> Results data
     * @depends testPostResultsAction201Created
     */
    public function testPostResultsAction400BadRequest(array $results): array
    {

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($results))
        );
        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_BAD_REQUEST
        );

        return $results;
    }

    /**
     * Test PUT /Results/{resultId} 209 Content Returned
     *
     * @param array<string,string> $results Results returned by testPostResultsAction201()
     * @param   string $etag returned by testGetResultsAction304NotModified()
     * @return  array<string,string> modified Results data
     * @depends testPostResultsAction201Created
     * @depends testGetResultsAction304NotModified
     * @depends testCGetResultsAction304NotModified
     * @depends testPostResultsAction400BadRequest
     */
    public function testPutResultsAction209ContentReturned(array $results, string $etag): array
    {
        $p_data = [
            Results::RESULT_ATTR  => self::$faker->randomFloat(),
            Results::TIME_ATTR    => self::$faker->time(),
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $results['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                ['HTTP_If-Match' => $etag]
            ),
            strval(json_encode($p_data))
        );
        $response = self::$client->getResponse();
        self::assertSame(209, $response->getStatusCode());
        $r_body = (string) $response->getContent();
        self::assertJson($r_body);
        $result_aux = json_decode($r_body, true)[Results::RESULTS_ATTR];
        self::assertSame($results['id'], $result_aux['id']);
        self::assertSame($p_data[Results::RESULT_ATTR], $result_aux[Results::RESULT_ATTR]);

        return $result_aux;
    }

    /**
     * Test PUT /results/{resultId} 400 Bad Request
     *
     * @param   array<string,string> $result result returned by testPutResultsAction209ContentReturned()
     * @return  void
     * @depends testPutResultsAction209ContentReturned
     */
    public function testPutResultsAction400BadRequest(array $result): void
    {
        self::assertNotEmpty($result, 'The $result parameter is empty.');

        $p_data = 'invalid-json-format';

        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );

        $etag = self::$client->getResponse()->getEtag();

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            array_merge(
                self::$adminHeaders,
                ['HTTP_If-Match' => $etag]
            ),
            $p_data
        );

        $response = self::$client->getResponse();

        $this->checkResponseErrorMessage($response, Response::HTTP_BAD_REQUEST);
    }

    /**
     * Test PUT /results/{resultId} 412 PRECONDITION_FAILED
     *
     * @param   array<string, mixed> $result Result returned by testPutResultsAction209ContentReturned()
     * @return  void
     * @depends testPutResultsAction209ContentReturned
     */
    public function testPutResultsAction412PreconditionFailed(array $result): void
    {
        $resultId = $result['id'];

        self::$client->request(
            Request::METHOD_HEAD,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );

        $etag = self::$client->getResponse()->getEtag();


        $p_data = [
            Results::RESULT_ATTR => 9999,
        ];
        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            array_merge(
                self::$adminHeaders,
                ['HTTP_If-Match' => $etag . '-invalid']
            ),
            strval(json_encode($p_data))
        );

        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage(
            $response,
            Response::HTTP_PRECONDITION_FAILED
        );
    }


    /**
     * Test PUT /results/{resultId} 403 FORBIDDEN - try to modify a result not owned by the user
     *
     * @return void
     * @depends testPutResultsAction209ContentReturned
     */
    public function testPutResultsAction403Forbidden(): void
    {
        $normalUserHeaders = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );

        self::$client->request(
            Request::METHOD_GET,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();
        $results = json_decode(strval($response->getContent()), true);
        $resultId = $results[Results::RESULTS_ATTR][0][Results::RESULT_ATTR]['id'];

        $p_data = [
            Results::RESULT_ATTR => 9999,
            Results::TIME_ATTR   => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        self::$client->request(
            Request::METHOD_PUT,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            $normalUserHeaders,
            strval(json_encode($p_data))
        );

        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_FORBIDDEN);
    }

    /**
     * Test DELETE /results/{resultId} 204 No Content
     *
     * @param   array<string, mixed> $result Result returned by testPostResultsAction400BadRequest()
     * @return  int resultId
     * @depends testPostResultsAction400BadRequest
     * @depends testPutResultsAction400BadRequest
     * @depends testPutResultsAction412PreconditionFailed
     * @depends testPutResultsAction403Forbidden
     * @depends testCGetResultsAction200XmlOk
     */
    public function testDeleteResultsAction204NoContent(array $result): int
    {
        self::$client->request(
            Request::METHOD_DELETE,
            self::RUTA_API . '/' . $result['id'],
            [],
            [],
            self::$adminHeaders
        );

        $response = self::$client->getResponse();

        self::assertSame(
            Response::HTTP_NO_CONTENT,
            $response->getStatusCode(),
            'Expected 204 No Content, got: ' . $response->getStatusCode()
        );

        self::assertEmpty($response->getContent(), 'Expected empty response content.');
        return intval($result['id']);
    }

    /**
     * Test POST /results 422 Unprocessable Entity
     *
     * @param float|null $result
     * @param string|null $time
     * @return void
     * @dataProvider providerInvalidResults
     * @depends      testPostResultsAction201Created
     */
    public function testPostResultsAction422UnprocessableEntity(?float $result, ?string $time): void
    {
        $p_data = [
            Results::RESULT_ATTR => $result,
            Results::TIME_ATTR   => $time
        ];

        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            self::$adminHeaders,
            strval(json_encode($p_data))
        );

        $response = self::$client->getResponse();
        $this->checkResponseErrorMessage($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Test GET    /results 401 UNAUTHORIZED
     * Test POST   /results 401 UNAUTHORIZED
     * Test GET    /results/{resultId} 401 UNAUTHORIZED
     * Test PUT    /results/{resultId} 401 UNAUTHORIZED
     * Test DELETE /results/{resultId} 401 UNAUTHORIZED
     *
     * @param string $method HTTP method to test
     * @param string $uri API endpoint to test
     * @dataProvider providerRoutes401
     * @return void
     */
    public function testResultsStatus401Unauthorized(string $method, string $uri): void
    {
        self::$client->request(
            $method,
            $uri,
            [],
            [],
            [ 'HTTP_ACCEPT' => 'application/json' ]
        );

        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Test GET    /results/{resultId} 404 NOT FOUND
     * Test PUT    /results/{resultId} 404 NOT FOUND
     * Test DELETE /results/{resultId} 404 NOT FOUND
     *
     * @param string $method HTTP method to test
     * @param int    $resultId ID del result, retornado por testDeleteResultsAction204NoContent()
     * @return void
     * @dataProvider providerRoutes404
     * @depends      testDeleteResultsAction204NoContent
     */
    public function testResultsStatus404NotFound(string $method, int $resultId): void
    {
        self::$client->request(
            $method,
            self::RUTA_API . '/' . $resultId,
            [],
            [],
            self::$adminHeaders
        );

        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_NOT_FOUND
        );
    }

    /**
     * Test POST   /results 403 FORBIDDEN
     * Test PUT    /results/{resultId} 403 FORBIDDEN
     * Test DELETE /results/{resultId} 403 FORBIDDEN
     *
     * @param string $method HTTP method to test
     * @param string $uri Endpoint to test
     * @dataProvider providerRoutes403
     * @return void
     */
    public function testResultsStatus403Forbidden(string $method, string $uri): void
    {
        $adminHeaders = $this->getTokenHeaders(
            self::$role_admin[User::EMAIL_ATTR],
            self::$role_admin[User::PASSWD_ATTR]
        );

        $p_data = [
            Results::RESULT_ATTR => self::$faker->randomFloat(2, 0, 100),
            Results::TIME_ATTR   => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        // Crear un nuevo resultado
        self::$client->request(
            Request::METHOD_POST,
            self::RUTA_API,
            [],
            [],
            $adminHeaders,
            strval(json_encode($p_data))
        );

        $response = self::$client->getResponse();
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $createdResult = json_decode(strval($response->getContent()), true);

        $userHeaders = $this->getTokenHeaders(
            self::$role_user[User::EMAIL_ATTR],
            self::$role_user[User::PASSWD_ATTR]
        );

        $resultId = $createdResult[Results::RESULTS_ATTR]['id'];
        $restrictedUri = str_replace('/1', '/' . $resultId, $uri);

        self::$client->request(
            $method,
            $restrictedUri,
            [],
            [],
            $userHeaders
        );

        $this->checkResponseErrorMessage(
            self::$client->getResponse(),
            Response::HTTP_FORBIDDEN
        );
    }


    /**
     * Proveedor de datos para resultados inválidos
     *
     * @return array<string, array{?float, ?string}>
     */
    public static function providerInvalidResults(): array
    {
        return [
            'null result and null time'     => [null, null],
            'null result'                   => [null, (new \DateTime())->format('H:i:s')],
            'null time'                     => [50.5, null],
            'invalid time format'           => [50.5, 'invalid-time'],
        ];
    }

    /**
     * Route provider (expected status: 401 UNAUTHORIZED)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'cgetAction401' => "array",
        'getAction401' => "array",
        'postAction401' => "array",
        'putAction401' => "array",
        'deleteAction401' => "array"
    ])]
    public static function providerRoutes401(): Generator
    {
        yield 'cgetAction401'   => [ Request::METHOD_GET,    self::RUTA_API ];
        yield 'getAction401'    => [ Request::METHOD_GET,    self::RUTA_API . '/1' ];
        yield 'postAction401'   => [ Request::METHOD_POST,   self::RUTA_API ];
        yield 'putAction401'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction401' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }


    /**
     * Route provider (expected status: 403 FORBIDDEN)
     *
     * @return Generator name => [ method, url ]
     */
    #[ArrayShape([
        'postAction403' => "array",
        'putAction403' => "array",
        'deleteAction403' => "array"
    ])]
    public static function providerRoutes403(): Generator
    {
        yield 'putAction403'    => [ Request::METHOD_PUT,    self::RUTA_API . '/1' ];
        yield 'deleteAction403' => [ Request::METHOD_DELETE, self::RUTA_API . '/1' ];
    }

    /**
     * Route provider (expected status 404 NOT FOUND)
     *
     * @return Generator name => [ method ]
     */
    #[ArrayShape([
        'getAction404' => "array",
        'putAction404' => "array",
        'deleteAction404' => "array"
    ])]
    public static function providerRoutes404(): Generator
    {
        yield 'getAction404'    => [Request::METHOD_GET, 55555];
        yield 'putAction404'    => [Request::METHOD_PUT, 55555];
        yield 'deleteAction404' => [Request::METHOD_DELETE, 555555];
    }
}
