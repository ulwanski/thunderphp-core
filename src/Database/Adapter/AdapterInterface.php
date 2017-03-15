<?php
/** $Id$
 * AdapterInterface.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database\Adapter;


interface AdapterInterface
{
    function getRow($from, $select, $where);
    function getRows($from, $select, $where, $limit);
    function getField($from, $field, $where);
    function getAll($from, $select, $where);
    function getColumns($table);

    function insert($table, $data);
    function update($table, $data, $where);
    function delete($from, $where, $limit = 1);
}