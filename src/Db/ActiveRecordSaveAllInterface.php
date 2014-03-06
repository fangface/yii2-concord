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

interface ActiveRecordSaveAllInterface
{

    /**
     * Saves the current record but also loops through defined relationships (if appropriate)
     * to save those as well
     *
     * @param boolean $runValidation
     *        should validations be executed on all models before allowing saveAll()
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $push
     *        is saveAll being pushed onto lazy (un)loaded models as well
     * @return boolean
     *        did saveAll() successfully process
     */
    public function saveAll($runValidation = true, $hasParentModel = false, $push = false);


    /**
     * Perform a saveAll() call but push the request down the model map including
     * models that are not currently loaded (perhaps because child models need to
     * pick up new values from parents
     *
     * @param boolean $runValidation
     *        should validations be executed on all models before allowing saveAll()
     * @return boolean
     *        did saveAll() successfully process
     */
    public function push($runValidation = true);


    /**
     * This method is called at the beginning of a saveAll() request on a record or model map
     *
     * @param boolean $runValidation
     *        should validations be executed on all models before allowing saveAll()
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $push
     *        is saveAll being pushed onto lazy (un)loaded models as well
     * @return boolean whether the saveAll() method call should continue
     *        If false, saveAll() will be cancelled.
     */
    public function beforeSaveAllInternal($runValidation = true, $hasParentModel = false, $push = false);


    /**
     * Called by beforeSaveAllInternal on the current model to determine if the whole of saveAll
     * can be processed - this is expected to be replaced in individual models when required
     *
     * @return boolean okay to continue with saveAll
     */
    public function beforeSaveAll();


    /**
     * This method is called at the end of a successful saveAll()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return void
     */
    public function afterSaveAllInternal($hasParentModel = false);


    /**
     * Called by afterSaveAllInternal on the current model once the whole of the saveAll() has
     * been successfully processed
     * @return void
     */
    public function afterSaveAll();


    /**
     * This method is called at the end of a failed saveAll()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return void
     */
    public function afterSaveAllFailedInternal($hasParentModel = false);


    /**
     * Called by afterSaveAllFailedInternal on the current model once saveAll() has
     * failed processing
     * @return void
     */
    public function afterSaveAllFailed();


    /**
     * Save the current record
     *
     * @see \yii\db\BaseActiveRecord::save()
     *
     * @param boolean $runValidation
     *        should validations be executed on all models before allowing saveAll()
     * @param array $attributes
     *        which attributes should be saved (default null means all changed attributes)
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $fromSaveAll
     *        has the save() call come from saveAll() or not
     * @return boolean
     *        did save() successfully process
     */
    public function save($runValidation = true, $attributes = null, $hasParentModel = false, $fromSaveAll = false);


    /**
     * deletes the current record but also loops through defined relationships (if appropriate)
     * to delete those as well
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return boolean
     *        did deleteFull() successfully process
     */
    public function deleteFull($hasParentModel = false);


    /**
     * This method is called at the beginning of a deleteFull() request on a record or model map
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return boolean whether the deleteFull() method call should continue
     *        If false, deleteFull() will be cancelled.
     */
    public function beforeDeleteFullInternal($hasParentModel = false);


    /**
     * Called by beforeDeleteFullInternal on the current model to determine if the whole of deleteFull
     * can be processed - this is expected to be replaced in individual models when required
     *
     * @return boolean okay to continue with deleteFull
     */
    public function beforeDeleteFull();


    /**
     * This method is called at the end of a successful deleteFull()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return void
     */
    public function afterDeleteFullInternal($hasParentModel = false);


    /**
     * Called by afterDeleteFullInternal on the current model once the whole of the deleteFull() has
     * been successfully processed
     * @return void
     */
    public function afterDeleteFull();


    /**
     * This method is called at the end of a failed deleteFull()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return void
     */
    public function afterDeleteFullFailedInternal($hasParentModel = false);


    /**
     * Called by afterDeleteFullFailedInternal on the current model once deleteFull() has
     * failed processing
     * @return void
     */
    public function afterDeleteFullFailed();


    /**
     * Delete the current record
     *
     * @see \yii\db\BaseActiveRecord::delete()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $fromDeleteFull
     *        has the delete() call come from deleteFull() or not
     * @return boolean
     *        did delete() successfully process
     */
    public function delete($hasParentModel = false, $fromDeleteFull = false);


    /**
     * Determine if model has any unsaved changes optionally checking to see if any sub
     * models in the current model map also have any changes even if the current model
     * does not
     *
     * @param boolean $checkRelations
     *        should changes in relations be checked as well
     * @return boolean
     *        changes exist
     */
    public function hasChanges($checkRelations=false);


    /**
     * Obtain data required to reset current record to state before saveAll() was called in the event
     * that saveAll() fails
     * @return array array of data required to rollback the current model
     */
    public function getResetDataForFailedSave();


    /**
     * Reset current record to state before saveAll() was called in the event
     * that saveAll() fails
     * @param array $data array of data required to rollback the current model
     * @return void
     */
    public function resetOnFailedSave($data);

}
