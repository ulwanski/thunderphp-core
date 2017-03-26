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

use Core\Exceptions\Core\BadRouteException;
use Core\Exceptions\Network\InfiniteRedirectLoopException;

class StandardRouter extends AbstractRouter
{

    private function parseRoute($route) {
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);
        $splitRegex = '~\{ \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s* (?: : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*) )? \}';

        // Split on [ while skipping placeholders
        $segments = preg_split($splitRegex . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);
        if ($numOptionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match($splitRegex . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new BadRouteException("Optional segments can only occur at the end of a route");
            }
            throw new BadRouteException("Number of opening '[' and closing ']' does not match");
        }

        $currentRoute = '';
        $routeData = [];
        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new BadRouteException("Empty optional part");
            }

            $currentRoute .= $segment;
            $routeData[] = [$currentRoute];
        }
        return $routeData;
    }

    private function buildRegexForRoute($routeData, $action, $module) {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart) = $part;

            if (isset($variables[$varName])) {
                throw new BadRouteException(sprintf(
                    'Cannot use the same placeholder "%s" twice', $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return [
            'expression' => $regex,
            'variables'  => $variables,
            'action'     => $action,
            'module'     => $module
        ];
    }

    private function getPatternGroup($pattern, $generalGroup = 'general-group'): string
    {
        $result = preg_match('~^/[A-Za-z]+~', $pattern, $matches);
        if($result){
            $group = $matches[0];
        } else {
            $group = $generalGroup;
        }

        return $group;
    }

    public function run()
    {
        # Get from cache or generate route map array
        $routesMap = $this->cache->entry('_framework_modules_routes', function(){
            $routesMap = [];
            foreach($this->routingTable as $routeItem){

                $group = $this->getPatternGroup($routeItem['pattern']);
                $routeData = $this->parseRoute($routeItem['pattern']);

                foreach ((array)$routeItem['method'] as $method){
                    foreach ($routeData as $data){
                        $routesMap[$method][$group][] = $this->buildRegexForRoute($data, $routeItem['action'], $routeItem['module']);
                    }
                }
            }

            return $routesMap;
        }, 43200 /* 12 hours */ );

        # Prepare information of current request
        $requestUri     = $this->getRequest()->getUrlPath();
        $requestGroup   = $this->getPatternGroup($requestUri);
        $requestMethod  = $this->getRequest()->getRequestMethod();

        # Redirect to default route
        if($requestUri === '/'){
            $this->redirect($this->defaultRoute);
        }

        $selectedRoute = false;
        if(isset($routesMap[$requestMethod][$requestGroup])){
            foreach ($routesMap[$requestMethod][$requestGroup] as $data){
                $result = preg_match('~^' . $data['expression'] . '$~', $requestUri, $matches);
                if($result){
                    array_shift($matches);
                    $selectedRoute = [
                        'module'    => $data['module'],
                        'handler'   => $data['action'],
                        'variables' => array_combine($data['variables'], $matches)
                    ];
                    break;
                }
            }
        }


        if($selectedRoute !== false){

            $controller = $this->requestHandler($selectedRoute['handler'], $selectedRoute['module']);

            # Run controller action
            $controller->runAction();

        } else {
            # TODO: Add not found errors handling
            header("HTTP/1.1 404 This is not the droids you're looking for.");
        }

//        $templateCookie = $this->request->getCookies()->getCookie(self::COOKIE_TEMPLATE, 'default');
//
//        # Set cookie with user language
//        $availableLangList = $this->config->getModuleConfig($this->module)->getValue('language')->getArrayCopy();
//        $langCookie = $this->request->getCookies()->getCookie(self::COOKIE_LANGUAGE);
//
//        if($langCookie && in_array($langCookie, $availableLangList)){
//            $this->requestLang = $langCookie;
//        } else {
//            $this->requestLang = $this->findClientLanguage($availableLangList, 'en_US');
//            $this->request->getCookies()->setCookie(self::COOKIE_LANGUAGE, $this->requestLang, time()+2592000);     // Expire in 30 days
//        }
//
//        # Set language environment variable
//        putenv('USER_LANG='.$this->requestLang);
//        putenv('USER_THEME='.$templateCookie);
//        header('Language: '.$this->requestLang, false);
//
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

    /** Send client redirect to specific url
     * @param String $url New url for client
     * @throws InfiniteRedirectLoopException
     */
    public function redirect($url = null): void
    {
        if ($url === null || $url === '') {
            $location = $this->request->getUrlBase();
        } else {
            $location = $this->request->getUrlBase().trim($url, '/');
        }

        if($this->request->getUrl() == $location) throw new InfiniteRedirectLoopException($location);

        header('location: ' . $location);
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

}
