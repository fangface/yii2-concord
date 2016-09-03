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

use backend\assets\MultiSelectAsset;
use fangface\widgets\InputWidget;
use fangface\helpers\Html;
use yii\helpers\ArrayHelper;


/**
 * Jquery Multi Select Widget
 */
class MultiSelect extends InputWidget
{

    /**
     * @var string the name of the jQuery plugin
     */
    public $pluginName = 'multiSelect';
    /**
     * @var array default widget plugin options that user pluginOptions will be merged into
     */
    public $defaultPluginOptions = [];
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-control'];
    /**
     * @var array the items to appear in the drop down list
     */
    public $items = null;


    /**
     * Renders the color picker widget
     */
    protected function renderWidget()
    {
        if (!isset($this->options['multiple']) || !$this->options['multiple']) {
            $this->options['multiple'] = 'multiple';
        }

        $this->prepareInput();
        $this->registerAssets();
        $this->prepareTemplate();
        echo $this->renderTemplate();
    }

    /**
     * Prepare the input
     *
     * @return string
     */
    protected function prepareInput()
    {
        if ($this->hasModel()) {
            $this->sections['input'] = Html::activeDropDownList($this->model, $this->attribute, $this->items, $this->options);
        } else {
            $this->sections['input'] = Html::dropDownList($this->model, $this->attribute, $this->items, $this->options);
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
        MultiSelectAsset::register($view);
        $element = "jQuery('#" . $this->options['id'] . "')";
        $this->registerPlugin($this->pluginName, $element);
    }

}