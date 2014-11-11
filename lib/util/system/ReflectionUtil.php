<?php
/**
 * @created 22.11.2012 13:54:55
 * @author mregner
 * @version $Id$
 */

class ReflectionUtil {
    protected static $classes = array();

    /**
     * @param string $class_name
     * @return ReflectionClass
     * @author mregner
     */
    public static function getReflectionClass($class_name) {
        if(!isset(self::$classes[$class_name])) {
            self::$classes[$class_name] = new ReflectionClass($class_name);
        }
        return self::$classes[$class_name];
    }

    /**
     * Ermittelt die Informationen zu allen Methoden der gegeben Klasse.
     *
     * @param mixed $class
     * @param bool $strict
     * @return array
     * @author mregner
     */
    public static function getReference($class, $strict=false) {
        $reference = array();

        if($class instanceof ReflectionClass) {
            $reflectionClass = $class;
        } else {
            $reflectionClass = self::getReflectionClass($class);
        }

        $reference['class'] = $reflectionClass->getName();

        $reference['tags'] = self::getClassTags($reflectionClass);

        $reference['methods'] = array();
        $methods = $reflectionClass->getMethods();
        foreach($methods as $method) {
            $name = $method->getName();
            $declaringClass = $method->getDeclaringClass()->getName();;
            if($strict === true && $declaringClass != $reference['class']) {
                continue;
            }

            $reference['normalized_methods'][strtolower($name)] = $name;

            $reference['methods'][$name] = array(
                'name' => $name,
            );
            $reference['methods'][$name]['class'] = $declaringClass;

            if(preg_match_all("~\* ([^@].*?)[\r\n]~", $method->getDocComment(), $matches)) {
                $reference['methods'][$name]['comment'] = implode("\n", $matches[1]);
            }

            ($method->isConstructor()) && ($reference['methods'][$name]['constructor']=true);
            ($method->isFinal()) && ($reference['methods'][$name]['final']=true);
            ($method->isStatic()) && ($reference['methods'][$name]['static']=true);

            if($method->isPublic()) {
                $reference['methods'][$name]['public']=true;
            } else if($method->isProtected()) {
                $reference['methods'][$name]['protected']=true;
            } else {
                $reference['methods'][$name]['private']=true;
            }

            $reference['methods'][$name]['tags'] = self::getMethodTags($method);

            $reference['methods'][$name]['parameters'] = array();
            $parameters = $method->getParameters();
            if(count($parameters) > 0) {
                foreach($parameters as $parameter) {
                    $parameterName = $parameter->getName();
                    $reference['methods'][$name]['parameters'][$parameterName] = array();
                    ($parameter->isOptional()) && ($reference['methods'][$name]['parameters'][$parameterName]['optional']=true);
                    ($parameter->allowsNull()) && ($reference['methods'][$name]['parameters'][$parameterName]['allows_null']=true);
                    ($parameter->isArray()) && ($reference['methods'][$name]['parameters'][$parameterName]['is_array']=true);
                    ($parameter->isPassedByReference()) && ($reference['methods'][$name]['parameters'][$parameterName]['by_reference']=true);
                    ($parameter->isDefaultValueAvailable()) && ($reference['methods'][$name]['parameters'][$parameterName]['default']=$parameter->getDefaultValue());
                }
            }
        }

        return $reference;
    }

    /**
     * @param $tags
     * @param $key
     * @param $value
     * @author mregner
     */
    protected static function appendTag(&$tags, $key, $value) {
        $value = preg_replace("~[\n\r]+~", "", $value);
        if(isset($tags[$key])) {
            if(!empty($value)) {
                is_array($tags[$key]) || $tags[$key] = array($tags[$key]);
                $tags[$key][] = $value;
            }
        } else {
            $tags[$key] = (!empty($value) ? $value:true);
        }
    }

    /**
     * @param string $class_name
     * @return string
     * @author mregner
     */
    public static function getClassSource($class_name) {
        $reflectionClass = self::getReflectionClass($class_name);
        return self::getReflectionClassSource($reflectionClass);
    }

    /**
     * @param string $class_name
     * @return string
     * @author mregner
     */
    public static function getClassFile($class_name) {
        $reflectionClass = self::getReflectionClass($class_name);
        return $reflectionClass->getFileName();
    }

    /**
     * @param string $class_name
     * @return string
     * @author mregner
     */
    public static function getParentClassName($class_name) {
        $reflectionClass = self::getReflectionClass($class_name);
        $parentClass = $reflectionClass->getParentClass();
        if($parentClass instanceof ReflectionClass) {
            return $parentClass->getName();
        }
        return null;
    }

    /**
     * @param string $class_name
     * @param string $interface
     * @return boolean
     * @author mregner
     */
    public static function isImplementing($class_name, $interface) {
        $reflectionClass = self::getReflectionClass($class_name);
        return $reflectionClass->implementsInterface($interface);
    }

