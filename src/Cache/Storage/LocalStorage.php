<?php
/** $Id$
 * LocalStorage.php
 * @version 1.0.0, $Revision$
 * @package TestApp
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2015, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Cache\Storage;

use \Api;
use \SQLite3;
use \SQLite3Result;

class LocalStorage extends SQLite3 {

    const DATABASE_PATH = 'data/database';
    const DATABASE_EXT  = 'db3';

    public function __construct($database, $encryption_key = null)
    {
        # Get project root path
        $rootPath = Api::getRouter()->getLocalRootPath();

        # Create database directory path
        $storagePath = realpath(implode(DIRECTORY_SEPARATOR, [$rootPath, self::DATABASE_PATH]));

        # Create database path
        $filePath = implode(DIRECTORY_SEPARATOR, [$storagePath , $database.'.'.self::DATABASE_EXT]);

        parent::__construct($filePath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE, $encryption_key);
    }

    public function set($table, $name, $value)
    {

        /*
            CREATE TABLE `test2` (
                `name`	TEXT NOT NULL UNIQUE,
                `value`	TEXT NOT NULL
            );
         */
        $this->exec("INSERT INTO $table ($name) VALUES ('$value')");

    }

}