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

namespace fangface\base\traits;


interface AttributeSupportInterface
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
    public function getAttributeConfig($attribute, $option = null, $setting = null, $encode = false);

    /**
     * Get array of attribute config suitable for providing fieldConfig, hintBlocks, icons, placeHolders and toolTips
     *
     * @return array
     */
    public function attributeConfig();

    /**
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute);

    /**
     * Override default implementation to make use of self::attributeConfig() to determine
     * rules. If not set then the default parent implementation will be used.
     * @see \yii\base\Model::rules()
     * @return array
     */
    public function rules();

    /**
     * Get any auto rules base on table schema
     * @param array $rules
     * @param array $config
     * @param string $attribute
     * @return array
     */
    public function getAutoRules($rules, $config, $attribute = '');

    /**
     * Check rules to see if one matches the type specified
     * @param array $rules
     * @param string $attribute
     * @param string $ruleType
     * @param string $ruleOption
     * @param mixed $ruleOptionValue
     * @return boolean
     */
    public function checkHasRule($rules, $attribute = '', $ruleType, $ruleOption = '', $ruleOptionValue = null);

    /**
     * Override default implementation to make use of self::attributeConfig() to determine
     * scenarios. If not set then the default parent implementation will be used.
     * @see \yii\base\Model::rules()
     * @return array
     */
    public function scenarios();

    /**
     * Default implementation returns an array of attributes associated with the current or specified scenario
     * @param string $scenario [optional]
     * @return array
     */
    public function getFormAttribs($scenario = null);

    /**
     * Get the default active field config for an attribute
     *
     * @param string $attribute Attribute name
     * @return array
     */
    public function getActiveFieldSettings($attribute);

    /**
     * Get array of attributes and their default active field config
     *
     * @return array
     */
    public function attributeActiveFieldSettings();

    /**
     * Get the default hint block text to use for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getHintBlock($attribute, $encode = false);

    /**
     * Get array of attributes and what the default hint block should be
     *
     * @return array
     */
    public function attributeHintBlocks();

    /**
     * Get the default hint block text to use for an attribute when flagged as readonly
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getReadOnlyHintBlock($attribute, $encode = false);

    /**
     * Get array of attributes and what the default hint block should be
     * when flagged as readonly
     *
     * @return array
     */
    public function attributeReadOnlyHintBlocks();

    /**
     * Get the default icon to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @return string
     */
    public function getIcon($attribute);

    /**
     * Get array of attributes and what the default icon should be associated with the attribute in forms
     *
     * @return array
     */
    public function attributeIcons();

    /**
     * Get the default place holder text to use in input boxes for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getPlaceholder($attribute, $encode = false);

    /**
     * Get array of attributes and what the default help block text should be
     *
     * @return array
     */
    public function attributePlaceholders();

    /**
     * Get the default tooltip text to use for an attribute
     *
     * @param string $attribute Attribute name
     * @param boolean $encode [default false] Should the resulting string be encoded
     * @return string
     */
    public function getTooltip($attribute, $encode = false);

    /**
     * Get array of attributes and what the default tooltip should be
     *
     * @return array
     */
    public function attributeTooltips();

    /**
     * Obtain list of fields that need to have string lengths checked as part of beforeSave()
     * By default look at formConfig when using this trait rather than return an empty array
     * @return array
     */
    public function beforeSaveStringFields();

}
