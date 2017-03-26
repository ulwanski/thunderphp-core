<?php

/** $Id$
 * AbstractController.php
 * @version 1.0.0, $Revision$
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Controller;
    
use \Core\Application\Api;
use Core\Router\Request;

abstract class AbstractController implements ControllerInterface {

    /** @var string|null */
    private $actionName = null;

    /** @var Request|null */
    private $request = null;

    /** ControllerInterface constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

    }

    /** Default action is called when no other suitable action was found
     *
     */
    abstract function defaultAction(): void;

    /** Set action which <i>run</i> method should call
     * @param string $actionName
     */
    public function setActionName(string $actionName): void
    {
        $this->actionName = $actionName;
    }

    /** Call previously setup action
     * @return ControllerInterface
     */
    public function runAction(): ControllerInterface
    {
        $action = $this->actionName;
        if($this->actionName !== null) $this->$action();

        return $this;
    }

    /** Get Request object
     * @return Request
     */
    function getRequest(): Request
    {
        return $this->request;
    }

}