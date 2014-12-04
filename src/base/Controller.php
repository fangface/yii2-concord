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

namespace fangface\base;

use fangface\base\traits\ServiceGetter;
use fangface\db\ActiveRecord;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;

class Controller extends \yii\web\Controller
{

    use ServiceGetter;

    /**
     * @var boolean has ajax response been prepared
     */
    private $ajaxResponseStarted = false;

    /**
     * @var array contains array of AJAX items to be output at the end of an AJAX request
     */
    private $ajaxResponses = array();

    /**
     * @var array contains variables for passing implicitly to all views
     */
    private $parameters = array();

    /**
     * Output a basic view allowing for if the request is ajax or not
     *
     * @param string $div div into which the view should be rendered for ajax requests
     * @param string $view name of view to render
     * @param string $params [optional] array of params to pass to view
     * @return multitype:|string
     */
    public function basicRenderActionView($div, $view, $params = [])
    {
        if (\Yii::$app->request->getIsAjax()) {
            $this->startAjaxResponse();
            $this->addAjaxResponse($div, $view, $params);
            return $this->endAjaxResponse();
        } else {
            return $this->render($view, $params);
        }
    }

    /**
     * Renders a view in response to an AJAX request.
     *
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * @return string the rendering result.
     */
    public function renderAjaxLocal($view, $params = [])
    {
        return $this->getView()->renderAjaxLocal($view, $params, $this);
    }

    /**
     * Start AJAX response
     */
    public function startAjaxResponse()
    {
        if (!$this->ajaxResponseStarted) {
            $this->getView()->clear();
            $this->ajaxResponses = array();
            $this->ajaxResponseStarted = true;
        }
    }

