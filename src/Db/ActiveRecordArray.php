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

use yii\base\ModelEvent;
use yii\db\ActiveRecord as YiiActiveRecord;
use Concord\Base\Traits\ActionErrors;
use Concord\Db\ActiveRecordArrayException;
use Concord\Db\ActiveRecordParentalTrait;
use Concord\Db\ActiveRecordParentalInterface;
use Concord\Db\ActiveRecordReadOnlyInterface;
use Concord\Db\ActiveRecordReadOnlyTrait;
use Concord\Db\ActiveRecordSaveAllInterface;

class ActiveRecordArray extends \ArrayObject implements ActiveRecordParentalInterface, ActiveRecordReadOnlyInterface, ActiveRecordSaveAllInterface
{

    use ActionErrors;
    use ActiveRecordParentalTrait;
    use ActiveRecordReadOnlyTrait;

    /**
     * @var string|false object class for use by $this->newElement()
     */
    protected $defaultObjectClass       = false;

    /**
     * @var boolean can elements automatically be created in the array when
     * a new key is used e.g. $myObject['key1]->myVar1 = 'myVal'; without first setting up $myObject['key1]
     * if false an exception will be thrown
     */
    protected $autoCreateObjectOnNewKey = true;

    /**
     * @event ModelEvent an event that is triggered before saveAll()
     * You may set [[ModelEvent::isValid]] to be false to stop the update.
     */
    const EVENT_BEFORE_SAVE_ALL = 'beforeSaveAll';

    /**
     * @event Event an event that is triggered after saveAll() has completed
     */
    const EVENT_AFTER_SAVE_ALL = 'afterSaveAll';

    /**
     * @event Event an event that is triggered after saveAll() has failed
     */
    const EVENT_AFTER_SAVE_ALL_FAILED = 'afterSaveAllFailed';

    /**
     * @event ModelEvent an event that is triggered before saveAll()
     * You may set [[ModelEvent::isValid]] to be false to stop the update.
     */
    const EVENT_BEFORE_DELETE_FULL = 'beforeDeleteFull';

    /**
     * @event Event an event that is triggered after saveAll() has completed
     */
    const EVENT_AFTER_DELETE_FULL = 'afterDeleteFull';

    /**
     * @event Event an event that is triggered after saveAll() has failed
     */
    const EVENT_AFTER_DELETE_FULL_FAILED = 'afterDeleteFullFailed';

    /**
     * Construct
     *
     * @param string $input
     * @param number $flags
     * @param string $iterator_class
     */
    public function __construct($input = null, $flags = 0, $iterator_class = 'ArrayIterator')
    {
        $inp = $input;
        $input = (is_null($input) ? array() : $input);

        parent::__construct($input, $flags, $iterator_class);

        if (is_array($inp) && $inp) {
            $this->isNewRecord = false;
        }
    }


    /**
     * Appends the value
     *
     * @param \Concord\Db\ActiveRecord|\Concord\Db\ActiveAttributeRecord $value
     * @return void
     */
    public function append($value)
    {
        $this->offsetSet(null, $value);
    }


    /**
     * Appends the value but with a specific key value
     *
     * @param \Concord\Db\ActiveRecord|\Concord\Db\ActiveAttributeRecord $value
     * @param mixed $key
     * @return void
     */
    public function appendWithKey($value, $key)
    {
        $this->offsetSet($key, $value);
    }


    /**
     * (non-PHPdoc)
     *
     * @see ArrayObject::offsetSet()
     * @throws \Concord\Db\ActiveRecordArrayException
     */
    public function offsetSet($key, $value)
    {
        if ($this->defaultObjectClass && !($value instanceof $this->defaultObjectClass)) {

            throw new ActiveRecordArrayException('Item added to array not of type `' . $this->defaultObjectClass . '`' . (is_object($value) ? ' it is of type `' . get_class($value) . '`' : ''));

        } elseif ($this->getReadOnly()) {

            throw new ActiveRecordArrayException('Attempting to add an element to a read only array of ' . \Concord\Tools::getClassName($this->defaultObjectClass));

        } else {

            if (is_null($key)) {

                // assign a temp key and create the new element
                $this->newElement($key, $value);

            } else {

                if ($this->parentModel && $value instanceof ActiveRecordParentalInterface) {
                    $value->setParentModel($this->parentModel);
                }

                if ($value instanceof ActiveRecordReadOnlyInterface) {
                    if ($this->readOnly !== null) {
                        $value->setReadOnly($this->readOnly);
                    }
                    if ($this->canDelete !== null) {
                        $value->setCanDelete($this->canDelete);
                    }
                }

                parent::offsetSet($key, $value);
            }
        }
    }


