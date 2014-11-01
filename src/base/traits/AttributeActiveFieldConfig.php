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

trait AttributeActiveFieldConfig
{
    /**
     * Get the default active field config for an attribute
     *
     * @param string $attribute Attribute name
     * @return array
     */
    public function getActiveFieldSettings($attribute)
    {
        $settings = false;
        $attributeDefaults = $this->attributeActiveFieldSettings();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $settings = $attributeDefaults[$attribute];
        } else {
            $settings = $this->getAttributeConfig($attribute, 'active');
        }
        return ($settings ? $settings : []);
    }

    /**
     * Get array of attributes and their default active field config
     *
     * @return array
     */
    public function attributeActiveFieldSettings()
    {
        return [
        ];
    }

}
