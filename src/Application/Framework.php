<?php

namespace Core\Application;

use Core\Loader\ConfigurationLoader as ConfLoader;

final class Framework {

    /** @var Framework */
    private static $instance = null;

    /** Returns class instances, if any was created, create one otherwise.
     * @return Framework
     */
    final public static function init() : Framework {

        if(self::$instance == false){
            self::$instance = new Framework();
        }

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
