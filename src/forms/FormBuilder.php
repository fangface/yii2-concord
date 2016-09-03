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

use backend\assets\BootstrapMaxlengthAsset;
use backend\assets\BootstrapPasswordStrengthAsset;
use backend\assets\BootstrapSwitchAsset;
use backend\assets\ICheckAsset;
use backend\assets\InputMaskAsset;
use backend\models\AdminUser;
use fangface\base\traits\AttributeSupportInterface;
use fangface\db\ActiveRecord;
use fangface\db\ActiveRecordReadOnlyInterface;
use fangface\forms\ActiveField;
use fangface\forms\ActiveForm;
use fangface\forms\InputField;
use fangface\widgets\WidgetTrait;
use Yii;
use yii\base\Model;
use yii\base\Widget;
use yii\db\ActiveRecord as YiiActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;


/**
 * Form Builder
 */
class FormBuilder extends Widget
{
    use WidgetTrait;

    /**
     * @var ActiveForm The form that this field is associated with.
     */
    public $form;
    /**
     * @var Model|ActiveRecord The data model that this field is associated with
     */
    public $model;
    /**
     * @var array The model attributes and config for processing
     */
    public $attributes = [];
    /**
     * @var string name of form
     */
    public $formName;
    /**
     * @var boolean should model level readOnly be ignored
     */
    public $ignoreReadOnly = false;
    /**
     * @var boolean should model level edit locks be ignored
     */
    public $ignoreIsEditLocked = false;
    /**
     * @var string default checkbox plugin, options 'make-switch' or 'icheck', default is null
     */
    public $defaultCheckboxPlugin = null;
    /**
     * @var string which attribute field should be given the default focus
     */
    public $defaultFocus = null;
    /**
     * @var string which attribute field should be given the default focus and content selected
     */
    public $defaultFocusSelect = null;

    /**
     * @var array|null holds table schema info for the current model when supported andrequired
     */
    private $spec = null;

    public function run()
    {
        if ($this->attributes) {
            echo $this->renderFieldSet();
        }
        parent::run();
    }

    private function renderFieldSet()
    {
        foreach ($this->attributes as $attribute => $settings) {
            echo $this->renderActiveInput($this->form, $this->model, $attribute, $settings);
        }
    }

