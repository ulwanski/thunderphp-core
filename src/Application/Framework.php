<?php

namespace Core\Application;

use Core\Exceptions\Handler\ErrorHandler;
use Core\Loader\ConfigurationLoader;

final class Framework {

    const DIRECTORY_MODULES = 'modules';

    /** @var Framework|null */
    private static $instance = null;

    /** Returns class instances, if any was created, create one otherwise.
     * @return Framework
     */
    final public static function init() : Framework {

        # Set request start time if it not set already by PHP
        if(!isset($_SERVER['REQUEST_TIME_FLOAT'])){
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
        }

        # Set document root from script filename if ot not set already by PHP
        if(!isset($_SERVER['DOCUMENT_ROOT']) && isset($_SERVER['SCRIPT_FILENAME'])){
            $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
        }

        # Framework initiation can be performed only once
        if(is_object(self::$instance)) return self::$instance;

        # Registering error handlers
        set_error_handler([ErrorHandler::class, 'catchError']);
        set_exception_handler([ErrorHandler::class, 'catchException']);

        # Set framework base paths
        $publicPath = $_SERVER['DOCUMENT_ROOT'];
        $corePath   = realpath(__DIR__.DIRECTORY_SEPARATOR.'..');
        $rootPath   = realpath($publicPath.DIRECTORY_SEPARATOR.'..');

        # Activates the circular reference collector
        gc_enable();

        # Create new object
        self::$instance = new Framework($rootPath, $corePath, $publicPath);

        # Return object
        return self::$instance;
    }

    /**
     * Framework constructor.
     * @param string $rootPath
     * @param string $corePath
     * @param string $publicPath
     */
    private function __construct(string $rootPath, string $corePath, string $publicPath)
    {
        # Turn on output buffering
        ob_start();

        # API class initialization
        Api::init();

        # Set framework modules path
        $modulesPath = $rootPath.DIRECTORY_SEPARATOR.self::DIRECTORY_MODULES;

        /** @var \Core\Cache\Volatile\Apcu $cache */
        $cache = Api::getCache();

        # Try to fetch modules list from cache
        $modulesList = $cache->entry('_framework_valid_modules_list', function() use ($modulesPath) {

            # Scan for valid modules
            $modulesList = $this->scanModules($modulesPath);

            return $modulesList;
        }, 43200 /* 12 hours */ );

        /** @var \Core\Loader\ConfigurationLoader $config */
        $config = ConfigurationLoader::getInstance();

        # Loading module configuration
        $config->loadModulesConfig($modulesList);

        # Loading main configuration
        $config->loadCoreConfig($corePath);

        # Preparing the router
        $router = Api::getRouter();

        # Preparing the routing table
        $router->setRoutingTable($config->getRoutingTable());
    }

    /** Search for valid modules
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
        $requiredPaths = array(
            'controller',
            'service',
            'module.php'
        );

        # List of valid modules
        $modules = [];

        /* @var $item \SplFileInfo */
        foreach ($objectsIterator as $item) {


            # Continue if element is not readable directory
            if (!$item->isDir() || !$item->isReadable()) continue;

            # Main module path is module name
            $moduleName = $item->getBasename();

            # Map module directory and file structure
            $structure = [];

            /* @var $child \SplFileInfo */
            foreach ($objectsIterator->callGetChildren() as $child) {

                # Continue if element is not readable
                if (!$child->isReadable()) continue;

                $dir = $child->getBasename();
                $structure[$moduleName][$dir] = $modulesPath.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.$dir;
            }

            # Continue if module has no files inside
            if(!isset($structure[$moduleName]) || !is_array($structure[$moduleName])) continue;

            # List of required directories found
            $validPaths = array_intersect_key(array_flip($requiredPaths), $structure[$moduleName]);

            # Add module to list if is valid
            if (count($validPaths) === count($requiredPaths)) {
                $modules[$moduleName] = $structure[$moduleName];
            }
        }

        return $modules;
    }

    /**
     *
     */
    public function run() : void
    {
        /** @var \Core\Router\StandardRouter $router */
        $router = Api::getRouter();

        //$router->run();
    }

}