    /**
     * (non-PHPdoc)
     * @see ArrayObject::offsetGet()
     * @throws \Concord\Db\ActiveRecordArrayException
     */
    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            if ($this->autoCreateObjectOnNewKey) {
                $this->newElement($key);
            } else {
                throw new ActiveRecordArrayException('Undefined index: ' . $key);
            }

        }

        return parent::offsetGet($key);
    }


    /**
     * Returns the value at the specified key
     *
     * @param mixed $key
     * @return \Concord\Db\ActiveRecord \Concord\Db\ActiveAttributeRecord
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }


    /**
     * Returns the value at the specified key
     *
     * @param mixed $key
     * @return \Concord\Db\ActiveRecord \Concord\Db\ActiveAttributeRecord
     */
    public function row($key)
    {
        return $this->offsetGet($key);
    }


    /**
     * Create a new entry of the relevant object in the array and return the
     * temp array key to that new object
     *
     * @param mixed $key
     *        [OPTIONAL] specify the temp key to be used for the new entry
     *        if it already exists then return false. default to assigning a temp key
     * @param mixed $value
     *        [OPTIONAL] add a specific object rather than creating one
     *        based on $this->defaultObjectClass
     * @return string false success return the key used to create the new element
     */
    public function newElement($key = null, $value = null)
    {
        if (!is_null($key)) {
            if ($this->offsetExists($key)) {return false;}
        } else {
            $counter = 0;
            $key = 'temp_' . strval($counter);
            while ($this->offsetExists($key)) {
                $counter++;
                $key = 'temp_' . strval($counter);
            }
        }

        if (!is_null($value)) {
            $this->offsetSet($key, $value);
            return $key;
        } elseif (class_exists($this->defaultObjectClass)) {
            $value = new $this->defaultObjectClass();
            if ($value instanceof $this->defaultObjectClass) {
                $this->offsetSet($key, $value);
                return $key;
            }
        }

        return false;
    }


    /**
     * Create a new entry of the relevant object in the array and return the
     * temp array key to that new object
     *
     * @param mixed $index
     *       specify index/key of the array element to remove - this will not delete data from the db though
     *        if it already exists then return false. default to assigning a temp key
     */
    public function removeElement($index)
    {
        $this->offsetUnset($index);
    }


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
    public function push($runValidation = true)
    {
        return $this->saveAll($runValidation, false, true);
    }


    /**
     * This method is called at the beginning of a saveAll() request
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
    public function beforeSaveAllInternal($runValidation = true, $hasParentModel = false, $push = false)
    {

        $this->clearActionErrors();
        $this->resetChildHasChanges();

        $canSaveAll = true;

        if (!$hasParentModel) {
            //$event = new ModelEvent;
            //$this->trigger(self::EVENT_BEFORE_SAVE_ALL, $event);
            //$canSaveAll = $event->isValid;
        }

        if ($this->getReadOnly()) {
            // will be ignored during saveAll()
        } else {

            if ($canSaveAll) {

                if ($runValidation) {

                }

                if ($this->count()) {

                    $iterator = $this->getIterator();
                    while ($iterator->valid()) {

                        $isReadOnly = false;
                        if ($iterator->current() instanceof ActiveRecordReadOnlyInterface) {
                            $isReadOnly = $iterator->current()->getReadOnly();
                        }

                        if (!$isReadOnly) {

                            $needsCheck = true;
                            if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                                $needsCheck = $iterator->current()->hasChanges(true);
                            } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                                if ($iterator->current()->getDirtyAttributes()) {
                                    $needsCheck = true;
                                }
                            }

                            if ($needsCheck) {

                                $this->setChildHasChanges($iterator->key());
                                if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                                    $this->setChildOldValues($iterator->key(), $iterator->current()->getResetDataForFailedSave());
                                } else {
                                    $this->setChildOldValues(
                                        $iterator->key(),
                                        array(
                                            'new' => $iterator->current()->getIsNewRecord(),
                                            'oldValues' => $iterator->current()->getOldAttributes(),
                                            'current' => $iterator->current()->getAttributes()
                                        )
                                    );
                                }

                                $canSaveThis = true;
                                if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                                    $canSaveThis = $iterator->current()->beforeSaveAllInternal($runValidation, true, $push);
                                    if (!$canSaveThis) {
                                        if (method_exists($iterator->current(), 'hasActionErrors')) {
                                            if ($iterator->current()->hasActionErrors()) {
                                                $this->mergeActionErrors($iterator->current()->getActionErrors());
                                            }
                                        }
                                    }
                                } elseif (method_exists($iterator->current(), 'validate')) {
                                    $canSaveThis = $iterator->current()->validate();
                                    if (!$canSaveThis) {
                                        $errors = $iterator->current()->getErrors();
                                        foreach ($errors as $errorField => $errorDescription) {
                                            $this->addActionError($errorDescription, 0, $errorField, \Concord\Tools::getClassName($iterator->current()));
                                        }
                                    }
                                }

                                if (!$canSaveThis) {
                                    $canSaveAll = false;
                                }
                            }
                        }

                        $iterator->next();
                    }
                }
            }
        }

        if ($this->hasActionErrors()) {
            $canSaveAll = false;
        } elseif (!$canSaveAll) {
            $this->addActionError('beforeSaveAllInternal checks failed');
        }

        if (!$canSaveAll) {
            $this->resetChildHasChanges();
        }

        return $canSaveAll;

    }


    /**
     * Saves all models in the array but also loops through defined
     * relationships (if appropriate) to save those as well
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
    public function saveAll($runValidation = true, $hasParentModel = false, $push = false)
    {

        $this->clearActionErrors();

        $allOk = true;

        if ($hasParentModel && $this->getReadOnly()) {

            // not allowed to amend or delete but is a child model so we will treat as okay without deleting the record
            $this->addActionWarning('Skipped saveAll on ' . \Concord\Tools::getClassName($this->defaultObjectClass) . '(s) which is read only');
            $allOk = true;

        } elseif (!$hasParentModel && $this->getReadOnly()) {

            // not allowed to amend
            throw new \Concord\Db\Exception('Attempting to saveAll on ' . \Concord\Tools::getClassName($this->defaultObjectClass) . '(s) which is read only');

        } else {

            if ($this->count()) {

                if (!$hasParentModel) {

                    // run beforeSaveAll and abandon saveAll() if it returns false
                    if (!$this->beforeSaveAllInternal($runValidation, $hasParentModel, $push)) {
                        return false;
                    }

                    /*
                     * note if validation was required it has already now been executed as part of the beforeSaveAll checks,
                    * so no need to do them again as part of save
                    */
                    $runValidation = false;

                }

                $alterKeys = array();
                $savedKeys = array();

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    $hasChanges = true;
                    if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                        $hasChanges = $iterator->current()->hasChanges(true);
                    } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                        if (!$iterator->current()->getDirtyAttributes()) {
                            $hasChanges = false;
                        }
                    }

                    if ($hasChanges) {

                        $isNewRecord = $iterator->current()->getIsNewRecord();

                        $ok = false;
                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $ok = $iterator->current()->saveAll($runValidation, true, $push, true);
                        } elseif (method_exists($this->$relation, 'save')) {
                            $ok = $iterator->current()->save($runValidation);
                            if ($ok) {
                                $iterator->current()->setIsNewRecord(false);
                            }
                        }

                        if (method_exists($iterator->current(), 'hasActionErrors')) {
                            if ($iterator->current()->hasActionErrors()) {
                                $this->mergeActionErrors($iterator->current()->getActionErrors());
                            }
                        }

                        if (method_exists($iterator->current(), 'hasActionWarnings')) {
                            if ($iterator->current()->hasActionWarnings()) {
                                $this->mergeActionWarnings($iterator->current()->getActionWarnings());
                            }
                        }

                        if (!$ok) {

                            $allOk = false;

                        } else {

                            $primaryKey = $iterator->current()->getPrimaryKey();
                            if (!is_array($primaryKey) && $primaryKey) {
                                $savedKeys[] = array_merge(array('key' => $primaryKey), $this->getChildOldValues($iterator->key()));
                            }

                            if ($isNewRecord) {

                                if (!is_array($primaryKey) && $primaryKey && $primaryKey != $iterator->key()) {
                                    $alterKeys[$iterator->key()] = $primaryKey;
                                }
                            }
                        }
                    }

                    $iterator->next();
                }

                if ($alterKeys) {
                    foreach ($alterKeys as $oldKey => $newKey) {
                        $this->offsetSet($newKey, $this->offsetGet($oldKey));
                        $this->offsetUnset($oldKey);
                    }
                }

                if ($savedKeys) {
                    // need to update childHasChanges flags ready for afterSaveAllInternal()
                    $this->resetChildHasChanges();
                    foreach ($savedKeys as $key => $value) {
                        $tempKey = $value['key'];
                        unset($value['key']);
                        $this->setChildHasChanges($tempKey);
                        $this->setChildOldValues(
                            $tempKey,
                            $value
                        );

                    }
                }

                if (!$hasParentModel) {
                    if ($allOk) {
                        $this->afterSaveAllInternal();
                    } else {
                        $this->afterSaveAllFailedInternal();
                    }
                }

            }
        }

        if (!$allOk) {
            return false;
        }

        return true;
    }


    /**
     * This method is called at the end of a successful saveAll()
     * The default implementation will trigger an [[EVENT_AFTER_SAVE_ALL]] event
     * When overriding this method, make sure you call the parent implementation so that
     * the event is triggered.
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     */
    public function afterSaveAllInternal($hasParentModel = false)
    {

        if ($this->getReadOnly()) {
            // will have been ignored during saveAll()
        } else {

            if ($this->count()) {

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    if ($this->getChildHasChanges($iterator->key())) {
                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->afterSaveAllInternal(true);
                        } elseif ($iterator->current() instanceof YiiActiveRecord && method_exists($iterator->current(), 'afterSaveAll')) {
                            $iterator->current()->afterSaveAll();
                        }
                    }

                    $iterator->next();
                }
            }
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL);
        }
    }


    /**
     * This method is called at the end of a failed saveAll()
     * The default implementation will trigger an [[EVENT_AFTER_SAVE_ALL_FAILED]] event
     * When overriding this method, make sure you call the parent implementation so that
     * the event is triggered.
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     */
    public function afterSaveAllFailedInternal($hasParentModel = false)
    {

        if ($this->getReadOnly()) {
            // will have been ignored during saveAll()
        } else {

            if ($this->count()) {

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    if ($this->getChildHasChanges($iterator->key())) {

                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->resetOnFailedSave($this->getChildOldValues($iterator->key()));
                        } elseif ($iterator->current() instanceof YiiActiveRecord) {
                            $iterator->current()->setAttributes($this->getChildOldValues($iterator->key(), 'current'), false);
                            $iterator->current()->setIsNewRecord($this->getChildOldValues($iterator->key(), 'new'));
                            $tempValue = $this->getChildOldValues($iterator->key(), 'oldValues');
                            $iterator->current()->setOldAttributes($tempValue ? $tempValue : null);
                        }

                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->afterSaveAllFailedInternal(true);
                        } elseif ($iterator->current() instanceof YiiActiveRecord && method_exists($iterator->current(), 'afterSaveAllFailed')) {
                            $iterator->current()->afterSaveAllFailed();
                        }
                    }

                    $iterator->next();
                }
            }
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL_FAILED);
        }
    }


    /**
     * Loops through the current array of objects and delete them
     *
     * @see \yii\db\BaseActiveRecord::delete()
     */
    public function deleteFull($hasParentModel = false)
    {

        $this->clearActionErrors();

        $allOk = true;

        if ($hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to amend or delete but is a child model so we will treat as okay without deleting the record
            $this->addActionWarning('Skipped delete of ' . \Concord\Tools::getClassName($this->defaultObjectClass) . '(s)' . ' which is ' . ($this->getReadOnly() ? 'read only' : 'flagged as not deletable'));
            $allOk = true;

        } elseif (!$hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to delete
            throw new \Concord\Db\Exception('Attempting to delete ' . \Concord\Tools::getClassName($this->defaultObjectClass) . '(s)' . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable'));

        } else {

            if ($this->count()) {

                if (!$hasParentModel) {
                    // run beforeDeleteFull and abandon deleteFull() if it returns false
                    if (!$this->beforeDeleteFullInternal($hasParentModel)) {
                        return false;
                    }
                }

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    $ok = false;
                    if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                        $ok = $iterator->current()->deleteFull(true);
                    } elseif (method_exists($this->$relation, 'delete')) {
                        $ok = $iterator->current()->delete();
                    }

                    if (method_exists($iterator->current(), 'hasActionErrors')) {
                        if ($iterator->current()->hasActionErrors()) {
                            $this->mergeActionErrors($iterator->current()->getActionErrors());
                        }
                    }

                    if (method_exists($iterator->current(), 'hasActionWarnings')) {
                        if ($iterator->current()->hasActionWarnings()) {
                            $this->mergeActionWarnings($iterator->current()->getActionWarnings());
                        }
                    }

                    if (!$ok) {
                        $allOk = false;
                    }

                    $iterator->next();
                }

                if (!$hasParentModel) {
                    if ($allOk) {
                        $this->afterDeleteFullInternal();
                    } else {
                        $this->afterDeleteFullFailedInternal();
                    }
                }

            }
        }

        return $allOk;
    }


    /**
     * This method is called at the beginning of a deleteFull() request on a record array
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return boolean whether the deleteFull() method call should continue
     *        If false, deleteFull() will be cancelled.
     */
    public function beforeDeleteFullInternal($hasParentModel = false)
    {
        $this->clearActionErrors();
        $this->resetChildHasChanges();

        $canDeleteFull = true;

        if (!$hasParentModel) {
            //$event = new ModelEvent;
            //$this->trigger(self::EVENT_BEFORE_SAVE_ALL, $event);
            //$canSaveAll = $event->isValid;
        }

        if ($this->getReadOnly()) {
            // will be ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will be ignored during deleteFull()
        } else {

            if ($canDeleteFull) {

                if ($this->count()) {

                    $iterator = $this->getIterator();
                    while ($iterator->valid()) {

                        $isReadOnly = false;
                        $canDelete = true;
                        if ($iterator->current() instanceof ActiveRecordReadOnlyInterface) {
                            $isReadOnly = $iterator->current()->getReadOnly();
                            $canDelete = $iterator->current()->getCanDelete();
                        }

                        if (!$isReadOnly && $canDelete) {

                            $this->setChildHasChanges($iterator->key());
                            if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                                $this->setChildOldValues($iterator->key(), $iterator->current()->getResetDataForFailedSave());
                            } else {
                                $this->setChildOldValues(
                                    $iterator->key(),
                                    array(
                                        'new' => $iterator->current()->getIsNewRecord(),
                                        'oldValues' => $iterator->current()->getOldAttributes(),
                                        'current' => $iterator->current()->getAttributes()
                                    )
                                );
                            }

                            $canDeleteThis = true;
                            if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                                $canDeleteThis = $iterator->current()->beforeDeleteFullInternal(true);
                                if (!$canDeleteThis) {
                                    if (method_exists($iterator->current(), 'hasActionErrors')) {
                                        if ($iterator->current()->hasActionErrors()) {
                                            $this->mergeActionErrors($iterator->current()->getActionErrors());
                                        }
                                    }
                                }
                            } elseif (method_exists($iterator->current(), 'beforeDeleteFull')) {
                                $canDeleteThis = $iterator->current()->beforeDeleteFull();
                                if (!$canDeleteThis) {
                                    $errors = $iterator->current()->getErrors();
                                    foreach ($errors as $errorField => $errorDescription) {
                                        $this->addActionError($errorDescription, 0, $errorField, \Concord\Tools::getClassName($iterator->current()));
                                    }
                                }
                            }

                            if (!$canDeleteThis) {
                                $canDeleteFull = false;
                            }

                        }

                        $iterator->next();
                    }
                }
            }
        }

        if ($this->hasActionErrors()) {
            $canDeleteFull = false;
        } elseif (!$canDeleteFull) {
            $this->addActionError('beforeDeleteFullInternal checks failed');
        }

        if (!$canDeleteFull) {
            $this->resetChildHasChanges();
        }

        return $canDeleteFull;
    }


    /**
     * This method is called at the end of a successful deleteFull()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     */
    public function afterDeleteFullInternal($hasParentModel = false)
    {
        if ($this->getReadOnly()) {
            // will have been ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will have been ignored during deleteFull()
        } else {

            if ($this->count()) {

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    if ($this->getChildHasChanges($iterator->key())) {
                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->afterDeleteFullInternal(true);
                        } elseif ($iterator->current() instanceof YiiActiveRecord && method_exists($iterator->current(), 'afterDeleteFull')) {
                            $iterator->current()->afterDeleteFull();
                        }
                    }

                    $iterator->next();
                }

                $this->exchangeArray(array());
            }
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL);
        }

    }


    /**
     * This method is called at the end of a failed deleteFull()
     *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     */
    public function afterDeleteFullFailedInternal($hasParentModel = false)
    {
        if ($this->getReadOnly()) {
            // will have been ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will have been ignored during deleteFull()
        } else {

            if ($this->count()) {

                $iterator = $this->getIterator();
                while ($iterator->valid()) {

                    if ($this->getChildHasChanges($iterator->key())) {

                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->resetOnFailedSave($this->getChildOldValues($iterator->key()));
                        } elseif ($iterator->current() instanceof YiiActiveRecord) {
                            $iterator->current()->setAttributes($this->getChildOldValues($iterator->key(), 'current'), false);
                            $iterator->current()->setIsNewRecord($this->getChildOldValues($iterator->key(), 'new'));
                            $tempValue = $this->getChildOldValues($iterator->key(), 'oldValues');
                            $iterator->current()->setOldAttributes($tempValue ? $tempValue : null);
                        }

                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $iterator->current()->afterDeleteFullFailedInternal(true);
                        } elseif ($iterator->current() instanceof YiiActiveRecord && method_exists($iterator->current(), 'afterDeleteFullFailed')) {
                            $iterator->current()->afterDeleteFullFailed();
                        }
                    }

                    $iterator->next();
                }
            }
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL_FAILED);
        }

    }


    /**
     * Return all objects in the array as arrays but do not load children
     *
     * @return array
     */
    public function toArray()
    {
        $data = array();
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                if (method_exists($iterator->current(), 'toArray')) {
                    $data[$iterator->key()] = $iterator->current()->toArray();
                }
                $iterator->next();
            }
        }
        return $data;
    }


    /**
     * Return all objects in the array as arrays including their children
     * if applicable
     *
     * @param boolean $loadedOnly [OPTIONAL] only show populated relations default is false
     * @param boolean $excludeNewAndBlankRelations [OPTIONAL] exclude new blank records, default true
     * @return array
     */
    public function allToArray($loadedOnly=false, $excludeNewAndBlankRelations=true)
    {
        $data = array();
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                $excludeFromArray = false;
                if ($excludeNewAndBlankRelations && $iterator->current()->getIsNewRecord()) {
                    if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                        if (!$iterator->current()->hasChanges(true)) {
                            $excludeFromArray = true;
                        }
                    } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                        if (!$iterator->current()->getDirtyAttributes()) {
                            $excludeFromArray = true;
                        }
                    }
                }
                if ($excludeFromArray) {
                    // exclude
                } elseif (method_exists($iterator->current(), 'allToArray')) {
                    $data[$iterator->key()] = $iterator->current()->allToArray($loadedOnly, $excludeNewAndBlankRelations);
                } elseif (method_exists($iterator->current(), 'toArray')) {
                    $data[$iterator->key()] = $iterator->current()->toArray();
                }
                $iterator->next();
            }
        }
        return $data;
    }


    /**
     * Set the default object class for use by $this->newElement()
     *
     * @param string $class
     */
    public function setDefaultObjectClass($class)
    {
        $this->defaultObjectClass = $class;
    }


    /**
     * Set parent model
     *
     * @param \Concord\Db\ActiveRecord|\yii\db\ActiveRecord $parentModel
     */
    public function setParentModel($parentModel)
    {
        $this->parentModel = $parentModel;
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                if ($iterator->current() instanceof ActiveRecordParentalInterface) {
                    $iterator->current()->setParentModel($parentModel);
                }
                $iterator->next();
            }
        }
    }


    /**
     * Set the read only value
     *
     * @param boolean $value [OPTIONAL] default true
     */
    public function setReadOnly($value=true)
    {
        $this->readOnly = $value;
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                if ($iterator->current() instanceof ActiveRecordReadOnlyInterface) {
                    $iterator->current()->setReadOnly($this->readOnly);
                }
                $iterator->next();
            }
        }
    }


    /**
     * Set the can delete value
     *
     * @param boolean $value [OPTIONAL] default true
     */
    public function setCanDelete($value=true)
    {
        $this->canDelete = $value;
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                if ($iterator->current() instanceof ActiveRecordReadOnlyInterface) {
                    $iterator->current()->setCanDelete($this->canDelete);
                }
                $iterator->next();
            }
        }
    }


    /**
     * Set if a new object should be created when an array element does not yes exist
     *
     * @param boolean $value [OPTIONAL] default true (which is also the default if the method is not used)
     */
    public function setAutoCreateObjectOnNewKey($value=true)
    {
        $this->autoCreateObjectOnNewKey = $value;
    }


    /**
     * Set attribute in each of the models within the array
     *
     * @param string $attributeName
     *        name of attribute to be updated
     * @param mixed $value
     *        value to be set
     * @param boolean $onlyIfChanged
     *        limit setting the value to records that have changes already
     *        helps avoid saving new otherwise empty records to the db
     * @param boolean $onlyIfNew
     *        limit setting the value to new records only
     */
    public function setAttribute($attributeName, $value, $onlyIfChanged = false, $onlyIfNew = false)
    {
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                if (method_exists($iterator->current(), 'setAttribute')) {

                    $hasChanges = true;

                    if ($onlyIfNew) {
                        $hasChanges = $iterator->current()->getIsNewRecord();
                    }

                    if ($hasChanges && $onlyIfChanged) {
                        if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                            $hasChanges = $iterator->current()->hasChanges(true);
                        } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                            if (!$iterator->current()->getDirtyAttributes()) {
                                $hasChanges = false;
                            }
                        }
                    }

                    if ($hasChanges) {
                        if ($iterator->current()->getAttribute($attributeName) != $value) {
                            $iterator->current()->setAttribute($attributeName, $value);
                        }
                    }

                }
                $iterator->next();
            }
        }
    }


    /**
     * Determine if any of the models in the array have any unsaved changed
     *
     * @param boolean $checkRelations should changes in relations be checked as well
     * @return boolean
     */
    public function hasChanges($checkRelations=false)
    {
        $hasChanges = false;
        if ($this->count()) {
            $iterator = $this->getIterator();
            while (!$hasChanges && $iterator->valid()) {
                if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                    $hasChanges = $iterator->current()->hasChanges($checkRelations);
                } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                    if ($iterator->current()->getDirtyAttributes()) {
                        $hasChanges = true;
                    }
                }
                $iterator->next();
            }
        }
        return ($hasChanges ? true : false);
    }


    /**
     * Call a function on each of the models in the array collection
     *
     * @param string $methodName
     * @param mixed $parameters
     * @param boolean $asArray
     *        call method with $parameters as the first parameter rather than as one parameter per array element
     */
    public function callMethodOnEachObjectInArray($methodName, $parameters = false, $asArray = true)
    {
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {

                if (method_exists($iterator->current(), $methodName)) {
                    if (!$parameters || $parameters === null) {
                        $iterator->current()->$methodName();
                    } else {
                        if ($asArray) {
                            call_user_func(array(
                                $iterator->current(),
                                $methodName
                            ), $parameters);
                        } else {
                            call_user_func_array(array(
                                $iterator->current(),
                                $methodName
                            ), $parameters);
                        }
                    }
                }
                $iterator->next();
            }
        }
    }


    /**
     * Call the debugTest method on all objects in the model map (used for testing)
     *
     * @param boolean $loadedOnly [OPTIONAL] only include populated relations default is false
     * @param boolean $excludeNewAndBlankRelations [OPTIONAL] exclude new blank records, default true
     * @return array
     */
    public function callDebugTestOnAll($loadedOnly=false, $excludeNewAndBlankRelations=true)
    {
        $data = array();
        if ($this->count()) {
            $iterator = $this->getIterator();
            while ($iterator->valid()) {
                $excludeFromArray = false;
                if ($excludeNewAndBlankRelations && $iterator->current()->getIsNewRecord()) {
                    if ($iterator->current() instanceof ActiveRecordSaveAllInterface) {
                        if (!$iterator->current()->hasChanges(true)) {
                            $excludeFromArray = true;
                        }
                    } elseif (method_exists($iterator->current(), 'getDirtyAttributes')) {
                        if (!$iterator->current()->getDirtyAttributes()) {
                            $excludeFromArray = true;
                        }
                    }
                }
                if ($excludeFromArray) {
                    // exclude
                } elseif (method_exists($iterator->current(), 'callDebugTestOnAll')) {
                    $data[$iterator->key()] = $iterator->current()->callDebugTestOnAll($loadedOnly, $excludeNewAndBlankRelations);
                } elseif (method_exists($iterator->current(), 'debugTest')) {
                    $data[$iterator->key()] = $iterator->current()->debugTest();
                }
                $iterator->next();
            }
        }
        return $data;
    }


    /**
     * Allows use of array_* functions on this object array
     * <code>
     * <?php
     * $yourObject->array_keys();
     * ?>
     * </code>
     *
     * @param string $func
     * @param mixed[] $argv
     * @throws \Concord\Db\ActiveRecordArrayException
     * @return mixed
     */
    public function __call($func, $argv)
    {
        if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {throw new \Concord\Db\ActiveRecordArrayException(__CLASS__ . '->' . $func . ' does not exist');}
        return call_user_func_array($func, array_merge(array(
            $this->getArrayCopy()
        ), $argv));
    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function save($runValidation = true, $attributes = null, $hasParentModel = false, $fromSaveAll = false)
    {
        return $this->saveAll($runValidation, $hasParentModel);
    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function beforeSaveAll()
    {
        return true;
    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function afterSaveAll()
    {

    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at,
     * this level, each object in the array will be processed by afterSaveAllFailedInternal()
     */
    public function afterSaveAllFailed()
    {

    }


    /**
     * Obtain data required to reset current record to state before saveAll() was called in the event
     * that saveAll() fails
     * @return array array of data required to rollback the current model
     */
    public function getResetDataForFailedSave()
    {

    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function resetOnFailedSave($data)
    {

    }

    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function delete($hasParentModel = false, $fromDeleteFull = false)
    {
        return $this->deleteFull($hasParentModel);
    }

    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function beforeDeleteFull()
    {
        return true;
    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at this level
     * @see \Concord\Db\ActiveRecordSaveAllInterface
     */
    public function afterDeleteFull()
    {

    }


    /**
     * Required to meet the needs of ActiveRecordSaveAllInterface but not used at,
     * this level, each object in the array will be processed by afterSaveAllFailedInternal()
     */
    public function afterDeleteFullFailed()
    {

    }

}
