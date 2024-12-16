<?php

namespace App\Controller;

use App\Entity\Results;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: ApiUsersQueryInterface::RUTA_API_RESULTS,
    name: 'api_results_'
)]
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(
        path: ".{_format}",
        name: 'post',
        requirements: [
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_POST],
    )]
    public function postAction(Request $request): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, '`Unauthorized`: Invalid credentials.', $format);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (!isset($data['result'], $data['time'])) {
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, 'Unprocessable Entity: Missing data.', $format);
        }

        $result = new Results(
            $this->getUser()->getId(),
            $data['result'],
            new \DateTime($data['time'])
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_CREATED, ['result' => $result], $format);
    }

    #[Route(
        path: "/{resultId}.{_format}",
        name: 'put',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_PUT],
    )]
    public function putAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, '`Unauthorized`: Invalid credentials.', $format);
        }

        $result = $this->entityManager->getRepository(Results::class)->find($resultId);

        if (!$result instanceof Results) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'Result not found.', $format);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['result'])) {
            $result->setResult($data['result']);
        }

        if (isset($data['time'])) {
            $result->setTime(new \DateTime($data['time']));
        }

        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_OK, ['result' => $result], $format);
    }


    #[Route(
        path: "/{resultId}.{_format}",
        name: 'delete',
        requirements: [
            'resultId' => "\d+",
            '_format' => "json|xml"
        ],
        defaults: [ '_format' => null ],
        methods: [Request::METHOD_DELETE],
    )]
    public function deleteAction(Request $request, int $resultId): Response
    {
        $format = Utils::getFormat($request);

        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, '`Unauthorized`: Invalid credentials.', $format);
        }

        $result = $this->entityManager->getRepository(Results::class)->find($resultId);

        if (!$result instanceof Results) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'Result not found.', $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }
}
