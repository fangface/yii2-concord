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

namespace fangface\grid;

class DataColumn extends \yii\grid\DataColumn
{
    /**
     * @var string tailor output based on the grid type
     */
    public $gridType = 'default';

    /**
     * @var array the HTML attributes for the filter input fields. This property is used in combination with
     * the [[filter]] property. When [[filter]] is not set or is an array, this property will be used to
     * render the HTML attributes for the generated filter input fields.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $filterInputOptions = ['placeholder' => 'Filter', 'prompt' => 'Select...', 'class' => 'form-control form-filter input-sm', 'id' => null];

    /**
     * @var array extra HTML attributes to be added to $filterInputOptions, usefull when the default $filterInputOptions
     * are otherwise required
     */
    public $filterInputOptionsExtra = [];


    /**
     * (non-PHPdoc)
     * @see \yii\base\Object::init()
     */
    public function init()
    {
        if ($this->filter !== null && is_array($this->filter) && $this->filter) {
            unset($this->filterInputOptions['placeholder']);
        } else {
            unset($this->filterInputOptions['prompt']);
        }
        if ($this->filterInputOptionsExtra) {
            $this->filterInputOptions = array_merge($this->filterInputOptions, $this->filterInputOptionsExtra);
        }
        parent::init();
    }

}
