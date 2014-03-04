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

trait OffsetAccess
{

    /**
     * Check if an attribute is defined
     *
     *<code>
     * var_dump(isset($config['database']));
     *</code>
     *
     * @param string $offset
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->__isset($key);
    }


    /**
     * Gets an attribute using the array-syntax
     *
     *<code>
     * print_r($config['database']);
     *</code>
     *
     * @param string $key
     * @return mixed
     */
    public function &offsetGet($key)
    {
        return $this->get($key);
    }


    /**
     * Sets an attribute using the array-syntax
     *
     *<code>
     * $config['database'] = array('adapter' => 'Mysql');
     *</code>
     *
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }


    /**
     * Unsets an attribute using the array-syntax
     *
     *<code>
     * unset($config['database']);
     *</code>
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->__unset($key);
        return true;
    }


}
