<?php

/** $Id$
 * PdoAdapter.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database\Adapter;

use \Api;
use \PDO;

class PdoAdapter extends AbstractDatabaseAdapter {

    const DB_MASTER = 'db_mode_master';
    const DB_SLAVE  = 'db_mode_slave';

    const MYSQL_DATETIME_FORMAT = "Y-m-d H:i:s";
    const MYSQL_DATE_FORMAT = "Y-m-d";

    /* @var $driver PDO */
    private $driver = null;

    public function __construct($databaseType = PdoAdapter::DB_MASTER){
        $config = Api::getConfig()->getCoreConfig();
        $db = $config['database'][$databaseType];
        $persistent = (bool)$config['database']['persistent'];

        $this->driver = new PDO($db['dsn'], $db['username'], $db['password'], array(
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT            => $persistent,
            PDO::MYSQL_ATTR_INIT_COMMAND    => "SET NAMES utf8",
        ));
    }

    /**
     * @return PDO
     */
    public function getDriver() {
        $driver = $this->driver;
        /* @var $driver PDO */
        return $driver;
    }

    /**
     * @param $table
     * @return array
     */
    public function getColumns($table){
        $statement = "SHOW columns FROM $table";

        $query = $this->driver->prepare($statement);
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach($data as $key => $column){
            $field = $column['Field'];
            $data[$field] = $column;
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * @param $from
     * @param $field
     * @param $where
     * @return bool
     */
    public function getField($from, $field, $where){
        $statement = "SELECT `$field` FROM `$from` WHERE $where LIMIT 1;";
        $query = $this->driver->prepare($statement);
        $query->execute();
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        if(isset($data[$field])){
            return $data[$field];
        }
        return false;
    }

    /**
     * @param $from
     * @param string $select
     * @param string $where
     * @return mixed
     */
    public function getRow($from, $select = "*", $where = '1'){
        $statement = "SELECT $select FROM `$from` WHERE $where LIMIT 1;";
        $query = $this->driver->prepare($statement);
        $query->execute();
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param $from
     * @param string $select
     * @param string $where
     * @param bool $limit
     * @return array
     */
    public function getRows($from, $select = "*", $where = '1', $limit = false){
        if($limit != false) $limit = 'LIMIT '.(int)$limit;
        $statement = "SELECT $select FROM `$from` WHERE $where $limit;";
        $query = $this->driver->prepare($statement);
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param $from
     * @param string $select
     * @param bool $where
     * @return array
     */
    public function getAll($from, $select = "*", $where = false){
        $statement = "SELECT $select FROM `$from`";
        if($where != false){
            $statement .= " WHERE $where";
        }
        $query = $this->driver->prepare($statement);
        $query->execute();
        $data = $query->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * @param $table
     * @param $data
     * @return null|string
     */
    public function insert($table, $data){
        $fields = $this->get_array_keys_to_query($data);
        $values = $this->get_array_values_to_query($data);

        $statement = "INSERT INTO `$table` ($fields) VALUES ($values);";
        $result  = $this->driver->exec($statement);
        $last_id = $this->driver->lastInsertId();
        return ($result == false)?null:$last_id;
    }

    /**
     * @param $table
     * @param $data
     * @param $where
     * @return int
     */
    public function update($table, $data, $where){
        $values = $this->get_array_to_query($data);

        $statement = "UPDATE `$table` SET $values WHERE $where;";
        $result = $this->driver->exec($statement);
        return $result;
    }

    /**
     * @param $from
     * @param $where
     * @param int $limit
     * @return bool
     */
    public function delete($from, $where, $limit = 1){
        if($limit != false) $limit = 'LIMIT '.(int)$limit;
        $statement = "DELETE FROM `$from` WHERE $where $limit;";

        $query = $this->driver->prepare($statement);
        $result = $query->execute();

        return $result;
    }

}