<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiResultsCommandInterface
{
    /**
     * **POST** action<br>
     * Summary: Creates a Results resource.
     *
     * @param Request $request request
     */
    public function postAction(Request $request): Response;

    /**
     * **PUT** action<br>
     * Summary: Updates the Results resource.<br>
     * _Notes_: Updates the result identified by <code>resultId</code>.
     *
     * @param Request $request request
     * @param int $resultId Result id
     */
    public function putAction(Request $request, int $resultId): Response;

    /**
     * **DELETE** Action<br>
     * Summary: Removes the User resource.<br>
     * _Notes_: Deletes the user identified by <code>userId</code>.
     *
     * @param int $resultId Result id
     */
    public function deleteAction(Request $request, int $resultId): Response;
}
