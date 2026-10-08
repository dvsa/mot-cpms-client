<?php

declare(strict_types=1);

namespace CpmsClientTest;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SampleController extends AbstractActionController
{
    #[\Override]
    public function indexAction(): ViewModel
    {
        /** @var Response $response */
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent('foo');
        return new ViewModel(['response' => $response]);
    }
}
