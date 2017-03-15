<?php

/** $Id$
 * HtmlView.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\View;

use \Api;

class MailView extends AbstractView {
    
    protected $tags = array();
    protected $preg_callback = null;

    public function prepareView() {
        $this->mergeLanguageTags();
    }
    
    public function parseView() {
        $this->parse();
        return $this->codeBuffer;
    }
    
    public function cleanView(){
    }
    
    public function __construct($template = 'default'){
        parent::__construct($template);

        $router       = \Api::getRouter();
        $root_path    = $router->getRemoteRootPath();
        $request_path = $router->getRequest()->getPathString();

        $this->preg_callback = function($matches) use($root_path, $request_path){

            $tagPart = explode(',', $matches[1]);

            $part = explode('|', $tagPart[0]);
            $name = $part[0];
            $tag  = '{$'.$name.'}';
            $mod  = isset($part[1])?$part[1]:false;
            $val  = false;

            if(isset($this->tags[$tag])){
                $val = $this->tags[$tag];

                if(count($tagPart) > 1){
                    array_shift($tagPart);
                    $val = vsprintf($val, $tagPart);
                }
            }


            if($val == false){
                switch($name){
                    
                    case '_root_path':
                        $val = $root_path;
                        break;
                    
                    case '_request_path':
                        $val = $request_path;
                        break;
                    
                }
            }
            
            if($val && $mod){                    
                switch($mod){
                    
                    case 'md5':
                        $val = md5($val);
                        break;
                    
                    case 'sha1':
                        $val = sha1($val);
                        break;
                    
                    case 'to_lower':
                    case 'lower':
                        $val = strtolower($val);
                        break;
                    
                    case 'to_upper':
                    case 'upper':
                        $val = strtoupper($val);
                        break;
                    
                    case 'link':
                        $val = '<a href="'.$val.'" rel="nofollow">'.$val.'</a>';
                        break;

                    case 'color':
                        $val = '<span style="color: '.$val.';">'.$val.'</span>';
                        break;

                    case 'ceil':
                        $val = ceil(floatval($val));
                        break;

                    case 'floor':
                        $val = floor(floatval($val));
                        break;

                    case 'round':
                        $val = round(floatval($val), 2);
                        break;
                }
            }
            
            return $val;
        };
    }

    public function getContent(){
        $this->prepareView();
        $content = $this->parseView();
        $this->cleanView();

        return $content;
    }

    public function __destruct() {
    }

    public function serialize() {
        return json_encode(array(
            'tags' => $this->tags,
            'code' => $this->codeBuffer,
        ));
    }

    public function unserialize($serialized) {

        $data = json_decode($serialized);

        $this->tags = $data['tags'];
        $this->codeBuffer = $data['code'];
    }
    
    protected function parse(){

        # Replace tags count
        $count_tpl = 0;
        $count_var = 0;
        
        do {
            # Replace {!example} tags until there is no tags to replace
            $this->codeBuffer = preg_replace_callback("/{\\!([\\w_]+)}/", function($matches) {
                $tagPart = explode(',', $matches[1]);
                $part = explode('|', $tagPart[0]);
                $name = $part[0];
                return $this->returnTemplate($name);
            }, $this->codeBuffer, -1, $count_tpl);

            # Replace {$example} or {$example|option} tags until there is no tags to replace
            $this->codeBuffer = preg_replace_callback("/{\\$([\\w,\\$\\{\\}]+|\\w+\\|\\w+)}/", $this->preg_callback, $this->codeBuffer, -1, $count_var);
        } while($count_tpl > 0 || $count_var > 0);

        # Return parsed code
        return trim($this->codeBuffer);
    }

    public function __set($name, $value = NULL) {
        if (is_array($value)) {
            foreach ($value as $key => $val) $this->__set($name.$key, $val);
        } else {
            if (isset($this->tags['{$' . $name . '}'])){
                $this->tags['{$' . $name . '}'] .= $value;
            } else {
                $this->tags['{$' . $name . '}'] = $value;
            }
        }
    }

    public function __get($name) {
        if (isset($this->tags['{$' . $name . '}'])){
            return $this->tags['{$' . $name . '}'];
        }
        return false;
    }

    public function __call($name, $arg) {
        if (connection_aborted()) return false;
        if (!isset($arg[0]))      $arg[0] = false;
        if (!isset($arg[1]))      $arg[1] = false;
        
        $this->loadTemplate($name);

        $this->loadLanguage($name);

//            if ($arg[0] & self::OPT_RETURN_TPL) {                                                                       // Jeżeli wybrano opcje zwrócenia kodu (zamiast dodawania do bufora)
//                return $content;
//            }

        return true;
    }
    
    public function purgeView(){
        $this->tags = array();
        $this->codeBuffer = '';
        return $this;
    }

    private function mergeLanguageTags(){

        foreach($this->langArray as $key => $value){
            $this->$key = $value;
        }
    }

}