    /**
     * Render ActiveField input based on provided settings
     *
     * @param ActiveForm $form
     * @param Model|ActiveRecord $model
     * @param string $attribute
     * @param array $settings
     * @return ActiveField|NULL|string
     */
    private function renderActiveInput($form, $model, $attribute, $settings)
    {
        if (is_string($settings) && $settings) {
            // no settings have been provided for the attribute so $settings will be the attribute name
            $attribute = $settings;
            $settings = [];
        }

        $settingsIn = $settings;

        if ($model instanceof AttributeSupportInterface) {
            //$activeConfig = $model->getAttributeConfig($attribute, 'active');
            $activeConfig = $model->getActiveFieldSettings($attribute);
            if ($activeConfig) {
                $settings = array_merge($activeConfig, $settings);
            }
        }

        $spec = false;
        if ($model instanceof YiiActiveRecord) {
            if ($this->spec === null) {
                $this->spec = $this->model->getTableSchema()->columns;
            }
            if ($this->spec && isset($this->spec[$attribute])) {
                $spec = (isset($this->spec[$attribute]) ? $this->spec[$attribute] : false);
            }
        }

        $type = ArrayHelper::getValue($settings, 'type', null);
        $typeAutomatic = false;
        $label = null;

        $items = ArrayHelper::getValue($settings, 'items', []);
        if ($items instanceof \Closure) {
            $items = $items($model, $attribute);
        }
        if (!($items)) {
            if (is_array($spec->enumValues) && $spec->enumValues) {
                foreach ($spec->enumValues as $k => $v) {
                    if (is_string($v) && $v == '') {
                    } else {
                        $items[$v] = $v;
                    }
                }
            }
        }

        $allowClear = false;

        if ($type === null && $items) {
            // we have been given items so assume a drop down list
            $type = InputField::INPUT_DROPDOWN_LIST;
        }

        if ($type === null) {
            // try to guess the appropriate type
            switch ($attribute)
            {
                case 'created_at':
                case 'createdAt':
                    $type = InputField::INPUT_STATIC;
                    $label = $model->getAttributeConfig($attribute, 'label');
                    $label = (!$label ? ArrayHelper::getValue($settings, 'label', false) : $label);
                    $label = (!$label ? 'Created' : $label);
                    $createAttribute = ($attribute == 'created_at' ? 'created_by' : 'createdBy');
                    if ($model->hasAttribute($createAttribute)) {
                        if ($model->$createAttribute == Yii::$app->user->identity->id) {
                            $settings['options']['value'] = $model->$attribute . ' by ' . Yii::$app->user->identity->display_name;
                        } else {
                            $createData = AdminUser::find()->select('display_name')->where(['id' => $model->$createAttribute])->limit(1)->asArray()->column();
                            $settings['options']['value'] = $model->$attribute . ' by ' . $createData[0];
                        }
                    }
                    break;
                case 'modified_at':
                case 'modifiedAt':
                    $type = InputField::INPUT_STATIC;
                    $label = $model->getAttributeConfig($attribute, 'label');
                    $label = (!$label ? ArrayHelper::getValue($settings, 'label', false) : $label);
                    $label = (!$label ? 'Last Modified' : $label);
                    $modifiedAttribute = ($attribute == 'modified_at' ? 'modified_by' : 'modifiedBy');
                    if ($model->hasAttribute($modifiedAttribute)) {
                        if ($model->$modifiedAttribute == Yii::$app->user->identity->id) {
                            $settings['options']['value'] = $model->$attribute . ' by ' . Yii::$app->user->identity->display_name;
                        } else {
                            $modifiedData = AdminUser::find()->select('display_name')->where(['id' => $model->$modifiedAttribute])->limit(1)->asArray()->column();
                            $settings['options']['value'] = $model->$attribute . ' by ' . $modifiedData[0];
                        }
                    }
                    break;
                case 'created_by':
                case 'createdBy':
                case 'modified_by':
                case 'modifiedBy':
                case 'id':
                    $type = InputField::INPUT_STATIC;
                    break;
                default:
                    $type = InputField::getDefaultInputFieldType($attribute, $spec, $settings);
                    if ($type) {
                        $typeAutomatic = true;
                    }
            }

            if ($type === null) {
                return '';
            }
        }

        $fieldConfig = ArrayHelper::getValue($settings, 'fieldConfig', []);
        $options = ArrayHelper::getValue($settings, 'options', []);
        $hint = ArrayHelper::getValue($settings, 'hint', null);
        $icon = ArrayHelper::getValue($settings, 'icon', ($typeAutomatic && InputField::getIsIconSupportedFieldType($type) ? true : null));
        $tooltip = ArrayHelper::getValue($settings, 'tooltip', null);
        $focus = ArrayHelper::getValue($settings, 'focus', null);
        $addon = ArrayHelper::getValue($settings, 'addon', null);
        $clear = ArrayHelper::getValue($settings, 'clear', null);

        $label = ($label === null ? ArrayHelper::getValue($settingsIn, 'label', $label) : $label);
        $label = ($label === null ? $this->model->getAttributeLabel($attribute) : $label);

        $hideuseInlineHelp = $useLabelColumn = ArrayHelper::getValue($settings, 'useInlineHelp', true);

        // is the current field read only
        $readOnly = false;
        if ($type == InputField::INPUT_READONLY) {
            $type = InputField::INPUT_TEXT;
            $readOnly = true;
        } elseif (!$this->ignoreReadOnly && $model instanceof ActiveRecordReadOnlyInterface && $model->getReadOnly()) {
            $readOnly = true;
        } elseif (method_exists($form, 'isEditLocked') && $form->isEditLocked()) {
            $readOnly = true;
        } elseif (!$this->ignoreIsEditLocked && method_exists($model, 'isEditLocked') && $model->isEditLocked()) {
            $readOnly = true;
        } elseif (ArrayHelper::getValue($settings, 'readonly', false)) {
            $readOnly = true;
        }

        /*
         * apply any conversions if required or extra settings to apply
         */
        switch ($type) {
            case InputField::INPUT_CHECKBOX:
                if (!ArrayHelper::keyExists('class', $options)) {
                    // should we apply a default checkbox style or plugin
                    if ($this->defaultCheckboxPlugin !== null && $this->defaultCheckboxPlugin) {
                        Html::addCssClass($options, $this->defaultCheckboxPlugin);
                    }
                }
                break;
            case InputField::INPUT_CHECKBOX_BASIC:
                // revert back to basic checkbox
                $type = InputField::INPUT_CHECKBOX;
                break;
            case InputField::INPUT_CHECKBOX_ICHECK:
                // revert to basic checkbox with icheck class applied
                $type = InputField::INPUT_CHECKBOX;
                Html::addCssClass($options, 'icheck');
                break;
            case InputField::INPUT_CHECKBOX_SWITCH:
                // revert to basic checkbox with make-switch class applied
                $type = InputField::INPUT_CHECKBOX;
                Html::addCssClass($options, 'make-switch');
                break;
            case InputField::INPUT_DATE:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = ['alias' => 'yyyy-mm-dd'];
                $options['maxlength'] = 10;
                $addon = [
                    'append' => [
                        [
                            'class' => 'glyphicon glyphicon-th show-datepicker',
                            'title' => 'Click to select date',
                        ],
                    ],
                    'groupOptions' => [
                        'class' => 'date input-small',
                    ]
                ];
                //$options['widgetOptions']['pluginOptions']['autoclose'] = false; // optionally override plugin or widget options
                $clear = ($clear !== false ? true : $clear);
                $allowClear['input'] = 'input';
                break;
            case InputField::INPUT_DATETIME:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = ['alias' => 'yyyy-mm-dd hh:mm:ss'];
                //$options['data-inputmask'] = ['alias' => 'yyyy-mm-dd hh:mm'];
                $options['maxlength'] = 19;
                $addon = [
                    'append' => [
                        [
                            'class' => 'glyphicon glyphicon-th show-date-time-picker',
                            'title' => 'Click to select date',
                        ],
                    ],
                    'groupOptions' => [
                        'class' => 'date input-medium',
                    ]
                ];
                //$options['widgetOptions']['pluginOptions']['autoclose'] = false; // optionally override plugin or widget options
                $clear = ($clear !== false ? true : $clear);
                $allowClear['input'] = 'input';
                break;
            case InputField::INPUT_YEAR:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = ['mask' => '9999'];
                break;
            case InputField::INPUT_TIME:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = ['alias' => 'hh:mm:ss'];
                //$options['data-inputmask'] = ['alias' => 'hh:mm'];
                $options['maxlength'] = 8;
                $addon = [
                    'append' => [
                        [
                            'class' => 'glyphicon glyphicon-th clickable show-timepicker',
                            'title' => 'Click to select time',
                        ],
                    ],
                    'groupOptions' => [
                        'class' => 'input-small',
                    ]
                ];
                $clear = ($clear !== false ? true : $clear);
                $allowClear['input'] = 'time';
                break;
            case InputField::INPUT_INTEGER:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = (isset($options['data-inputmask']) ? $options['data-inputmask'] : []);
                $options['maxlength'] = (isset($options['maxlength']) ? $options['maxlength'] : $spec->size + ($spec->unsigned ? 0 : 1));
                $defaults = [
                    'alias' => 'numeric',
                    'allowMinus' => !$spec->unsigned,
                    'digits' => 0,
                    'rightAlign' => true,
                ];
                $options['data-inputmask'] = array_merge($defaults, $options['data-inputmask']);
                $clear = ($clear !== false ? true : $clear);
                $allowClear['input'] = 'integer';
                //$allowClear['value'] = '0';
                break;
            case InputField::INPUT_DECIMAL:
                $options['data-maxlength'] = false;
                $options['data-inputmask'] = (isset($options['data-inputmask']) ? $options['data-inputmask'] : []);
                $options['maxlength'] = (isset($options['maxlength']) ? $options['maxlength'] : $spec->size + 1 + ($spec->unsigned ? 0 : 1));
                $defaults = [
                    'alias' => 'decimal',
                    'allowMinus' => !$spec->unsigned,
                    'integerDigits' => $spec->size - $spec->scale,
                    'digits' => $spec->scale,
                    'rightAlign' => true,
                ];
                $options['data-inputmask'] = array_merge($defaults, $options['data-inputmask']);
                $clear = ($clear !== false ? true : $clear);
                $allowClear['input'] = 'decimal';
                if ($spec->scale != 2) {
                    $allowClear['value'] = number_format(0, $spec->scale);
                }
                break;
            case InputField::INPUT_COLOR:
                $options['data-maxlength'] = false;
                $allowClear['input'] = 'colorpicker';
                break;
            case InputField::INPUT_MINI_COLORS:
                $options['data-maxlength'] = false;
                $allowClear['input'] = 'minicolors';
                break;
            case InputField::INPUT_TEXT:
            case InputField::INPUT_TEXTAREA:
                $allowClear['input'] = 'input';
                break;
            case InputField::INPUT_DROPDOWN_LIST:
            case InputField::INPUT_LIST_BOX:
                // will select first item in drop down list
                $allowClear['input'] = 'select';
                break;
            case InputField::INPUT_SELECT2:
            case InputField::INPUT_SELECT2_MULTI:
                $allowClear['input'] = 'select2';
                break;
            case InputField::INPUT_SELECT_PICKER:
            case InputField::INPUT_SELECT_PICKER_MULTI:
                $allowClear['input'] = 'selectpicker';
                break;
            case InputField::INPUT_MULTISELECT:
                $allowClear['input'] = 'multiselect';
                break;
            default:
        }

        /**
         * @var ActiveField $field
         */
        $field = $form->field($model, $attribute, $fieldConfig);

        switch ($type) {

            case InputField::INPUT_HIDDEN:
            case InputField::INPUT_STATIC:

                return static::getInput($field, $type, [$options], $label, $hint, $icon, $tooltip, null);
                break;

            case InputField::INPUT_TEXT:
            case InputField::INPUT_PASSWORD:
            case InputField::INPUT_PASSWORD_STRENGTH:
            case InputField::INPUT_TEXTAREA:
            case InputField::INPUT_INTEGER:
            case InputField::INPUT_DECIMAL:
            case InputField::INPUT_YEAR:
            case InputField::INPUT_TIME:
            case InputField::INPUT_DATE:
            case InputField::INPUT_DATETIME:
            case InputField::INPUT_COLOR:
            case InputField::INPUT_MINI_COLORS:

                Html::addCssClass($options, 'form-control');
                if (ArrayHelper::getValue($settings, 'placeholder', null) !== null) {
                    $options['placeholder'] = ArrayHelper::getValue($settings, 'placeholder', null);
                }
                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                    $clear = false;
                }

                $clear = ($clear === true ? true : false); // null defaults to false if still null at this stage
                if ($clear === false) {
                    $allowClear = false;
                }

                $maxlength = ArrayHelper::getValue($options, 'maxlength', ($spec ? $spec->size : 0));
                $inputSize = ArrayHelper::getValue($settings, 'size', InputField::INPUT_SIZE_AUTO);

                if ($maxlength) {

                    $inputSize = $this->getDefaultInputSize($inputSize, $maxlength, $icon, $tooltip);

                    if (!$readOnly) {

                        $options['maxlength'] = $maxlength;
                        if (isset($options['data-maxlength']) && !$options['data-maxlength']) {
                            // do not use the plugin
                            unset($options['data-maxlength']);
                        } else {
                            BootstrapMaxlengthAsset::register($this->getView());
                            if (!isset($options['data-maxlength']['threshold'])) {
                                if ($maxlength > 99) {
                                    $options['data-maxlength']['threshold'] = '50';
                                } elseif ($maxlength > 50) {
                                    $options['data-maxlength']['threshold'] = '20';
                                } elseif ($maxlength > 10) {
                                    $options['data-maxlength']['threshold'] = '10';
                                } else {
                                    $options['data-maxlength']['threshold'] = $maxlength - 1;
                                }
                            }
                            if (!isset($options['data-maxlength']['placement'])) {
                                $options['data-maxlength']['placement'] = 'top';
                            }
                            $options['data-maxlength'] = Json::encode($options['data-maxlength']);
                            Html::addCssClass($options, 'bs-max-me');
                        }

                        if ($type == InputField::INPUT_PASSWORD_STRENGTH) {
                            BootstrapPasswordStrengthAsset::register($this->getView());
                            Html::addCssClass($options, 'strength-me');
                        }

                        $options = $this->setFocus($options, $field, $attribute, $focus);
                    }
                }

                if (isset($options['data-inputmask'])) {
                    Html::addCssClass($options, 'mask-me');
                    if (isset($options['data-inputmask'])) {
                        $options['data-inputmask'] = Json::encode($options['data-inputmask']);
                    }
                    InputMaskAsset::register($this->getView());
                }

                $options = $this->applyInputSize($inputSize, $options);

                // by default make inputs select their own content when they get focus
                Html::addCssClass($options, 'select-me');

                $options = $this->convertOptionsForWidgets($type, $options);

                if ($allowClear) {
                    $allowClear['size'] = $inputSize;
                }

                return static::getInput($field, $type, [$options], $label, $hint, $icon, $tooltip, $addon, $allowClear);
                break;

            case InputField::INPUT_FILE:
                return static::getInput($field, $type, [$options], $label, $hint, $icon, $tooltip, $addon);
                break;

            case InputField::INPUT_CHECKBOX:
                $enclosedByLabel = ArrayHelper::getValue($settings, 'enclosedByLabel', false);
                $useLabelColumn = ArrayHelper::getValue($settings, 'useLabelColumn', true);
                if ($label !== null && $label) {
                    $options['label'] = ($useLabelColumn ? null : $label);
                }
                $useInlineHelp = ArrayHelper::getValue($settings, 'useInlineHelp', true);
                if ($useInlineHelp) {
                    $field->hintOptions = [
                        'tag' => 'span',
                        'class' => 'help-inline',
                    ];
                    $field->template = str_replace("\n{error}", '', $field->template);
                }

                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                }

