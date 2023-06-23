<?php

namespace CpmsClientTest;

use Laminas\Mvc\Application;

/**
 * Test bootstrap, for setting up auto loading
 * @method setUpDatabase()
 */
class Bootstrap
{

    /** @var  \Laminas\ServiceManager\ServiceManager */
    protected static $serviceManager;

    /** @var  string This is the root directory where the test is run from which likely the test directory */
    protected static $dir;

    protected static $application;

    protected static $instance;

    protected function __construct()
    {
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * @param        $dir
     * @param string $testModule
     */
    public function init($dir, $testModule = null)
    {
        static::$dir = $dir;

        $this->setPaths();

        $zf2ModulePaths = array(dirname(dirname($dir)));
        if (($path = static::findParentPath('vendor'))) {
            $zf2ModulePaths[] = $path;
        }
        if (($path = static::findParentPath('src')) !== $zf2ModulePaths[0]) {
            $zf2ModulePaths[] = $path;
        }

        $zf2ModulePaths[] = './';

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

        static::$serviceManager = $serviceManager;
        static::$application    = $application;
    }

    /**
     * set paths
     */
    protected function setPaths()
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
            $classList = include static::$dir . "/autoload_classmap.php";

            spl_autoload_register(
                function ($class) use ($classList, $basePath) {
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
    protected function findParentPath($path)
    {
        $srcDir = realpath(static::$dir . '/../');

        return $srcDir . '/' . $path;
    }

    /**
     * @return \Laminas\ServiceManager\ServiceManager
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

$path = realpath(__DIR__ . '/../');
chdir(dirname($path));
Bootstrap::getInstance()->init($path, array('CpmsClientTest'));
