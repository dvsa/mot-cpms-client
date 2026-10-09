<?php

declare(strict_types=1);

namespace CpmsClient\Controller\Plugin;

use CpmsClient\Service\ApiService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @method AbstractActionController getController()
 */
class GetRestClient extends AbstractPlugin
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * Work around to get the API domain based on naming convention
     *
     * @return ApiService
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(): ApiService
    {
        /** @var ApiService $service */
        $service = $this->container->get('cpms\service\api');
        return $service;
    }
}
