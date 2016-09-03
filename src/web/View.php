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

namespace fangface\web;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class View extends \yii\web\View
{

    /**
     * @event \yii\base\Event an event that is triggered by [[endAjax()]].
     */
    const EVENT_END_AJAX_DEBUG = 'endAjax';

    /**
     * The location of registered JavaScript code block or files.
     * This means the location is in the head section.
     */
    const POS_JS_BLOCK = 6;

    /**
     * This is internally used as the placeholder for receiving the content registered for the js block section.
     */
    const PH_JS_BLOCK = '<![CDATA[APP-JS-BLOCK]]>';

    /**
     * @var array Body html tag parameters
     */
    public $bodyOptions = [];

    /**
     * @var array general options for this view
     */
    public $Opts = [];

    /**
     * @var boolean allow idle timeout of user session
     */
    public $enableIdleTimeout = true;

    /**
     * @var string simple single page style sheet
     */
    public $pageStyle = '';

    /**
     * @var array list of style sheets to be added to the view
     */
    public $pageStyles = [];

    /**
     * @var array list of style sheets to be added to the view after all other styles
     */
    public $pageStylesFinal = [];

    /**
     * @var string simple single page javascript file to be added to the view
     */
    public $pagePlugin = '';

    /**
     * @var array list of javascript files to be added to the view
     */
    public $pagePlugins = [];

    /**
     * @var string simple single javascript function to call on jQuery page ready
     */
    public $pageReadyJS = '';

    /**
     * @var array list of javascript functions to call on jQuery page ready
     */
    public $pageReadyJSs = [];

    /**
     * @var array list of javascript functions to call on jQuery page ready (before pageReadyJSs)
     */
    public $pageReadyPriorityJSs = [];

    /**
     * @var array list of javascript functions to call on jQuery page ready (after other page ready js)
     */
    public $pageReadyFinalJSs = [];

    /**
     * @var string simple single javascript function to call on jQuery page loaded
     */
    public $pageLoadJS = '';

    /**
     * @var array list of javascript functions to call on jQuery page loaded
     */
    public $pageLoadJSs = [];

    /**
     * @var array list of javascript functions to call on jQuery page ready (before pageLoadJSs)
     */
    public $pageLoadPriorityJSs = [];

    /**
     * @var array list of javascript functions to call on jQuery page load (after other page load js)
     */
    public $pageLoadFinalJSs = [];

    /**
     * @var array the registered base tag
     * @see registerBaseTag()
     */
    public $baseTag;

    /**
     * @var string Code for active menu item
     */
    public $activeMenuCode = '';

    /**
     * Add in some controller parameters that we want to make available to all
     * views without needing to pass them explicitly
     *
     * (non-PHPdoc)
     * @see \yii\base\View::renderFile($viewFile, $params, $context)
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        if (!$params) {
            $params = [];
        }
        if (method_exists(\Yii::$app->controller, 'getParameters')) {
            if (\Yii::$app->controller->getParameters()) {
                $params = array_merge(\Yii::$app->controller->getParameters(), $params);
            }
        }
        return parent::renderFile($viewFile, $params, $context);
    }

    /**
     * Renders a view in response to an AJAX request.
     *
     * Identical to \yii\web\View for renderAjax but clears out unwanted jsFiles from being repeated within the ajax request
     *
     * @param string $view the view name. Please refer to [[render()]] on how to specify this parameter.
     * @param array $params the parameters (name-value pairs) that will be extracted and made available in the view file.
     * @param object $context the context that the view should use for rendering the view. If null,
     * existing [[context]] will be used.
     * @return string the rendering result
     * @see render()
     */
    public function renderAjaxLocal($view, $params = [], $context = null)
    {
        $viewFile = $this->findViewFile($view, $context);
        ob_start();
        ob_implicit_flush(false);

        $this->beginPage();
        $this->head();
        $this->beginBody();
        echo $this->renderFile($viewFile, $params, $context);
        $this->jsBlock();
        $this->endBody();
        //$this->jsFiles = array();
        $this->renderPageReadyJS(true);
        $this->renderPageLoadJS(true);
        $this->endPage(true);
        $this->clear();

        return ob_get_clean();
    }

