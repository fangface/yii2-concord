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

use fangface\concord\Tools;
use fangface\concord\tools\InputField;
use fangface\concord\validators\FilterValidator;
use fangface\concord\validators\StrengthValidator;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;


trait AttributeSupport
{

    /**
     * @var boolean
     */
    public $allowAutoRule = true;


    /**
     * Get the config for an attribute
     *
     * @param string $attribute Attribute name
     * @param string $option [optional] name of the entry to be returned,
     *   otherwise all entries for attribute will be returned
     * @param string $setting [optional] name of the active field setting to be returned
     *   if $option is 'active'
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getAttributeConfig($attribute, $option = null, $setting = null, $encode = false)
    {
        $config = false;
        $attributeDefaults = $this->attributeConfig();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $config = $attributeDefaults[$attribute];
            if ($option !== null) {
                if (array_key_exists($option, $config)) {
                    if ($setting !== null) {
                        if (array_key_exists($setting, $config[$option])) {
                            return ($encode ? Html::encode($config[$option][$setting]) : $config[$option][$setting]);
                        }
                    } else {
                        return ($encode ? Html::encode($config[$option]) : $config[$option]);
                    }
                }
                return false;
            }
        }
        return $config;
    }

    /**
     * Get array of attribute config suitable for providing fieldConfig, hintBlocks, icons, placeHolders and toolTips
     *
     * @return array
     */
    public function attributeConfig()
    {
        return [];
    }

    /**
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (array_key_exists($attribute, $labels)) {
            $message = $labels[$attribute];
        } else {
            $message = $this->getAttributeConfig($attribute, 'label');
        }
        if (!$message) {
            $message = $this->generateAttributeLabel($attribute);
        }
        return $message;
    }

    /**
     * Override default implementation to make use of self::attributeConfig() to determine
     * rules. If not set then the default parent implementation will be used.
     * @see \yii\base\Model::rules()
     * @return array
     */
    public function rules()
    {
        $config = $this->attributeConfig();
        if (!$config) {
            return parent::rules();
        }
        $data = [];
        foreach ($config as $attribute => $attrConfig) {
            $rules = [];
            if (array_key_exists('rules', $attrConfig)) {
                if ($attrConfig['rules']) {
                    if (array_key_exists('scenarios', $attrConfig['rules'])) {
                        if (array_key_exists($this->getScenario(), $attrConfig['rules']['scenarios'])) {
                            $rules = $attrConfig['rules']['scenarios'][$this->getScenario()];
                            foreach ($rules as $key => $rule) {
                                $rules[] = $rule;
                            }
                        }
                    } else {
                        foreach ($attrConfig['rules'] as $key => $rule) {
                            $rules[] = $rule;
                        }
                    }
                }
            }
            if ($this->allowAutoRule) {
                if (ArrayHelper::getValue($attrConfig, 'autorules', true)) {
                    $extraRules = $this->getAutoRules($rules, $attrConfig, $attribute);
                    if ($extraRules) {
                        foreach ($extraRules as $key => $rule) {
                            $rules[] = $rule;
                        }
                    }
                }
            }
            foreach ($rules as $key => $rule) {
                array_unshift($rule, [$attribute]);
                $data[] = $rule;
            }
        }
        return $data;
    }

