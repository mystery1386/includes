<?php
/**
 * Global Session Instanz. Die entsprechende Superglobal nicht
 * benutzen.
 */

class Session {
    /**
     * @var Session
     */
    protected static $instance = null;

    /**
     * @var string
     */
    protected static $cookie_domain = null;

    /**
     * @var string
     */
    protected static $cookie_path = "/";

    /**
     * Konstuktor
     */
    protected function __construct() {}

    /**
     * @return Session
     * @author mregner
     */
    public static function getInstance() {
        if(!isset(self::$instance)) {
            self::$instance = new Session();
        }
        return self::$instance;
    }

    /**
     * @param string $session_id
     * @author mregner
     */
    public static function start($session_id="") {
        session_set_cookie_params(0, self::$cookie_path, self::$cookie_domain);

        if(strlen($session_id) > 0) {
            session_id($session_id);
        }

        @session_start();
    }

    /**
     * @author mregner
     */
    public static function close() {
        session_write_close();
    }

    /**
     * @return string
     * @author mregner
     */
    public static function getID() {
        return session_id();
    }

    /**
     * @author mregner
     */
    public static function destroy() {
        unset($_COOKIE["PHPSESSID"]);

        setcookie("PHPSESSID", "", null, self::$cookie_path, self::$cookie_domain);
        @session_destroy();
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value) {
        $session = self::getInstance();
        $session[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get($key) {
        $session = self::getInstance();
        return $session[$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        $session = self::getInstance();
        return isset($session[$key]);
    }

    /**
     * @param string $key
     */
    public static function delete($key) {
        $session = self::getInstance();
        unset($session[$key]);
    }

    /**
     * @return array
     */
    public static function getData() {
        return self::getInstance();
    }

    /**
     * @return int
     */
    public static function getTTL() {
        return (int) ini_get("session.gc_maxlifetime");
    }
}