<?php

/** $Id$
 * DatabaseService.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Service {
    
    use ReflectionProperty as Prop;
    use \Core\Database\Adapter\PdoAdapter;
    use \Core\Exceptions\Database\MissingDataException;
    use \Core\Database\SqlGateway;

    abstract class DatabaseService extends AbstractService {
        
        abstract protected function getTableName();
        abstract protected function getPrimaryKey();
        abstract protected function getAttachedModel();

        /* @var $db_write_adapter \Core\Database\SqlGateway */
        private $db_write_adapter = null;
        
        /* @var $db_read_adapter \Core\Database\SqlGateway */
        private $db_read_adapter = null;

        public function __construct() {
            $this->db_write_adapter = SqlGateway::getInstance(PdoAdapter::DB_MASTER);
            $this->db_read_adapter = SqlGateway::getInstance(PdoAdapter::DB_SLAVE);
        }
        
        /**
         * @return \Core\Database\SqlGateway
         */
        protected function getWriteAdapter() {
            return $this->db_write_adapter;
        }
        
        /**
         * @return \Core\Database\SqlGateway
         */
        protected function getReadAdapter() {
            return $this->db_read_adapter;
        }

        public function getNewModel() {
            $model = $this->getAttachedModel();
            return new $model();
        }
        
        public function loadById($id) {
            $table  = $this->getTableName();
            $key    = $this->getPrimaryKey();
            $model  = $this->getAttachedModel();
            $obj    = new $model();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);
            
            // Pobieranie danych
            $data = $this->db_read_adapter->getRowByField($table, "*", $key, $id);
            
            if($data == false){
                throw new MissingDataException();
            }
            
            // Mapowanie danych
            foreach($reflection as $field){
                $field_name = $field['reflection']->name;
                $set_method = $field['setMethod'];
                if($set_method != false){
                    if(isset($data[$field_name])){
                        $field = $this->detectDataType($data[$field_name]);
                        $obj->$set_method($field);
                    } else {
                        $obj->$set_method(null);
                    }
                }
            }
            
            return $obj;
        }
        
        public function load($limit = false){
            $table  = $this->getTableName();
            $model  = $this->getAttachedModel();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);            
            $data = $this->db_read_adapter->getRows($table, "*", $limit);
            
            if($data == false){
                throw new MissingDataException();
            }
            
            $results = array();
            foreach($data as $key => $row){
                $obj = new $model();
                foreach($reflection as $field){
                    $field_name = $field['reflection']->name;
                    $set_method = $field['setMethod'];
                    if($set_method != false){
                        $field = $this->detectDataType($row[$field_name]);
                        $obj->$set_method($field);
                    }
                }
                $results[$key] = $obj;
            }
            return $results;
        }
        
        public function loadOneByField($field, $value) {
            $table  = $this->getTableName();
            $model  = $this->getAttachedModel();
            $obj    = new $model();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);
            
            // Pobieranie danych
            $data = $this->db_read_adapter->getRowByField($table, "*", $field, $value);
            
            if($data == false){
                throw new MissingDataException();
            }
            
            // Mapowanie danych
            foreach($reflection as $field){
                $field_name = $field['reflection']->name;
                $set_method = $field['setMethod'];
                if($set_method != false && isset($data[$field_name])){
                    $field = $this->detectDataType($data[$field_name]);
                    $obj->$set_method($field);
                }
            }
            
            return $obj;
        }
        
        public function loadByField($field, $value){
            $table  = $this->getTableName();
            $model  = $this->getAttachedModel();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);
            
            $data = $this->db_read_adapter->getRowsByField($table, "*", $field, $value);
            
            if($data == false){
                throw new MissingDataException();
            }
            
            $results = array();
            foreach($data as $key => $row){
                $obj = new $model();
                foreach($reflection as $field){
                    $field_name = $field['reflection']->name;
                    $set_method = $field['setMethod'];
                    if($set_method != false){
                        $field = $this->detectDataType($row[$field_name]);
                        $obj->$set_method($field);
                    }
                }
                $results[$key] = $obj;
            }
            return $results;
        }
        
        public function loadWhere($where, $limit = false){
            $table  = $this->getTableName();
            $model  = $this->getAttachedModel();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);            
            $data = $this->db_read_adapter->getRowsByWhere($table, "*", $where, $limit);
            
            if($data == false){
                throw new MissingDataException();
            }
            
            $results = array();
            foreach($data as $key => $row){
                $obj = new $model();
                foreach($reflection as $field){
                    $field_name = $field['reflection']->name;
                    $set_method = $field['setMethod'];
                    if($set_method != false){
                        $field = $this->detectDataType($row[$field_name]);
                        $obj->$set_method($field);
                    }
                }
                $results[$key] = $obj;
            }
            return $results;
        }
        
        public function save(&$model){
            $table  = $this->getTableName();
            $key    = $this->getPrimaryKey();
            $filter = Prop::IS_PRIVATE | Prop::IS_PROTECTED | Prop::IS_PUBLIC;
            $get_pk = 'get'.ucfirst($key);
            $set_pk = 'set'.ucfirst($key);
            
            // Pobieranie struktury modelu
            $reflection = $this->getClassProperties($this->getAttachedModel(), $filter);
            
            // Pobiernie wartosci klucza glownego
            $pkVal = $model->$get_pk();

           // Mapowanie danych
            $data = array();
            foreach($reflection as $field_name => $field){
                $get_method = $field['getMethod'];
                if($get_method == false) continue;
                $data[$field_name] = $model->$get_method();
            }

            // Wybieranie metody zapisu
            if($pkVal == null){
                $id = $this->db_write_adapter->insertRow($table, $data);
                $model->$set_pk($id);
                return $id;
            } else {
                $result = $this->db_write_adapter->updateRow($table, $data, "`$key` = $pkVal");
                return $result;
            }
        }
    
                
    }
    
}