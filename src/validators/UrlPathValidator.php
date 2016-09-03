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

use fangface\validators\AlphaValidator;

/**
 * AlphaValidator validates that the attribute value is a alpha/alphanumeric string.
 *
 * @author Nicola Puddu (based on yii1 version at http://www.yiiframework.com/extension/alpha/)
 * @author Fangface
 */
class UrlPathValidator extends AlphaValidator
{
    /**
     * @var int maximum number of characters to validate the string
     */
    public $maxChars = 255;
    /**
     * @var boolean
     */
    public $allowNumbers = true;
    /**
     * @var boolean
     */
    public $allowMinus = true;
    /**
     * @var boolean
     */
    public $allowUnderscore = true;
    /**
     * @var boolean
     */
    public $allowDot = true;
    /**
     * @var boolean first character of string must be forward slash
     */
    public $forceChar1Slash = true;
    /**
     * @var array list of additional characters allowed
     */
    public $extra = array('/');


    /**
     * @inheritdoc
     */
    public function validateAttribute($object, $attribute)
    {
        /* @var \yii\base\Model $object */
        // validate the string
        $value = $object->$attribute;
        if ($this->skipOnEmpty && empty($value)) {
            return;
        }

        parent::validateAttribute($object, $attribute);

        if ($object->hasErrors($attribute)) {
            return;
        }
    }
}