    /**
     * Render an AJAX view and add it to the AJAX response
     *
     * @param string $div DIV id into which the view should be rendered
     * @param string $view Name of view to render
     * @param array $params [optional]
     * @param string $type [optional] default 'general'
     */
    public function addAjaxResponse($div, $view, $params = [], $type = 'general')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses[] = [
            'type' => $type,
            'name' => $div,
            'data' => $this->renderAjaxLocal($view, $params),
        ];
        $this->getView()->clear();
    }

    /**
     * Add an AJAX response
     *
     * @param string $div DIV id into which the content should be rendered
     * @param string $content Content to be rendered
     * @param string $type [optional] default 'general'
     */
    public function addAjaxResponseRaw($div, $content, $type = 'general')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses[] = [
            'type' => $type,
            'name' => $div,
            'data' => $content,
        ];
    }

    /**
     * Add a redirect to the AJAX response
     *
     * @param string $url URL to redirect the user to
     * @param string $type [optional] default 'general'
     */
    public function addAjaxRedirect($url, $type = 'general')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses[] = [
            'type' => $type,
            'name' => 'redirect',
            'data' => $url,
        ];
    }

    /**
     * Add a javascript snippet to be eval'd by the AJAX response processor client side
     *
     * @param string $js JS to be processed
     * @param string $type [optional] default 'general'
     */
    public function addAjaxJSResponse($js, $type = 'general')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses[] = [
            'type' => $type,
            'name' => 'js_x',
            'data' => $js,
        ];
    }

    /**
     * Add data to the response
     * @param array $array array of data to be included in the response
     */
    public function addAjaxDataResponse($array = [])
    {
        $this->startAjaxResponse();
        if ($array) {
            foreach ($array as $k => $v) {
                $this->ajaxResponses[$k] = $v;
            }
        }
    }

    /**
     * output a jQuery call to scroll the specified div to the position given
     * by default the function scrolls to the top of the page
     *
     * @param string $div
     * @param number $scrollTop
     * @param string $scrollSpeed
     */
    public function addAjaxScrollToTop($div = 'html, body', $scrollTop = 0, $scrollSpeed = 'slow')
    {
        $this->startAjaxResponse();
        $this->addAjaxJSResponse('$("' . $div . '").animate({ scrollTop: ' . $scrollTop . ' }, "' . $scrollSpeed . '");');
    }

    /**
     * Inlucde a toastr ajax response
     *
     * @param string $title
     * @param string $message
     * @param ActiveRecord $model
     * @param string $type [optional] 'success', 'info', 'warning' or 'error'. default is 'info'
     * @param array $options [optional] override default toast options
     *      closeButton: true,
     *      positionClass: 'toast-top-right',
     *      onclick: null,
     *      showDuration: 1000,
     *      hideDuration: 1000,
     *      timeOut: 5000,
     *      extendedTimeOut: 1000,
     *      showEasing: 'swing',
     *      hideEasing: 'linear',
     *      showMethod: 'fadeIn',
     *      hideMethod: 'fadeOut',
     */
    public function addAjaxToastResponse($title, $message, $type = 'info', $model = null, $options = [])
    {
        $message = ($message ? Html::encode($message) : '');
        if ($model !== null) {
            if ($model instanceof Model && $model->hasErrors()) {
                $errors = $model->getFirstErrors();
                foreach ($errors as $error) {
                    $message .= ($message != '' ? '<br/>' : '') . Html::encode($error);
                }
            }
            if ($model instanceof ActiveRecord && $model->hasActionErrors()) {
                $errors = $model->getBasicActionErrors();
                foreach ($errors as $error) {
                    $message .= ($message != '' ? '<br/>' : '') . Html::encode($error);
                }
            }
        }
        if ($options) {
            foreach ($options as $k => $option) {
                $options[$k] = Html::encode($option);
            }
        }
        $this->addAjaxJSResponse("App.newToast('" . Html::encode($type) . "', '" . Html::encode($title) . "', '" . $message . "', '" . Json::encode($options) . "');");
    }

    /**
     * End and return AJAX response
     */
    public function endAjaxResponse()
    {
        $this->startAjaxResponse();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->ajaxResponses;
    }

    /**
     * Send an OK response
     * @param array $array array to be included in the response
     * @param string $element name of element to put array in default is support
     */
    public function sendOk($array = [], $element = 'support')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses['answer'] = 1;
        $this->ajaxResponses[$element] = $array;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->ajaxResponses;
    }

    /**
     * Send a FAIL response
     * @param array $array array to be included in the response
     * @param string $element name of element to put array in default is support
     */
    public function sendFail($array = [], $element = 'support')
    {
        $this->startAjaxResponse();
        $this->ajaxResponses['answer'] = 0;
        $this->ajaxResponses[$element] = $array;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $this->ajaxResponses;
    }

    /**
     * Sets the response status code.
     * This method will set the corresponding status text if `$text` is null.
     * @param integer $value the status code
     * @param string $text the status text. If not set, it will be set automatically based on the status code.
     * @throws InvalidParamException if the status code is invalid.
     */
    public function setStatusCode($value, $text = null)
    {
        \Yii::$app->response->setStatusCode($value, $text);
    }

    /**
     * Add a parameter to be passed to all views
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Add many parameters to be passed to all views
     *
     * @param array $params
     * @param boolean $reset [optional] should paramaters be reset and replaced by $params
     */
    public function setParameters($params, $reset = false)
    {
        $this->parameters = ($reset ? $params : array_merge($this->parameters, $params));
    }

    /**
     * Get a parameter if set, that has been made available to all views
     *
     * @param string $name
     * @return mixed
     */
    public function getParameter($name)
    {
        return (isset($this->parameters[$name]) ? $this->parameters[$name] : false);
    }

    /**
     * Get all parameters that have been made available to all views
     *
     * @param string $name
     * @return mixed
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Check if request is in ajax mode else redirect
     * @param string $redirect
     */
    public function checkAjaxOrRedirect($redirect = 'index')
    {
        if (!\Yii::$app->request->getIsAjax()) {
            $this->redirect($redirect);
        }
    }

}
