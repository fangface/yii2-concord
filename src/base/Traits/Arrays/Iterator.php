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

namespace fangface\concord\base\traits\arrays;

trait Iterator
{

    private $position = 0;
    private $keys = array();

    /**
     * Rewinds the position back to the start i.e. 0
     */
    function rewind() {
        $this->resetKeys();
        $this->position = 0;
    }


    /**
     * Return the current active entry in the array based on the position
     */
    function current() {
        return $this->records[$this->key()];
    }


    /**
     * Returns the key of the current position
     */
    function key() {
        return $this->keys[$this->position];
    }


    /**
     * Moves to the next position in the array
     */
    function next() {
        $this->position++;
    }


    /**
     * Checks if the current position is valid
     */
    function valid() {
        if (isset($this->keys[$this->position])) {
            return $this->__isset($this->keys[$this->position]);
        }
        return false;
    }


    /**
     * Resets the numerical keys vs associative array values
     */
    function resetKeys() {
        $this->keys = array_keys($this->records);
    }

}
