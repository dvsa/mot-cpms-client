<?php

namespace CpmsClientTest;

use Laminas\Mvc\Controller\AbstractActionController;

/**
 * Class SampleController
 *
 * @package CpmsClientTest
 */
class SampleController extends AbstractActionController
{
    public function indexAction(): \Laminas\Http\Response
    {
        /** @var \Laminas\Http\Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent('foo');
        return $response;
    }
}
