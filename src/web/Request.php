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

/**
 * Extend \yii\web\Request so that we can override csrf validation on a limited number of routes
 * @author Fangface
 */
class Request extends \yii\web\Request
{
    public $noCsrfRoutes = [];

    /**
     * (non-PHPdoc)
     * @see \yii\web\Request::validateCsrfToken()
     */
    public function validateCsrfToken($token = null)
    {
        if(
            $this->enableCsrfValidation &&
            in_array(\Yii::$app->getUrlManager()->parseRequest($this)[0], $this->noCsrfRoutes)
        ) {
            return true;
        }
        return parent::validateCsrfToken();
    }

    /**
     * Returns POST or GET parameter with a given name.
     *
     * @param string $name the parameter name
     * @param mixed $defaultValue the default parameter value if the parameter does not exist.
     * @return array|mixed
     */
    public function postOrGet($name, $defaultValue = null)
    {
        $value = \Yii::$app->request->post($name, null);
        if ($value === null) {
            $value = \Yii::$app->request->get($name, $defaultValue);
        }
        return $value;
    }

}
