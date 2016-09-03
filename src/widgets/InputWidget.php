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

use fangface\base\traits\InputAddonTrait;
use fangface\forms\ActiveForm;
use fangface\forms\ActiveField;
use fangface\widgets\WidgetTrait;
use yii\widgets\ActiveField as YiiActiveField;
use yii\widgets\InputWidget as YiiInputWidget;


/**
 * Form Builder
 */
class InputWidget extends YiiInputWidget
{
    use WidgetTrait;
    use InputAddonTrait;

    /**
     * @var ActiveForm
     */
    public $form;
    /**
     * @var boolean is the input disabled, can be used to prevent registered assets for example
     */
    public $disabled = false;
    /**
     * @var array the HTML attributes for the input tag.
     */
    public $options = ['class' => 'form-control'];


    /**
     * {@inheritDoc}
     * @see \yii\base\Widget::run()
     */
    public function run()
    {
        if (isset($this->options['disabled'])) {
            $this->disabled = true;
        }
        $this->applyPluginSettings();
        parent::run();
        if (method_exists($this, 'renderWidget')) {
            $this->renderWidget();
        }
    }

    /**
     * Prepare the template
     */
    public function prepareTemplate()
    {
        $this->template = strtr($this->template, [
            '{input}' => $this->generateAddon(),
        ]);
    }

    /**
     * Renders all sections using the current template [[template]].
     * @return string the full rendering result
     */
    protected function renderTemplate()
    {
        return $this->findSectionsToRender($this->template);
    }

}