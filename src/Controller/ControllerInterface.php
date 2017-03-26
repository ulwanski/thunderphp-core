<?php

namespace Core\Controller;

use Core\Router\Request;

interface ControllerInterface {

    /** ControllerInterface constructor.
     * @param Request $request
     */
    function __construct(Request $request);

    /** Default action is called when no other suitable action was found
     *
     */
    function defaultAction(): void;

    /** Set action which <i>run</i> method should call
     * @param string $actionName
     */
    function setActionName(string $actionName): void;

    /** Call previously setup action
     * @return ControllerInterface
     */
    function runAction(): ControllerInterface;

    /** Get Request object
     * @return Request
     */
    function getRequest(): Request;

}