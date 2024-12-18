<?php

namespace App\Controller;

use App\Entity\Results;
use App\Entity\User;
use App\Utility\Utils;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;

use function in_array;

#[Route(
    path: ApiResultsQueryInterface::RUTA_API,
    name: 'api_results_'
)]
class ApiResultsCommandController extends AbstractController implements ApiResultsCommandInterface
{
    private const ROLE_ADMIN = 'ROLE_ADMIN';
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }


    /**
     * * @throws JsonException
     * *@throws \DateMalformedStringException
     * @see ApiResultsQueryInterface::postAction()
     *
     */
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

        $body = $request->getContent();
        $data = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

        if (
            !isset($data[Results::RESULT_ATTR], $data[Results::TIME_ATTR]) ||
            !is_numeric($data[Results::RESULT_ATTR]) ||
            !$this->isValidDate($data[Results::TIME_ATTR])
        ) {
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid input data.', $format);
        }

        try {
            $time = new \DateTime($data[Results::TIME_ATTR]);
        } catch (\Exception $e) {
            return Utils::errorMessage(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid time format.', $format);
        }

        $result_exists = $this->entityManager
            ->getRepository(Results::class)
            ->findOneBy([
                Results::RESULT_ATTR => $data[Results::RESULT_ATTR],
                Results::TIME_ATTR => new \DateTime($data[Results::TIME_ATTR])
            ]);

        if ($result_exists instanceof Results) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null, $format);
        }



        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, null, $format);
        }
        $currentUser = $this->entityManager->getRepository(User::class)->find($currentUser->getId());

        $result = new Results(
            $currentUser,
            (float) $data[Results::RESULT_ATTR],
            $time
        );

        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return Utils::apiResponse(
            Response::HTTP_CREATED,
            [ Results::RESULTS_ATTR => $result ],
            $format,
            [
                'Location' => $request->getScheme() . '://' . $request->getHttpHost() .
                    ApiResultsQueryInterface::RUTA_API . '/' . $result->getId(),
            ]
        );
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
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, null, $format);
        }

        $result = $this->entityManager->getRepository(Results::class)->find($resultId);

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, null, $format);
        }
        if (!$this->isGranted(self::ROLE_ADMIN) && $result->getUser()->getId() !== $currentUser->getId()) {
            return Utils::errorMessage(Response::HTTP_FORBIDDEN, null, $format);
        }

        if (!$result instanceof Results) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, null, $format);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return Utils::errorMessage(Response::HTTP_BAD_REQUEST, null . $e->getMessage(), $format);
        }

        $etag = md5(json_encode($result, JSON_THROW_ON_ERROR));
        if (!$request->headers->has('If-Match') || $etag != $request->headers->get('If-Match')) {
            return Utils::errorMessage(
                Response::HTTP_PRECONDITION_FAILED,
                'PRECONDITION FAILED: one or more conditions given evaluated to false',
                $format
            ); // 412
        }


        if (isset($data[Results::RESULT_ATTR])) {
            $result->setResult($data[Results::RESULT_ATTR]);
        }

        if (isset($data[Results::TIME_ATTR])) {
            $result->setTime(new \DateTime($data[Results::TIME_ATTR]));
        }

        $this->entityManager->flush();

        return Utils::apiResponse(209, [Results::RESULTS_ATTR => $result], $format);
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

        $result = $this->entityManager
            ->getRepository(Results::class)
            ->find($resultId);

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            return Utils::errorMessage(Response::HTTP_UNAUTHORIZED, 'Invalid user instance.', $format);
        }
        if (!$this->isGranted(self::ROLE_ADMIN) && $result->getUser()->getId() !== $currentUser->getId()) {
            return Utils::errorMessage(Response::HTTP_FORBIDDEN, 'Access denied: You cannot modify this result.', $format);
        }

        if (!$result instanceof Results) {
            return Utils::errorMessage(Response::HTTP_NOT_FOUND, 'Result not found.', $format);
        }

        $this->entityManager->remove($result);
        $this->entityManager->flush();

        return Utils::apiResponse(Response::HTTP_NO_CONTENT);
    }

    private function isValidDate(string $date): bool
    {
        try {
            new \DateTime($date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