    /**
     * Renders the content to be inserted in the head section.
     * The content is rendered using the registered meta tags, link tags, CSS/JS code blocks and files.
     * @return string the rendered content
     */
    protected function renderHeadHtml()
    {
        $lines = [];
        if (!empty($this->metaTags)) {
            $lines[] = implode("\n\t", $this->metaTags);
        }

        if (!empty($this->baseTag)) {
            $lines[] = $this->baseTag;
        }

        if (!empty($this->linkTags)) {
            $lines[] = implode("\n\t", $this->linkTags);
        }

        if (empty($this->cssFiles)) {
            $this->cssFiles = [];
        }

        if ($this->pageStyle) {
            $this->addPageStyle($this->pageStyle, true);
            $this->pageStyle = '';
        }

        if ($this->pageStyles) {
            foreach ($this->pageStyles as $pageStyle) {
                $this->cssFiles[] = '<link href="' . (substr($pageStyle, 0, 1) == '.' || substr($pageStyle, 0, 1) == '/' ? $pageStyle : './css/pages/' . $pageStyle) . (substr($pageStyle, -4, 4) != '.css' ? '.css' : '')  . '" rel="stylesheet">';
            }
            $this->pageStyles = [];
        }

        if ($this->pageStylesFinal) {
            foreach ($this->pageStylesFinal as $pageStyle) {
                $this->cssFiles[] = '<link href="' . (substr($pageStyle, 0, 1) == '.' || substr($pageStyle, 0, 1) == '/' ? $pageStyle : './css/pages/' . $pageStyle) . (substr($pageStyle, -4, 4) != '.css' ? '.css' : '')  . '" rel="stylesheet">';
            }
            $this->pageStylesFinal = [];
        }

        if (!empty($this->cssFiles)) {
            $lines[] = implode("\n\t", $this->cssFiles);
        }

        if (!empty($this->css)) {
            $lines[] = implode("\n\t", $this->css);
        }

        if (!empty($this->jsFiles[self::POS_HEAD])) {
            $lines[] = implode("\n\t", $this->jsFiles[self::POS_HEAD]);
        }

        if (!empty($this->js[self::POS_HEAD])) {
            $lines[] = Html::script("\n\t\t" . implode("\t\t", $this->js[self::POS_HEAD]) . "\t", ['type' => 'text/javascript']);
        }

        return empty($lines) ? '' : "\t" . implode("\n\t", $lines);
    }

    /**
     * Marks the ending of an HTML page.
     * @param boolean $ajaxMode whether the view is rendering in AJAX mode.
     * If true, the JS scripts registered at [[POS_READY]] and [[POS_LOAD]] positions
     * will be rendered at the end of the view like normal scripts.
     */
    public function endPage($ajaxMode = false)
    {
        $this->trigger(self::EVENT_END_PAGE);
        $content = ob_get_clean();
        echo strtr($content, [
            self::PH_HEAD => $this->renderHeadHtml(),
            self::PH_BODY_BEGIN => $this->renderBodyBeginHtml(),
            self::PH_JS_BLOCK => $this->renderJsBlockHtml($ajaxMode),
            self::PH_BODY_END => $this->renderBodyEndHtml($ajaxMode),
        ]);

        $this->clear();
    }

    /**
     * Marks the position of the JS Block section.
     */
    public function jsBlock()
    {
        echo self::PH_JS_BLOCK;
    }

