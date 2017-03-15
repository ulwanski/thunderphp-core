<?php

/** $Id$
 * HtmlView.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\View {

    use \Api;
    use \Core\Events\EventManager;
    use \Core\Session\SessionGateway;
    use \Core\Session\AbstractSessionHandler;

    class HtmlView extends AbstractView {
        
        const OPT_RETURN_TPL             = 0x01;
        const OPT_DISABLE_COMPRESSION    = 0x02;
        const OPT_STRONG_COMPRESSION     = 0x04;
        //const OPT_REPLACE_NEWLINE_TO_BR  = 0x08;
        const OPT_NO_SESSION_MSG_PARSE   = 0x10;

        protected $tags = array();
        protected $preg_callback = null;

        public function getSessionMessages(){

            /** @var \Core\Session\SessionGateway $session */
            $session = SessionGateway::fetchInstance();

            if($session == false) return false;

            foreach($session->sessionMessages()->getAll() as $level => $list){
                foreach($list as $msg){
                    $html = '<div class="message '.$level.'"><p>'.$msg['message'].'</p><a class="close">x</a></div>';
                    $this->__set('_session_messages', $html);
                }
            }

            $session->sessionMessages()->removeAll();
        }

        public function prepareView() {
            $this->mergeLanguageTags();
        }
        
        public function parseView() {
            if(connection_aborted() || empty($this->codeBuffer)) return;
            $this->parse();
            return $this->codeBuffer;
        }
        
        public function cleanView(){
            
        }
        
        public function __construct($template = 'default'){
            parent::__construct($template);

            # Subscribe session close event
            EventManager::subscribe(AbstractSessionHandler::EVENT_SESSION_WRITE, array($this, 'getSessionMessages'));
            
            header('Content-Type: text/html; charset=utf-8');
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

                        case '_user_session_id':
                            $val = session_id();
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
                $this->codeBuffer = preg_replace_callback('/{\$([\w]+|[\w]+\,[\{\$\w+\d\}]+|\w+\|\w+)}/', $this->preg_callback, $this->codeBuffer, -1, $count_var);
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

            $this->loadTemplate($name);
            $this->loadLanguage($name);

//            if (isset($arg[0]) && ($arg[0] & self::OPT_RETURN_TPL)){
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

        public function generatePagination($currentPage, $totalPages, $pageRange = 4, $showFirstLink = true, $showLastLink = true){

            $html = '';

            if($showFirstLink && $currentPage > 1){
                $previousPage = $currentPage - 1;
                $html .= '<a href="{$_request_path}?page=1" class="first"></a>';
                $html .= '<a href="{$_request_path}?page='.$previousPage.'" class="previous"></a>';
            }

            $min = max($currentPage - ($pageRange + max($pageRange - ($totalPages - $currentPage), 0)), 1);
            $max = min($currentPage + ($pageRange + max($pageRange - $currentPage, 0)), $totalPages);

            for($page = $min; $page <= $max; $page++){
                if(($page == $currentPage)){
                    $html .= '<span class="current">'.$page.'</span>';
                } else {
                    $html .= '<a href="{$_request_path}?page='.$page.'">'.$page.'</a>';
                }
            }

            if($showLastLink && $currentPage < $totalPages){
                $nextPage = $currentPage + 1;
                $html .= '<a href="{$_request_path}?page='.$nextPage.'" class="next"></a>';
                $html .= '<a href="{$_request_path}?page='.$totalPages.'" class="last"></a>';
            }

            return $html;
        }

    }
    
}