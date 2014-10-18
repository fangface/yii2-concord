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

namespace fangface\concord\base\traits;

trait Singleton
{

    protected static $instance;

    final public static function getInstance()
    {
        return isset(static::$instance) ? static::$instance : static::$instance = new static();
    }

    final private function __construct()
    {
        $this->onConstruct();
    }

    protected function onConstruct()
    {
    }

    final private function __wakeup()
    {
    }

    final private function __clone()
    {
    }

}
