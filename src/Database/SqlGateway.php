<?php

/** $Id$
 * TableGateway.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database;

use \Core\Database\Adapter\PdoAdapter;
    
class SqlGateway extends Adapter\PdoAdapter {

    private static $Instance = false;

    /**
     * @param string $databaseType
     * @return \Core\Database\SqlGateway
     */
    public static function getInstance($databaseType = PdoAdapter::DB_MASTER) {
        if (self::$Instance == false)
            self::$Instance = new SqlGateway($databaseType);
        return self::$Instance;
    }

    public function __construct($databaseType = PdoAdapter::DB_MASTER) {
        parent::__construct($databaseType);
    }

    public function getRowByWhere($table, $select, $where){
        return $this->getRow($table, $select, $where);
    }

    public function getRowsByWhere($table, $select, $where, $limit = false){
        return $this->getRows($table, $select, $where, $limit);
    }

    public function getRowByField($table, $select, $pk, $value){
        return $this->getRow($table, $select, "`$pk` = '$value'");
    }

    public function getRowsByField($table, $select, $pk, $value, $limit = false){
        return $this->getAll($table, $select, "`$pk` = '$value'", $limit);
    }

    public function insertRow($table, &$data){
        return $this->insert($table, $data);
    }

    public function updateRow($table, &$data, $where){
        return $this->update($table, $data, $where);
    }

}
