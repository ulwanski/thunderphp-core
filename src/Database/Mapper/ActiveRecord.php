<?php

/** $Id$
 * ActiveRecord.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Database\Mapper;

use \Core\Database\Adapter\AdapterInterface;
use \ArrayIterator;

/**
 * Class ActiveRecord
 * @package Core\Database\Mapper
 */
final class ActiveRecord extends ArrayIterator implements ActiveRecordInterface {

    /** @var null AdapterInterface */
    private $adapter = null;

    # Database table primary key
    private $pk = null;

    # Primary key value for loaded row
    private $id = null;

    # Database table
    private $table = null;

    # Original data
    private $original = array();

    # Original data sha1 sum
    private $hash = null;

    # If true, changes will save automatically
    private $auto_save = false;

    /**
     * ActiveRecord constructor.
     * @param AdapterInterface $adapter
     * @param null $table
     * @param null $id
     * @param array $data
     */
    public function __construct(AdapterInterface &$adapter, $table = null, $id = null, $data = array())
    {
        $this->original = $data;
        $this->adapter = $adapter;
        $this->table = $table;
        $this->id = $id;
        $this->hash = sha1(json_encode($this->original));

        parent::__construct($data);
    }

    public function __destruct()
    {
        if($this->auto_save == true){
            $this->save();
        }
    }

    /** Return serialized data
     * @return string
     */
    public function serialize()
    {
        $adapter = $this->adapter;
        $this->adapter = null;
        $data = parent::serialize();
        $this->adapter = $adapter;
        return $data;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value){
        $this[$name] = $value;
    }

    /**
     * @param $name
     */
    public function __get($name)
    {
        $this[$name];
    }

    /** Set target table to load
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /** Load row from database
     * @param $id
     * @param bool $auto_save If true, changes will save automatically
     * @return $this
     */
    public function load($id, $auto_save = false)
    {
        $this->findTablePrimaryKey();

        $where = $this->pk.' = '.$id;

        $data = $this->adapter->getRow($this->table, '*', $where);

        $this->id = $id;

        $this->original = $data;

        $this->auto_save = (bool)$auto_save;

        foreach($data as $property => $value){
            $this[$property] = $value;
        }

        return $this;
    }

    /** Insert or update row into database
     * @return $this
     */
    public function save()
    {
        $data = array();

        $this->findTablePrimaryKey();

        $where = $this->pk.' = '.$this->id;

        foreach($this->original as $name => $value){
            if($this[$name] != $value){
                $data[$name] = $this[$name];
            }
        }

        if(count($data) == 0){
            return $this;
        }

        if($this->id != null){
            $this->adapter->update($this->table, $data, $where);
        } else {
            $id = $this->adapter->insert($this->table, $this->getArrayCopy());
            $this->id = $id;
        }

        return $this;
    }

    /** If true, changes will save automatically
     * @param bool $enabled
     */
    public function autoSave($enabled = true)
    {
        $this->auto_save = (bool)$enabled;
        return $this;
    }

    /** Removes row from database
     * @return $this
     */
    public function delete()
    {
        if($this->table == null || $this->id == null) return $this;

        $this->findTablePrimaryKey();

        $where = $this->pk.' = '.$this->id;

        $result = $this->adapter->delete($this->table, $where, 1);

        if($result){
            $this->id = null;
        }

        return $this;
    }

    /** Returns sha1 sum of original data
     * @return string
     */
    public function hash()
    {
        return $this->hash;
    }

    /**
     * @return $this
     */
    private function findTablePrimaryKey()
    {
        if($this->pk != null) return $this;

        $columns = $this->adapter->getColumns($this->table);

        foreach($columns as $column => $data){
            if(isset($data['Key']) and $data['Key'] == 'PRI'){
                $this->pk = $data['Field'];
            }
        }

        return $this;
    }

}