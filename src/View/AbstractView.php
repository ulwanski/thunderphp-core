<?php

/** $Id$
 * BasicView.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\View {
    
    use \Api;
    use \SplObserver;
    use \Serializable;
    use \Core\View\Interfaces\SimpleView;

    abstract class AbstractView implements SimpleView, Serializable {

        const TEMPLATE_DEFAULT = 'default';

        protected $templateName = self::TEMPLATE_DEFAULT;
        protected $defaultPath  = null;
        protected $templatePath = null;
        protected $languagePath = null;
        protected $langArray = array();
        protected $codeBuffer = '';
        protected $cache = null;

        public function __construct($template = 'default'){

            $language = getenv("USER_LANG");
            $template = getenv("USER_THEME");
            if($language == false) $language = 'en_US';

            $this->templateName = $template;
            $this->cache = Api::getCache();

            $router = Api::getRouter();
            $base = $router->getLocalRootPath().'/modules/'.$router->getModuleName();
            $this->defaultPath  = realpath($base.'/layout/'.self::TEMPLATE_DEFAULT);
            $this->templatePath = realpath($base.'/layout/'.$template);
            $this->languagePath = realpath($base.'/language/'.$language);
        }
        
        public function __destruct() {
            $this->prepareView();
            $content = $this->parseView();
            $this->cleanView();

            # Calculate run time (debug)
            $runTime = round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000); # ms
            header('Run-Time:'.$runTime."ms" );

            # Send content to browser
            echo $content;

            # Clear
            while (ob_get_level() > 0) ob_end_flush();
        }

        /** Translate string name for string in current language
         * @param $name
         * @return string
         */
        public function __($name)
        {

            $name = explode(',', $name);
            if(count($name) > 1){
                $tag = array_shift($name);
                if(!isset($this->langArray[$tag])){
                    $value = $tag;
                } else {
                    $value = vsprintf($this->langArray[$tag], $name);
                }
            } else {
                if(isset($this->langArray[$name[0]])){
                    $value = $this->langArray[$name[0]];
                } else {
                    $value = $name[0];
                }
            }

            return (string)$value;
        }

        public function loadLanguage($languageName) {

            # @TODO: Add cache to language files

            $langFile = realpath($this->languagePath.'/'.$languageName.'.php');
            if($langFile){
                $data = include_once $langFile;
                if(is_array($data)){
                    $this->langArray = array_merge($this->langArray, $data);
                }
            }
        }

        public function loadTemplate($fileName) {

            $cacheKey = 'template_'.$this->templateName.'_'.$fileName;

            //$content = $this->cache->get($cacheKey, false);
            $content = false;

            if($content != false){
                $this->codeBuffer .= $content;
                return true;
            }

            $file = realpath($this->templatePath.'/'.$fileName.'.tpl');
            if($file){
                $content = file_get_contents($file);
            } else {
                $file = realpath($this->defaultPath.'/'.$fileName.'.tpl');
                if($file){
                    $content = file_get_contents($file);
                }
            }

            if($content != false){
                $content = $this->codeCompress($content);
                $this->codeBuffer .= $content;
                $this->cache->add($cacheKey, $content, 600);
            }
        }

        protected function returnTemplate($fileName) {

            $cacheKey = 'template_'.$this->templateName.'_'.$fileName;

            $content = $this->cache->get($cacheKey, false);
            if($content != false){
                return $content;
            }

            $file = realpath($this->templatePath.'/'.$fileName.'.tpl');
            if($file){
                $content = file_get_contents($file);
                $content = $this->codeCompress($content);
                $this->cache->add($cacheKey, $content, 600);
            } else {
                $file = realpath($this->defaultPath.'/'.$fileName.'.tpl');
                if($file){
                    $content = file_get_contents($file);
                    $content = $this->codeCompress($content);
                    $this->cache->add($cacheKey, $content, 600);
                }
            }
            return $content;
        }

        private function codeCompress($content) {
            $order = array("\n", "\r", "\t", "  ");
            $content = str_replace($order, '', $content);
            return trim($content);
        }
        
    }
    
}