    /**
     * Renders the content to be inserted within the default js block
     * The content is rendered using the registered JS code blocks and files.
     * @param boolean $ajaxMode whether the view is rendering in AJAX mode.
     * @return string the rendered content
     */
    protected function renderJsBlockHtml($ajaxMode = false)
    {
        $lines = [];
        if (true) {

            if (!empty($this->jsFiles[self::POS_JS_BLOCK])) {
                $lines[] = implode("\n\t", $this->jsFiles[self::POS_JS_BLOCK]);
                $this->jsFiles[self::POS_JS_BLOCK] = [];
            }

            if ($this->pagePlugin) {
                $this->addPagePlugin($this->pagePlugin, true);
                $this->pagePlugin = '';
            }

            if ($this->pagePlugins) {
                foreach ($this->pagePlugins as $pagePlugin) {
                    $lines[] = Html::jsFile($pagePlugin);
                }
                $this->pagePlugins = [];
            }

            if (!empty($this->jsFiles[self::POS_END])) {
                $lines[] = implode("\n\t", $this->jsFiles[self::POS_END]);
                $this->jsFiles[self::POS_END] = [];
            }

            if (empty($this->js[self::POS_READY])) {
                $this->js[self::POS_READY] = [];
            }

            if ($this->pageReadyJS) {
                array_unshift($this->js[self::POS_READY], $this->pageReadyJS);
                $this->pageReadyJS = '';
            }

            if ($this->pageReadyJSs) {
                $this->js[self::POS_READY] = array_merge($this->pageReadyJSs, $this->js[self::POS_READY]);
                $this->pageReadyJSs = [];
            }

            if ($this->pageReadyPriorityJSs) {
                $this->js[self::POS_READY] = array_merge($this->pageReadyPriorityJSs, $this->js[self::POS_READY]);
                $this->pageReadyPriorityJSs = [];
            }

            if ($this->pageReadyFinalJSs) {
                $this->js[self::POS_READY] = array_merge($this->js[self::POS_READY], $this->pageReadyFinalJSs);
                $this->pageReadyFinalJSs = [];
            }


            if (!empty($this->js[self::POS_READY])) {
                if ($ajaxMode) {
                    $js = "\n\t\t" . implode("\n\t\t", $this->js[self::POS_READY]) . "\n\t";
                } else {
                    $js = "\n\t\tjQuery(document).ready(function () {\n\t\t\t" . implode("\n\t\t\t", $this->js[self::POS_READY]) . "\n\t\t});\n\t";
                }
                $this->js[self::POS_READY] = [];
                $lines[] = Html::script($js, ['type' => 'text/javascript']);
            }

            if (empty($this->js[self::POS_LOAD])) {
                $this->js[self::POS_LOAD] = [];
            }

            if ($this->pageLoadJS) {
                array_unshift($this->js[self::POS_LOAD], $this->pageLoadJS);
                $this->pageLoadJS = '';
            }

            if ($this->pageLoadJSs) {
                $this->js[self::POS_LOAD] = array_merge($this->pageLoadJSs, $this->js[self::POS_READY]);
                $this->pageLoadJSs = [];
            }

            if ($this->pageLoadPriorityJSs) {
                $this->js[self::POS_LOAD] = array_merge($this->pageLoadPriorityJSs, $this->js[self::POS_LOAD]);
                $this->pageLoadPriorityJSs = [];
            }

            if ($this->pageLoadFinalJSs) {
                $this->js[self::POS_LOAD] = array_merge($this->js[self::POS_LOAD], $this->pageLoadFinalJSs);
                $this->pageLoadFinalJSs = [];
            }
            if (!empty($this->js[self::POS_LOAD])) {
                if ($ajaxMode) {
                    $js = "\n\t\t" . implode("\n\t\t", $this->js[self::POS_LOAD]) . "\n\t";
                } else {
                    $js = "\n\t\tjQuery(window).load(function () {\n\t\t\t" . implode("\n\t\t\t", $this->js[self::POS_LOAD]) . "\n\t\t});\n\t";
                }
                $this->js[self::POS_LOAD] = [];
                $lines[] = Html::script($js, ['type' => 'text/javascript']);
            }
        }
        return empty($lines) ? '' : "\t" . implode("\n\t", $lines) . "\n";
    }

