<?php
/**
 * Created by PhpStorm.
 * User: mark
 * Date: 11.10.14
 * Time: 13:43
 */
require_once('FormatException.php');

class FormatFactory {
    /**
     * @var array
     */
    public static $CONTENT_TYPE_MAP = array(
        'application/json' => 'json',
        'text/xml' => 'xml',
        'application/xml' => 'xml',
        'text/yaml' => 'yml',
        'text/plain' => 'plain',
        'txt' => 'plain',
    );

    /**
     * @var array
     */
    protected static $loaders = array();

    /**
     * @param string $type
     * @return bool
     */
    public static function has($type) {
        isset(self::$CONTENT_TYPE_MAP[$type]) && ($type = self::$CONTENT_TYPE_MAP[$type]);
        $suffix =  ucfirst($type);
        return file_exists(__DIR__ . "/Format{$suffix}.php");
    }

    /**
     * @param string $extension
     * @return AbstractFormat
     * @author mregner
     */
    protected static function getFormat($extension) {
        $suffix =  ucfirst($extension);
        if(!isset(self::$loaders[$suffix])) {
            $className = "Format{$suffix}";
            if(!class_exists($className)) {
                $classFileName = "{$className}.php";
                //Ist die Datei nicht vorhanden dann wird hier ein Fehler
                //geschmissen.
                require_once ($classFileName);
            }
            self::$loaders[$suffix] = new $className();
        }
        return self::$loaders[$suffix];
    }

    /**
     * @param $file
     * @param callable $transformator
     *
     * @return array
     *
     * @throws FormatException
     *
     * @author mregner
     */
    public static function load($file, $transformator=null) {
        if(file_exists($file)) {
            $pathInfo = pathinfo($file);
            return self::get($pathInfo['extension'])->load($file, $transformator);
        } else {
            throw new FormatException("File {$file} does not exist!");
        }
    }

    /**
     * @param string $type
     * @return AbstractFormat
     * @author mregner
     */
    public static function get($type) {
        isset(self::$CONTENT_TYPE_MAP[$type]) && ($type = self::$CONTENT_TYPE_MAP[$type]);
        return self::getFormat($type);
    }
} 