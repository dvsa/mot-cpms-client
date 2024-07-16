<?php

namespace CpmsClientTest;

use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;

/**
 * Test bootstrap, for setting up auto loading
 * @method setUpDatabase()
 */
class Bootstrap
{
    protected static ServiceManager $serviceManager;

    /** This is the root directory where the test is run from which likely the test directory */
    protected static string $dir;

    protected static Application $application;

    protected static ?self $instance;

    protected function __construct()
    {
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function init(string $dir, array $testModule = null): void
    {
        static::$dir = $dir;

        $this->setPaths();

        $zf2ModulePaths = array(dirname(dirname($dir)));
        $path = static::findParentPath('vendor');
        if ($path) {
            $zf2ModulePaths[] = $path;
        }
        $path = static::findParentPath('src');
        if ($path && $path !== $zf2ModulePaths[0]) {
            $zf2ModulePaths[] = $path;
        }

        $zf2ModulePaths[] = './';

        /** @psalm-suppress UnresolvableInclude */
        $config = include $dir . '/../config/application.config.php';

        if ($testModule !== null && count($testModule) >= 1) {
            foreach ($testModule as $mod) {
                if (!in_array($mod, $config['modules'])) {
                    $config['modules'][] = $mod;
                }
            }
        }

        /** @psalm-suppress UnresolvableInclude */
        include $dir . '/../init_autoloader.php';

        $application    = Application::init($config);
        $serviceManager = $application->getServiceManager();

        static::$serviceManager = $serviceManager;
        static::$application    = $application;
    }

    /**
     * set paths
     */
    protected function setPaths(): void
    {
        $basePath = realpath(static::$dir) . '/';

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
            /** @psalm-suppress UnresolvableInclude */
            $classList = include static::$dir . "/autoload_classmap.php";

            spl_autoload_register(
                function ($class) use ($classList) {
                    if (isset($classList[$class])) {
                        /** @psalm-suppress UnresolvableInclude */
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
     * @return string false if the path cannot be found
     */
    protected function findParentPath($path)
    {
        $srcDir = realpath(static::$dir . '/../');

        return $srcDir . '/' . $path;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return static::$serviceManager;
    }

    /**
     * @return mixed
     */
    public static function getApplication()
    {
        return self::$application;
    }

    private function __clone()
    {
    }
}
