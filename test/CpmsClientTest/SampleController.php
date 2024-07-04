<?php

namespace CpmsClientTest;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

/**
 * Class SampleController
 *
 * @package CpmsClientTest
 */
class SampleController extends AbstractActionController
{
    public function sampleIndexAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent('foo');
        return $response;
    }
}
