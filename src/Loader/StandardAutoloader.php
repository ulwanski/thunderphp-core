<?php

/** $Id$
 * StandardAutoloader.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Loader {

    class StandardAutoloader {

        const OPT_THROW    = true;
        const OPT_PREPEND  = false;
        
        private $path_root    = null;
        private $path_core    = null;
        private $path_system  = null;
        private $path_modules = null;
        private $path_library = null;
        private $structure    = array();
        private $modules      = array();
        
        public function __construct( $base ) {
            $this->path_system  = realpath($base);
            $this->path_root    = realpath($base."/..");
            $this->path_core    = realpath($base."/../system");
            $this->path_modules = realpath($base."/../modules");
            $this->path_library = realpath($base."/../library");
            $this->scanModules();
        }
        
        public function __toString() {
            return (string) $this->path_root;
        }
        
        public function spl_autoload($class) {
            $path = explode('\\', $class);
            $base = strtolower($path[0]);
            
            if(count($path) < 2){
                return false;
            } else if($base === 'core'){
                return $this->loadCoreClass($path, $class);
            } else if($base === 'scripts'){
                return $this->loadScriptsClass($path, $class);
            } else if (in_array($base, $this->modules)){
                return $this->loadModuleClass($path, $class, $base);
            } else {
                return $this->loadLibraryClass($path, $class);
            }
        }
        
        public function getModules() {
            return $this->modules;
        }
        
        public function getStructure() {
            return $this->structure;
        }
        
        public function getModulesPath() {
            return $this->path_modules;
        }
        
        public function getRootPath() {
            return $this->path_root;
        }
        
        private function scanModules(){

            $iterator = new \RecursiveDirectoryIterator($this->path_modules, \FilesystemIterator::SKIP_DOTS);
            $objects  = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CATCH_GET_CHILD);

            $required = array(
                'controller',
                'service',
            );

            /* @var $item \SplFileInfo */
            foreach($objects as $item){

                # Continue if element is not directory
                if(!$item->isDir() || !$item->isReadable()) continue;

                $moduleName = $item->getBasename();

                /* @var $child \SplFileInfo */
                foreach($objects->callGetChildren() as $child){

                    # Continue if element is not directory
                    if(!$child->isDir() || !$child->isReadable()) continue;

                    $dir = $child->getBasename();
                    $this->structure[$moduleName][$dir] = null;
                }

                // Create list of valid modules
                if(count(array_intersect_key(array_flip($required), $this->structure[$moduleName])) === count($required)){
                    $this->modules[] = $moduleName;
                }
            }
        }

        private function loadCoreClass($path, $class) {
            $path[0] = $this->path_core;
            $file    = implode(DIRECTORY_SEPARATOR, $path).'.php';

            require_once $file;
            
            if(class_exists($class)){
                return true;
            } else {
                return false;
            }
        }

        private function loadScriptsClass($path, $class) {
            $path[0] = $this->path_root.DIRECTORY_SEPARATOR.'scripts';
            $path[1] = strtolower($path[1]);
            $file    = implode(DIRECTORY_SEPARATOR, $path).'.php';
            
            require_once $file;
            
            if(class_exists($class)){
                return true;
            } else {
                return false;
            }
        }
        
        private function loadModuleClass($path, $class, $module) {
            $path[0] = $this->path_modules.DIRECTORY_SEPARATOR.$module;

            if(count($path) >= 1) for($i = 1; $i < count($path) - 1; $i++){
                $path[$i] = strtolower($path[$i]);
            }

            if(!in_array($path[1], array(
                'controller',
                'model',
                'service',
                'library',
            ))) return false;

            $filePath = implode(DIRECTORY_SEPARATOR, $path).'.php';

            if(!file_exists($filePath)) return false;
            require_once $filePath;
            if(!class_exists($class)) return false;

            return true;
        }

        private function loadLibraryClass($path, $class) {
            $path[0]  = $this->path_root.DIRECTORY_SEPARATOR.'library'.DIRECTORY_SEPARATOR.$path[0];
            $filePath = implode(DIRECTORY_SEPARATOR, $path).'.php';

            if(!file_exists($filePath)) return false;
            require_once $filePath;
            if(!class_exists($class)) return false;

            return true;
        }
        
    }

}