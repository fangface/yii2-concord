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

namespace Concord\Base\Traits;

use Yii;

trait ServiceGetterStatic
{

    /**
     * Helper method to return a service (component) from the application manager
     * @param string $service
     * @param boolean $load should service be loaded if it has not been already default true
     * @return \yii\base\Component|null
     */
    public static function getService($service, $load = true)
    {
        return Yii::$app->get($service, $load);
    }

    /**
     * Helper method to check if a service (component) exists in the application manager
     * @param string $service
     * @return boolean
     */
    public static function hasService($service)
    {
        return Yii::$app->has($service);
    }

    /**
     * Registers a service (component) with this application manager
     * @param string $id component ID
     * @param \yii\base\Component|array|null $component the component to be registered with the module.
     */
    public static function setService($id, $component)
    {
        Yii::$app->set($id, $component);
    }


    /**
     * Helper method to return all services (components) from the application manager
     * @param boolean $loadedOnly return only loaded services (default is false which will return all services)
     * @return \yii\base\Component[]
     */
    public static function getServices($loadedOnly = false)
    {
        return Yii::$app->getComponents($loadedOnly);
    }


}