                if (strpos(ArrayHelper::getValue($options, 'class', ''), 'make-switch') !== false) {
                    //$options['data-on-text'] = ArrayHelper::getValue($options, 'data-on-text', 'ON'); // value can be an icon e.g. <i class='fa fa-check'></i>
                    //$options['data-off-text'] = ArrayHelper::getValue($options, 'data-off-text', 'OFF'); // value can be an icon e.g. <i class='fa fa-times'></i>
                    //$options['data-on-color'] = ArrayHelper::getValue($options, 'data-on-color', 'primary');
                    //$options['data-off-color'] = ArrayHelper::getValue($options, 'data-off-color', 'default');
                    $options['data-size'] = ArrayHelper::getValue($options, 'data-size', 'small');
                    BootstrapSwitchAsset::register($this->getView());
                } elseif (strpos(ArrayHelper::getValue($options, 'class', ''), 'icheck') !== false) {
                    //Html::addCssClass($options, 'icheck');
                    $options['data-checkbox'] = ArrayHelper::getValue($options, 'data-checkbox', 'icheckbox_square-blue');
                    ICheckAsset::register($this->getView());
                }

                return $this->getInput($field->$type($options, $enclosedByLabel), $type, null, ($useLabelColumn ? $label : null), $hint);
                break;