    /**
     * Add a page style for rendering later
     *
     * @param string $style
     * @param boolean $start [optional] should style be added to start of existing page styles
     */
    public function addPageStyle($style, $start = false)
    {
        if ($start) {
            array_unshift($this->pageStyles, \Yii::getAlias($style));
        } else {
            $this->pageStyles[] = \Yii::getAlias($style);
        }
    }

    /**
     * Add a page style for rendering later (after all other styles)
     *
     * @param string $style
     * @param boolean $start [optional] add to start of existing array
     */
    public function addFinalPageStyle($style, $start = false)
    {
        if ($start) {
            array_unshift($this->pageStylesFinal, \Yii::getAlias($style));
        } else {
            $this->pageStylesFinal[] = \Yii::getAlias($style);
        }
    }

    /**
     * Add a page javascript plugin for rendering later
     *
     * @param string $style
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPagePlugin($plugin, $start = false)
    {
        if ($start) {
            array_unshift($this->pagePlugins, \Yii::getAlias($plugin));
        } else {
            $this->pagePlugins[] = \Yii::getAlias($plugin);
        }
    }

    /**
     * Add js function to execute on jQuery page ready
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageReadyJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageReadyJSs, $js);
        } else {
            $this->pageReadyJSs[] = $js;
        }
    }

    /**
     * Add priority js function to execute on jQuery page ready
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageReadyPriorityJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageReadyPriorityJSs, $js);
        } else {
            $this->pageReadyPriorityJSs[] = $js;
        }
    }

    /**
     * Add priority js function to execute on jQuery page ready
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageReadyFinalJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageReadyFinalJSs, $js);
        } else {
            $this->pageReadyFinalJSs[] = $js;
        }
    }

    /**
     * Add js function to execute on jQuery page loaded
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageLoadJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageLoadJSs, $js);
        } else {
            $this->pageLoadJSs[] = $js;
        }
    }

    /**
     * Add priority js function to execute on jQuery page loaded
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageLoadPriorityJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageLoadPriorityJSs, $js);
        } else {
            $this->pageLoadPriorityJSs[] = $js;
        }
    }

    /**
     * Add priority js function to execute on jQuery page loaded
     *
     * @param string $js
     * @param boolean $start [optional] add to start of existing array
     */
    public function addPageLoadFinalJS($js, $start = false)
    {
        if ($start) {
            array_unshift($this->pageLoadFinalJSs, $js);
        } else {
            $this->pageLoadFinalJSs[] = $js;
        }
    }

    /**
     * Render stylesheet tags for the current view
     */
    public function renderPageStyles()
    {
        if ($this->pageStyle) {
            $this->addPageStyle($this->pageStyle, true);
            $this->pageStyle = '';
        }

        foreach ($this->pageStyles as $pageStyle) {
            echo "\t" . '<link href="' . (substr($pageStyle, 0, 1) == '.' || substr($pageStyle, 0, 1) == '/' ? $pageStyle : './css/pages/' . $pageStyle) . (substr($pageStyle, -4, 4) != '.css' ? '.css' : '')  . '" rel="stylesheet" type="text/css"/>' . "\n";
        }

        foreach ($this->pageStylesFinal as $pageStyle) {
            echo "\t" . '<link href="' . (substr($pageStyle, 0, 1) == '.' || substr($pageStyle, 0, 1) == '/' ? $pageStyle : './css/pages/' . $pageStyle) . (substr($pageStyle, -4, 4) != '.css' ? '.css' : '')  . '" rel="stylesheet" type="text/css"/>' . "\n";
        }
    }

