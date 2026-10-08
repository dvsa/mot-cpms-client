<?php

declare(strict_types=1);

namespace CpmsClientTest;

use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Test bootstrap, for setting up auto loading
 * @method setUpDatabase()
 */
class Bootstrap
{
    protected static ServiceManager $serviceManager;

    /** @var  string This is the root directory where the test is run from which likely the test directory */
    protected static string $dir;

    protected static mixed $application;

    protected static ?self $instance = null;

    protected function __construct()
    {
    }

    public static function getInstance(): Bootstrap
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param string $dir
     * @param array<int, string>|string|null $testModule
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function init(string $dir, array|string|null $testModule = null): void
    {
        static::$dir = $dir;

        $this->setPaths();

        $zf2ModulePaths = array(dirname($dir, 2));
        if (($path = static::findParentPath('vendor'))) {
            $zf2ModulePaths[] = $path;
        }
        if (($path = static::findParentPath('src')) !== $zf2ModulePaths[0]) {
            $zf2ModulePaths[] = $path;
        }

        /**
         * @var array{
         *     modules: array<int, string>,
         *     module_listener_options: array<string, mixed>
         * } $config
         */
        $config = include $dir . '/../config/application.config.php';

        if (!empty($testModule)) {
            foreach ((array)$testModule as $mod) {
                if (!in_array($mod, $config['modules'])) {
                    $config['modules'][] = $mod;
                }
            }
        }

        include $dir . '/../init_autoloader.php';

        $application    = Application::init($config);
        $serviceManager = $application->getServiceManager();

        $serviceManager->setAllowOverride(true);

        /** @var array<string, mixed> $appConfig */
        $appConfig = $serviceManager->get('config');

        $appConfig['mot_logger'] = [
            'channel' => 'cpms-api-client-test',
            'writers' => [
                [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'formatter' => 'pipe',
                    'level' => 'error',
                    'enabled' => true,
                ],
            ],
        ];

        $serviceManager->setService('config', $appConfig);
        static::$serviceManager = $serviceManager;
        static::$application    = $application;
    }

    protected function setPaths(): void
    {
        $basePath = realpath(static::$dir);
        if ($basePath === false) {
            throw new \RuntimeException('Unable to resolve the test bootstrap directory.');
        }
        $basePath .= '/';

        set_include_path(
            implode(
                PATH_SEPARATOR,
                array($basePath,
                    $basePath . '/vendor',
                    $basePath . '/test',
                    get_include_path(),
                )
            )
        );

        if (file_exists(static::$dir . "/autoload_classmap.php")) {
            /** @var array<string, string> $classList */
            $classList = include static::$dir . "/autoload_classmap.php";

            spl_autoload_register(
                function ($class) use ($classList) {
                    if (isset($classList[$class])) {
                        include $classList[$class];
                    } else {
                        $filename = str_replace('\\\\', '/', $class) . '.php';
                        if (file_exists($filename)) {
                            require $filename;
                        }
                    }
                }
            );
        }
    }

    /**
     * @param string $path
     *
     * @return boolean|string false if the path cannot be found
     */
    protected function findParentPath(string $path): bool|string
    {
        $srcDir = realpath(static::$dir . '/../');

        return $srcDir . '/' . $path;
    }

    public function getServiceManager(): ServiceManager
    {
        return static::$serviceManager;
    }

    private function __clone()
    {
    }
}
