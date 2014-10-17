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

namespace Concord\Base\Traits;

trait AttributeIcons
{
    /**
     * Get the default icon to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @return string
     */
    public function getIcon($attribute)
    {
        $message = '';
        $attributeDefaults = $this->attributeIcons();
        if (isset($attributeDefaults[$attribute])) {
            $message = $attributeDefaults[$attribute];
        }
        return $message;
    }

    /**
     * Get array of attributes and what the default icon should be associated with the attribute in forms
     *
     * @return array
     */
    public function attributeIcons()
    {
        return [
        ];
    }

}
