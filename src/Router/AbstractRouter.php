<?php

/** $Id$
 * AbstractRouter.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Router;


use Core\Cache\Volatile\CacheInterface;
use Core\Controller\ControllerInterface;
use Core\Exceptions\Core\ActionNotCallableException;
use Core\Exceptions\Core\ControllerInterfaceException;

abstract class AbstractRouter
{
    const COOKIE_LANGUAGE = 'path_lang';
    const COOKIE_TEMPLATE = 'path_theme';

    /** @var CacheInterface|null  */
    protected $cache = null;

    /** @var Request|null */
    protected $request = null;

    /** @var array|null */
    protected $routingTable = null;

    /** @var null|string */
    protected $defaultRoute = null;

    public function __construct(array $routingTable, string $defaultRoute, CacheInterface $cache)
    {
        $this->cache = $cache;
        $this->request = new Request();
        $this->defaultRoute = $defaultRoute;
        $this->routingTable = $routingTable;
    }

    public function __destruct()
    {
        $max_ms = 15;
        $time_ms = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
        $percent = $time_ms * 100 / $max_ms;
        echo "Execution time: ".round($time_ms, 2),"ms (".round($percent)."%)";
    }

    abstract public function run();

    public function setRoutingTable(array $table)
    {
        $this->routingTable = $table;
    }

    /** Return Request object
     * @return \Core\Router\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    protected function requestHandler(string $handler, string $module): ControllerInterface
    {
        $controllerPath = $this->subtractController($handler, $module);

        # Create new controller instance
        $controller = new $controllerPath($this->request);

        # Checking if controller implements ControllerInterface
        if(!$controller instanceof ControllerInterface){
            throw new ControllerInterfaceException();
        }

        # Find controller method for action
        $actionName = $this->subtractAction($controller, $this->request, $handler);

        # Set action name to run
        $controller->setActionName($actionName);

        # Return controller
        return $controller;
    }

    /**
     * @param string $handlerPath
     * @param string $module
     * @return string
     */
    protected function subtractController(string $handlerPath, string $module): string
    {
        $actionParts = explode('/', $handlerPath);
        $className = ucfirst($actionParts[0]).'Controller';
        $classPath = ucfirst($module).'\\Controller\\'.$className;

        return $classPath;
    }

    /**
     * @param ControllerInterface $controller
     * @param Request $request
     * @param string $handlerPath
     * @return string
     * @throws ActionNotCallableException
     */
    protected function subtractAction(ControllerInterface $controller, Request $request, string $handlerPath): string
    {
        $actionName  = null;
        $actionParts = explode('/', $handlerPath);

        if(count($actionParts) > 1) {
            $actionPart = end($actionParts);
            $actionName = $this->findAction($controller, $request, $actionPart);
        }

        if($actionName === null) {
            $actionName = $this->findAction($controller, $request, 'default');
        }

        if(!is_string($actionName) || !is_callable([$controller, $actionName])){
            throw new ActionNotCallableException();
        }

        return $actionName;
    }

    /**
     * @param ControllerInterface $controller
     * @param Request $request
     * @param string $actionPart
     * @return null|string
     */
    private function findAction(ControllerInterface $controller, Request $request, string $actionPart): ?string
    {
        if($request->isAjaxRequest()){
            $actionName = $actionPart.'Ajax';
            if(is_callable([$controller, $actionName])) return $actionName;
        } else {
            $actionName = $actionPart.'Action';
            if(is_callable([$controller, $actionName])) return $actionName;
        }

        return null;
    }
}