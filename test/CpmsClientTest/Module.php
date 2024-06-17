<?php
/**
 *
 * @package      CPMS Payment
 * @subpackage   controller
 * @author       Pele Odiase <pele.odiase@valtech.co.uk>
 */

namespace CpmsClientTest;

/**
 * Class Module
 *
 * @package ApplicationTest
 */
class Module
{

    public function getConfig(): array
    {
        return include __DIR__ . '/../test.global.php';
    }

    public function getAutoloaderConfig(): array
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    'Laminas\Console' => realpath('./src/Laminas/Console'),
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
