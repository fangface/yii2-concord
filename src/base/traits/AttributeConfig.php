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

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

trait AttributeConfig
{
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
            if (array_key_exists('rules', $attrConfig)) {
                if ($attrConfig['rules']) {
                    if (array_key_exists('scenarios', $attrConfig['rules'])) {
                        if (array_key_exists($this->getScenario(), $attrConfig['rules']['scenarios'])) {
                            $rules = $attrConfig['rules']['scenarios'][$this->getScenario()];
                            foreach ($rules as $key => $rule) {
                                array_unshift($rule, [$attribute]);
                                $data[] = $rule;
                            }
                        }
                    } else {
                        foreach ($attrConfig['rules'] as $key => $rule) {
                            array_unshift($rule, [$attribute]);
                            $data[] = $rule;
                        }
                    }
                }
            }
        }
        return $data;
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
                                if (array_key_exists('except', $rule)) {
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
                                if (!array_key_exists(self::SCENARIO_DEFAULT, $data)) {
                                    $data[self::SCENARIO_DEFAULT] = [];
                                    $data[self::SCENARIO_DEFAULT][] = $attribute;
                                } elseif (!in_array($attribute, $data[self::SCENARIO_DEFAULT])) {
                                    $data[self::SCENARIO_DEFAULT][] = $attribute;
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($byException) {
            $scenarios = array_keys($data);
            foreach ($byException as $attribute => $except) {
                foreach ($scenarios as $key => $scenario) {
                    if (!in_array($scenario, $except)) {
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
        } elseif (!array_key_exists(self::SCENARIO_DEFAULT, $data)) {
            $data[self::SCENARIO_DEFAULT] = [];
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

}
