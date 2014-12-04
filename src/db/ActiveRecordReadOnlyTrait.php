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

trait ActiveRecordReadOnlyTrait
{

    /**
     * @var boolean|null all objects in the array read only or not (default null same as false)
     */
    protected $readOnly                 = null;

    /**
     * @var boolean|null all objects in the array can be deleted or not (default null same as true)
     */
    protected $canDelete                = null;


    /**
     * Set the read only value for this AR
     *
     * @param boolean $value [OPTIONAL] default true
     */
    public function setReadOnly($value=true)
    {
        $this->readOnly = $value;
    }


    /**
     * Return true if this AR is set to read only
     *
     * @return boolean
     */
    public function getReadOnly()
    {
        return ($this->readOnly === null ? false : $this->readOnly);
    }


    /**
     * Set the can delete value for this AR
     *
     * @param boolean $value [OPTIONAL] default true
     */
    public function setCanDelete($value=true)
    {
        $this->canDelete = $value;
    }


    /**
     * Return true if this AR can be deleted
     *
     * @return boolean
     */
    public function getCanDelete()
    {
        return ($this->canDelete === null ? true : $this->canDelete);
    }

}
