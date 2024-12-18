<?php


namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultsQueryInterface
{
    public final const RUTA_API = '/api/v1/results';
    /**
     * Retorna todos los resultados del usuario autenticado.
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request): Response;

    /**
     * Retorna un resultado específico por ID.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function getAction(Request $request, int $resultId): Response;

    /**
     * Proporciona los métodos HTTP disponibles para /results o /results/{resultId}.
     *
     * @param int|null $resultId
     * @return Response
     */
    public function optionsAction(int|null $resultId): Response;
}
