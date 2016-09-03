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

namespace fangface\forms;

use fangface\base\traits\InputAddonTrait;
use fangface\helpers\Html;
use fangface\widgets\BootstrapColorPicker;
use fangface\widgets\BootstrapSelect;
use fangface\widgets\BootstrapSelectSplitter;
use fangface\widgets\CKEditor;
use fangface\widgets\DatePicker;
use fangface\widgets\DateTimePicker;
use fangface\widgets\MiniColors;
use fangface\widgets\MultiSelect;
use fangface\widgets\Select2;
use fangface\widgets\TimePicker;
use yii\helpers\ArrayHelper;


class ActiveField extends \yii\widgets\ActiveField
{

    use InputAddonTrait;

    /**
     * @var ActiveForm the form that this field is associated with.
     */
    public $form;


    public function render($content = null)
    {
        if (!isset($this->parts['{hint}']) || $this->parts['{hint}'] === null) {
            $this->template = str_replace("\n{hint}" , '', $this->template);
        }
        $this->prepareTemplate();
        return parent::render($content);
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
     * Renders the closing tag of the field container.
     *
     * @return string the rendering result.
     */
    public function end()
    {
        return Html::endTag(isset($this->options['tag']) ? $this->options['tag'] : 'div') . "\n";
    }


    /**
     * Generates a icon for input.
     *
     * @param array|string|boolean $iconOptions icon options.
     * @param array|string|boolean $tooltipOptions tooltip options.
     * @return static the field object itself
     */
    public function icon($iconOptions = [], $tooltipOptions = [])
    {
        if (($iconOptions !== null && $iconOptions !== null) || ($tooltipOptions !== null && $tooltipOptions !== false)) {

            if (is_bool($iconOptions)) {
                $icon = 'fa fa-cogs';
                $iconOptions = [];
            } elseif (is_string($iconOptions)) {
                $icon = $iconOptions;
                $iconOptions = [];
            } else {
                $icon = ArrayHelper::remove($iconOptions, 'icon', 'fa fa-cogs');
            }

            if ($tooltipOptions !== null) {
                if (is_string($tooltipOptions)) {
                    $tooltipOptions = ['title' => $tooltipOptions];
                }
                $iconOptions['data-container'] = ArrayHelper::getValue($tooltipOptions, 'container', 'body');
                $iconOptions['data-placement'] = ArrayHelper::getValue($tooltipOptions, 'placement', 'top');
                $iconOptions['data-original-title'] = ArrayHelper::getValue($tooltipOptions, 'title', '');
            }


            if ($icon) {
                $position = ArrayHelper::remove($iconOptions, 'position', InputField::ICON_POSITION_LEFT);
                $tooltip = false;
                if (isset($iconOptions['data-original-title'])) {
                    $tooltip = true;
                }
                Html::addCssClass($iconOptions, $icon . ($tooltip ? ' tooltips' : ''));
                $this->addIconAddon($iconOptions, $position);
            }
        }
        return $this;
    }


    /**
     * If supported set the focus to this field once the form is loaded
     *
     * @param boolean $select should contents of field also be selected
     * @return static the field object itself
     */
    public function setFocus($select = false)
    {
        Html::addCssClass($this->inputOptions, 'default-field' . ($select ? '-select' : ''));
        return $this;
    }


    /**
     * Add a clear input option
     *
     * @param string $clearType
     * @param string $clearValue [optional]
     * @param string $groupSize [optional] default input group size
     */
    public function addClearAddOn($clearType = 'input', $clearValue = '', $groupSize = '')
    {
        $iconData = [
            'class' => 'glyphicon glyphicon-remove clickable',
            'title' => 'Click to reset',
            'data-remove-input' => $clearType,
        ];
        if ($clearValue) {
            $iconData['data-remove-value'] = $clearValue;
        }

        $addon = Html::tag('span', Html::tag('i', '', $iconData),
            ['class' => 'input-group-addon addon-after']
        );

        $this->addAddon(['content' => $addon, 'raw' => true], 'append');
        $this->setGroupSize($groupSize);
    }


    /**
     * Override to allow for not including the uncheck hidden element when not wanted
     * e.g. when the checkbox is disabled
     *
     * {@inheritDoc}
     * @see \yii\widgets\ActiveField::checkbox($options, $enclosedByLabel)
     */
    public function checkbox($options = [], $enclosedByLabel = true)
    {
        if ($enclosedByLabel) {
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
            $this->parts['{label}'] = '';
        } else {
            if (isset($options['label']) && !isset($this->parts['{label}'])) {
                $this->parts['{label}'] = $options['label'];
                if (!empty($options['labelOptions'])) {
                    $this->labelOptions = $options['labelOptions'];
                }
            }
            unset($options['labelOptions']);
            $options['label'] = null;
            $this->parts['{input}'] = Html::activeCheckbox($this->model, $this->attribute, $options);
        }
        $this->adjustLabelFor($options);

        return $this;
    }


    /**
     * Generates a tag that contains error.
     *
     * @param $error string the error to use.
     * @param array $options the tag options in terms of name-value pairs.
     *                       It will be merged with [[errorOptions]].
     * @return static the field object itself
     */
    public function staticError($error, $options = [])
    {
        $options = array_merge($this->errorOptions, $options);
        $tag = isset($options['tag']) ? $options['tag'] : 'div';
        unset($options['tag']);
        $this->parts['{error}'] = Html::tag($tag, $error, $options);
        return $this;
    }


    /**
     * Renders a static input
     *
     * @param array $options the tag options
     * @return $this
     */
    public function staticInput($options = [])
    {
        if (isset($options['value'])) {
            $content = $options['value'];
            unset($options['value']);
        } else {
            $content = Html::getAttributeValue($this->model, $this->attribute);
        }
        Html::addCssClass($options, 'form-control-static');
        $this->parts['{input}'] = Html::tag('p', $content, $options);
        $this->template = str_replace("\n{hint}", '', $this->template);
        $this->template = str_replace("\n{error}", '', $this->template);
        return $this;
    }


    /**
     * Generates datePicker component [[DatePicker]].
     *
     * @param array $options
     *        datePicker options
     * @return $this
     */
    public function datePicker($options = [])
    {
        $this->parts['{input}'] = DatePicker::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates dateTimePicker component [[DateTimePicker]].
     *
     * @param array $options
     *        datePicker options
     * @return $this
     */
    public function dateTimePicker($options = [])
    {
        $this->parts['{input}'] = DateTimePicker::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates timePicker component [[TimePicker]].
     *
     * @param array $options
     *        datePicker options
     * @return $this
     */
    public function timePicker($options = [])
    {
        $this->parts['{input}'] = TimePicker::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates dateRangePicker component [[DateRangePicker]].
     *
     * @param array $options
     *        dateRangePicker options
     * @return $this
     */
    public function dateRangePicker($options = [])
    {
        if ($this->form->type == ActiveForm::TYPE_VERTICAL) {
            //$options = array_merge($options, ['options' => ['style' => 'display:table-cell;']]);
            $options = array_merge($options, [
                'options' => [
                    'class' => 'show'
                ]
            ]);
        }
        $this->parts['{input}'] = DateRangePicker::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates colorPicker component [[BootstrapColorPicker]].
     *
     * @param array $options colorPicker options
     * @return $this
     */
    public function bsColorPicker($options = [])
    {
        $this->parts['{input}'] = BootstrapColorPicker::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates mini color widget [[MiniColor]].
     *
     * @param array $options colorPicker options
     * @return $this
     */
    public function miniColors($options = [])
    {
        $this->parts['{input}'] = MiniColors::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates select2 component [[Select2]].
     *
     * @param array $items
     * @param array $options select2 options
     * @return $this
     */
    public function select2($items, $options = [])
    {
        $this->parts['{input}'] = Select2::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'items' => $items,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates select2 component [[Select2]] for multiple selection.
     *
     * @param array $items
     * @param array $options select2 options
     * @return $this
     */
    public function select2Multi($items, $options = [])
    {
        return $this->select2($items, $options);
    }


    /**
     * Generates bootstrap select picker component [[BootstrapSelect]].
     *
     * @param array $items
     * @param array $options bootstrap select options
     * @return $this
     */
    public function bsSelectPicker($items, $options = [])
    {
        $this->parts['{input}'] = BootstrapSelect::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'items' => $items,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates bootstrap select picker component [[BootstrapSelect]] for multiple selection.
     *
     * @param array $items
     * @param array $options bootstrap select options
     * @return $this
     */
    public function bsSelectPickerMulti($items, $options = [])
    {
        return $this->bsSelectPicker($items, $options);
    }


    /**
     * Generates bootstrap select picker component [[BootstrapSelect]].
     *
     * @param array $items
     * @param array $options bootstrap select options
     * @return $this
     */
    public function bsSelectSplitter($items, $options = [])
    {
        $this->parts['{input}'] = BootstrapSelectSplitter::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'items' => $items,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates select2 component [[Select2]] for tag selection.
     *
     * @param array $items
     * @param array $options select2 options
     * @return $this
     */
    public function select2Tags($items, $options = [])
    {
        return $this->select2($items, $options);
    }


    /**
     * Generates multiSelect component [[MultiSelect]].
     *
     * @param array $items
     * @param array $options multiSelect options
     * @return $this
     */
    public function multiSelect($items, $options = [])
    {
        $this->parts['{input}'] = MultiSelect::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'items' => $items,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates range component.
     *
     * @param array $options range options
     * @return $this
     */
    public function range($options = [])
    {
        $this->parts['{input}'] = IonRangeSlider::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Generates spinner component.
     *
     * @param array $options spinner options
     * @return $this
     */
    public function spinner($options = [])
    {
        $this->parts['{input}'] = Spinner::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }


    /**
     * Renders a text input for an integer input.
     * @return $this the field object itself
     */
    public function textInputInteger($options = [])
    {
        return $this->textInput($options);
    }


    /**
     * Renders a text input for a decimal input.
     * @return $this the field object itself
     */
    public function textInputDecimal($options = [])
    {
        return $this->textInput($options);
    }


    /**
     * Renders a text input for a year input.
     * @return $this the field object itself
     */
    public function textInputYear($options = [])
    {
        return $this->textInput($options);
    }


    /**
     * Renders a text input for a time input.
     * @return $this the field object itself
     */
    public function textInputTime($options = [])
    {
        return $this->textInput($options);
    }


    /**
     * Renders a text input for a password strength input.
     * @return $this the field object itself
     */
    public function passwordStrength($options = [])
    {
        return $this->passwordInput($options);
    }


    /**
     * Generates CKEditor component [[CKEditor]].
     *
     * @param array $options CKEditor options
     * @return $this
     */
    public function editor_CK($options = [])
    {
        $this->parts['{input}'] = CKEditor::widget(array_merge($options, [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'form' => $this->form,
            'type' => $this->type,
            'addon' => $this->addon, // hand addon over to widget
        ]));
        $this->addon = []; // addon already processed by widget
        return $this;
    }

}