    /**
     * Get any auto rules base on table schema
     * @param array $rules
     * @param array $config
     * @param string $attribute
     * @return array
     */
    public function getAutoRules($rules, $config, $attribute = '')
    {
        static $columns = null;

        $data = [];

        switch ($attribute) {
            case 'id':
            case 'created_at':
            case 'createdAt':
            case 'modified_at':
            case 'modifiedAt':
                return $data;
                break;
        }

        $type = (isset($config['active']['type']) ? $config['active']['type'] : '');
        if ($type == InputField::INPUT_STATIC) {
            return $data;
        }

        $columns = ($columns === null ? self::getTableSchema()->columns : $columns);

        if (isset($columns[$attribute])) {
            $spec = $columns[$attribute];

            $max = -1;
            $noTrim = false;
            $defaultOnEmpty = null;
            $isString = true;
            $isMultiple = false;
            $dateFormat = '';
            $addRules = [];

            if ($type == '') {
                $type = InputField::getDefaultInputFieldType($attribute, $spec, (isset($config['active']) ? $config['active'] : null));
                if ($type == InputField::INPUT_STATIC) {
                    return $data;
                }
            }

            switch ($type) {
                case InputField::INPUT_TEXT:
                case InputField::INPUT_PASSWORD:
                    $max = $spec->size;
                    break;
                case InputField::INPUT_PASSWORD_STRENGTH:
                    $max = $spec->size;
                    $addRules[] = ['check' => [StrengthValidator::className()], 'rule' => [StrengthValidator::className(), 'preset' => 'normal', 'hasUser' => false, 'hasEmail' => false, 'userAttribute' => false]];
                    break;
                case InputField::INPUT_INTEGER:
                    $max = $spec->size + ($spec->unsigned ? 0 : 1);
                    $maxValue = pow(10, $spec->size) - 1;
                    $minValue = ($spec->unsigned ? 0 : -1 * $maxValue);
                    $noTrim = true;
                    $defaultOnEmpty = 0;
                    $addRules[] = ['check' => ['integer'], 'rule' => ['integer', 'max' => $maxValue, 'min' => $minValue]];
                    break;
                case InputField::INPUT_DECIMAL:
                    $max = $spec->size + ($spec->unsigned ? 1 : 2);
                    $maxValue = pow(10, $spec->size - $spec->scale) - 0.01;
                    $minValue = ($spec->unsigned ? 0 : -1 * $maxValue);
                    $noTrim = true;
                    $defaultOnEmpty = '0.00';
                    $addRules[] = ['check' => ['double'], 'rule' => ['double', 'max' => $maxValue, 'min' => $minValue]];
                    $addRules[] = ['check' => [FilterValidator::className()], 'rule' => [FilterValidator::className(), 'filter' => 'number_format', 'args' => [$spec->scale, '.', '']]];
                    break;
                case InputField::INPUT_TEXTAREA:
                    break;
                case InputField::INPUT_CHECKBOX:
                    $isString = false;
                    $addRules[] = ['check' => ['boolean'], 'rule' => ['boolean']];
                    break;
                case InputField::INPUT_DATE:
                    $defaultOnEmpty = Tools::DATE_DB_EMPTY;
                    $max = 10;
                    $dateFormat = 'Y-m-d';
                    break;
                case InputField::INPUT_DATETIME:
                    $defaultOnEmpty = Tools::DATE_TIME_DB_EMPTY;
                    $max = 19;
                    $dateFormat = 'Y-m-d H:i:s';
                    break;
                case InputField::INPUT_TIME:
                    $defaultOnEmpty = Tools::DATE_DB_EMPTY;
                    $max = 8;
                    $dateFormat = 'H:i:s';
                    break;
                case InputField::INPUT_YEAR:
                    $defaultOnEmpty = Tools::YEAR_DB_EMPTY;
                    $max = 4;
                    $dateFormat = 'Y';
                    $addRules[] = ['check' => ['integer'], 'rule' => ['integer', 'max' => Tools::YEAR_DB_MAX, 'min' => Tools::YEAR_DB_EMPTY]];
                    break;
                case InputField::INPUT_COLOR:
                    $max = ($spec->size > 18 ? 18 : $spec->size);
                    break;
                case InputField::INPUT_SELECT_PICKER_MULTI:
                case InputField::INPUT_CHECKBOX_LIST:
                case InputField::INPUT_CHECKBOX_LIST_ICHECK:
                case InputField::INPUT_MULTISELECT:
                case InputField::INPUT_SELECT2_MULTI:
                    $isMultiple = true;
                    break;
                case InputField::INPUT_SELECT2:
                case InputField::INPUT_DROPDOWN_LIST:
                case InputField::INPUT_LIST_BOX:
                    if (isset($config['active']['options']['multiple']) && $config['active']['options']['multiple']) {
                        $isMultiple = true;
                    }
                    break;
                case InputField::INPUT_SELECT2_TAGS:
                    // received as a pipe delimited string
                    $max = $spec->size;
                    default:
                case InputField::INPUT_SELECT_PICKER:
                case InputField::INPUT_RADIO_LIST:
                case InputField::INPUT_RADIO_LIST_ICHECK:
                case InputField::INPUT_RADIO:
                    break;
                case InputField::INPUT_EDITOR_CK:
                case InputField::INPUT_EDITOR_BS_WYSIHTML5:
                case InputField::INPUT_EDITOR_BS_SUMMERNOTE:
                    break;
                default:
                    break;
            }

            if ($isMultiple) {
                // apply default conversion of multiple selections array into pipe delimited string
                $addRules[] = ['check' => [FilterValidator::className()], 'rule' => [FilterValidator::className(), 'filter' => 'implode', 'makeArray' => true, 'argsFirst' => true, 'args' => ['|']]];
            }

            if ($isString && $max != -1) {
                if (!$this->checkHasRule($rules, $attribute, 'string', 'max')) {
                    if (isset($config['active']['options']['maxlength'])) {
                        $max = $config['active']['options']['maxlength'];
                    }
                    $data[] = ['string', 'max' => ($max ? $max : $spec->size)];
                }
                if (!$noTrim && !$this->checkHasRule($rules, $attribute, 'filter', 'filter', 'trim')) {
                    $data[] = ['filter', 'filter' => 'trim'];
                }
            }

            if ($dateFormat != '') {
                if (!$this->checkHasRule($rules, $attribute, 'date')) {
                    $data[] = ['date', 'format' => 'php:' . $dateFormat];
                }
            }

            if ($defaultOnEmpty !== null) {
                if (!$this->checkHasRule($rules, $attribute, 'default' , 'value')) {
                    $data[] = ['default', 'value' => $defaultOnEmpty];
                }
            }

            if ($addRules) {
                foreach ($addRules as $rule) {
                    $skipRule = false;
                    if (isset($rule['check']) && $rule['check']) {
                        if (is_array($rule['check']) && $this->checkHasRule($rules, $attribute, $rule['check'][0] , (isset($rule['check'][1]) ? $rule['check'][1] : ''), (isset($rule['check'][2]) ? $rule['check'][2] : null))) {
                            $skipRule = true;
                        } elseif ($this->checkHasRule($rules, $attribute, $rule['check'])) {
                            $skipRule = true;
                        }
                    }
                    if (!$skipRule) {
                        $data[] = (isset($rule['rule']) ? $rule['rule'] : $rule);
                    }
                }
            }

        }
        return $data;
    }

