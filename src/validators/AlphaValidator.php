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

namespace fangface\validators;

use yii\helpers\Json;
use yii\validators\Validator;

/**
 * AlphaValidator validates that the attribute value is a alpha/alphanumeric string.
 *
 * @author Nicola Puddu (based on yii1 version at http://www.yiiframework.com/extension/alpha/)
 * @author Fangface
 */
class AlphaValidator extends Validator
{
 	/**
	 * @var string the basic pattern used to create the regular expression.
	 */
	private $_basicPattern1 = '/^[a-zA-Z{additionalParams}]{{minChars},{maxChars}}$/';
 	/**
	 * @var string the pattern used to create the regular expression when first character must be simple alpha.
	 */
	private $_basicPattern2 = '/^[a-zA-Z]{1}[a-zA-Z{additionalParams}]{{minChars},{maxChars}}$/';
	/**
	 * @var string the additional parameter for numeric data.
	 */
	private $_numericData = '0-9';
	/**
	 * @var string the additional parameter for spaces
	 */
	private $_spaces = ' ';
	/**
	 * @var string the additional parameter for minus
	 */
	private $_minus = '-';
	/**
	 * @var string the additional parameter for underscore
	 */
	private $_underscore = '_';
	/**
	 * @var string the additional parameter for dot
	 */
	private $_dot = '\\.';
	/**
	 * @var string list of all latin accented letters
	 */
	private $_allAccentedLetters = 'ÀÁÂÃÄÅĀĄĂÆÇĆČĈĊĎĐÈÉÊËĒĘĚĔĖĜĞĠĢĤĦÌÍÎÏĪĨĬĮİĲĴĶŁĽĹĻĿÑŃŇŅŊÒÓÔÕÖØŌŐŎŒŔŘŖŚŠŞŜȘŤŢŦȚÙÚÛÜŪŮŰŬŨŲŴÝŶŸŹŽŻàáâãäåāąăæçćčĉċďđèéêëēęěĕėƒĝğġģĥħìíîïīĩĭįıĳĵķĸłľĺļŀñńňņŉŋòóôõöøōőŏœŕřŗśšşŝșťţŧțùúûüūůűŭũųŵýÿŷžżźÞþßſÐð';
	/**
	 * @var string list of most common latin accented letters
	 */
	private $_basicAccentedLetters = 'ÀÁÂÃÄĀĂÈÉÊËĚĔĒÌÍÎÏĪĨĬÒÓÔÕÖŌÙÚÛÜŪŬŨàáâãäāăèéêëēěĕìíîïīĩĭòóôõöōŏùúûüūŭũ';

	/**
	 * @var int minimum number of characters to validate the string
	 */
	public $minChars = 1;
	/**
	 * @var int maximum number of characters to validate the string
	 */
	public $maxChars = null;
	/**
	 * @var boolean
	 */
	public $allowNumbers = false;
	/**
	 * @var boolean
	 */
	public $allowSpaces = false;
	/**
	 * @var boolean
	 */
	public $allowMinus = false;
	/**
	 * @var boolean
	 */
	public $allowUnderscore = false;
	/**
	 * @var boolean
	 */
	public $allowDot = false;
	/**
	 * @var boolean
	 */
	public $allAccentedLetters = false;
	/**
	 * @var boolean
	 */
	public $accentedLetters = false;
	/**
	 * @var array list of additional characters allowed
	 */
	public $extra = array();

    /**
     * @inheritdoc
     */
	public function validateAttribute($object, $attribute)
	{
		// get the pattern
		$pattern = $this->elaboratePattern();
		// validate the string
		$value = $object->$attribute;
        if ($this->skipOnEmpty && empty($value)) {
            return;
        }
		if(!preg_match($pattern, $value))
		{
            $message = ($this->message ? $this->message : \Yii::t('yii', '{attribute} is invalid.'));
		    $this->addError($object, $attribute, str_replace('{attribute}', $object->getAttributeLabel($attribute), $message));
		}
	}

	/**
	 * Returns the JavaScript needed for performing client-side validation.
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 * @return string the client-side validation script.
	 */
	public function clientValidateAttribute($object, $attribute)
	{

		// get the pattern
		$pattern = $this->elaboratePattern();
		// get the error message
		$message = ($this->message ? $this->message : \Yii::t('yii', '{attribute} is invalid.'));
        $message = str_replace('{attribute}', $object->getAttributeLabel($attribute), $message);
		$condition="!value.match({$pattern})";

		return "
if(".$condition.") {
	messages.push(".Json::encode($message).");
}
";
	}

    /**
	 * @return string the regular expression used for validation
	 */
	public function elaboratePattern()
	{
		$additionalParams = null;
		// add numbers
		if ($this->allowNumbers) {
			$additionalParams .= $this->_numericData;
		}
		// add spaces
		if ($this->allowSpaces) {
			$additionalParams .= $this->_spaces;
		}
		// add accented letters
		if ($this->allAccentedLetters) {
			$additionalParams .= $this->_allAccentedLetters;
		} elseif ($this->accentedLetters) {
			$additionalParams .= $this->_basicAccentedLetters;
		}
		// add minus
		if ($this->allowMinus) {
			$additionalParams .= $this->_minus;
		}
		// add underscore
		if ($this->allowUnderscore) {
			$additionalParams .= $this->_underscore;
		}
        // add dot
        if ($this->allowDot) {
            $additionalParams .= $this->_dot;
        }
		// add extra characters
		if (count($this->extra)) {
			$additionalParams .= implode('\\', $this->extra);
		}
		if (true) {
		    if ($this->minChars > 0) {
		        $this->minChars--;
		    }
		}
		return str_replace(array('{additionalParams}', '{minChars}', '{maxChars}'), array($additionalParams, $this->minChars, $this->maxChars), (true ? $this->_basicPattern2 : $this->_basicPattern1));
	}
}
