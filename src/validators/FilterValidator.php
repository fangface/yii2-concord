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

namespace fangface\validators;

use yii\base\InvalidConfigException;
use yii\validators\Validator;

/**
 * FilterValidator converts the attribute value according to a filter.
 *
 * FilterValidator is actually not a validator but a data processor.
 * It invokes the specified filter callback to process the attribute value
 * and save the processed value back to the attribute. The filter must be
 * a valid PHP callback with the following signature:
 *
 * ~~~
 * function foo($value, $arg1, .. $argN) {...return $newValue; }
 * or
 * function foo($value) {...return $newValue; }
 * ~~~
 *
 * Many PHP functions qualify this signature (e.g. `number_format()` and `substr()`).
 *
 * To specify the filter, set [[filter]] property to be the callback.
 * To specify the arguments, set the [[args]] property array, if just one value then either
 * use the array format of specify the single value as-is.
 *
 * If the method is held within a static class you can also specify [[filterClass]].
 *
 * @author Fangface
 */
class FilterValidator extends Validator
{
    /**
     * @var callable the filter. This can be a global function name, anonymous function, etc.
     * The function signature must be as follows,
     *
     * ~~~
     * function foo($value, $arg1, .. $argN) {...return $newValue; }
     * ~~~
     */
    public $filter;
    /**
     * @var fully qualified class name if the filter is a static method that exists within that class
     */
    public $filterClass;
    /**
     * Array of arguments to pass to the filter function
     * @var array
     */
    public $args;
    /**
     * @var boolean Should function be called with arguments fed in before the value e.g. implode(arg[0],$value)
     */
    public $argsFirst = false;
    /**
     * @var boolean should input be converted to an array before processing
     */
    public $makeArray = false;
    /**
     * @var boolean whether the filter should be skipped if an array input is given.
     * If false and an array input is given, the filter will not be applied.
     */
    public $skipOnArray = false;
    /**
     * @var boolean this property is overwritten to be false so that this validator will
     * be applied when the value being validated is empty.
     */
    public $skipOnEmpty = false;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->filter === null) {
            throw new InvalidConfigException('The "filter" property must be set.');
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($object, $attribute)
    {
        $value = $object->$attribute;
        if ($this->makeArray && !is_array($value)) {
            $value = ($value ? [$value] : []);
        }
        if (!$this->skipOnArray || !is_array($value)) {
            if ($this->args !== null) {
                $arguments = (is_array($this->args) ? $this->args : [$this->args]);
                if ($this->argsFirst) {
                    $arguments[] = $value;
                } else {
                    array_unshift($arguments, $value);
                }
                if ($this->filterClass === null) {
                    $object->$attribute = call_user_func_array($this->filter, $arguments);
                } else {
                    $object->$attribute = call_user_func_array([$this->filterClass, $this->filter], $arguments);
                }
            } else {
                if ($this->filterClass === null) {
                    $object->$attribute = call_user_func($this->filter, $value);
                } else {
                    $object->$attribute = call_user_func([$this->filterClass, $this->filter], $value);
                }
            }
        }
    }
}
