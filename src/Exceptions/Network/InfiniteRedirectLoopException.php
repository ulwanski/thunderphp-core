<?php
/**
 * Created by PhpStorm.
 * User: Marek Ulwanski
 * Date: 2017-03-25
 * Time: 22:08
 */

namespace Core\Exceptions\Network;


use Core\Exceptions\CoreException;

class InfiniteRedirectLoopException extends CoreException
{
    public function __construct($location)
    {
        $message  = 'Router has detected infinite redirects loop when trying to redirect to "<i>'.$location.'</i>".';
        parent::__construct($message);
    }

}