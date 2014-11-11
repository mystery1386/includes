<?php
/**
 * Warpper fuer alle Cookie Operationen.
 */

class Cookie {
    /**
     * @param string $key
     * @param string $value
     * @param string $expire
     * @param string $path
     * @param string $domain
     */
    public static function set($key, $value, $expire=null, $path=null, $domain=null) {
        setcookie($key, $value, $expire, $path, $domain);
    }

    /**
     * @param string $key
     * @return string
     */
    public static function get($key) {
        if(self::has($key)) {
            return $_COOKIE[$key];
        }
        return null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        return isset($_COOKIE[$key]);
    }

    /**
     * @param string $key
     */
    public static function delete($key) {
        self::set($key, '', time() - 3600);
    }
}