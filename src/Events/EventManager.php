<?php
/** $Id$
 * EventManager.php
 *
 * @version 1.0.0, $Revision$
 * @package Core\Session
 * @author Marek Ulwański <marek@ulwanski.pl>
 * @copyright Copyright (c) 2016, Marek Ulwański
 * @link $HeadURL$ Subversion
 */

namespace Core\Events;

class EventManager
{

    private static $eventsSubscribe = array();
    private static $disposableEvents = array();

    static public function dispatchEvent($eventName)
    {
        $subscribeCount = 0;
        if(isset(self::$eventsSubscribe[$eventName])) foreach(self::$eventsSubscribe[$eventName] as $function){
            $function($eventName);
            $subscribeCount ++;
        }

        if(isset(self::$disposableEvents[$eventName])) foreach(self::$disposableEvents[$eventName] as $eventId => $function){
            $function($eventName);
            unset(self::$disposableEvents[$eventName][$eventId]);
            $subscribeCount ++;
        }

        return ($subscribeCount == 0)?false:(int)$subscribeCount;
    }

    static public function subscribe($eventName, callable $callback, $disposable = false)
    {
        $className = null;

        if(!isset(self::$eventsSubscribe[$eventName]))  self::$eventsSubscribe[$eventName]  = array();
        if(!isset(self::$disposableEvents[$eventName])) self::$disposableEvents[$eventName] = array();

        if(is_callable($callback, true, $className)){
            if($disposable){
                $subscribeId = array_push(self::$disposableEvents[$eventName], $callback);
            } else {
                $subscribeId = array_push(self::$eventsSubscribe[$eventName], $callback);
            }
            return $subscribeId;
        }
        return false;
    }

    static public function remove($eventName, $subscribeId)
    {
        if(!isset(self::$eventsSubscribe[$eventName][$subscribeId])) return false;

        unset(self::$eventsSubscribe[$eventName][$subscribeId]);
        return true;
    }

}