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

use yii\validators\DateValidator as YiiDateValidator;
use fangface\Tools;

/**
 * DateValidator extends default to allow for non validation of empty dates
 *
 * @author Fangface
 */
class DateValidator extends YiiDateValidator
{
    /**
     * Checks if the given value is empty.
     * A value is considered empty if it is null, an empty array, or an empty string.
     * We also treat some dates e.g. 0000-00-00 as empty
     * Note that this method is different from PHP empty(). It will return false when the value is 0.
     * @param mixed $value the value to be checked
     * @return boolean whether the value is empty
     */
    public function isEmpty($value)
    {
        if ($this->isEmpty !== null) {
            return call_user_func($this->isEmpty, $value);
        } else {
            return $value === null || $value === [] || $value === '' || $value === Tools::DATE_TIME_DB_EMPTY || $value === Tools::DATE_DB_EMPTY;
        }
    }

}
