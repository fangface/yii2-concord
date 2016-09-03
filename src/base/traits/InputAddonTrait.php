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

use fangface\forms\InputField;
use fangface\helpers\Html;
use yii\helpers\ArrayHelper;


trait InputAddonTrait
{

    /**
     * @var string the input type
     */
    public $type = null;

    /**
     * @var array addon options. The following settings can be configured:
     * - prepend: array the prepend addon configuration
     * - append: array the append addon configuration
     * - content: string the prepend addon content
     * - asButton: boolean whether the addon is a button or button group. Defaults to false.
     * - options: array the HTML attributes to be added to the container.
     * - groupOptions: array HTML options for the input group
     * - contentBefore: string content placed before addon
     * - contentAfter: string content placed after addon
     * - icon: array HTML options for the input group
     * - iconPosition: string icon position 'left' or 'right'
     */
    public $addon = [];


    /**
     * Set the input field type
     *
     * @param string $type input field type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Add an addon to prepend or append to the output
     *
     * @param string|array $config addon config or string content
     * @param string $type [optional] default 'append' can be 'append' or 'prepend'
     * @param boolean $first [optional] force to front of array, default false
     * @return void
     */
    public function addAddon($config, $type = 'append', $first = false)
    {
        if (is_string($config)) {
            $config = ['content' => $config];
        }
        if (!isset($this->addon[$type])) {
            $this->addon[$type] = [];
        }
        if ($first) {
            array_unshift($this->addon[$type], $config);
        } else {
            $this->addon[$type][] = $config;
        }
    }

    /**
     * Add an icon addon
     *
     * @param string $options HTML options for the icon element
     * @param string $position [optional] default null (left) or right
     * @return void
     */
    public function addIconAddon($options, $position = null)
    {
        $this->addon['icon'] = $options;
        $this->addon['iconPosition'] = $position;
    }

    /**
     * Merge in extra addon settings
     *
     * @param array $addon
     * @return void
     */
    public function mergeAddon($addon)
    {
        $prepend = ArrayHelper::remove($addon, 'prepend', []);
        $append = ArrayHelper::remove($addon, 'append', []);
        $this->addon = array_merge($this->addon, $addon);
        if ($prepend) {
            if (is_string($prepend)) {
                $this->addAddon($prepend, 'prepend');
            } else {
                foreach ($prepend as $k => $v) {
                    $this->addAddon($v, 'prepend');
                }
            }
        }
        if ($append) {
            if (is_string($append)) {
                $this->addAddon($append, 'append');
            } else {
                foreach ($append as $k => $v) {
                    $this->addAddon($v, 'append');
                }
            }
        }
    }

    /**
     * Setup or extend the groupOptions
     *
     * @param string $name
     * @param mixed $value
     * @param boolean $extend [optional] default false
     * @return void
     */
    public function setGroupOption($name, $value, $extend = false)
    {
        if ($extend) {
            if (isset($this->addon['groupOptions'][$name])) {
                if (strpos($this->addon['groupOptions'][$name], $value) !== false) {
                    // value already exists
                } else {
                    $this->addon['groupOptions'][$name] .= ' ' . $value;
                }
            } else {
                $this->addon['groupOptions'][$name] = $value;
            }
        } else {
            $this->addon['groupOptions'][$name] = $value;
        }
    }

    /**
     * Set the default group size for the input-group
     *
     * @param string $size
     * @return void
     */
    public function setGroupSize($size = '')
    {
        if ($size) {
            $this->addon['groupSize'] = $size;
        }
    }

    /**
     * Generates the addon markup
     *
     * @return string
     */
    protected function generateAddon()
    {
        $content = '{input}';
        if (empty($this->addon)) {
            return $content;
        }

        $addon = $this->addon;
        $icon = ArrayHelper::getValue($addon, 'icon', []);
        if ($icon) {
            $iconPosition = ArrayHelper::getValue($addon, 'iconPosition', InputField::ICON_POSITION_LEFT);
            if ($this->isUnsupportedIconInput()) {
                $this->addAddon(['content' => Html::tag('i', '', $icon)], 'prepend', true);
            } else {
                $content = Html::tag('i', '', $icon) . "\n" . $content;
                $content = Html::tag('div', $content, [
                    'class' => trim('input-icon' . ($iconPosition && $iconPosition != InputField::ICON_POSITION_LEFT ? ' ' . $iconPosition : '')),
                ]);
            }
        }

        $addon = $this->addon;
        $prepend = $this->getAddonContent(ArrayHelper::getValue($addon, 'prepend', ''), 'prepend');
        $append = $this->getAddonContent(ArrayHelper::getValue($addon, 'append', ''), 'append');
        if ($prepend || $append) {
            $content = $prepend . ($prepend ? "\n" : '') . $content . ($append ? "\n" : '') . $append;
            $group = ArrayHelper::getValue($addon, 'groupOptions', []);
            $groupSize = ArrayHelper::getValue($addon, 'groupSize', '');
            $tag = ArrayHelper::remove($group, 'tag', 'div');
            Html::addCssClass($group, 'input-group');
            if ($groupSize) {
                Html::addCssClass($group, 'input-' . $groupSize);
            }
            $contentBefore = ArrayHelper::getValue($addon, 'contentBefore', '');
            $contentAfter = ArrayHelper::getValue($addon, 'contentAfter', '');
            $content = Html::tag($tag, $contentBefore . $content . $contentAfter, $group);
        }
        return $content;
    }


    /**
     * Parses and returns addon content
     *
     * @param string|array $addon the addon parameter
     * @param string $type type of addon prepend or append
     * @return string
     */
    protected function getAddonContent($addon, $type = 'append')
    {
        if (!is_array($addon)) {
            return $addon;
        }

        if (is_string($addon)) {
            return $addon;
        } elseif (isset($addon['content'])) {
            // a single addon
            $addons = [$addon];
        } else {
            // multiple addons
            $addons = $addon;
        }

        $allContent = '';
        foreach ($addons as $k => $addon) {
            $content = ArrayHelper::getValue($addon, 'content', '');
            if ($content == '') {
                if (isset($addon['class'])) {
                    // assume an icon needs to be output
                    $content = Html::tag('i', '', $addon);
                }
            }
            if (ArrayHelper::getValue($addon, 'raw', false) == true) {
                // content already prepared
                $allContent .= "\n" . $content;
            } else {
                $options = ArrayHelper::getValue($addon, 'options', []);
                $tag = ArrayHelper::remove($options, 'tag', 'span');
                if (ArrayHelper::getValue($addon, 'asButton', false) == true) {
                    Html::addCssClass($options, 'input-group-btn');
                    $allContent .= "\n" . Html::tag($tag, $content, $options);
                } else {
                    Html::addCssClass($options, 'input-group-addon' . ($type == 'append' ? ' addon-after' : ''));
                    $allContent .= "\n" . Html::tag($tag, $content, $options);
                }
            }
        }
        return $allContent;
    }

    protected function isUnsupportedIconInput()
    {
        switch ($this->type) {
            case InputField::INPUT_SELECT2:
            case InputField::INPUT_SELECT2_MULTI:
            case InputField::INPUT_SELECT2_TAGS:
            case InputField::INPUT_SELECT_PICKER:
            case InputField::INPUT_SELECT_PICKER_MULTI:
            case InputField::INPUT_SELECT_SPLITTER:
            case InputField::INPUT_MULTISELECT:
            case InputField::INPUT_MINI_COLORS:
            case InputField::INPUT_EDITOR_CK:
                return true;
        }
        return false;
    }
}
