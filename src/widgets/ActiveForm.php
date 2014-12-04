<?php
/**
 * This file is part of the fangface/yii2-widgets package
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 *
 * @package fangface/yii2-widgets
 * @author Fangface <dev@fangface.net>
 * @copyright Copyright (c) 2014 Fangface <dev@fangface.net>
 * @license https://github.com/fangface/yii2-widgets/blob/master/LICENSE.md MIT License
 *
 */

namespace fangface\widgets;

use fangface\widgets\ActiveField;

class ActiveForm extends \kartik\form\ActiveForm
{

    const DEFAULT_LABEL_SPAN = 3;

    /**
     * @var boolean should hint block content be taken from model where supported
     */
    public $autoHint = true;

    /**
     * @var boolean should icon prepend content be taken from model where supported
     */
    public $autoIcon = true;

    /**
     * @var boolean should placeholder text be taken from model where supported
     */
    public $autoPlaceholderText = true;

    /**
     * @var boolean whether to attempt a tidy up of some html output to aid review and debug during development
     */
    public $devTidy = false;

    /**
     * @var boolean whether fields should all be treated as edit locked
     */
    public $editLocked = false;

    /**
     * @var array the default form configuration
     */
    private $_config = [
        self::TYPE_VERTICAL => [
            'labelSpan' => self::NOT_SET, // must be between 1 and 12
            'deviceSize' => self::NOT_SET, // must be one of the SIZE modifiers
            'showLabels' => true, // show or hide labels (mainly useful for inline type form)
            'showErrors' => true, // show or hide errors (mainly useful for inline type form)
        ],
        self::TYPE_HORIZONTAL => [
            'labelSpan' => self::DEFAULT_LABEL_SPAN,
            'deviceSize' => self::SIZE_MEDIUM,
            'showLabels' => true,
            'showErrors' => true,
        ],
        self::TYPE_INLINE => [
            'labelSpan' => self::NOT_SET,
            'deviceSize' => self::NOT_SET,
            'showLabels' => false,
            'showErrors' => false,
        ],
    ];

    /**
     * (non-PHPdoc)
     * @see \kartik\form\ActiveForm::init()
     */
    public function init()
    {
        parent::init();
        if ($this->type == self::TYPE_HORIZONTAL) {
            // prevent hint-block and help-block from adding a div wrapper to deal with offset
            $this->setOffsetCss(self::NOT_SET);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \kartik\form\ActiveForm::initForm()
     */
    public function initForm()
    {
        if (!isset($this->fieldConfig['class'])) {
            $this->fieldConfig['class'] = ActiveField::className();
        }
        if (!isset($this->fieldConfig['devTidy'])) {
            $this->fieldConfig['devTidy'] = $this->devTidy;
        }
        if (!isset($this->type) || strlen($this->type) == 0) {
            $this->type = self::TYPE_HORIZONTAL;
        }
        if ($this->type == self::TYPE_HORIZONTAL && !isset($this->formConfig['labelSpan'])) {
            $this->formConfig['labelSpan'] = self::DEFAULT_LABEL_SPAN;
        }
        parent::initForm();
    }

    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
    }
}
