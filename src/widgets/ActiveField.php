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

use fangface\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveField as grandParent;

class ActiveField extends \kartik\form\ActiveField
{

    /**
     * @var boolean whether to show hints for the field
     */
    public $showHints;

    /**
     * @var boolean whether to attempt a tidy up of some html output to aid review and debug during development
     */
    public $devTidy = false;

    /**
     * (non-PHPdoc)
     * @see \kartik\form\ActiveField::initTemplate()
     */
    protected function initTemplate()
    {
        parent::initTemplate();

        $this->template = str_replace("{error}\n{hint}", "{hint}\n{error}", $this->template);

        $showHints = isset($this->showHints) ? $this->showHints : ArrayHelper::getValue($this->form->formConfig, 'showHints', true);
        $showLabels = isset($this->showLabels) ? $this->showLabels : ArrayHelper::getValue($this->form->formConfig, 'showLabels', true);
        $showErrors = isset($this->showErrors) ? $this->showErrors : ArrayHelper::getValue($this->form->formConfig, 'showErrors', true);

        if (!$showLabels) {
            $this->template = str_replace("{label}\n", '', $this->template);
        }

        if (!$showHints) {
            $this->template = str_replace("{hint}\n", '', $this->template);
            $this->template = str_replace("{hint}", '', $this->template);
        }

        if (!$showErrors) {
            $this->template = str_replace("{error}", '', $this->template);
        }

        if ($this->devTidy) {
            $this->template = str_replace("{label}\n", "\t{label}\n\t", $this->template);
            $this->template = str_replace("{input}", "\n\t\t{input}\t", $this->template);
            $this->template = str_replace("{hint}", "\t\t{hint}", $this->template);
        }

        if ($this->form->type == ActiveForm::TYPE_HORIZONTAL) {
            $this->moveHintAndError($showLabels, $showHints, $showErrors);
        }

        $this->template = rtrim($this->template);
    }

    /**
     * Amend template for horizontal forms
     * @param boolean $showLabels
     * @param boolean $showHints
     * @param boolean $showErrors
     */
    public function moveHintAndError($showLabels, $showHints, $showErrors)
    {
        // shuffle hint and error into a different position
        if ($showErrors) {
            $this->template = str_replace("{error}", '', $this->template);
            $this->template = str_replace("{input}", ($this->devTidy ? "{input}\n\t\t{error}\n" : "{input}{error}"), $this->template);
        }
        if ($showHints) {
            if (!$showErrors) {
                $this->template = str_replace("{hint}", '', $this->template);
                $this->template = str_replace("{input}", ($this->devTidy ? "{input}\n\t\t{hint}\n" : "{input}{hint}"), $this->template);
            } else {
                $this->template = str_replace("{hint}\n", '', $this->template);
                $this->template = str_replace("{input}\n", ($this->devTidy ? "{input}\n\t\t{hint}\n" : "{input}{hint}"), $this->template);
            }
        }

    }

    /**
     * Parses and returns addon content
     *
     * @param string|array $addon the addon parameter
     * @param string $type type of addon append or prepend
     * @param boolean $devTidy [optional] should debug tidy up be performed
     * @param boolean $hasInputGroup [optional] if $type = 'icon' does addon also have prepend and/or append
     * @return string
     */
    public function getLocalAddonContent($addon, $type = '', $devTidy = false, $hasInputGroup = false)
    {
        if (is_array($addon) && $type != '') {
            $content = ArrayHelper::getValue($addon, 'content', '');
            $asButton = ArrayHelper::getValue($addon, 'asButton', false);
            if ($asButton) {
                $options = ArrayHelper::getValue($addon, 'options', []);
                $tag = ArrayHelper::getValue($options, 'tag', 'span');
                unset($options['tag']);
                if ($type == 'prepend') {
                    Html::addCssClass($options, 'input-group-addon');
                } else {
                    Html::addCssClass($options, 'input-group-btn');
                }
                $content = Html::tag($tag, "\n\t\t\t\t" . $content . "\n\t\t\t", $options);
            } elseif ($type == 'prepend') {
                $options = ArrayHelper::getValue($addon, 'options', []);
                $tag = ArrayHelper::getValue($options, 'tag', 'span');
                unset($options['tag']);
                Html::addCssClass($options, 'input-group-addon');
                $content = Html::tag($tag, "\n\t\t\t\t" . $content . "\n\t\t\t", $options);
            }
            if ($devTidy) {
                if ($content != '' && ArrayHelper::getValue($addon, 'tidy', true)) {
                    if ($type == 'append') {
                        $content = "\n\t\t\t" . $content;
                    } elseif ($type == 'icon' && $hasInputGroup) {
                        $content = "\n\t\t\t\t" . $content . "\n\t\t\t\t";
                    } else {
                        $content = "\n\t\t\t" . $content . "\n\t\t\t";
                    }
                }
            }
            return $content;
        }
        return parent::getAddonContent($addon);
    }