            case InputField::INPUT_RAW:
                $value = ArrayHelper::getValue($settings, 'value', '');
                if ($value instanceof \Closure) {
                    $value = $value();
                }
                return $value;
                break;

            case InputField::INPUT_SELECT_PICKER_MULTI:
            case InputField::INPUT_SELECT2_MULTI:
            case InputField::INPUT_MULTISELECT:
                $isMultiple = true;
                // no break
            case InputField::INPUT_DROPDOWN_LIST:
            case InputField::INPUT_LIST_BOX:
            case InputField::INPUT_SELECT2:
            case InputField::INPUT_SELECT_PICKER:
            case InputField::INPUT_SELECT_SPLITTER:

                $isMultiple = ((isset($isMultiple) && $isMultiple) || isset($options['multiple']) ? true : false);
                if ($isMultiple) {
                    $options['multiple'] = 'multiple';
                    $this->model->setAttribute($attribute, explode('|',$this->model->getAttribute($attribute)));
                }

                if (($isMultiple && !InputField::getIsWidgetFromFieldType($type)) || $type == InputField::INPUT_LIST_BOX) {
                    $options['size'] = ArrayHelper::getValue($options, 'size', 4);
                }

                Html::addCssClass($options, 'form-control');
                if (ArrayHelper::getValue($settings, 'placeholder', null) !== null) {
                    $options['prompt'] = ArrayHelper::getValue($settings, 'placeholder', null);
                }
                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                    $clear = false;
                } else {
                    $options = $this->setFocus($options, $field, $attribute, $focus);
                }

                $clear = ($clear === true ? true : false); // null defaults to false if still null at this stage
                if ($clear === false) {
                    $allowClear = false;
                }

                if ($typeAutomatic && !isset($options['prompt'])) {
                    if ($model->hasAttribute($attribute) && $model->getAttribute($attribute) != '' && $model->getAttribute($attribute) !== null) {
                        // no prompt required by default
                    } else {
                        $options['prompt'] = 'Select...';
                        $options['promptValue'] = '';
                    }
                }

                switch ($type) {
                    case InputField::INPUT_SELECT2:
                    case InputField::INPUT_SELECT2_MULTI:
                    case InputField::INPUT_SELECT_PICKER:
                    case InputField::INPUT_SELECT_PICKER_MULTI:
                    case InputField::INPUT_SELECT_SPLITTER:

                        if (isset($options['prompt']) && $options['prompt'] != '') {
                            // default value will be blank and convert to null by default
                            if (($type == InputField::INPUT_SELECT2 || $type == InputField::INPUT_SELECT2_MULTI)) {
                                $promptValue = (isset($options['promptValue']) ?  $options['promptValue'] : '0');
                                if (!isset($options['groups'])) {
                                    $options['widgetOptions']['pluginOptions']['placeholder'] = $options['prompt'];
                                    $options['widgetOptions']['pluginOptions']['id'] = $promptValue;
                                } else {
                                    $promptValue = $options['promptValue'];
                                    $items = array_merge([$promptValue => $options['prompt']], $items);
                                }
                            } elseif ($type == InputField::INPUT_SELECT_PICKER || $type == InputField::INPUT_SELECT_PICKER_MULTI) {
                                $promptValue = (isset($options['promptValue']) ?  $options['promptValue'] : '');
                                if ($promptValue != '') {
                                    $items = array_merge([$promptValue => $options['prompt']], $items);
                                } else {
                                    $options['title'] = $options['prompt'];
                                    if (!$isMultiple) {
                                        //wlchere - makes it work well front end but breaks back end when an array is submitted
                                        //$isMultiple = true;
                                        //$options['multiple'] = 'multiple';
                                        //$options['widgetOptions']['pluginOptions']['maxOptions'] = 1;
                                    }
                                }
                            } elseif ($type == InputField::INPUT_SELECT_SPLITTER) {
                                if (!$isMultiple) {
                                    $promptValue = (isset($options['promptValue']) ?  $options['promptValue'] : '');
                                    $items = array_merge(['' => [$promptValue => $options['prompt']]], $items);
                                    $options['groups'] = (isset($options['groups']) ? $options['groups'] : []);
                                    $options['groups'] = array_merge(['' => ['label' => $options['prompt']]], $options['groups']);
                                }
                            }
                        }
                        break;
                    default:
                        if (!$isMultiple && isset($options['prompt']) && $options['prompt'] != '') {
                            // default value will be blank and convert to null by default
                            $promptValue = (isset($options['promptValue']) ?  $options['promptValue'] : '0');
                            $items = array_merge([$promptValue => $options['prompt']], $items);
                        }
                }
                unset($options['prompt']);
                unset($options['promptValue']);

                $inputSize = ArrayHelper::getValue($settings, 'size', InputField::INPUT_SIZE_AUTO);
                $inputSize = $this->getDefaultInputSize($inputSize, 0, $icon, $tooltip);
                $options = $this->applyInputSize($inputSize, $options);

                $options = $this->convertOptionsForWidgets($type, $options);

                if ($allowClear) {
                    $allowClear['size'] = $inputSize;
                }

                return $this->getInput($field, $type, [$items, $options], $label, $hint, $icon, $tooltip, $addon, $allowClear);

                break;

            case InputField::INPUT_CHECKBOX_LIST:
            case InputField::INPUT_CHECKBOX_LIST_ICHECK:

                $this->model->setAttribute($attribute, explode('|',$this->model->getAttribute($attribute)));

                $allowClear['input'] = 'checkbox';

                if (!isset($options['itemOptions'])) {
                    $options['itemOptions'] = [];
                }

                if (!isset($options['itemOptions']['labelOptions'])) {
                    $options['itemOptions']['labelOptions'] = [];
                }

                $inline = ArrayHelper::getValue($settings, 'inline', false);
                if ($inline) {
                    Html::addCssClass($options['itemOptions']['labelOptions'], 'checkbox-inline');
                } else {
                    // vertically listed so disable form control as well
                    $settings['noFormControl'] = true;
                }

                if (ArrayHelper::getValue($settings, 'noFormControl', false)) {
                    // icon and tooltip will not be supported
                    $icon = false;
                    $tooltip = false;
                    Html::addCssClass($options, 'checkbox-list');
                    $allowClear = false;
                } else {
                    // wrap the whole checkbox list in a form-control box
                    Html::addCssClass($options, 'form-control fc-checkbox-list checkbox-list');
                }

                if ($type == InputField::INPUT_CHECKBOX_LIST_ICHECK) {
                    $allowClear['input'] = 'icheckbox';
                    Html::addCssClass($options['itemOptions'], 'icheck');
                    $options['itemOptions']['data-checkbox'] = ArrayHelper::getValue($options['itemOptions'], 'data-checkbox', 'icheckbox_square-blue');
                    ICheckAsset::register($this->getView());
                }

                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                    $options['itemOptions']['disabled'] = 'disabled';
                    $clear = false;
                }

                if ($allowClear) {
                    $clear = ($clear === true ? true : false); // null defaults to false if still null at this stage
                    if ($clear === false) {
                        $allowClear = false;
                    }
                }

                $options = $this->applyInputSize(ArrayHelper::getValue($settings, 'size', InputField::INPUT_SIZE_NONE), $options);

                return $this->getInput($field, 'checkboxList' ,[$items, $options], $label, $hint, $icon, $tooltip, $addon, $allowClear);
                break;

            case InputField::INPUT_RADIO_LIST:
            case InputField::INPUT_RADIO_LIST_ICHECK:

                $allowClear['input'] = 'radio';

                if (!isset($options['itemOptions'])) {
                    $options['itemOptions'] = [];
                }

                if (!isset($options['itemOptions']['labelOptions'])) {
                    $options['itemOptions']['labelOptions'] = [];
                }

                $inline = ArrayHelper::getValue($settings, 'inline', false);
                if ($inline) {
                    Html::addCssClass($options['itemOptions']['labelOptions'], 'radio-inline');
                } else {
                    // vertically listed so disable form control as well
                    $settings['noFormControl'] = true;
                }

                if (ArrayHelper::getValue($settings, 'noFormControl', false)) {
                    // icon and tooltip will not be supported
                    $icon = false;
                    $tooltip = false;
                    Html::addCssClass($options, 'radio-list');
                    $allowClear = false;
                } else {
                    // wrap the whole checkbox list in a form-control box
                    Html::addCssClass($options, 'form-control fc-radio-list radio-list');
                }

                if ($type == InputField::INPUT_RADIO_LIST_ICHECK) {
                    $allowClear['input'] = 'iradio';
                    if (!$inline) {
                        Html::addCssClass($options['itemOptions']['labelOptions'], 'radio-vertical');
                    }
                    Html::addCssClass($options['itemOptions'], 'icheck');
                    $options['itemOptions']['data-radio'] = ArrayHelper::getValue($options['itemOptions'], 'data-radio', 'iradio_square-blue');
                    ICheckAsset::register($this->getView());
                }

                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                    $options['itemOptions']['disabled'] = 'disabled';
                    $clear = false;
                }

                if ($allowClear) {
                    $clear = ($clear === true ? true : false); // null defaults to false if still null at this stage
                    if ($clear === false) {
                        $allowClear = false;
                    }
                }

                $options = $this->applyInputSize(ArrayHelper::getValue($settings, 'size', InputField::INPUT_SIZE_NONE), $options);

                return $this->getInput($field, 'radioList', [$items, $options], $label, $hint, $icon, $tooltip, $addon, $allowClear);
                break;

            case InputField::INPUT_HTML5:
                return 'WORK IN PROGRESS: ' . $attribute . ' : ' . $type . '<br/>';
                break;

            case InputField::INPUT_EDITOR_CK:
            case InputField::INPUT_EDITOR_BS_WYSIHTML5:
            case InputField::INPUT_EDITOR_BS_SUMMERNOTE:

