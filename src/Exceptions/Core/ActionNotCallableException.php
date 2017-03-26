<?php
/**
 * Created by PhpStorm.
 * User: Marek Ulwanski
 * Date: 2017-03-26
 * Time: 17:54
 */

namespace Core\Exceptions\Core;


use Core\Exceptions\CoreException;

class ActionNotCallableException extends CoreException
{
    public function __construct()
    {
        $message = 'Controller must have at least one callable method "defaultAction()"';
        parent::__construct($message);
    }

}