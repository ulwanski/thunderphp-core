<?php
require_once 'Loader/StandardAutoloader.php';                                                                           # Autoload class must be loaded traditionally
require_once 'api.php';                                                                                                 # Api class must also be loaded by include

const CORE_VERSION = "1.0.1";                                                                                           # Core version
const ENV_PROD = 'production_env';                                                                                      # Production environment, no errors for user
const ENV_TEST = 'testing_env';                                                                                         # Testing environment, show errors on 5xx page
const ENV_DEV  = 'development_env';                                                                                     # Development environment, show errors on 5xx page and warnings in code

use Core\Loader\StandardAutoloader  as AutoLoader;
use Core\Loader\ConfigurationLoader as ConfLoader;

# Registering autoloader
$loader = new AutoLoader(__DIR__);                                                                                      # Creating autoloader instances
spl_autoload_register(array($loader, 'spl_autoload'), AutoLoader::OPT_THROW, AutoLoader::OPT_PREPEND);                  # Registering autoloader
set_exception_handler(array('\Core\Loader\ExceptionHandler', 'catch_exception'));                                       # Registering exception handling
set_error_handler(array('\Core\Loader\ExceptionHandler', 'catch_error'));                                               # Registering error handling

Api::init();                                                                                                            # API class initialization

# Loading configuration
$config = ConfLoader::getInstance();                                                                                    # Creating a service configuration
$config->loadModulesConfig($loader->getModulesPath(), $loader->getModules());                                           # Loading module configuration
$config->loadCoreConfig($loader->getRootPath());                                                                        # Loading main configuration

# Preparing the router
$router = Api::getRouter();                                                                                             # Creating router instances
$router->setRoutingTable($config->getRoutingTable());                                                                   # Preparing the routing table