//wlchere move to normal place above plus possibly switch this whole section into textarea above
// and make use of InputField::getIsEditorFromFieldType($type)
                $allowClear['input'] = 'val';

                Html::addCssClass($options, 'form-control');
                if (ArrayHelper::getValue($settings, 'placeholder', null) !== null) {
                    $options['placeholder'] = ArrayHelper::getValue($settings, 'placeholder', null);
                }
                if ($readOnly) {
                    $options['disabled'] = 'disabled';
                    $clear = false;
                }

                $clear = ($clear === true ? true : false); // null defaults to false if still null at this stage
                if ($clear === false) {
                    $allowClear = false;
                }

                $inputSize = ArrayHelper::getValue($settings, 'size', InputField::INPUT_SIZE_AUTO);
                $options = $this->applyInputSize($inputSize, $options);
                $options = $this->convertOptionsForWidgets($type, $options);

                if ($allowClear) {
                    $allowClear['size'] = $inputSize;
                }

                return static::getInput($field, $type, [$options], $label, $hint, $icon, $tooltip, $addon, $allowClear);
                break;

            case InputField::INPUT_SELECT2_TAGS:
            case InputField::INPUT_WIDGET:
                return 'WORK IN PROGRESS (other): ' . $attribute . ' : ' . $type . '<br/>';
                break;
            case InputField::INPUT_RADIO:
                return 'Not currently supported: ' . $attribute . ' : ' . $type . '<br/>';
                break;
            default:
                return 'WORK IN PROGRESS (other): ' . $attribute . ' : ' . $type . '<br/>';
        }

        return null;
    }

    /**
     * Extend ActiveField with any other generic settings ahead of rendering it
     *
     * @param ActiveField $field
     * @param string $type
     * @param array $params array of params for the field type call
     * @param string $label
     * @param string $hint
     * @param array $icon
     * @param array $tooltip
     * @param array $addon
     * @param false|array $allowClear
     * @return ActiveField
     */
    private function getInput($field, $type, $params = null, $label = null, $hint = null, $icon = null, $tooltip = null, $addon = null, $allowClear = null)
    {

        $field->setType($type);

        if ($label !== null && $label !== false) {
            $field = $field->label($label);
        }

        if ($hint !== null && $hint !== false) {
            $field = $field->hint($hint);
        }

        if (($icon !== null && $icon !== false) || ($tooltip !== null && $tooltip !== false)) {
            $field->icon($icon, $tooltip);
        }

        if ($addon !== null && is_array($addon)) {
            $field->mergeAddon($addon);
        }

        if ($allowClear !== null && is_array($allowClear) && $allowClear) {
            $inputClearType = ArrayHelper::remove($allowClear, 'input', 'input');
            $inputClearValue = ArrayHelper::remove($allowClear, 'value', '');
            $inputClearGroupSize = ArrayHelper::remove($allowClear, 'size', '');
            $field->addClearAddOn($inputClearType, $inputClearValue, $inputClearGroupSize);
        }

        if ($params !== null) {
            $field = call_user_func_array([$field, $type], $params);
        }

        return $field;
    }

    /**
     * Check to see if the provided attribute should have the default focus and apply it
     *
     * @param array $options field input options
     * @param ActiveField $field
     * @param string $attribute
     * @param string $defaultFocusSetting
     * @return array updated $options
     */
    public function setFocus($options, $field, $attribute, $defaultFocusSetting = null)
    {
        if ($this->defaultFocus === null && $this->defaultFocusSelect === null) {
            if ($defaultFocusSetting !== null) {
                if ($defaultFocusSetting == 'focus') {
                    Html::addCssClass($options, 'default-field');
                } elseif ($defaultFocusSetting == 'select') {
                    Html::addCssClass($options, 'default-field-select');
                }
            }
        } else {
            if ($attribute == $this->defaultFocus) {
                Html::addCssClass($options, 'default-field');
            } elseif ($attribute == $this->defaultFocusSelect) {
                Html::addCssClass($options, 'default-field-select');
            }
        }
        return $options;
    }

    /**
     * Automate default input field size based on maxlength setting
     *
     * @param string $inputSize
     * @param integer $maxlength
     * @param array|string|null $icon
     * @param array|string|null $tooltip
     * @return string
     */
    public function getDefaultInputSize($inputSize, $maxlength, $icon = null, $tooltip = null)
    {
        if ($inputSize == InputField::INPUT_SIZE_AUTO) {
            if ($maxlength == 0) {
                $inputSize = InputField::INPUT_SIZE_NONE;
            } else {
                if ($icon !== null || $tooltip !== null) {
                    if ($maxlength < 5) {
                        $inputSize = InputField::INPUT_SIZE_XSMALL;
                    } elseif ($maxlength < 11) {
                        $inputSize = InputField::INPUT_SIZE_SMALL;
                    } elseif ($maxlength < 24) {
                        $inputSize = InputField::INPUT_SIZE_MEDIUM;
                    } elseif ($maxlength < 34) {
                        $inputSize = InputField::INPUT_SIZE_LARGE;
                    } elseif ($maxlength < 47) {
                        $inputSize = InputField::INPUT_SIZE_XLARGE;
                    }
                } else {
                    if ($maxlength < 7) {
                        $inputSize = InputField::INPUT_SIZE_XSMALL;
                    } elseif ($maxlength < 15) {
                        $inputSize = InputField::INPUT_SIZE_SMALL;
                    } elseif ($maxlength < 27) {
                        $inputSize = InputField::INPUT_SIZE_MEDIUM;
                    } elseif ($maxlength < 37) {
                        $inputSize = InputField::INPUT_SIZE_LARGE;
                    } elseif ($maxlength < 49) {
                        $inputSize = InputField::INPUT_SIZE_XLARGE;
                    }
                }
            }
        }
        return $inputSize;
    }

    /**
     * Apply input size to input field
     *
     * @param string $inputSize
     * @param array $options
     * @return array
     */
    public function applyInputSize($inputSize, $options)
    {
        if ($inputSize != InputField::INPUT_SIZE_NONE && $inputSize != InputField::INPUT_SIZE_AUTO) {
            Html::addCssClass($options, 'input-' . $inputSize);
        }
        return $options;
    }

    /**
     * Amend options array suitable for passing to widgets
     *
     * @param string $type
     * @param array $options
     * @return array
     */
    public function convertOptionsForWidgets($type, $options)
    {
        if (InputField::getIsWidgetFromFieldType($type)) {
            // we need to present the options differently
            $newOptions = $options;
            $widgetOptions = ArrayHelper::getValue($options, 'widgetOptions', []);
            unset($newOptions['widgetOptions']);
            $options = $widgetOptions;
            $options['options'] = $newOptions;
        }
        return $options;
    }

}
