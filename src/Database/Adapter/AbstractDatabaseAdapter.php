<?php

/** $Id$
 * AbstractDatabaseAdapter.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database\Adapter;

/**
 * Class AbstractDatabaseAdapter
 * @package Core\Database\Adapter
 */
abstract class AbstractDatabaseAdapter implements AdapterInterface {

    abstract public function getRow($from, $select, $where);
    abstract public function getRows($from, $select, $where, $limit);
    abstract public function getField($from, $field, $where);
    abstract public function getAll($from, $select, $where);
    abstract public function getColumns($table);

    abstract public function insert($table, $data);
    abstract public function update($table, $data, $where);
    abstract public function delete($from, $where, $limit = 1);

    protected function get_array_keys_to_query(&$array){
        $values = array();
        foreach($array as $key => $val){
            $values[] = '`'.$key.'`';
        }
        return implode(', ', $values);
    }

    protected function get_array_values_to_query(&$array){
        $values = array();
        foreach($array as $key => $val){
            if(is_null($val)){
                $values[] = 'NULL';
            } else {
                $values[] = "'$val'";
            }
        }
        return implode(', ', $values);
    }

    protected function get_array_to_query(&$array){
        $values = array();
        foreach($array as $key => $val){
            if(is_null($val)){
                $values[] = "`$key` = NULL";
            } else {
                $values[] = "`$key` = '$val'";
            }
        }
        return implode(', ', $values);
    }

}