    /**
     * Check rules to see if one matches the type specified
     * @param array $rules
     * @param string $attribute
     * @param string $ruleType
     * @param string $ruleOption
     * @param mixed $ruleOptionValue
     * @return boolean
     */
    public function checkHasRule($rules, $attribute = '', $ruleType, $ruleOption = '', $ruleOptionValue = null)
    {
        if ($rules) {
            foreach ($rules as $key => $rule) {
                if ($rule[0] == $ruleType) {
                    if ($ruleOption != '') {
                        if (isset($rule[$ruleOption])) {
                            if ($ruleOptionValue !== null) {
                                if ($ruleOptionValue == '!') {
                                    if (!empty($rule[$ruleOption])) {
                                        // not empty
                                        return true;
                                    }
                                } else {
                                    if ($rule[$ruleOption] == $ruleOptionValue) {
                                        return true;
                                    }
                                }
                                return false;
                            }
                            return true;
                        }
                        return false;
                    }
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Override default implementation to make use of self::attributeConfig() to determine
     * scenarios. If not set then the default parent implementation will be used.
     * @see \yii\base\Model::rules()
     * @return array
     */
    public function scenarios()
    {
        $config = $this->attributeConfig();
        if (!$config) {
            return parent::scenarios();
        }
        $data = [];
        $data[self::SCENARIO_DEFAULT] = [];
        $data['all'] = [];
        $byException = [];
        foreach ($config as $attribute => $attrConfig) {
            if (array_key_exists('scenarios', $attrConfig)) {
                if ($attrConfig['scenarios']) {
                    foreach ($attrConfig['scenarios'] as $key => $scenario) {
                        if (!array_key_exists($scenario, $data)) {
                            $data[$scenario] = [];
                            $data[$scenario][] = $attribute;
                        } elseif (!in_array($attribute, $data[$scenario])) {
                            $data[$scenario][] = $attribute;
                        }
                    }
                }
            }
            if (array_key_exists('rules', $attrConfig)) {
                if ($attrConfig['rules']) {
                    if (array_key_exists('scenarios', $attrConfig['rules'])) {
                        foreach ($attrConfig['rules']['scenarios'] as $scenario => $rules) {
                            if (!array_key_exists($scenario, $data)) {
                                $data[$scenario] = [];
                                $data[$scenario][] = $attribute;
                            } elseif (!in_array($attribute, $data[$scenario])) {
                                $data[$scenario][] = $attribute;
                            }
                        }
                    } else {
                        foreach ($attrConfig['rules'] as $key => $rule) {
                            if (array_key_exists('except', $rule)) {
                                $scenarios = (!is_array($rule['except']) ? [$rule['except']] : $rule['except']);
                                if (!array_key_exists($attribute, $byException)) {
                                    $byException[$attribute] = [];
                                }
                                $byException[$attribute] = array_merge($byException[$attribute], $scenarios);
                            } elseif (array_key_exists('on', $rule)) {
                                $scenarios = (!is_array($rule['on']) ? [$rule['on']] : $rule['on']);
                                foreach ($scenarios as $key2 => $scenario) {
                                    if (!array_key_exists($scenario, $data)) {
                                        $data[$scenario] = [];
                                        $data[$scenario][] = $attribute;
                                    } elseif (!in_array($attribute, $data[$scenario])) {
                                        $data[$scenario][] = $attribute;
                                    }
                                }
                            } else {
                                // add to default scenario
                                if (!in_array($attribute, $data[self::SCENARIO_DEFAULT])) {
                                    $data[self::SCENARIO_DEFAULT][] = $attribute;
                                }
                                if (false) {
                                    // actually add to all scenarios, unless excepted elsewhere
                                    if (!array_key_exists($attribute, $byException)) {
                                        $byException[$attribute] = [];
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // add to default scenario
                if (!in_array($attribute, $data[self::SCENARIO_DEFAULT])) {
                    $data[self::SCENARIO_DEFAULT][] = $attribute;
                }
            }
        }

        if ($byException) {
            $scenarios = array_keys($data);
            foreach ($byException as $attribute => $except) {
                foreach ($scenarios as $key => $scenario) {
                    if (!$except || !in_array($scenario, $except)) {
                        if (!array_key_exists($scenario, $data)) {
                            $data[$scenario] = [];
                            $data[$scenario][] = $attribute;
                        } elseif (!in_array($attribute, $data[$scenario])) {
                            $data[$scenario][] = $attribute;
                        }
                    }
                }
            }
        }

        if (!$data) {
            return parent::scenarios();
        }
        return $data;
    }

    /**
     * Default implementation returns an array of attributes associated with the current or specified scenario
     * @param string $scenario [optional]
     * @return array
     */
    public function getFormAttribs($scenario = null) {
        $scenario = (is_null($scenario) ? $this->getScenario() : $scenario);
        return ArrayHelper::getValue($this->scenarios(), $scenario, []);
    }

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

    /**
     * Get the default hint block text to use for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getHintBlock($attribute, $encode = false)
    {
        $message = '';
        $attributeDefaults = $this->attributeHintBlocks();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $message = $attributeDefaults[$attribute];
        } else {
            $message = $this->getAttributeConfig($attribute, 'hint');
        }
        if ($message) {
            $message = ($encode ? Html::encode($message) : $message);
        }
        return $message;
    }

    /**
     * Get array of attributes and what the default hint block should be
     *
     * @return array
     */
    public function attributeHintBlocks()
    {
        return [
        ];
    }

    /**
     * Get the default hint block text to use for an attribute when flagged as readonly
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getReadOnlyHintBlock($attribute, $encode = false)
    {
        $message = '';
        $attributeDefaults = $this->attributeReadOnlyHintBlocks();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $message = $attributeDefaults[$attribute];
        } else {
            $message = $this->getAttributeConfig($attribute, 'readonlyhint');
        }
        if ($message) {
            $message = ($encode ? Html::encode($message) : $message);
        }
        return $message;
    }

    /**
     * Get array of attributes and what the default hint block should be
     * when flagged as readonly
     *
     * @return array
     */
    public function attributeReadOnlyHintBlocks()
    {
        return [
        ];
    }

    /**
     * Get the default icon to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @return string
     */
    public function getIcon($attribute)
    {
        $icon = '';
        $attributeDefaults = $this->attributeIcons();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $icon = $attributeDefaults[$attribute];
        } else {
            $icon = $this->getAttributeConfig($attribute, 'icon');
        }
        return ($icon ? $icon : '');
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

    /**
     * Get the default place holder text to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getPlaceholder($attribute, $encode = false)
    {
        $text = '';
        $attributeDefaults = $this->attributePlaceholders();
        if (array_key_exists($attribute, $attributeDefaults)) {
            $text = $attributeDefaults[$attribute];
        } else {
            $text = $this->getAttributeConfig($attribute, 'placeholder');
        }
        if ($text) {
            $text = ($encode ? Html::encode($text) : $text);
        }
        return $text;
    }

    /**
     * Get array of attributes and what the default help block text should be
     *
     * @return array
     */
    public function attributePlaceholders()
    {
        return [
        ];
    }

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
        if (array_key_exists($attribute, $attributeDefaults)) {
            $message = $attributeDefaults[$attribute];
        } else {
            $message = $this->getAttributeConfig($attribute, 'tooltip');
        }
        if ($message) {
            $message = ($encode ? Html::encode($message) : $message);
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

    /**
     * Obtain list of fields that need to have string lengths checked as part of beforeSave()
     * By default look at formConfig when using this trait rather than return an empty array
     * @return array
     */
    public function beforeSaveStringFields()
    {
        $config = $this->attributeConfig();
        if (!$config) {
            return [];
        }
        $data = [];
        foreach ($config as $attribute => $attrConfig) {
            if (ArrayHelper::getValue($attrConfig, 'truncateOnSave', false)) {
                $data[] = $attribute;
            }
        }
        return $data;
    }

}
