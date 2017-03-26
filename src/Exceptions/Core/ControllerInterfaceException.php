<?php
/**
 * Created by PhpStorm.
 * User: Marek Ulwanski
 * Date: 2017-03-26
 * Time: 17:54
 */

namespace Core\Exceptions\Core;


use Core\Exceptions\CoreException;

class ControllerInterfaceException extends CoreException
{
    public function __construct()
    {
        $message = 'Controller must implement ControllerInterface';
        parent::__construct($message);
    }

}