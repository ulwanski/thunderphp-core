<?php
/** $Id$
 * ActiveRecordInterface.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database\Mapper;

use \Core\Database\Mapper;

interface ActiveRecordInterface
{
    function __set($name, $value);
    function __get($name);
    function table($table);
    function load($id);
    function save();
    function hash();

}