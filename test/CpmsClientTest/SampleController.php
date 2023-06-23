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
    public function indexAction()
    {
        /** @var \Laminas\Http\Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent('foo');
        return $response;
    }
}
