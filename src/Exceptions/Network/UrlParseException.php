<?php
/**
 * Created by PhpStorm.
 * User: Marek Ulwanski
 * Date: 2017-03-11
 * Time: 22:18
 */

namespace Core\Exceptions\Network;


class UrlParseException extends \Exception
{

    public function __construct(string $url)
    {
        $message = 'Error while parse url: '.$url;
        parent::__construct($message);
    }

}