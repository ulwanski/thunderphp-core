<?php

/** $Id$
 * StandardRouter.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Router;

class SimpleRouter
{
    const COOKIE_LANGUAGE = 'lang';
    const COOKIE_TEMPLATE = 'theme';

    private $requestLang = null;
    private $config = null;
    private $request = null;
    private $source = null;
    private $module = null;
    private $table = array();
    private static $instance = false;

    private function __construct()
    {
        $this->request = new Request();
    }

    /**
     * @return StandardRouter
     */
    public static function getInstance()
    {
        if (self::$instance == false) self::$instance = new SimpleRouter();
        return self::$instance;
    }

    /** Zwraca obiekt żądania http
     * @return \Core\Router\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function run()
    {
        $param = '/'.$this->request->getPath(0);
        $action = $this->request->getPath(1);

        # Redirect to default if empty
        if (empty($param) || $param == '/') {
            $default = $this->config->getDefaultRoute();
            if (!empty($default) && $default != '/') {
                $this->basicRedirect($default);
                return $default;
            }
        }

        $requestKey = false;

        # Search for available pattern
        $requestPattern = '/'.$this->request->getPathString();
        foreach(array_keys($this->table) as $path){
            $pattern = "/^".str_replace('/', '\/', $path)."/";
            $result = preg_match($pattern, $requestPattern, $matches);

            if($result === 1){
                $requestKey = $path;
                break;
            }
        }

        if ($requestKey) {
            $this->module = $this->table[$requestKey]['module'];
            $config = $this->parse_config($this->table[$requestKey]);
            $source = $this->source;
            $config['action'] = $action;

            $templateCookie = $this->request->getCookies()->getCookie(self::COOKIE_TEMPLATE, 'default');

            # Set cookie with user language
            $availableLangList = $this->config->getModuleConfig($this->module)->getValue('language')->getArrayCopy();
            $langCookie = $this->request->getCookies()->getCookie(self::COOKIE_LANGUAGE);

            if($langCookie && in_array($langCookie, $availableLangList)){
                $this->requestLang = $langCookie;
            } else {
                $this->requestLang = $this->findClientLanguage($availableLangList, 'en_US');
                $this->request->getCookies()->setCookie(self::COOKIE_LANGUAGE, $this->requestLang, time()+2592000);     // Expire in 30 days
            }

            # Set language environment variable
            putenv('USER_LANG='.$this->requestLang);
            putenv('USER_THEME='.$templateCookie);
            header('Language: '.$this->requestLang, false);

            $result = $this->runRemoteSource($config);

        } else {
            header("HTTP/1.1 404 I do not have what you're looking for.");
            $msg = 'Path "' . $param . '" does not exist.';
            throw new RouterException($msg, RouterException::ERROR_MISSING_CONTROLLER);
        }
        return $param;
    }

    /**
     * Function convert url action from some-nice-work to run action: someNiceWorkAction
     *
     * @param $action string
     * @return string
     */
    private function makeAction($action)
    {
        if(!empty($action)) {
            if (strpos($action, '-') !== FALSE) {
                $actionParts = explode('-', $action);

                foreach ($actionParts as &$part) {
                    $part = ucwords($part);
                }

                $actionParts[0] = strtolower($actionParts[0]);

                return implode('', $actionParts);
            }

            return strtolower($action);
        }
    }

    private function runRemoteSource($config)
    {
        $controller = new $config['class']();
        $interfaces = class_implements($controller);
        $action = $this->makeAction($config['action']);

        if (!in_array('Core\Controller\BasicActionController', $interfaces)) {
            $msg = 'Class ' . $config['class'] . ' must implements BasicActionController interface!';
            throw new RouterException($msg, RouterException::ERROR_MISSING_INTERFACE);
        }

        $actionName = null;
        if ($this->request->isAjaxRequest()) {
            if (method_exists($controller, $action . 'Ajax')) {
                $actionName = $action . 'Ajax';
            } else if (method_exists($controller, 'defaultAjax')) {
                $actionName = 'defaultAjax';
            }
        }
        if ($actionName == null && $this->request->isXmlRequest()) {
            $xml = substr($action, 0, -4);
            if (method_exists($controller, $xml . 'Xml')) {
                $actionName = $xml . 'Xml';
            } else if (method_exists($controller, 'defaultXml')) {
                $actionName = 'defaultXml';
            }
            if ($actionName !== null) header('Content-type: application/xml; charset="utf-8"');
        }
        if ($actionName == null) {
            if (method_exists($controller, $action . 'Action')) {
                $actionName = $action . 'Action';
            } else {
                $actionName = 'defaultAction';
            }
        }

        $controller->$actionName();
    }

    private function runConsoleSource($config)
    {
        cli_set_process_title($this->request->getPathString());
        $controller = new $config['class']();
        $interfaces = class_implements($controller);
        $action = strtolower($config['action']);

        if (!in_array('Core\Controller\BasicConsoleController', $interfaces)) {
            $msg = 'Class ' . $config['class'] . ' must implements BasicConsoleController interface!';
            throw new RouterException($msg, RouterException::ERROR_MISSING_INTERFACE);
        }

        $actionName = null;
        if (method_exists($controller, $action . 'Run')) {
            $actionName = $action . 'Run';
        } else {
            $actionName = 'defaultRun';
        }

        $controller->$actionName();
    }

    /** Powoduje przekierowanie na inny adres url
     * @param String $url Adres do przekierowania, jeśli pusty - przekieruje do strony głównej serwisu
     */
    public function basicRedirect($url = null): void
    {
        if ($url === null) {
            header('location: ' . $this->getRemoteRootPath());
        } else {
            header('location: ' . $this->getRemoteRootPath() . '/' . trim($url, '/'));
        }

        die;
    }

    /**
     *
     */
    public function sslRedirect(): void
    {
        $isSsl = $this->request->isSSLRequest();

        if(!$isSsl){
            $_SERVER['REQUEST_SCHEME'] = 'https';
            $urlString = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('location: '.$urlString);
            die;
        }
    }

    private function parse_config($conf)
    {
        $action = array();
        $action['class'] = implode('\\', array('', ucfirst($conf['module']), 'Controller', $conf['controller']));
        return $action;
    }

    public function setRoutingTable(array $table)
    {
        $this->table = $table;
    }

    /**
     * Return service domain
     *
     * @return mixed
     */
    public function getDomain()
    {
        return $_SERVER['SERVER_NAME'];
    }

    /** Metoda zwraca ścieżkę <b>zdalną</b> do katalogu głównego serwera, np. katalog <i>public</i>.
     *  Jest to najwyższa lokalizacja dostępna zdalnie.
     *
     * @return String
     */
    public function getRemoteRootPath()
    {
        static $path = false;
        if (!$path) {
            if (!isset($_SERVER["HTTPS"])) $_SERVER["HTTPS"] = 'off';
            $scheme = ($_SERVER["HTTPS"] == "on") ? 'https' : 'http';
            if (isset($_SERVER['PHP_SELF'])) {
                $path = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['SCRIPT_NAME'])) {
                $path = $_SERVER['SCRIPT_NAME'];
            }
            if ($this->source == self::ROUTER_SOURCE_REMOTE) {
                $path = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($path);
                $path = trim($path, '/');
            } else if ($this->source == self::ROUTER_SOURCE_CONSOLE) {
                $path = dirname($path);
                $path = '/' . trim($path, '/');
            }
        }
        return $path;
    }

    /** Metoda zwraca ścieżkę <b>lokalną</b> do katalogu głównego serwera, np. <i>public</i>.
     *  Jest to najwyższa lokalizacja dostępna zdalnie.
     *
     * @return String
     */
    public function getLocalPublicPath()
    {
        static $path = false;
        if (!$path) {
            if (DIRECTORY_SEPARATOR == "/") {
                $path = realpath(str_replace("\\", "/", dirname($_SERVER['SCRIPT_FILENAME'])));
            } else {
                $path = realpath(str_replace("/", "\\", dirname($_SERVER['SCRIPT_FILENAME'])));
            }
        }
        return $path;
    }

    public function getLocalRootPath()
    {
        return realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    }

    public function getModuleName()
    {
        return $this->module;
    }

    private function findClientLanguage($availableLangList, $defaultLanguage) {

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {

            $headersLanguagePattern = '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i';
            $acceptLanguageList = '';
            preg_match_all($headersLanguagePattern, $_SERVER['HTTP_ACCEPT_LANGUAGE'], $acceptLanguageList);

            if(isset($acceptLanguageList[4]) && isset($acceptLanguageList[1])){
                foreach($acceptLanguageList[4] as $key => $languageWeight){
                    if($languageWeight == "") $languageWeight = 1;
                    $acceptLanguageList[4][$key] = intval(floatval($languageWeight)*100);
                }

                if(count($acceptLanguageList[1])) {
                    foreach($acceptLanguageList[1] as $key => $lang){
                        $acceptLanguageList[1][$key] = str_replace('-', '_', $lang);
                    }
                    $acceptLanguageList = array_combine($acceptLanguageList[4], $acceptLanguageList[1]);
                    krsort($acceptLanguageList);

                    # Choose a matching language
                    foreach($acceptLanguageList as $languageWeight => $languageCode){
                        foreach($availableLangList as $availableLanguage){
                            $shortCode = substr($availableLanguage, 0, 2);
                            if($languageCode == $availableLanguage || $languageCode == $shortCode){
                                return $availableLanguage;
                            }
                        }
                    }
                }
            }
        }

        if (isset($_SERVER['COUNTRY_CODE'])) {
            $languageCode = $_SERVER['COUNTRY_CODE'];
            foreach($availableLangList as $availableLanguage){
                list($shortCode1, $shortCode2) = explode('_', $availableLanguage);
                if($languageCode == $availableLanguage || $languageCode == $shortCode1 || $languageCode == $shortCode2){
                    return $availableLanguage;
                }
            }
        }

        return $defaultLanguage;
    }

}
