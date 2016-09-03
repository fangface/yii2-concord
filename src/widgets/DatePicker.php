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

namespace fangface\widgets;

use backend\assets\BootstrapDatePickerAsset;
use fangface\widgets\InputWidget;
use fangface\helpers\Html;


/**
 * Date Picker widget
 */
class DatePicker extends InputWidget
{

    /**
     * @var string the name of the jQuery plugin
     */
    public $pluginName = 'datepicker';
    /**
     * @var array default widget plugin options that user pluginOptions will be merged into
     */
    public $defaultPluginOptions = ['format' => 'yyyy-mm-dd', 'todayBtn' => 'linked', 'clearBtn' => true, 'autoclose' => true, 'weekStart' => 1];
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-control input-small'];
    /**
     * @var string element id to use when calling the datepicker
     */
    public $elementId = null;

    /**
     * Renders the color picker widget
     */
    protected function renderWidget()
    {
        $this->prepareInput();
        $this->registerAssets();
        $this->prepareTemplate();
        echo $this->renderTemplate();
    }

    /**
     * Prepare the input fields for the input
     *
     * @return void
     */
    protected function prepareInput()
    {
        if ($this->hasModel()) {
            if (!isset($this->options['value']) && $this->model->{$this->attribute} == '0000-00-00') {
                $this->options['value'] = '';
            }
            $this->sections['input'] = Html::activeTextInput($this->model, $this->attribute, $this->options);
        } else {
            $this->sections['input'] = Html::textInput($this->name, ($this->value == '0000-00-00' ? '' : $this->value), $this->options);
        }
    }

    /**
     * Registers the needed client assets
     *
     * @return void
     */
    public function registerAssets()
    {
        if ($this->disabled) {
            return;
        }
        $view = $this->getView();
        BootstrapDatePickerAsset::register($view);
        if ($this->elementId !== null) {
            $element = "jQuery('#" . $this->elementId . "')";
        } else {
            $element = "jQuery('#" . $this->options['id'] . "')";
        }
        $this->registerPlugin($this->pluginName, $element);
    }

}
