<?php

/** $Id$
 * AbstractRequest.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Http;


abstract class AbstractRequest
{

    /** @var mixed */
    protected $hostComponents;

    public function __construct($host)
    {
        $this->hostComponents = parse_url(trim($host));
    }

}