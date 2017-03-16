<?php

namespace Core\Application;

use Core\Loader\ConfigurationLoader;
use Core\Application\Api;

final class Framework {

    const DIRECTORY_MODULES = 'modules';

    /** @var Framework|null */
    private static $instance = null;

    /** Returns class instances, if any was created, create one otherwise.
     * @var $basePath string
     * @return Framework
     */
    final public static function init($basePath) : Framework {

        # Framework initiation can be performed only once
        if(is_object(self::$instance)) return self::$instance;

        # Registering error handlers
        set_error_handler(array('\Core\Exception\Handler\ErrorHandler', 'catchError'));
        set_exception_handler(array('\Core\Exception\Handler\ErrorHandler', 'catchException'));

        # Set framework base path
        $publicPath  = dirname($basePath);
        $corePath    = realpath(dirname(__DIR__));
        $rootPath    = realpath($publicPath.DIRECTORY_SEPARATOR.'..');

        # Create new object
        self::$instance = new Framework($rootPath, $corePath, $publicPath);

        # Return object
        return self::$instance;
    }

    /**
     * Framework constructor.
     * @param $rootPath string
     * @param $corePath string
     * @param $publicPath string
     */
    private function __construct($rootPath, $corePath, $publicPath)
    {
        Api::init();                                                                                                            # API class initialization

        $modulesPath = $rootPath.DIRECTORY_SEPARATOR.self::DIRECTORY_MODULES;

        $modulesList = $this->scanModules($modulesPath);

        # Loading configuration
        $config = ConfigurationLoader::getInstance();                                                                   # Creating a service configuration
        //$config->loadModulesConfig($loader->getModulesPath(), $loader->getModules());                                   # Loading module configuration
        //$config->loadCoreConfig($loader->getRootPath());                                                                # Loading main configuration
//
//        # Preparing the router
//        $router = Api::getRouter();                                                                                             # Creating router instances
//        $router->setRoutingTable($config->getRoutingTable());                                                                   # Preparing the routing table
    }

    /**
     * @param string $modulesPath
     * @return array
     */
    private function scanModules(string $modulesPath) : array
    {

        /** @var \RecursiveDirectoryIterator $directoryIterator */
        $directoryIterator = new \RecursiveDirectoryIterator($modulesPath, \FilesystemIterator::SKIP_DOTS);

        /** @var \RecursiveIteratorIterator $objectsIterator */
        $objectsIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CATCH_GET_CHILD);

        # Paths required to consider the module valid
        $requiredDirectories = array(
            'controller',
            'service',
            'module.php'
        );

        $structure = [];

        $modules = [];

        /* @var $item \SplFileInfo */
        foreach ($objectsIterator as $item) {

            # Continue if element is not directory
            if (!$item->isDir() || !$item->isReadable()) continue;

            $moduleName = $item->getBasename();

            /* @var $child \SplFileInfo */
            foreach ($objectsIterator->callGetChildren() as $child) {

                # Continue if element is not readable
                if (!$child->isReadable()) continue;

                $dir = $child->getBasename();
                $structure[$moduleName][$dir] = null;
            }

            # List of required directories found
            $validDirectories = array_intersect_key(array_flip($requiredDirectories), $structure[$moduleName]);

            # Create list of valid modules
            if (count($validDirectories) === count($requiredDirectories)) {
                $modules[] = $moduleName;
            }
        }
    }

    /**
     *
     */
    public function run() : void
    {


    }

}
