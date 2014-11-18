<?php
/**
 * @created 28.11.2012 18:05:47
 * @author mregner
 * @version $Id$
 */

require_once('AbstractFormat.php');

class FormatYml extends AbstractFormat {
    /**
     * (non-PHPdoc)
     * @see AbstractFormat::loadFormatFile()
     */
    protected function loadFile($filename) {
        $yamlExtension = dirname(__DIR__) . "/extensions/spyc/Spyc.php";
        if(file_exists($filename)) {
            if(function_exists('yaml_parse_file')) {
                try {
                    $data = yaml_parse_file($filename);
                } catch(Exception $exception) {
                    throw new Exception($exception->getMessage()."[{$filename}]");
                }
            } else if (file_exists($yamlExtension)) {
                require_once $yamlExtension;
                $data = Spyc::YAMLLoad($filename);
            } else {
                throw new FormatException("Yaml extension is not installed!");
            }
            if($data === false) {
                throw new FormatException("Data for configfile {$filename} invalid. Please Check.");
            }
            return $data;
        }
        return array();
    }

    /**
     * @param mixed $data
     * @return array
     * @throws FormatException
     * @author mregner
     */
    public function convert($data) {
        if(function_exists('yaml_parse')) {
            if(is_array($data)) {
                $convertedData = yaml_emit($data);
            } else {
                $convertedData = yaml_parse($data);
            }
        } else {
            throw new FormatException("Yaml extension is not installed!");
        }
        if($convertedData === false) {
            throw new FormatException("Error while decoding yaml data!!!");
        }
        return $convertedData;
    }
}
