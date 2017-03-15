<?php

namespace Core\Application;

use Core\Loader\ConfigurationLoader as ConfLoader;

final class Framework {

    /** @var Framework */
    private static $instance = null;

    /** @var string */
    private static $basePath = null;

    /** Returns class instances, if any was created, create one otherwise.
     * @var $basePath string
     * @return Framework
     */
    final public static function init($basePath) : Framework {

        if(is_object(self::$instance)) return self::$instance;

        # Set framework base path
        self::$basePath = dirname($basePath);

        # Create new object
        self::$instance = new Framework();

        # Return object
        return self::$instance;
    }

    /**
     * Framework constructor.
     */
    private function __construct()
    {
        # Registering autoloader
        set_exception_handler(array('\Core\Loader\ExceptionHandler', 'catch_exception'));                               # Registering exception handling
        set_error_handler(array('\Core\Loader\ExceptionHandler', 'catch_error'));                                       # Registering error handling

//        Api::init();                                                                                                            # API class initialization
//
//        # Loading configuration
//        $config = ConfLoader::getInstance();                                                                                    # Creating a service configuration
//        $config->loadModulesConfig($loader->getModulesPath(), $loader->getModules());                                           # Loading module configuration
//        $config->loadCoreConfig($loader->getRootPath());                                                                        # Loading main configuration
//
//        # Preparing the router
//        $router = Api::getRouter();                                                                                             # Creating router instances
//        $router->setRoutingTable($config->getRoutingTable());                                                                   # Preparing the routing table
    }

    /**
     *
     */
    public function run() : void
    {


    }

}
