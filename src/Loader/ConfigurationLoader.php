<?php

/** $Id$
 * ConfigurationLoader.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Loader;

use \Core\Application\Api;
use \Core\Model\Core\ConfigModel;

class ConfigurationLoader {

    private static $Instance = false;
    private $core    = array();
    private $modules = array();

    /** Tworzy nową instancje klasy, lub zwraca już istniejącą
     *  @return \Core\Loader\ConfigurationLoader
     */
    public static function getInstance() {
        if (self::$Instance == false) self::$Instance = new ConfigurationLoader();
        return self::$Instance;
    }

    private function __construct() {}

    /**
     * @param string $corePath
     */
    public function loadCoreConfig(string $corePath): void {

        $config = Api::getCache()->entry('_framework_core_config', function() use ($corePath){

            $globalFile = $corePath.DIRECTORY_SEPARATOR.'config.php';
            $buffer = $this->loadFileConfig($globalFile);

            $localFile = $corePath.DIRECTORY_SEPARATOR.'config.local.php';
            $buffer = $this->loadFileConfig($localFile, $buffer);

            return $buffer;
        }, 43200 /* 12 hours */ );

        $this->core = new ConfigModel($config);
    }

    /**
     * @param array $modulesList
     */
    public function loadModulesConfig(array $modulesList): void {

        $config = Api::getCache()->entry('_framework_modules_config', function() use ($modulesList){

            $buffer = [];

            # Load config file for each module
            foreach ($modulesList as $moduleName => $moduleData){

                # Check if path is exists
                if(!isset($moduleData['module.php'])) continue;

                # Add configuration to the buffer
                $buffer[$moduleName] = $this->loadFileConfig($moduleData['module.php']);
            }

            # Return modules configuration
            return $buffer;

        }, 43200 /* 12 hours */ );

        $this->modules = new ConfigModel($config);
    }

    /**
     * @param string $filePath
     * @param array|null $existingConfig
     * @return array
     */
    private function loadFileConfig(string $filePath, ?array $existingConfig = null): array
    {
        $existingConfig = is_null($existingConfig)?[]:$existingConfig;

        if(!file_exists($filePath)) return $existingConfig;

        # Load data from file
        $data = include_once $filePath;

        # Simple file validation
        if(!is_array($data)) return $existingConfig;

        return array_merge($existingConfig, $data);
    }

    public function getCoreConfig($key = null) {

        if($key == null){
            return $this->core;
        } else if(isset($this->core[$key])) {
            return $this->core[$key];
        } else {
            return null;
        }
    }

    public function getModuleConfig($module) {
        if(isset($this->modules[$module])){
            return $this->modules[$module];
        } else {
            return array();
        }
    }

    public function getRoutingTable() {

        $table = array();

        foreach($this->modules as $module => $data){
            if(!isset($data['router']['routes'])) continue;

            /** @var ConfigModel $route */
            foreach($data['router']['routes'] as $path => $route){

                $table[trim($path)] = array_merge($route->getArrayCopy(), array(
                    'module'  => $module,
                ));
            }
        }

        return $table;
    }

    public function getDefaultRoute() {
        if(isset($this->core['router']['default'])){
            return $this->core['router']['default'];
        } else {
            return false;
        }
    }

}