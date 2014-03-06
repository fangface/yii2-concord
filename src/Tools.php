<?php
/**
 * This file is part of the fangface/yii2-concord package
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 *
 * @package fangface/yii2-concord
 * @author Fangface <dev@fangface.net>
 * @copyright Copyright (c) 2014 Fangface <dev@fangface.net>
 * @license https://github.com/fangface/yii2-concord/blob/master/LICENSE.md MIT License
 *
 */

namespace Concord;

use Concord\Tools;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class Tools
{

    use \Concord\Base\Traits\Singleton;
    use \Concord\Base\Traits\ServiceGetterStatic;

    const DATE_TIME_DB_EMPTY = '0000-00-00 00:00:00';
    const DATE_DB_EMPTY = '0000-00-00';

    const DATETIME_DATABASE = 'Y-m-d H:i:s';
    const DATE_DATABASE = 'Y-m-d';

    /*
     * Returns the current active client Id @return integer
     *
     * @return integer
     */
    public static function getClientId()
    {
        $client = static::getService('client');
        if ($client && $client instanceof \Concord\Models\Db\Client) {
            return $client->id;
        }
        return 0;
    }


    /*
     * Returns the current active client code @return string
     *
     * @return string
     */
    public static function getClientCode()
    {
        $client = static::getService('client');
        if ($client && $client instanceof \Concord\Models\Db\Client) {
            return $client->clientCode;
        }
        return '';
    }

    /**
     * Determine if a value is a closure object
     * @param mixed $t
     * @return boolean
     */
    public static function is_closure($t) {
        return is_object($t) && ($t instanceof \Closure);
    }


    /**
     * Return a class name with namespace removed
     *
     * @param object $object
     * @return string
     */
    public static function getClassName($object = null)
    {
        if (! is_object($object) && ! is_string($object)) {
            return false;
        }

        $class = explode('\\', (is_string($object) ? $object : get_class($object)));
        $str = $class[count($class) - 1];

        return $str;
    }


    /**
     * Determine default table name for an ActiveRecord class
     * The default method will return the table name the same as the class name with the first character
     * converted to lower case and 's' or 'ies' added to the end of the table name if it is not already
     * The camel method returns the class name as the table name by calling [[Inflector::camel2id()]]
     * For example, 'Customer' becomes 'customer', and 'OrderItem' becomes'order_item'.
     * @param \Concord\Db\ActiveRecord $object
     * @return string
     */

    public static function getDefaultTableNameFromClass($object = null)
    {
        $str = self::getClassName($object);

        $method = 'default';

        switch ($method) {
          case 'yii':
              $tableName = 'tbl_' . Inflector::camel2id($str, '_');
              break;

          case 'camel':
              $tableName = Inflector::camel2id($str, '_');
                $tableName = Inflector::pluralize($tableName);
              break;

          default:
                $tableName = lcfirst($str);
                $tableName = Inflector::pluralize($tableName);
              break;
        }

        return $tableName;
    }


    /**
     * Return a class name with namespace cleaned of characters not required
     * Typically used when the class name needs to be used as a key for anything
     *
     * @param object $object
     * @return string
     */
    public static function getCleanClassNameWithNamespace($object)
    {
        if (!is_object($object) && !is_string($object)) {
            return false;
        }
        $className = (is_string($object) ? $object : get_class($object));
        $className = strtolower(strtr($className, array(
            '-' => '',
            '_' => '',
            ' ' => '',
            '\\' => '',
            '/' => ''
        )));
        return $className;
    }


    /**
     * Converts a size in bytes to something human readable.
     */
    public static function getHumanBytes($bytes, $precision = 2)
    {
        $unit = array(
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
            'PB',
            'EB'
        );

        if (! $bytes) {
            return "0 B";
        }

        return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision) . ' ' . $unit[$i];
    }


    /**
     * Chcek if two values are numerically the same.
     * Casts the
     * arguments as integers to type check.
     *
     * @param integer $arg_1
     * @param integer $arg_2
     * @return boolean
     */
    public static function intEquals($arg_1, $arg_2)
    {
        return (int) $arg_1 === (int) $arg_2;
    }


    /**
     * Chcek if two values are equal string.
     * Casts the
     * arguments as strings to type check.
     *
     * @param string $arg_1
     * @param string $arg_2
     * @return boolean
     */
    public static function strEquals($arg_1, $arg_2)
    {
        return "" . $arg_1 === "" . $arg_2;
    }


    /**
     * Returns the indexed element for the mixed object.
     * You can specify
     * a default value to return in $default and specify if you want to
     * just find out of the index exists ($check_index_exists). The latter
     * will return a boolean.
     *
     * @param mixed $object
     * @param string $index
     * @param mixed $default
     * @param boolean $check_index_exists
     * @return boolean or mixed
     */
    public static function get($object, $index, $default = FALSE, $check_index_exists = FALSE)
    {
        if (is_array($object)) {
            if (isset($object[$index])) {
                return ($check_index_exists) ? TRUE : $object[$index];
            }
        } else {
            if (isset($object->$index)) {
                return ($check_index_exists) ? TRUE : $object->$index;
            }
        }

        return $default;
    }


    /**
     * Checks if the variable is of the specified type and a valid value
     *
     * @param mixed $mixed
     * @param string $expected_type
     * @return boolean
     */
    public static function isValid($mixed, $expected_type = INT)
    {
        if (is_numeric($mixed) || $expected_type === INT) {
            return is_numeric($mixed) && strlen($mixed) && (int) $mixed > 0;
        } elseif ($expected_type === STRING) {
            return is_string($mixed) && strlen($mixed) > 0;
        } elseif ($expected_type === DATE) {
            // check if date is of form YYYY-MM-DD HH:MM:SS and that it
            // is not 0000-00-00 00:00:00.
            //
            if (strlen($mixed) === 19 && !\Common\Tools::strEqual($mixed, '0000-00-00 00:00:00')) {
                return TRUE;
            }

            // check for MM/DD/YYYY type dates
            //
            $parts = explode("/", $mixed);

            return count($parts) === 3 && checkdate($parts[0], $parts[1], $parts[2]);
        } elseif ($expected_type === OBJECT) {
            // iterate through object and check if there are any properties
            //
            foreach ($mixed as $property) {
                if ($property) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }


    /**
     * List the services currently specified in the service manager
     * (useful for debug)
     * @param string $lineBreak (default is "\n")
     */
    public static function listServices($lineBreak = "\n") {
        print '<hr/>';
        $services = self::getDI()->getServices();
        foreach ($services as $service => $serviceDefinition) {
            print 'Service: ' . $service . $lineBreak;
        }
        print '<hr/>';
    }


    /**
     *
     * @param number|string|boolean|null $value
     * @param string|array $dataType type of attribute or an array of values specifying the column schema
     * @param integer $length number of characters for the attribute within the db table [OPTIONAL] default 0
     * @param integer $decimals how many decimal characters exist for this attribute within the db table [OPTIONAL] default 0
     * @param boolean $unsigned is the atribute unsigned within the db table [OPTIONAL] default false
     * @param boolean $zerofill is the attribute zerofilled within the db table [OPTIONAL] default false
     * @param boolean $isNullable is the attribute nullable within the db table [OPTIONAL] default false
     * @param boolean $autoIncrement is the attribute auto incrementing within the db table [OPTIONAL] default false
     * @param boolean $primaryKey is the attribute the primary key within the table [OPTIONAL] default false
     * @param mixed $defaultValue default value [OPTIONAL] default ''
     * @return number|string|boolean|null
     */
    public static function formatAttributeValue($value, $dataType, $length = 0, $decimals = 0, $unsigned = false, $zerofill = false, $isNullable = false, $autoIncrement = false, $primaryKey = false, $defaultValue = '')
    {

        if (is_array($dataType) || is_object($dataType)) {
            // assume parameters are being passed in the array
            $options = $dataType;
            if (is_object($dataType)) {
                // Yii schema object format
                $dataType = strtolower($options->type);
                $length = ($options->size ? $options->size : 0);
                $decimals = $options->precision;
                $unsigned = $options->unsigned;
                $zerofill = (strpos($options->dbType, 'zerofill') !== false ? true : false);
                $isNullable = $options->allowNull;
                $autoIncrement = $options->autoIncrement;
                $primaryKey = $options->isPrimaryKey;
                $defaultValue = $options->defaultValue;
            } elseif (isset($options['characterMaximumLength'])) {
                // Zend schema format
                $dataType = strtolower($options['dataType']);
                $length = (is_null($options['characterMaximumLength']) ? (is_null($options['numericPrecision']) ? 0 : $options['numericPrecision']) : $options['characterMaximumLength']);
                $decimals = $options['numericScale'];
                $unsigned = $options['numericUnsigned'];
                $zerofill = $options['zeroFill'];
                $isNullable = $options['isNullable'];
                $autoIncrement = $options['autoIncrement'];
                $primaryKey = $options['isPrimaryKey'];
                $defaultValue = $options['defaultValue'];
            } elseif (isset($options['phpType'])) {
                // Yii schema format
                $dataType = strtolower($options['type']);
                $length = ($options['size'] ? $options['size'] : 0);
                $decimals = $options['precision'];
                $unsigned = $options['unsigned'];
                $zerofill = (strpos($options['dbType'], 'zerofill') !== false ? true : false);
                $isNullable = $options['allowNull'];
                $autoIncrement = $options['autoIncrement'];
                $primaryKey = $options['isPrimaryKey'];
                $defaultValue = $options['defaultValue'];
            } else {
                // EAV definitions format
                $dataType = strtolower($options['dataType']);
                $length = $options['length'];
                $decimals = $options['decimals'];
                $unsigned = $options['unsigned'];
                $zerofill = $options['zerofill'];
                $isNullable = $options['isNullable'];
                $autoIncrement = false;
                $primaryKey = false;
                $defaultValue = $options['defaultValue'];
            }

        } else {
            $dataType = strtolower($dataType);
        }

        if ($dataType == 'char' && $length == 0) {
            $dataType = 'longtext';
        }

        if ($value == '__DEFAULT__') {
            if (is_null($defaultValue) && $isNullable) {
                $value = null;
            } elseif ($autoIncrement && $primaryKey) {
                $value = 0;
            } else {
                $value = $defaultValue;
            }
        }

        if ($isNullable && ($value == '__NULL__' || is_null($value))) {

            $value = null;

        } else {

            switch ($dataType) {

                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'bigint':
                case 'serial':
                case 'numeric':
                case 'int':
                case 'timestamp':
                    if ($value == '' || $value == '__NULL__') {
                        $value = 0;
                    }
                    if ($zerofill) {
                        if ($value < 0) {
                            $value = '-' . str_pad(str_replace('-', '', $value), $length, "0", STR_PAD_LEFT);
                        } else {
                            $value = str_pad($value, $length, "0", STR_PAD_LEFT);
                        }
                    } else {
                        $value = (int) $value;
                    }
                    return $value;

                case 'char':
                case 'longtext':
                case 'blob':
                case 'longblob':
                case 'enum':
                case 'string':
                    if ($value == '__NULL__') {
                        return ((string) '');
                    } else {
                        return ((string) $value);
                    }

                case 'dec':
                case 'double':
                case 'numeric':
                case 'fixed':
                case 'float':
                case 'decimal':
                    if ($value == '' || $value == '__NULL__') {
                        $value = 0;
                    }
                    $value = number_format($value, $decimals, '.', '');
                    if ($zerofill) {
                        if ($value < 0) {
                            $value = '-' . str_pad(str_replace('-', '', $value), ($length + 1), "0", STR_PAD_LEFT);
                        } else {
                            $value = str_pad($value, ($length + 1), "0", STR_PAD_LEFT);
                        }
                    }
                    return $value;

                case 'bool':
                case 'boolean':
                    if (is_bool($value)) {
                        return (($value ? true : false));
                    } elseif (is_string($value)) {
                        $valueX = strtoupper(substr(trim($value), 0, 1));
                        return (($valueX == 'N' || $value == '0' || $value == '_' || ($value == 'O' && strtoupper($value) != 'ON') || $value == '' ? false : true));
                    } else {
                        return (($value ? true : false));
                    }

                case 'date':
                    return ($value != '' && $value != '0000-00-00' && strtotime($value) ? $value : '0000-00-00');
                case 'datetime':
                    return ($value != '' && $value != '0000-00-00 00:00:00' && strtotime($value) ? $value : '0000-00-00 00:00:00');
                default:
                    return $value;
            }
        }
    }


    /**
     * Generate a random string to the specified length and optionally exclude often
     * mis-read characters
     *
     * @param integer $len number of characters to generate
     * @param boolean $excludeEasyMisRead [OPTIONAL] exclude typically mis-ead characters, default false
     * @return string
     */
    public static function randomString($len, $excludeEasyMisRead = false)
    {
        $pass = '';
        $lchar = 0;
        $char = 0;
        for ($i = 0; $i < $len; $i ++) {
            $charOK = false;
            while ($char == $lchar || ! $charOK) {
                $char = rand(48, 109);
                if ($char > 57)
                    $char += 7;
                if ($char > 90)
                    $char += 6;
                if ($excludeEasyMisRead) {
                    // we want to exclude characters that can and do often get mis-read by the user
                    switch ($char) {

                        case 49: // 1
                        case 73: // I
                        case 105: // i

                        case 48: // 0
                        case 79: // O
                        case 111: // o

                        case 53: // 5
                        case 83: // S
                        case 115: // s

                            break;
                        default:
                            $charOK = true;
                            break;
                    }
                } else {
                    $charOK = true;
                }
            }
            $pass .= chr($char);
            $lchar = $char;
        }
        return $pass;
    }


    /**
     * Translates a camel case string into a string with
     * underscores (e.g. firstName -> first_name)
     *
     * @param string $str String to convert
     * @return string $str translated from camel case
     */
    public static function unCamelCase($str) {
        return Inflector::camel2id(StringHelper::basename($str), '_');
    }


    /**
     * Translates a string with underscores
     * into camel case (e.g. first_name -> firstName)
     *
     * @param string $str String to convert
     * @param bool $capitaliseFirst If true, capitalise the first char in $str
     * @return string $str translated into camel case
     */
    function camelCase($str, $capitaliseFirst = false) {
        if ($capitaliseFirst) {
            $str = Inflector::camelize($str);
        } else {
            $str = Inflector::variablize($str);
        }
        return $str;
    }




    /**
     * Debug helper function.  This is a wrapper for var_dump() that adds
     * the <pre /> tags, cleans up newlines and indents, and runs
     * htmlentities() before output.
     *
     * @param  mixed  $var   The variable to dump.
     * @param  string $label OPTIONAL Label to prepend to output.
     * @param  bool   $echo  OPTIONAL Echo output if true.
     * @return string
     */
    public static function debug($var, $label = null, $echo = true)
    {
        // format the label
        $label = ($label===null) ? '' : trim($label) . ' ';

        // var_dump the variable into a buffer and keep the output
        ob_start();
        var_dump($var);
        $output = ob_get_clean();

        // neaten the newlines and indents
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL . $label
            . PHP_EOL . $output
            . PHP_EOL;
        } else {
            $output = \yii\helpers\Html::encode($output);
            $output = '<pre>' . trim($label . $output) . '</pre>';
        }

        if ($echo) {
            echo $output;
        }
        return $output;
    }

    /*
     * Returns a safe string representing the specified db connection Dsn
     * suitable for using as a cache key for data hooked to the connection
     *
     * @param \yii\db\Connection $connection db connection
     * @param string $name table name
     */
    public static function getDbConnectionCacheKey($connection, $name='')
    {
        return $connection->getSchema()->getCacheKey($name);
    }

}
