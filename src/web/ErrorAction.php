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

use yii\base\Action;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\HttpException;

class ErrorAction extends Action
{
    /**
     * @var string the view file to be rendered. If not set, it will take the value of [[id]].
     * That means, if you name the action as "error" in "SiteController", then the view name
     * would be "error", and the corresponding view file would be "views/site/error.php".
     */
    public $view;
    /**
     * @var string the name of the error when the exception name cannot be determined.
     * Defaults to "Error".
     */
    public $defaultName;
    /**
     * @var string the message to be displayed when the exception message contains sensitive information.
     * Defaults to "An internal server error occurred.".
     */
    public $defaultMessage;


    /**
     * Runs the action
     *
     * @return string result content
     */
    public function run()
    {
        if (($exception = \Yii::$app->getErrorHandler()->exception) === null) {
            return '';
        }

        if ($exception instanceof HttpException) {
            $code = $exception->statusCode;
        } else {
            $code = $exception->getCode();
        }
        if ($exception instanceof Exception) {
            $name = $exception->getName();
        } else {
            $name = $this->defaultName ?: \Yii::t('yii', 'Error');
        }

        $basicName = $name;

        if ($code) {
            $name .= " (#$code)";
        }

        if ($exception instanceof UserException) {
            $message = $exception->getMessage();
        } else {
            $message = $this->defaultMessage ?: \Yii::t('yii', 'An internal server error occurred.');
        }

        if (\Yii::$app->getRequest()->getIsAjax()) {
            return "$name: $message";
        } else {
            return $this->controller->render($this->view ?: $this->id, [
                'basicName' => $basicName,
                'name' => $name,
                'message' => $message,
                'code' => $code,
                'exception' => $exception,
            ]);
        }
    }
}
