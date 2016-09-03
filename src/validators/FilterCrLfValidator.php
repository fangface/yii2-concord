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

use yii\validators\Validator;
use yii\validators\ValidationAsset;

/**
 * FilterCrLfValidator converts the attribute value by stripping all cr, lf and
 * any double spacing, and returning the trimmed result
 *
 * FilterValidator is actually not a validator but a data processor.
 * It invokes the filter callback to process the attribute value
 * and save the processed value back to the attribute.
 *
 * @author Fangface
 */
class FilterCrLfValidator extends Validator
{
    /**
     * @var boolean whether the filter should be skipped if an array input is given.
     * If true and an array input is given, the filter will not be applied.
     */
    public $skipOnArray = false;
    /**
     * @var boolean this property is overwritten to be false so that this validator will
     * be applied when the value being validated is empty.
     */
    public $skipOnEmpty = false;
    /**
     * @var boolean should spaces before comms be removed from stings, default true
     */
    public $stripSpaceBeforeComma = true;
    /**
     * @var boolean should spaces before comms be removed from stings, default false
     */
    public $stripSpaceAfterComma = false;

    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if (!$this->skipOnArray || !is_array($value)) {
            $model->$attribute = str_replace("\r\n", ' ', trim($model->$attribute));
            $model->$attribute = str_replace("\n", ' ', trim($model->$attribute));
            if ($this->stripSpaceBeforeComma) {
                while (strpos($model->$attribute, ' ,')) {
                    $model->$attribute = str_replace(' ,', ',', trim($model->$attribute));
                }
            }
            if ($this->stripSpaceAfterComma) {
                while (strpos($model->$attribute, ', ')) {
                    $model->$attribute = str_replace(', ', ',', trim($model->$attribute));
                }
            }
            while (strpos($model->$attribute, '  ')) {
                $model->$attribute = trim(str_replace('  ', ' ', $model->$attribute));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {

        $options = [];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        if ($this->stripSpaceBeforeComma) {
            $options['stripSpaceBeforeComma'] = 1;
        }

        if ($this->stripSpaceAfterComma) {
            $options['stripSpaceAfterComma'] = 1;
        }

        ValidationAsset::register($view);

        return 'value = localAppValidation.nocrlf($form, attribute, ' . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }
}
