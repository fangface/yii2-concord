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

namespace Concord\Base\Traits\Arrays;

trait MagicAccess
{

    /**
     * Magic method to Get the value of an attribute
     *
     *<code>
     * echo $config->database;
     *</code>
     *
     * @param string $key
     */
    public function &__get($key)
    {
        return $this->get($key);
    }


    /**
     * Magic method to Set an attribute
     *
     *<code>
     * $config->database = array('adapter' => 'Mysql');
     *</code>
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }


    /**
     * Magic method to check if attribute exists
     *
     *<code>
     * var_dump(isset($config->database));
     *</code>
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->records[$key]);
    }


    /**
     * Magic method to Unset the value of an attribute
     *
     *<code>
     * unset($config->database);
     *</code>
     *
     * @param string $key
     */
    public function __unset($key)
    {
        unset($this->records[$key]);
        return true;
    }

}
