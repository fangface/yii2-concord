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

use common\lib\Exception;
use fangface\web\View;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\JsExpression;


trait Widget {

    /**
     * Section name yags to replace in the rendered layout. Enter this as `$key => $value` pairs,
     * where:
     * - $key: string, defines the section name.
     * - $value: string|Closure, the value that will be replaced. You can set it as a callback
     *   function to return a string of the signature:
     *      function ($widget) { return 'custom'; }
     *
     * For example:
     * ['{section}' => '<span class="glyphicon glyphicon-asterisk"></span']
     * @var array
     */
    public $sections = [];

    /**
     * @var string the name of the jQuery plugin
     */
    public $pluginName = '';

    /**
     * @var array widget plugin options
     */
    public $pluginOptions = [];

    /**
     * @var string id of object that events should be attached to if different from the widgets id
     */
    public $pluginEventId = '';

    /**
     * @var array widget JQuery events. You must define events in
     * event-name => event-function format
     * for example:
     * ~~~
     * pluginEvents = [
     *        "change" => "function() { log("change"); }",
     *        "open" => "function() { log("open"); }",
     * ];
     * ~~~
     */
    public $pluginEvents = [];

    /**
     * @var string the hashed variable to store the pluginOptions
     */
    protected $_hashVar;

    /**
     * @var string the Json encoded options
     */
    protected $_encOptions = '';


    protected function findSectionsToRender($content)
    {
        $content = preg_replace_callback("/{\\w+}/", function ($matches) {
            $content = $this->renderSection($matches[0]);
            if ($content === false) {
                return $matches[0];
            } else {
                return $this->findSectionsToRender($content);
            }
        }, $content);
        return $content;
    }

    /**
     * Renders a section of the specified name.
     * If the named section is not supported, an empty string will be returned
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     * @return string the rendering result of the section
     */
    public function renderSection($name)
    {
        return $this->getSection($name);
    }

    /**
     * Renders a section of the specified name.
     * If the named section is not supported false is returned
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     * @param boolean $blankOnEmpty return a blank string rather than false if not supported, default false
     * @return string|boolean the rendering result of the section or false if not supported
     */
    protected function getSection($name, $blankOnEmpty = false)
    {
        $value = '';
        if (is_array($this->sections) && !empty($this->sections)) {
            $value = ArrayHelper::getValue($this->sections, substr($name, 1, -1), ($blankOnEmpty ? '' : false));
            if ($value instanceof Closure) {
                $value = call_user_func($value, $this);
            }
            if (is_array($value)) {
                $array = $value;
                $value = '';
                foreach ($array as $k => $v) {
                    $value .= ($value != '' ? "\n" : '') . $v;
                }
            }
            return $value;
        }
        return ($blankOnEmpty ? '' : false);
    }

    /**
     * Generates a hashed variable to store the pluginOptions. The following special data attributes
     * will also be setup for the input widget, that can be accessed through javascript :
     * - 'data-plugin-{name}' will store the hashed variable storing the plugin options. The {name}
     *   tag will represent the plugin name (e.g. select2, typeahead etc.) - Fixes issue #6.
     * @param string $name the name of the plugin
     */
    protected function hashPluginOptions($name)
    {
        $this->_encOptions = empty($this->pluginOptions) ? '' : Json::encode($this->pluginOptions);
        $this->_hashVar = $name . '_' . hash('crc32', $this->_encOptions);
        $this->options['data-plugin-' . $name] = $this->_hashVar;
    }

    /**
     * Registers plugin options by storing it in a hashed javascript variable
     */
    protected function registerPluginOptions($name)
    {
        $view = $this->getView();
        $this->hashPluginOptions($name);
        $encOptions = empty($this->_encOptions) ? '{}' : $this->_encOptions;
        $view->registerJs("var {$this->_hashVar} = {$encOptions};\n", View::POS_HEAD);
    }

    /**
     * Registers a specific plugin and the related events
     *
     * @param string $name the name of the plugin
     * @param string $element the plugin target element
     * @param string $callback the javascript callback function to be called after plugin loads
     * @param string $callbackCon the javascript callback function to be passed to the plugin constructor
     */
    protected function registerPlugin($name = null, $element = null, $callback = null, $callbackCon = null)
    {
        $name = $name == null ? $this->pluginName : $name;
        $id = $element == null ? "jQuery('#" . $this->options['id'] . "')" : $element;
        $view = $this->getView();
        if ($this->pluginOptions !== false) {
            $this->registerPluginOptions($name, View::POS_HEAD);
            $script = "{$id}.{$name}({$this->_hashVar})";
            if ($callbackCon != null) {
                $script = "{$id}.{$name}({$this->_hashVar}, {$callbackCon})";
            }
            if ($callback != null) {
                $script = "jQuery.when({$script}).done({$callback});";
            }
            $view->registerJs($script);
        }

        if (!empty($this->pluginEvents)) {
            $eventId = $this->pluginEventId ? "jQuery('#" . $this->pluginEventId . "')" : $id;
            $js = [];
            foreach ($this->pluginEvents as $event => $handler) {
                $function = new JsExpression($handler);
                $js[] = "{$eventId}.on('{$event}', {$function});";
            }
            $js = implode("\n", $js);
            $view->registerJs($js);
        }
    }

    /**
     * Magic method to handle static method calls
     * @param string $name static method name
     * @param array $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        if ($name !== 'run' && substr($name, 0, 3) == 'run') {
            $method = lcfirst(substr($name, 3));
            if (method_exists(get_called_class(), $method)) {
                ob_start();
                ob_implicit_flush(false);
                /* @var $widget Widget */
                $config = (is_array($arguments) ? $arguments[0] : []);
                $config['class'] = get_called_class();
                $widget = \Yii::createObject($config);
                $out = $widget->$method();
                $b = ob_get_clean();
                return ($b ? $b . $out : $out);
            }
        }
        throw new Exception(sprintf('The required method "%s" does not exist for %s', $name, get_called_class()));
    }
}
