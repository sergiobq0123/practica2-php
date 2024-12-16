<?php


namespace App\Controller;

use App\Entity\Results;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/api/results',
    name: 'api_results_'
)]
class ApiResultsQueryController extends AbstractController implements ApiResultsQueryInterface
{
    private const HEADER_CACHE_CONTROL = 'Cache-Control';
    private const HEADER_ETAG = 'ETag';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route(
        path: ".{_format}/{sort?id}",
        name: 'cget',
        requirements: [
            'sort' => "id|email|roles",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json', 'sort' => 'id' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function cgetAction(Request $request): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, '`Unauthorized`: Invalid credentials.', $format);
        }

        $user = $this->getUser();
        $results = $this->entityManager->getRepository(Results::class)->findBy(['userId' => $user->getId()], ['time' => 'DESC']);

        if (empty($results)) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'No results found.', $format);
        }

        $etag = md5(json_encode($results, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['results' => $results],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    #[Route(
        path: "/{resultId}.{_format}",
        name: 'get',
        requirements: [
            "resultId" => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => 'json' ],
        methods: [ Request::METHOD_GET ],
    )]
    public function getAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, '`Unauthorized`: Invalid credentials.', $format);
        }

        $result = $this->entityManager->getRepository(Results::class)->find($resultId);

        if (!$result instanceof Results) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'Result not found.', $format);
        }

        if ($this->getUser()->getId() !== $result->getUserId() && !$this->isGranted('ROLE_ADMIN')) {
            return Utils::errorMessage(Response::HTTP_FORBIDDEN, '`Forbidden`: Access denied.', $format);
        }

        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (($etags = $request->getETags()) && (in_array($etag, $etags) || in_array('*', $etags))) {
            return new Response(null, Response::HTTP_NOT_MODIFIED);
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            ['result' => $result],
            $format,
            [
                self::HEADER_CACHE_CONTROL => 'private',
                self::HEADER_ETAG => $etag,
            ]
        );
    }

    #[Route(
        path: "/{userId}.{_format}",
        name: 'options',
        requirements: [
            'userId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ 'userId' => 0, '_format' => 'json' ],
        methods: [ Request::METHOD_OPTIONS ],
    )]
    public function optionsAction(int|null $resultId): Response
    {
        $methods = $resultId !== 0
            ? [Request::METHOD_GET, Request::METHOD_PUT, Request::METHOD_DELETE]
            : [Request::METHOD_GET, Request::METHOD_POST];
        $methods[] = Request::METHOD_OPTIONS;

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            [
                'Allow' => implode(',', $methods),
                'Cache-Control' => 'public, immutable',
            ]
        );
    }
}
