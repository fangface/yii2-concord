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

use fangface\db\ActiveRecord;

trait ActiveRecordParentalTrait
{

    /**
     * @var ActiveRecord false parent model if this model is part of a map
     */
    protected $parentModel = false;

    /**
     * @var boolean Indicates if the current model was loaded from the db or is new
     */
    private $isNewRecord = true;

    /**
     * @var array Indicates if the current model was loaded from the db or is new
     */
    private $childHasChanges = array();

    /**
     * @var array Used to record oldAttributes values during beforeSaveAllInternal, allowing for revert in the event of a failure
     */
    private $childOldValues = array();


    /**
     * Set parent model
     *
     * @param ActiveRecord $parentModel
     */
    public function setParentModel($parentModel)
    {
        $this->parentModel = $parentModel;
    }


    /**
     * get parent model
     *
     * @return ActiveRecord
     */
    public function getParentModel()
    {
        return $this->parentModel;
    }


    /**
     * shorthand get parent model
     *
     * @return ActiveRecord
     */
    public function parent()
    {
        return $this->parentModel;
    }


    /**
     * check if a parent model exists
     *
     * @return boolean
     */
    public function hasParent()
    {
        return ($this->parentModel ? true : false);
    }


    /**
     * Returns a value indicating whether the current record is new or not.
     *
     * @return boolean
     */
    public function getIsNewRecord()
    {
        return $this->isNewRecord;
    }


    /**
     * Sets the value indicating whether the record is new.
     *
     * @param boolean $value
     *        whether the record is new or not
     */
    public function setIsNewRecord($value)
    {
        $this->isNewRecord = $value;
    }


    /**
     * Reset the array that records if a child has changes as part
     * of the saveAll() handling
     */
    public function resetChildHasChanges()
    {
        $this->childHasChanges = array();
        $this->childOldValues = array();
    }


    /**
     * Flag a child/relation as having changes, as part of the
     * saveAll() handling to know which relations required saving and may need
     * afterSaveAllInternal handling
     *
     * @param string|integer $childId
     * @param boolean $hasChanges [OPTIONAL] default true
     */
    public function setChildHasChanges($childId, $hasChanges = true)
    {
        $this->childHasChanges[$childId] = $hasChanges;
    }


    /**
     * Check to see if a child/relation has been flagged as having changes, as part of the
     * saveAll() handling to know which relations required saving and may need
     * afterSaveAllInternal handling
     *
     * @param string|integer $childId
     * @return boolean
     */
    public function getChildHasChanges($childId)
    {
        return (isset($this->childHasChanges[$childId]) ? $this->childHasChanges[$childId] : false);
    }


    /**
     * Return the oldAttribute values of the specified child at the time beforeSaveAllInternal
     * was processed. Typically used to revert old attributes back to what they were
     * in the event of a saveAll failing midway and needing to be reverted
     *
     * @param string|integer $childId
     * @param mixed $value
     * @param string $option (default null means set all options)
     * @return array
     */
    public function setChildOldValues($childId, $value, $option=null)
    {
        if ($option === null) {
            $this->childOldValues[$childId] = $value;
        } else {
            $this->childOldValues[$childId][$option] = $value;
        }
    }


    /**
     * Return the oldAttribute values of the specified child at the time beforeSaveAllInternal
     * was processed. Typically used to revert old attributes back to what they were
     * in the event of a saveAll failing midway and needing to be reverted
     *
     * @param string|integer $childId (default null will return all child values)
     * @param string $option (default null return all options)
     * @return array
     */
    public function getChildOldValues($childId = null, $option = null)
    {
        if ($childId === null) {
            return $this->childOldValues;
        } elseif ($option === null) {
            return (isset($this->childOldValues[$childId]) ? $this->childOldValues[$childId] : false);
        }
        return (isset($this->childOldValues[$childId][$option]) ? $this->childOldValues[$childId][$option] : false);
    }


    /**
     * Temporary function to help with testing
     */
    public function debugTest()
    {
        return array('debugTest' => array(
            'isNewRecord' => ($this->getIsNewRecord() ? 'true' : 'false'),
            'changed' => $this->getDirtyAttributes(),
            'old' => $this->getOldAttributes(),
            'current' => $this->toArray(),
            'recorded' => $this->getChildOldValues(),
        ));
    }

}
