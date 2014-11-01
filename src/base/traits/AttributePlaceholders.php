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

namespace fangface\concord\base\traits;

use yii\helpers\Html;

trait AttributePlaceholders
{
    /**
     * Get the default place holder text to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getPlaceholder($attribute, $encode = false)
    {
        $text = '';
        $attributeDefaults = $this->attributePlaceholders();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $text = $attributeDefaults[$attribute];
        } else {
            $text = $this->getAttributeConfig($attribute, 'placeholder');
        }
        if ($text) {
            $text = ($encode ? Html::encode($text) : $text);
        }
        return $text;
    }

    /**
     * Get array of attributes and what the default help block text should be
     *
     * @return array
     */
    public function attributePlaceholders()
    {
        return [
        ];
    }

}