    /**
     * @param ReflectionClass $class
     * @return array
     * @author mregner
     */
    public static function getClassTags(ReflectionClass $class) {
        $tags = array();
        $comment = $class->getDocComment();
        if(isset($comment)) {
            if(preg_match_all("~@([a-zA-Z0-9_-]+)(?:\\s+(.*))?~m", $comment, $matches)) {
                foreach($matches[1] as $index => $key) {
                    self::appendTag($tags, $key, $matches[2][$index]);
                }
            }
        }
        return $tags;
    }

    /**
     * @param  ReflectionFunctionAbstract $method
     * @return array
     * @author mregner
     */
    public static function getMethodTags( ReflectionFunctionAbstract $method) {
        $tags = array();
        $comment = $method->getDocComment();
        if(isset($comment)) {
            if(preg_match_all("~@param ([a-zA-Z_]+)\\s+(\\$[a-z0-9_]+)~", $comment, $matches)) {
                $tags['param'] = array();
                foreach($matches[1] as $index => $key) {
                    $tags['param'][trim($matches[2][$index])] = $key;
                }
            }
            if(preg_match_all("~@([a-zA-Z0-9_-]+)(?: (.*))?~m", $comment, $matches)) {
                foreach($matches[1] as $index => $key) {
                    if($key != 'param') {
                        self::appendTag($tags, $key, $matches[2][$index]);
                    }
                }
            }
        }
        return $tags;
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @return array
     * @author mregner
     */
    public static function getMethodParameter(ReflectionFunctionAbstract $method) {
        $methodParameter = array();

        $parameters = $method->getParameters();
        if(count($parameters) > 0) {
            foreach($parameters as $parameter) {
                $parameterName = $parameter->getName();
                $methodParameter[$parameterName] = array();
                ($parameter->isOptional()) && ($methodParameter[$parameterName]['optional']=true);
                ($parameter->allowsNull()) && ($methodParameter[$parameterName]['allows_null']=true);
                ($parameter->isArray()) && ($methodParameter[$parameterName]['is_array']=true);
                ($parameter->isPassedByReference()) && ($methodParameter[$parameterName]['by_reference']=true);
                ($parameter->isDefaultValueAvailable()) && ($methodParameter[$parameterName]['default']=$parameter->getDefaultValue());
            }
        }
        return $methodParameter;
    }

    /**
     * Ermittelt den Sourcecode der gegebenen Metode.
     *
     * @param ReflectionFunctionAbstract $method
     * @return string
     * @author mregner
     */
    public static function getMethodSource(ReflectionFunctionAbstract $method) {
        $path = $method->getFileName();
        $lines = @file( $path );
        $from = $method->getStartLine();
        $to   = $method->getEndLine();
        $len  = $to-$from+1;
        return implode( array_slice( $lines, $from-1, $len ));
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @return mixed
     * @author mregner
     */
    public static function getMethodBody(ReflectionFunctionAbstract $method) {
        $methodSource = self::getMethodSource($method);
        $body = preg_replace("~^[^{]+?{|}[\s]+\$~", "", $methodSource);
        return $body;
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @return string
     */
    public static function getMethodSignature(ReflectionFunctionAbstract $method) {
        $path = $method->getFileName();
        $lines = @file( $path );
        $from = $method->getStartLine();
        return preg_replace("~^\s+|\s*\{.*\$||\s+\$~", "", $lines[$from-1]);
    }

    /**
     * @author mregner
     */
    public static function validateMethodSignature(ReflectionFunctionAbstract $method) {
        $methodTags = self::getMethodTags($method);
        $methodParemeters = self::getMethodParameter($method);

        $paramTags = array();
        if(isset($methodTags['param'])) {
            $paramTags = $methodTags['param'];
        }

        $missingParamTags = array();
        foreach($methodParemeters as $parameter => $definition) {
            if(!isset($paramTags["\${$parameter}"])) {
                $missingParamTags[] = $parameter;
            }
        }

        if(!empty($missingParamTags)) {
            throw new Exception("Missing @param tags on {$method->getName()}' for parameters: " . implode(",", $missingParamTags) . ". Or check your spelling!");
        }
    }

    /**
     * @param ReflectionFunctionAbstract $method
     * @return mixed
     * @author mregner
     */
    public static function getMethodParamList(ReflectionFunctionAbstract $method) {
        $signature = self::getMethodSignature($method);
        preg_match_all("~(\\$[a-zA-Z0-9_]+)~", $signature, $matches);
        return $matches[1];
    }

    /**
     * Erzeugt ein Array mit den Methodennamen als key und dem jeweiligen
     * Sourcecode als value.
     *
     * @param ReflectionClass $reflection_class
     * @return array
     * @author mregner
     */
    public static function getMethodsSources (ReflectionClass $reflection_class) {
        $methodsSources = array();

        $methods = $reflection_class->getMethods();
        foreach($methods as $method) {
            if($method->getDeclaringClass()->getName() === $reflection_class->getName()) {
                $methodsSources[$method->getName()] = self::getMethodSource($method);
            }
        }

        return $methodsSources;
    }

    /**
     * Erzeugt ein Array, das alle Definitionen der Membervariablen der gegebenen Klasse
     * enthaelt.
     *
     * @param ReflectionClass $reflection_class
     * @return array
     * @author mregner
     */
    public static function getPropertyDefinitions ( ReflectionClass $reflection_class) {
        $propertyDefinitions = array();

        $classSource = file_get_contents($reflection_class->getFileName());
        $pattern = "~(public|protected|private)\s+(?:(static)\s+)?(\\$([a-zA-Z_0-9]+))(?:\s*=\s*([^;]*))?;~m";

        if(preg_match_all($pattern, $classSource, $matches)) {
            foreach($matches[0] as $index => $definition) {
                $name = $matches[4][$index];
                $propertyDefinitions[$name] = $definition;
            }
        }

        return $propertyDefinitions;
    }

    /**
     * Erzeugt ein Array mit allen Kostanten der gegeben Klasse.
     *
     * @param ReflectionClass $reflection_class
     * @return array
     * @author mregner
     */
    public static function getConstants ( ReflectionClass $reflection_class) {
        $constantDefinitions = array();

        $classSource = file_get_contents($reflection_class->getFileName());
        $pattern = "~(const)\s+([a-zA-Z_0-9]+)(?:\s*=\s*([^;]*))?;~m";

        if(preg_match_all($pattern, $classSource, $matches)) {
            foreach($matches[0] as $index => $definition) {
                $name = $matches[2][$index];
                $constantDefinitions[$name] = $matches[3][$index];
            }
        }

        return $constantDefinitions;
    }

    /**
     * Ermittelt die von der Klasse benoetigten Sourcen die per include eingebunden werden.
     *
     * @param ReflectionClass $reflection_class
     * @return array
     * @author mregner
     */
    public static function getRequiredLibs ( ReflectionClass $reflection_class) {
        $requiredLibs = array();

        $classSource = file_get_contents($reflection_class->getFileName());
        $pattern = "~(?:(?:require_once|include_once)\s*\(([a-zA-Z0-9_'\"./ -]+)\))~";

        if(preg_match_all($pattern, $classSource, $matches)) {
            foreach($matches[1] as $libpath) {
                $requiredLibs[$libpath] = $libpath;
            }
        }
        return $requiredLibs;
    }

    /**
     * Ermittelt die in der Klassendatei definierten globalen Konstanten.
     *
     * @param ReflectionClass $reflection_class
     * @return string
     * @author mregner
     */
    public static function getDefines (ReflectionClass $reflection_class) {
        $defines = array();

        $classSource = file_get_contents($reflection_class->getFileName());
        $pattern = "~define\s*\('([^']+)',(.*)\);~";

        if(preg_match_all($pattern, $classSource, $matches)) {
            foreach($matches[1] as $index => $define) {
                $value = trim($matches[2][$index]);
                $defines[$define] = $value;
            }
        }
        return $defines;
    }

    /**
     * Ermittlelt die Sourcen von in der Klassendatei zusaetzlich definierten Klassen.
     *
     * @param ReflectionClass $reflection_class
     * @return array
     * @author mregner
     */
    public static function getAdditionalClasses (ReflectionClass $reflection_class) {
        $additionalClasses = array();

        $classSource = file_get_contents($reflection_class->getFileName());
        $pattern = "~^(?<!//)\s*class\s+([a-zA-Z_]+)(?:\s+extends\s+([a-zA-Z_]+))?\s*{}?\s*$~mi";
        if(preg_match_all($pattern, $classSource, $matches)) {
            foreach($matches[1] as $index => $className) {
                if($className != $reflection_class->getName()) {
                    $addClassReflection = self::getReflectionClass($className);
                    $additionalClasses[$className] = array(
                        'parent' => $matches[2][$index],
                        'source' => self::getReflectionClassSource($addClassReflection),
                    );
                }
            }
        }
        return $additionalClasses;
    }

    /**
     * Gibt den Sourcecode der gegeben Klasse zurueck.
     *
     * @param ReflectionClass $reflection_class
     * @return string
     * @author mregner
     */
    public static function getReflectionClassSource(ReflectionClass $reflection_class) {
        $path = $reflection_class->getFileName();
        $lines = @file( $path );
        $from = $reflection_class->getStartLine();
        $to   = $reflection_class->getEndLine();
        $len  = $to-$from+1;
        return implode( array_slice( $lines, $from-1, $len ));
    }

    /**
     * @param object $object
     * @return number
     * @author mregner
     */
    public static function class2ScalarHash($object) {
        $class = new ReflectionObject($object);
        $name = $class->getName();
        $nameHash = md5($name);
        $length1 = strlen($name);
        $length2 = strlen($nameHash);

        $strHexValue2 = bin2hex($nameHash);

        $strIntValue = $strHexValue2 % ($length1 * $length2);

        return $strIntValue;
    }
}
?>