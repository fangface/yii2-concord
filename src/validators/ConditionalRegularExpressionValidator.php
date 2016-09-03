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

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\helpers\Json;
use yii\validators\Validator;
use yii\validators\ValidationAsset;

/**
 * ConditionalRegularExpressionValidator validates that the attribute value matches the specified [[pattern]].
 *
 * If the [[not]] property is set true, the validator will ensure the attribute value do NOT match the [[pattern]].
 *
 * Once the validation is performed a subsequent [[patternFail]] or [[patternPass]] pattern match will be perfomed
 * accordingly
 *
 * If [[patternPass]] should be run on [[pattern]] passing, leaving [[patternFail]] unused in the event of
 * [[pattern]] failing then simply setting [[patternFail]] to false will achieve this
 */
class ConditionalRegularExpressionValidator extends Validator
{
    /**
     * @var string the regular expression to be matched with
     */
    public $pattern;
    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the regular expression defined via [[pattern]] should NOT match the attribute value.
     */
    public $not = false;
    /**
     * @var string the regular expression to be matched with when passing the conditional pattern
     */
    public $patternOnPass;
    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the regular expression defined via [[patternOnPass]] should NOT match the attribute value.
     */
    public $notOnPass = false;
    /**
     * @var string the error message to use if [[patternOnPass]] is used and successfully matched
     */
    public $messageOnPass;
    /**
     * @var string the regular expression to be matched with when failing the conditional pattern
     */
    public $patternOnFail;
    /**
     * @var boolean whether to invert the validation logic. Defaults to false. If set to true,
     * the regular expression defined via [[patternOnFail]] should NOT match the attribute value.
     */
    public $notOnFail = false;
    /**
     * @var string the error message to use if [[patternOnFail]] is used and successfully matched
     */
    public $messageOnFail = '';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->pattern === null) {
            throw new InvalidConfigException('The "pattern" property must be set.');
        }
        if ($this->patternOnPass === null) {
            throw new InvalidConfigException('The "patternOnPass" property must be set.');
        }
        if ($this->patternOnFail === null) {
            throw new InvalidConfigException('The "patternOnPass" property must be set.');
        }
        if ($this->messageOnPass === null) {
            $this->messageOnPass = Yii::t('yii', '{attribute} is invalid.');
        }
        if ($this->messageOnFail === null) {
            $this->messageOnFail = Yii::t('yii', '{attribute} is invalid.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        $valid = !is_array($value) &&
            (!$this->not && preg_match($this->pattern, $value)
            || $this->not && !preg_match($this->pattern, $value));

        if ($valid) {
            $valid = (!$this->notOnPass && preg_match($this->patternOnPass, $value)
                || $this->notOnPass && !preg_match($this->patternOnPass, $value));
            return $valid ? null : [$this->messageOnPass, []];
        } else {
            if ($this->patternOnFail === false) {
                $valid = true;
            } else {
                $valid = (!$this->notOnFail && preg_match($this->patternOnFail, $value)
                    || $this->notOnFail && !preg_match($this->patternOnFail, $value));
            }
            return $valid ? null : [$this->messageOnFail, []];
        }
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        $pattern = Html::escapeJsRegularExpression($this->pattern);
        $options = [
            'pattern' => new JsExpression($pattern),
            'not' => $this->not,
        ];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        $pattern = Html::escapeJsRegularExpression($this->patternOnPass);
        $optionsPass = [
            'pattern' => new JsExpression($pattern),
            'not' => $this->notOnPass,
            'message' => Yii::$app->getI18n()->format($this->messageOnPass, [
                'attribute' => $model->getAttributeLabel($attribute),
            ], Yii::$app->language),
        ];
        if ($this->skipOnEmpty) {
            $optionsPass['skipOnEmpty'] = 1;
        }

        if ($this->patternOnFail === false) {
            $optionsFail = false;
        } else {
            $pattern = Html::escapeJsRegularExpression($this->patternOnFail);
            $optionsFail = [
                'pattern' => new JsExpression($pattern),
                'not' => $this->notOnFail,
                'message' => Yii::$app->getI18n()->format($this->messageOnFail, [
                    'attribute' => $model->getAttributeLabel($attribute),
                ], Yii::$app->language),
            ];
            if ($this->skipOnEmpty) {
                $optionsFail['skipOnEmpty'] = 1;
            }
        }

        ValidationAsset::register($view);

        return 'localAppValidation.conditionalRegExp(value, messages, ' . Json::htmlEncode($options) . ', ' . Json::htmlEncode($optionsPass) . ', ' . Json::htmlEncode($optionsFail) . ');';
    }
}
