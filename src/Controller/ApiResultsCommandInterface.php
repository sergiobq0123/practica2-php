<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultsCommandInterface
{
    /**
     * Crea un nuevo resultado.
     *
     * @param Request $request
     * @return Response
     */
    public function postAction(Request $request): Response;

    /**
     * Actualiza un resultado específico.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function putAction(Request $request, int $resultId): Response;

    /**
     * Elimina un resultado específico.
     *
     * @param Request $request
     * @param int $resultId
     * @return Response
     */
    public function deleteAction(Request $request, int $resultId): Response;
}
