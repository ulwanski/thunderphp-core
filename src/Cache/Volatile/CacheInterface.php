<?php

/** $Id$
 * CacheInterface.php
 *
 * @version 1.0.0, $Revision$
 * @package Core\Cache\Volatile
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Cache\Volatile;

interface CacheInterface {

    /** Return class instance
     * @return \Core\Cache\Volatile\CacheInterface
     */
    static function getInstance();

    public function __set($key, $value);

    public function __get($key);

    /** Cache a variable in the data store.
     * @param $key
     * @param $value
     * @param int $ttl
     * @return mixed
     */
    public function add($key, $value, $ttl = 0);

    /** Cache an array in the data store.
     * @param array $values
     * @param int $ttl
     * @return mixed
     */
    public function add_array(array $values, $ttl = 0);

    /** Fetch a stored variable from the cache.
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null);

    /** Atomically fetch or generate a cache entry.
     * @param string $key Identity of cache entry
     * @param callable $func A callable that accepts key as the only argument and returns the value to cache.
     * @param int $ttl Time To Live
     * @return mixed
     */
    public function entry($key, callable $func, $ttl = 0);

    /** Removes a stored variable from the cache.
     * @param $key
     */
    public function delete($key);

    /** Clears the cache.
     */
    public function clear();

}