    /**
     * Render plugin javascript script tags for the current view
     */
    public function renderPagePlugins()
    {
        if ($this->pagePlugin) {
            $this->addPagePlugin($this->pagePlugin, true);
            $this->pagePlugin = '';
        }

        foreach ($this->pagePlugins as $pagePlugin) {
            echo "\t" . '<script src="' . (substr($pagePlugin, 0, 1) == '.' || substr($pagePlugin, 0, 1) == '/' ? $pagePlugin : './js/pages/' . $pagePlugin) . (substr($pagePlugin, -3, 3) != '.js' ? '.js' : '') . '"></script>' . "\n";
        }

    }

    /**
     * Render jQuery page ready js function calls
     *
     * @param boolean $ajax is function being called as a result of rendering an ajax page
     */
    public function renderPageReadyJS($ajax = false)
    {

        // note non ajax output is already rendered in a jQuery document ready wrapper

        if ($ajax) {
            echo '<script>jQuery(document).ready(function() {' . "\n";
        }

        if ($this->pageReadyJS) {
            echo "\t\t" . $this->pageReadyJS . "\n";
        }

        foreach ($this->pageReadyPriorityJSs as $pageReadyJS) {
            echo "\t\t" . $pageReadyJS . "\n";
        }

        foreach ($this->pageReadyJSs as $pageReadyJS) {
            echo "\t\t" . $pageReadyJS . "\n";
        }

        foreach ($this->pageReadyFinalJSs as $pageReadyJS) {
            echo "\t\t" . $pageReadyJS . "\n";
        }

        if ($ajax) {
            echo '});</script>' . "\n";
        }

    }

    /**
     * Render jQuery page loaded js function calls
     *
     * @param boolean $ajax is function being called as a result of rendering an ajax page
     */
    public function renderPageLoadJS($ajax = false)
    {

        // note non ajax output is already rendered in a jQuery window load wrapper

        if ($ajax) {
            echo '<script>jQuery(window).load(function() {' . "\n";
        }

        if ($this->pageLoadJS) {
            echo "\t\t" . $this->pageLoadJS . "\n";
        }

        foreach ($this->pageLoadPriorityJSs as $pageLoadJS) {
            echo "\t\t" . $pageLoadJS . "\n";
        }

        foreach ($this->pageLoadJSs as $pageLoadJS) {
            echo "\t\t" . $pageLoadJS . "\n";
        }

        foreach ($this->pageLoadFinalJSs as $pageLoadJS) {
            echo "\t\t" . $pageLoadJS . "\n";
        }

        if ($ajax) {
            echo '});</script>' . "\n";
        }

    }

    /**
     * Called at the end of an ajax request by Controller::endAjaxResponse()
     */
    public function endAjax()
    {
        ob_start();
        ob_implicit_flush(false);
        $this->trigger(self::EVENT_END_AJAX_DEBUG);
        return ob_get_clean();
    }

    /**
     * Registers a base tag.
     * @param string $url
     * @param array $options the HTML attributes for the base tag.
     */
    public function registerBaseTag($url, $options = [])
    {
        $options['href'] = \Yii::getAlias($url);
        $this->baseTag = Html::tag('base', '', $options);
    }

    /**
     * (non-PHPdoc)
     * @see \yii\web\View::registerLinkTag()
     */
    public function registerLinkTag($options, $key = null)
    {
        if (ArrayHelper::keyExists('href', $options)) {
            $options['href'] = \Yii::getAlias($options['href']);
        }
        parent::registerLinkTag($options, $key);
    }

    /**
     * (non-PHPdoc)
     * @see \yii\web\View::clear()
     */
    public function clear()
    {
        $this->pageStyle = '';
        $this->pageStyles = [];
        $this->pageStylesFinal = [];
        $this->pagePlugin = '';
        $this->pagePlugins = [];
        $this->pageReadyJS = '';
        $this->pageReadyPriorityJSs = [];
        $this->pageReadyJSs = [];
        $this->pageReadyFinalJSs = [];
        $this->pageLoadJS = '';
        $this->pageLoadPriorityJSs = [];
        $this->pageLoadJSs = [];
        $this->pageLoadFinalJSs = [];
        $this->baseTag = [];
        parent::clear();
    }

}
