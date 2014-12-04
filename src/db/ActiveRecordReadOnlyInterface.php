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

namespace fangface\db;

interface ActiveRecordReadOnlyInterface
{

    /**
     * Set if te AR is read only
     *
     * @param boolean $value
     *        [OPTIONAL] default true
     * @return void
     */
    public function setReadOnly($value = true);


    /**
     * Return true if this AR is set to read only
     *
     * @return boolean
     */
    public function getReadOnly();


    /**
     * Set if the AR can be deleted
     *
     * @param boolean $value
     *        [OPTIONAL] default true
     * @return void
     */
    public function setCanDelete($value = true);


    /**
     * Return true if this AR can be deleted
     *
     * @return boolean
     */
    public function getCanDelete();

}