    /**
     * Initializes the addon for text inputs
     */
    protected function initAddon()
    {
        if (!empty($this->addon)) {
            $addon = $this->addon;
            $prepend    = $this->getLocalAddonContent(ArrayHelper::getValue($addon, 'prepend',  ''), 'prepend', $this->devTidy);
            $append     = $this->getLocalAddonContent(ArrayHelper::getValue($addon, 'append',   ''), 'append',  $this->devTidy);
            $icon       = $this->getLocalAddonContent(ArrayHelper::getValue($addon, 'icon',     ''), 'icon',    $this->devTidy, ($prepend != '' || $append != '' ? true : false));
            if ($prepend != '' || $append != '') {
                $addonText = "{input}";
                if ($icon) {
                    $addonText = $icon . $addonText;
                    $group = ArrayHelper::getValue($addon, 'iconOptions', []);
                    if (!array_key_exists('class', $group)) {
                        Html::addCssClass($group, 'input-icon');
                    }
                    if ($this->devTidy) {
                        $addonText = "\n\t\t\t" . Html::tag('div', $addonText . "\n\t\t\t", $group);
                    } else {
                        $addonText = Html::tag('div', $addonText, $group);
                    }
                }
                $addonText = $prepend . $addonText . $append;
                $group = ArrayHelper::getValue($addon, 'groupOptions', []);
                if (!array_key_exists('class', $group)) {
                    Html::addCssClass($group, 'input-group');
                }
                $contentBefore = ArrayHelper::getValue($addon, 'contentBefore', '');
                $contentAfter = ArrayHelper::getValue($addon, 'contentAfter', '');
                if ($this->devTidy) {
                    $addonText = Html::tag('div', $contentBefore . $addonText . $contentAfter . "\n\t\t", $group);
                } else {
                    $addonText = Html::tag('div', $contentBefore . $addonText . $contentAfter, $group);
                }
                $this->template = str_replace('{input}', $addonText, $this->template);
            } elseif ($icon != '') {
                $addonText = $icon . "{input}";
                $group = ArrayHelper::getValue($addon, 'iconOptions', []);
                if (!array_key_exists('class', $group)) {
                    Html::addCssClass($group, 'input-icon');
                }
                $contentBefore = ArrayHelper::getValue($addon, 'contentBefore', '');
                $contentAfter = ArrayHelper::getValue($addon, 'contentAfter', '');
                if ($this->devTidy) {
                    $addonText = Html::tag('div', $contentBefore . $addonText . $contentAfter . "\n\t\t", $group);
                } else {
                    $addonText = Html::tag('div', $contentBefore . $addonText . $contentAfter, $group);
                }
                $this->template = str_replace('{input}', $addonText, $this->template);
            }
        }
    }

    /**
     * Skip \kartik\form\ActiveField::checkbox()
     * @see \kartik\form\ActiveField::checkbox()
     */
    public function checkbox($options = [], $enclosedByLabel = true)
    {
        return grandParent::checkbox($options, $enclosedByLabel);
    }

    /**
     * Renders a static input (display only).
     *
     * @param array $options the tag options in terms of name-value pairs.
     * @return ActiveField object
     */
    public function staticInput($options = [])
    {
        if ($options && ArrayHelper::keyExists('value', $options)) {
            $content = $options['value'];
            unset($options['value']);
        } else {
            $content = isset($this->model[Html::getAttributeName($this->attribute)]) ? $this->model[Html::getAttributeName($this->attribute)] : '-';
        }
        Html::addCssClass($options, 'form-control-static');
        $this->parts['{input}'] = Html::tag('p', $content, $options);
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see \kartik\form\ActiveField::getToggleFieldList()
     */
    protected function getToggleFieldList($type, $items, $options = []) {
        $inline = ArrayHelper::remove($options, 'inline', false);
        $inputType = "{$type}List";
        if ($inline && !isset($options['itemOptions'])) {
            $options['itemOptions'] = [
                'labelOptions' => ['class' => "{$type}-inline"],
            ];
        }
        return grandParent::$inputType($items, $options);
    }
}
