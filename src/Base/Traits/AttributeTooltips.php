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

use yii\helpers\Html;

trait AttributeTooltips
{
    /**
     * Get the default tooltip text to use for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getTooltip($attribute, $encode = false)
    {
        $message = '';
        $attributeDefaults = $this->attributeTooltips();
        if (isset($attributeDefaults[$attribute])) {
            $message = ($encode ? Html::encode($attributeDefaults[$attribute]) : $attributeDefaults[$attribute]);
        }
        return $message;
    }

    /**
     * Get array of attributes and what the default tooltip should be
     *
     * @return array
     */
    public function attributeTooltips()
    {
        return [
        ];
    }

}
