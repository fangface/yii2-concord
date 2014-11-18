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

namespace fangface\concord\helpers;

/**
 * Html provides a set of static methods for generating commonly used HTML tags.
 */
class Html extends \yii\helpers\Html
{

    /**
     * (non-PHPdoc)
     * @see \yii\helpers\BaseHtml::activeCheckbox($model, $attribute, $options = [])
     */
    public static function activeCheckbox($model, $attribute, $options = [])
    {
        $name = isset($options['name']) ? $options['name'] : static::getInputName($model, $attribute);
        $value = static::getAttributeValue($model, $attribute);

        if (!array_key_exists('value', $options)) {
            $options['value'] = '1';
        }

        if (!array_key_exists('disabled', $options) || (array_key_exists('disabled', $options) && !$options['disabled'])) {
            if (!array_key_exists('uncheck', $options)) {
                $options['uncheck'] = '0';
            }
        }

        $checked = "$value" === "{$options['value']}";

        if (!array_key_exists('id', $options)) {
            $options['id'] = static::getInputId($model, $attribute);
        }

        return static::checkbox($name, $checked, $options);
    }
}
