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

namespace Concord\Db;

interface ActiveRecordParentalInterface
{


    /**
     * Set parent model
     *
     * @param \Concord\Db\ActiveRecord $parentModel
     */
    public function setParentModel($parentModel);


    /**
     * get parent model
     *
     * @return \Concord\Db\ActiveRecord
     */
    public function getParentModel();


    /**
     * shorthand get parent model
     *
     * @return \Concord\Db\ActiveRecord
     */
    public function parent();


    /**
     * check if a parent model exists
     *
     * @return boolean
     */
    public function hasParent();


    /**
     * Returns a value indicating whether the current record is new or not.
     *
     * @return boolean
     */
    public function getIsNewRecord();


    /**
     * Sets the model to reflect that the current record is a new record
     *
     * @param boolean $value
     *        whether the record is new or not
     */
    public function setIsNewRecord($value);


    /**
     * Reset the array that records if a child has changes as part
     * of the saveAll() handling
     */
    public function resetChildHasChanges();


    /**
     * Flag a child/relation as having changes, as part of the
     * saveAll() handling to know which relations required saving and may need
     * afterSaveAllInternal handling
     *
     * @param string|integer $childId
     * @param boolean $hasChanges [OPTIONAL] default true
     */
    public function setChildHasChanges($childId, $hasChanges = true);


    /**
     * Check to see if a child/relation has been flagged as having changes, as part of the
     * saveAll() handling to know which relations required saving and may need
     * afterSaveAllInternal handling
     *
     * @param string|integer $childId
     * @return boolean
     */
    public function getChildHasChanges($childId);


    /**
     * Return the oldAttribute values of the specified child at the time beforeSaveAllInternal
     * was processed. Typically used to revert old attributes back to what they were
     * in the event of a saveAll failing midway and needing to be reverted
     *
     * @param string|integer $childId
     * @param array|integer $value
     * @param string $option (default null means set all options)
     * @return array
     */
    public function setChildOldValues($childId, $value, $option=null);


    /**
     * Return the oldAttribute values of the specified child at the time beforeSaveAllInternal
     * was processed. Typically used to revert old attributes back to what they were
     * in the event of a saveAll failing midway and needing to be reverted
     *
     * @param string|integer $childId (default null will return all child values)
     * @param string $option (default null return all options)
     * @return array
     */
    public function getChildOldValues($childId = null, $option = null);


}
