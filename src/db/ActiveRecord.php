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

namespace fangface\concord\db;

use Yii;
use fangface\concord\Tools;
use fangface\concord\base\traits\ActionErrors;
use fangface\concord\base\traits\AttributeActiveFieldConfig;
use fangface\concord\base\traits\AttributeConfig;
use fangface\concord\base\traits\AttributeHintBlocks;
use fangface\concord\base\traits\AttributeIcons;
use fangface\concord\base\traits\AttributePlaceholders;
use fangface\concord\base\traits\AttributeTooltips;
use fangface\concord\behaviors\AutoSavedBy;
use fangface\concord\behaviors\AutoDatestamp;
use fangface\concord\db\ActiveAttributeRecord;
use fangface\concord\db\ActiveRecord;
use fangface\concord\db\ActiveRecordArray;
use fangface\concord\db\ActiveRecordParentalInterface;
use fangface\concord\db\ActiveRecordParentalTrait;
use fangface\concord\db\ActiveRecordReadOnlyInterface;
use fangface\concord\db\ActiveRecordReadOnlyTrait;
use fangface\concord\db\ActiveRecordSaveAllInterface;
use fangface\concord\db\ConnectionManager;
use fangface\concord\db\Exception;
use yii\base\InvalidParamException;
use yii\base\ModelEvent;
use yii\base\UnknownMethodException;
use yii\db\ActiveRecord as YiiActiveRecord;
use yii\db\ActiveQuery;
use yii\db\ActiveQueryInterface;
use yii\db\Connection;
use yii\helpers\ArrayHelper;


class ActiveRecord extends YiiActiveRecord implements ActiveRecordParentalInterface, ActiveRecordReadOnlyInterface, ActiveRecordSaveAllInterface
{

    use ActionErrors;
    use ActiveRecordParentalTrait;
    use ActiveRecordReadOnlyTrait;
    use AttributeActiveFieldConfig;
    use AttributeConfig;
    use AttributeHintBlocks;
    use AttributeIcons;
    use AttributePlaceholders;
    use AttributeTooltips;

    protected static $dbResourceName    = false;
    protected static $isClientResource  = false;
    protected static $dbTableName       = false;
    protected static $dbTableNameMethod = false; // yii, camel, default

    private $modelRelationMap           = [];

    protected $disableCreatedUpd        = false;
    protected $disableModifiedUpd       = false;
    protected $createdAtAttr            = 'createdAt';
    protected $createdByAttr            = 'createdBy';
    protected $modifiedAtAttr           = 'modifiedAt';
    protected $modifiedByAttr           = 'modifiedBy';

    protected $applyDefaults            = true;
    protected $defaultsApplied          = false;
    protected $defaultsAppliedText      = false;

    private $savedNewChildRelations     = [];


    const SAVE_NO_ACTION                = 1;
    const SAVE_CASCADE                  = 2;

    const DELETE_NO_ACTION              = 4;
    const DELETE_CASCADE                = 8;

    const LINK_NONE                     = 16;
    const LINK_ONLY                     = 32;
    const LINK_FROM_PARENT              = 64;
    const LINK_FROM_CHILD               = 128;
    const LINK_BI_DIRECT                = 256;
    const LINK_FROM_PARENT_MAINT        = 512;
    const LINK_FROM_CHILD_MAINT         = 1024;
    const LINK_BI_DIRECT_MAINT          = 2048;
    const LINK_BI_DIRECT_MAINT_FROM_PARENT = 4096;
    const LINK_BI_DIRECT_MAINT_FROM_CHILD  = 8192;

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
     * Get the name of the table associated with this ActiveRecord class.
     *
     * @param boolean $includeDbName
     * @return string
     */
    public static function tableName($includeDbName = true)
    {
        /** @var Connection $connection */
        $connection = static::getDb();
        $calledClass = get_called_class();
        $tablePrefix = $connection->tablePrefix;
        if (isset($calledClass::$dbTableName) && !is_null($calledClass::$dbTableName) && $calledClass::$dbTableName) {
            $tableName = $tablePrefix . $calledClass::$dbTableName;
        } else {
            $tableName = $tablePrefix . Tools::getDefaultTableNameFromClass($calledClass, (isset($calledClass::$tableNameMethod) && !is_null($calledClass::$tableNameMethod) && $calledClass::$tableNameMethod ? $calledClass::$tableNameMethod : 'default'));
        }
        if ($includeDbName) {
            preg_match("/dbname=([^;]+)/i", $connection->dsn, $matches);
            return $matches[1] . '.' . $tableName;
        }
        return $tableName;
    }

    /**
     * Returns the database connection used by this AR class.
     * By default, the "db" application component is used as the database connection.
     * You may override this method if you want to use a different database connection.
     * @throws Exception if no connection can be found
     * @return Connection|false The database connection used by this AR class.
     */
    public static function getDb()
    {
        $dbResourceName = 'db';
        $isClientResource = false;

        $calledClass = get_called_class();
        if (isset($calledClass::$dbResourceName) && !is_null($calledClass::$dbResourceName) && $calledClass::$dbResourceName) {
            $dbResourceName = $calledClass::$dbResourceName;
            $isClientResource = (isset($calledClass::$isClientResource) ? $calledClass::$isClientResource : false);
        }

        if (Yii::$app->has('dbFactory')) {
            /** @var ConnectionManager $dbFactory */
            $dbFactory = Yii::$app->get('dbFactory');
            return $dbFactory->getConnection($dbResourceName, true, true, $isClientResource);
        } elseif (Yii::$app->has($dbResourceName)) {
            return Yii::$app->get($dbResourceName);
        }

        throw new Exception('Database resource \'' . $dbResourceName . '\' not found');
    }

    /**
     * Reset the static dbResourceName (will impact all uses of the called class
     * until changed again)
     *
     * @param string $name
     */
    public function setDbResourceName($name)
    {
        if ($name) {
            $calledClass = get_called_class();
            $calledClass::$dbResourceName = $name;
        }

    }

    /**
     * Return the static dbResourceName
     */
    public function getDbResourceName()
    {
        $calledClass = get_called_class();
        if (isset($calledClass::$dbResourceName) && !is_null($calledClass::$dbResourceName) && $calledClass::$dbResourceName) {
            return $calledClass::$dbResourceName;
        }
    }

    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        $result = parent::hasAttribute($name);

        if (!$this->applyDefaults || $this->defaultsApplied) {
            return $result;
        }

        if ($this->getIsNewRecord() && $result) {
            $this->applyDefaults(false);
        }

        return $result;
    }

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            // we will now have optionally applied default values if this is a new record
            return parent::getAttribute($name);
        }
        return null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name
     *        the attribute name
     * @param mixed $value
     *        the attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @throws Exception if the current record is read only
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->getReadOnly()) {
            throw new Exception('Attempting to set attribute `' . $name . '` on a read only ' . Tools::getClassName($this) . ' model');
        }

        parent::setAttribute($name, $value);
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::setAttributes()
     * @throws Exception if the current record is read only
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if ($this->getReadOnly()) {
            throw new Exception('Attempting to set attributes on a read only ' . Tools::getClassName($this) . ' model');
        }

        parent::setAttributes($values, $safeOnly);
    }


    /**
     * Overriding BaseActiveRecord::setOldAttributes() because
     * we do not want to lose other old attributes following a call to this
     * function after a save which may have been limited to only some attributes effectively
     * marking all other attributes as dirty when they might not actually be
     * @see \yii\db\BaseActiveRecord::setOldAttributes($values)
     */
    public function setOldAttributes($values)
    {
        if ($values !== null && is_array($values) && $values) {
            parent::setOldAttributes(array_merge($this->getOldAttributes(), $values));
        } else {
            parent::setOldAttributes($values);
        }
    }


    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::load($data, $formName)
     * @throws Exception if the current record is read only
     */
    public function load($data, $formName = null)
    {
        if ($this->getReadOnly()) {
            throw new Exception('Attempting to load attributes on a read only ' . Tools::getClassName($this) . ' model');
        }

        if ($this->getIsNewRecord() && $this->applyDefaults && !$this->defaultsApplied) {
            $this->applyDefaults(false);
        }

        return parent::load($data, $formName);
    }

    /**
     * Apply defaults to the model
     * @param boolean $skipIfSet if existing value should be preserved
     */
    public function applyDefaults($skipIfSet = true)
    {
        if ($this->applyDefaults && !$this->defaultsApplied) {
            $this->loadDefaultValues($skipIfSet);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \yii\db\ActiveRecord::loadDefaultValues()
     */
    public function loadDefaultValues($skipIfSet = true)
    {
        $this->defaultsApplied = true;
        $stru = self::getTableSchema();
        $columns = $stru->columns;
        foreach ($columns as $colName => $spec) {
            if ($spec->isPrimaryKey && $spec->autoIncrement) {
                // leave value alone
            } else {
                if (isset($this->$colName) && !is_null($this->$colName)) {
                    // use the default value provided by the variable within the model
                    $defaultValue = Tools::formatAttributeValue($this->$colName, $spec);
                    // we want this all to work with magic getters and setters so now is a good time to remove the attribute from the object
                    unset($this->$colName);
                } else {
                    // use the default value from the DB if available
                    $defaultValue = Tools::formatAttributeValue('__DEFAULT__', $spec);
                }

                if (is_null($defaultValue)) {
                    // leave as null
                } elseif ($skipIfSet && $this->getAttribute($colName) !== null) {
                    // leave as is
                } elseif (false && is_string($defaultValue) && $defaultValue == '') {
                    // leave as null
                } elseif (false && is_numeric($defaultValue) && $defaultValue === 0) {
                    // leave as null
                } elseif (false && !$this->disableCreatedUpd && $this->createdAtAttr && $colName == $this->createdAtAttr && $defaultValue == Tools::DATE_TIME_DB_EMPTY) {
                    // leave as null
                } elseif (false && !$this->disableModifiedUpd && $this->modifiedAtAttr && $colName == $this->modifiedAtAttr && $defaultValue == Tools::DATE_TIME_DB_EMPTY) {
                    // leave as null
                } else {
                    $this->setAttribute($colName, $defaultValue);
                    if ($spec->type == 'text' && $defaultValue == '') {
                        // some fields need to have the default set and included in the sql statements even if not set to anything yet
                        $this->defaultsAppliedText = true;
                    } else {
                        $this->setOldAttribute($colName, $defaultValue);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Default ActiveRecord behaviors (typically createdBy, createdAt, modifiedBy and modifiedAt
     * (non-PHPdoc)
     * @see \yii\base\Component::behaviors()
     */
    public function behaviors()
    {
        $defaults = array();

        if ($this->disableCreatedUpd && $this->disableModifiedUpd) {

            // neither modified or created attributes require updating

        } else {

            if ($this->createdByAttr || $this->modifiedByAttr) {

                $defaults['savedby'] = [
                    'class' => AutoSavedBy::className()
                ];

                $defaults['savedby']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT] = array();

                if (!$this->disableCreatedUpd && $this->createdByAttr) {
                    $defaults['savedby']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT][] = $this->createdByAttr;
                }

                if (!$this->disableModifiedUpd && $this->modifiedByAttr) {
                    $defaults['savedby']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT][] = $this->modifiedByAttr;
                    $defaults['savedby']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE] = array();
                    $defaults['savedby']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE][] = $this->modifiedByAttr;
                }

            }

            if ($this->createdAtAttr || $this->modifiedAtAttr) {

                $defaults['datestamp'] = [
                    'class' => AutoDatestamp::className()
                ];

                $defaults['datestamp']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT] = array();

                if (!$this->disableCreatedUpd && $this->createdAtAttr) {
                    $defaults['datestamp']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT][] = $this->createdAtAttr;
                }

                if (!$this->disableModifiedUpd && $this->modifiedAtAttr) {
                    $defaults['datestamp']['attributes'][ActiveRecord::EVENT_BEFORE_INSERT][] = $this->modifiedAtAttr;
                    $defaults['datestamp']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE] = array();
                    $defaults['datestamp']['attributes'][ActiveRecord::EVENT_BEFORE_UPDATE][] = $this->modifiedAtAttr;
                }

            }

        }

        return $defaults;
    }

    /**
     * Determine if model has any unsaved changes
     *
     * @param boolean $checkRelations should changes in relations be checked as well
     * @return boolean
     */
    public function hasChanges($checkRelations = false)
    {

        if ($this->getOldAttributes() == [] && !$this->getIsNewRecord()) {
            $hasChanges = false;
        } elseif ($this->getIsNewRecord() && $this->defaultsApplied && $this->defaultsAppliedText) {
            $hasChanges = $this->getIsNewDirtyAttributes();
        } else {
            $hasChanges = $this->getDirtyAttributes();
        }

        if (!$hasChanges) {
            /*
             * check to see if any of our sub relations have unsaved changes that would be saved
             * if we called saveAll()
             */
            if ($checkRelations) {
                $this->processModelMap();
                if ($this->modelRelationMap) {
                    foreach ($this->modelRelationMap as $relation => $relationInfo) {
                        if ($relationInfo['onSaveAll'] == self::SAVE_CASCADE && $this->isRelationPopulated($relation)) {

                            $isReadOnly = ($relationInfo['readOnly'] === null || !$relationInfo['readOnly'] ? false : true);
                            if ($this->$relation instanceof ActiveRecordReadOnlyInterface) {
                                $isReadOnly = $this->$relation->getReadOnly();
                            }

                            if (!$isReadOnly) {
                                if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                    $hasChanges = $this->$relation->hasChanges($checkRelations);
                                } elseif (method_exists($this->$relation, 'getDirtyAttributes')) {
                                    if ($this->$relation->getDirtyAttributes()) {
                                        $hasChanges = true;
                                    }
                                }
                            }
                        }

                        if ($hasChanges) {
                            break;
                        }
                    }
                }
            }
        }

        return ($hasChanges ? true : false);
    }

    /**
     * Return if an attribute has changed
     * @param string $attribute [optional] if not specified returns true if anything within the currect AR has changed
     * @return boolean
     */
    public function hasChanged($attribute = null)
    {
        $hasChanges = $this->getDirtyAttributes();
        if (is_null($attribute)) {
            return ($hasChanges ? true : false);
        } elseif (isset($hasChanges[$attribute])) {
            return true;
        }
        return false;
    }

    /**
     * Returns the attribute values that have been modified in this new record since loading the default values
     * Only really gets used by self::hasChanges() when _oldAttributes == [] (typically when no default values are set
     * because they are all set to null in the table schema)
     * @param string[]|null $names the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getIsNewDirtyAttributes($names = null)
    {
        if (!$this->getIsNewRecord() || !$this->defaultsApplied) {
            return $this->getDirtyAttributes($names);
        }
        $attributes = [];
        $theAttributes = $this->getAttributes($names);
        if ($theAttributes) {
            $stru = self::getTableSchema();
            $columns = $stru->columns;
            foreach ($theAttributes as $name => $value) {
                $spec = ArrayHelper::getValue($columns, $name, []);
                if ($spec) {
                    if ($spec->isPrimaryKey && $spec->autoIncrement) {
                        if ($value !== null) {
                            $attributes[$name] = $value;
                        }
                    } else {
                        $defaultValue = Tools::formatAttributeValue('__DEFAULT__', $spec);
                        if ($defaultValue === null && $value !== null) {
                            $attributes[$name] = $value;
                        } elseif ($defaultValue != $value) {
                            $attributes[$name] = $value;
                        }
                    }
                }
            }
        }
        return $attributes;
    }

    /**
     * This method is called when the AR object is created and populated with the query result.
     * The default implementation will trigger an [[EVENT_AFTER_FIND]] event.
     * When overriding this method, make sure you call the parent implementation to ensure the
     * event is triggered.
     */
    public function afterFind()
    {
        $this->setIsNewRecord(false);
        parent::afterFind();
    }

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
    public function save($runValidation = true, $attributes = null, $hasParentModel = false, $fromSaveAll = false)
    {
        if ($this->getReadOnly() && !$hasParentModel) {

            // return failure if we are at the top of the tree and should not be asking to saveAll
            // not allowed to amend or delete
            $message = 'Attempting to save on ' . Tools::getClassName($this) . ' readOnly model';
            //$this->addActionError($message);
            throw new Exception($message);

        } elseif ($this->getReadOnly() && $hasParentModel) {

            $message = 'Skipping save on ' . Tools::getClassName($this) . ' readOnly model';
            $this->addActionWarning($message);
            return true;

        } else {
            if ($this->hasChanges()) {
                try {
                    $ok = parent::save($runValidation, $attributes);
                    if ($ok) {
                        $this->setIsNewRecord(false);
                    }
                } catch (\Exception $e) {
                    $ok = false;
                    $this->addActionError($e->getMessage(), $e->getCode());
                }
                return $ok;
            } elseif ($this->getIsNewRecord() && !$hasParentModel) {
                $message = 'Attempting to save an empty ' . Tools::getClassName($this) . ' model';
                //$this->addActionError($message);
                throw new Exception($message);
            }
        }

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \yii\db\ActiveRecord::update()
     */
    public function update($runValidation = true, $attributeNames = null, $hasParentModel = false, $fromUpdateAll = false)
    {
        if ($this->getReadOnly() && !$hasParentModel) {

            // return failure if we are at the top of the tree and should not be asking to supdateAll
            // not allowed to amend or delete
            $message = 'Attempting to update on ' . Tools::getClassName($this) . ' readOnly model';
            //$this->addActionError($message);
            throw new Exception($message);

        } elseif ($this->getReadOnly() && $hasParentModel) {

            $message = 'Skipping update on ' . Tools::getClassName($this) . ' readOnly model';
            $this->addActionWarning($message);
            return true;

        } else {
            try {
                $ok = parent::update($runValidation, $attributeNames);
            } catch (\Exception $e) {
                $ok = false;
                $this->addActionError($e->getMessage(), $e->getCode());
            }
            return $ok;
        }
    }

    /**
     * Touch the model (update modified datetime stamps
     * @param boolean $incrementRevision default true
     */
    public function touch($incrementRevision = true)
    {
        if ($incrementRevision && $this->hasAttribute('revision')) {
            $this->revision++;
        } elseif ($this->hasAttribute('modified_at')) {
            $this->modified_at = date(Tools::DATETIME_DATABASE);
        } elseif ($this->hasAttribute('modifiedAt')) {
            $this->modifiedAt = date(Tools::DATETIME_DATABASE);
        }
        $this->saveAll();
    }

    /**
     * Optionally fix and truncate strings for fields that may contain unknown responses
     *
     * @see \yii\db\BaseActiveRecord::beforeSave($insert)
     */
    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            $beforeSaveStringFields = $this->beforeSaveStringFields();
            if ($beforeSaveStringFields) {
                $this->checkAndFixLongStrings($beforeSaveStringFields);
            }
            return true;
        }
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \yii\db\BaseActiveRecord::afterSave($insert, $changedAttributes)
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->setIsNewRecord(false);
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * Check changed attributes and compare the table schema, truncating any fields as required
     *
     * @param array $attributes Array of attributes to limit checking to those only
     */
    public function checkAndFixLongStrings($attributes = false) {
        $hasChanges = $this->getDirtyAttributes();
        if ($hasChanges) {
            $columns = self::getTableSchema()->columns;
            foreach ($hasChanges as $name => $value) {
                if (array_key_exists($name, $columns)) {
                    if (!$attributes || in_array($name, $attributes) !== false) {
                        if ($columns[$name]->type == 'string' && $columns[$name]->size) {
                            if (Tools::strlen($value) > $columns[$name]->size) {
                                $this->setAttribute($name, Tools::substr($value, 0, $columns[$name]->size));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Perform a saveAll() call but push the request down the model map including
     * models that are not currently loaded (perhaps because child models need to
     * pick up new values from parents
     *
     * @param boolean $runValidation
     *        should validations be executed on all models before allowing saveAll()
     * @return boolean
     *         did saveAll() successfully process
     */
    public function push($runValidation = true)
    {
        return $this->saveAll($runValidation, false, true);
    }

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
     *         did saveAll() successfully process
     */
    public function saveAll($runValidation = true, $hasParentModel = false, $push = false)
    {

        $this->clearActionErrors();

        if ($this->getReadOnly() && !$hasParentModel) {

            // return failure if we are at the top of the tree and should not be asking to saveAll
            // not allowed to amend or delete
            $message = 'Attempting to saveAll on ' . Tools::getClassName($this) . ' readOnly model';
            //$this->addActionError($message);
            throw new Exception($message);

        } elseif ($this->getReadOnly() && $hasParentModel) {

            $message = 'Skipping saveAll on ' . Tools::getClassName($this) . ' readOnly model';
            $this->addActionWarning($message);
            return true;

        } elseif (!$this->getReadOnly()) {

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

            // start with empty array
            $this->savedNewChildRelations = array();

            $isNewRecord = $this->getIsNewRecord();

            try {

                $ok = $this->saveRelation('fromChild', $runValidation, $push);

                if ($ok) {

                    $skipFromParentRelationSave = false;

                    if ($this->hasChanges()) {

                        $ok = $this->save($runValidation, null, $hasParentModel, true);

                    } elseif ($isNewRecord && !$hasParentModel) {

                        // only return false for no point saving when on the top level
                        $message = 'Attempting to save an empty ' . Tools::getClassName($this) . ' model';
                        $this->addActionError($message);
                        $ok = false;

                    } elseif ($isNewRecord && $hasParentModel) {

                        // no point saving children if we have not saved this parent
                        $skipFromParentRelationSave = true;

                    }

                    if ($ok && !$skipFromParentRelationSave) {

                        $ok = $this->saveRelation('fromParent', $runValidation, $push);

                    }

                }

            } catch (\Exception $e) {
                $ok = false;
                $this->addActionError($e->getMessage(), $e->getCode());
            }

            if (!$hasParentModel) {
                if ($ok) {
                    $this->afterSaveAllInternal();
                } else {
                    $this->afterSaveAllFailedInternal();
                }
            }

            // reset
            $this->savedNewChildRelations = array();

            return $ok;

        }

        return true;
    }

    private function saveRelation($saveRelationType = 'fromParent', $runValidation = true, $push = false)
    {
        $allOk = true;

        $this->processModelMap();
        if ($this->modelRelationMap) {

            $limitAutoLinkType = self::LINK_NONE;
            if ($saveRelationType == 'fromParent') {
                $limitAutoLinkType = (self::LINK_FROM_PARENT | self::LINK_FROM_PARENT_MAINT | self::LINK_BI_DIRECT | self::LINK_BI_DIRECT_MAINT | self::LINK_BI_DIRECT_MAINT_FROM_PARENT | self:: LINK_BI_DIRECT_MAINT_FROM_CHILD);
            } elseif ($saveRelationType == 'fromChild') {
                $limitAutoLinkType = (self::LINK_FROM_CHILD | self::LINK_FROM_CHILD_MAINT | self::LINK_BI_DIRECT | self::LINK_BI_DIRECT_MAINT | self:: LINK_BI_DIRECT_MAINT_FROM_CHILD | self::LINK_BI_DIRECT_MAINT_FROM_PARENT | self::LINK_NONE);
            }

            foreach ($this->modelRelationMap as $relation => $relationInfo) {

                if ($relationInfo['onSaveAll'] == self::SAVE_CASCADE && ($this->isRelationPopulated($relation) || ($push && isset($this->$relation)))) {

                    if (($limitAutoLinkType & $relationInfo['autoLinkType']) == $relationInfo['autoLinkType']) {

                        $ok = true;

                        $isReadOnly = ($relationInfo['readOnly'] === null || !$relationInfo['readOnly'] ? false : true);
                        if ($this->$relation instanceof ActiveRecordReadOnlyInterface) {
                            $isReadOnly = $this->$relation->getReadOnly();
                        }

                        if (!$isReadOnly) {

                            $relationIsNew = false;

                            $isActiveRecordArray = ($this->$relation instanceof ActiveRecordArray);

                            if (!$isActiveRecordArray) {
                                $relationIsNew = $this->$relation->getIsNewRecord();
                            }

                            if ($saveRelationType == 'fromParent') {

                                $applyLinks = true;
                                $applyLinksNewOnly = false;
                                if ($relationInfo['autoLinkType'] == self::LINK_FROM_PARENT) {
                                    $applyLinksNewOnly = true;
                                } elseif ($relationInfo['autoLinkType'] == self::LINK_FROM_PARENT_MAINT) {
                                    // apply
                                } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT && ($limitAutoLinkType & self::LINK_FROM_PARENT) == self::LINK_FROM_PARENT) {
                                    $applyLinksNewOnly = true;
                                } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT && ($limitAutoLinkType & self::LINK_FROM_PARENT_MAINT) == self::LINK_FROM_PARENT_MAINT) {
                                    // apply
                                } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT_FROM_PARENT && ($limitAutoLinkType & self::LINK_BI_DIRECT_MAINT_FROM_PARENT) == self::LINK_BI_DIRECT_MAINT_FROM_PARENT) {
                                    // apply
                                } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT_FROM_CHILD && ($limitAutoLinkType & self::LINK_BI_DIRECT_MAINT_FROM_CHILD) == self::LINK_BI_DIRECT_MAINT_FROM_CHILD) {
                                    $applyLinksNewOnly = true;
                                } else {
                                    $applyLinks = false;
                                }

                                if ($applyLinks && $applyLinksNewOnly && !$isActiveRecordArray) {
                                    if (in_array($relation, $this->savedNewChildRelations)) {
                                        // relation was new before it was saved in the fromChild iteration of saveRelation()
                                    } else {
                                        $applyLinks = $relationIsNew;
                                    }
                                }

                                if ($applyLinks && !$isActiveRecordArray && ($relationIsNew || in_array($relation, $this->savedNewChildRelations))) {
                                    // we only want to apply links fromParent on new records if something
                                    // else within the record has also been changed (to avoid saving a blank record)
                                    if (in_array($relation, $this->savedNewChildRelations)) {
                                        // definately can apply these values now that the child has been created
                                    } elseif ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                        $applyLinks = $this->$relation->hasChanges(true);
                                    } elseif (method_exists($this->$relation, 'getDirtyAttributes')) {
                                        if (!$this->$relation->getDirtyAttributes()) {
                                            $applyLinks = false;
                                        }
                                    }
                                }

                                if ($applyLinks) {

                                    if (isset($relationInfo['autoLink']['fromParent'])) {
                                        $autoLinkLink = $relationInfo['autoLink']['fromParent'];
                                    } else {
                                        $autoLinkLink = ($relationInfo['autoLink'] ? $relationInfo['autoLink'] : ($relationInfo['link'] ? $relationInfo['link'] : false));
                                    }

                                    if ($autoLinkLink) {
                                        foreach ($autoLinkLink as $k => $v) {
                                            if ($this->getAttribute($v) !== null) {
                                                if ($isActiveRecordArray) {
                                                    // only update objects in the array if they already have other changes, we don't want to save records that were otherwise not used
                                                    if ($v == '__KEY__') {
                                                        $this->$relation->setAttribute($k, $v, (!$push), $applyLinksNewOnly);
                                                    } else {
                                                        $this->$relation->setAttribute($k, $this->getAttribute($v), (!$push), $applyLinksNewOnly);
                                                    }
                                                } else {
                                                    if ($this->$relation->getAttribute($k) != $this->getAttribute($v)) {
                                                        $this->$relation->setAttribute($k, $this->getAttribute($v));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ($isActiveRecordArray) {

                                $ok = $this->$relation->saveAll($runValidation, true, $push);

                            } else {

                                $hasChanges = true;
                                if (!$this->getIsNewRecord() && $this->$relation instanceof ActiveRecord) {

                                    // sub models may exist that have changes even though the relation itself does not have any changes
                                    // also we may need to apply auto link updates fromChild and fromParent depending on changes to this
                                    // and/or changes to sub models

                                    if ($relationIsNew || in_array($relation, $this->savedNewChildRelations)) {
                                        // we only want to apply links fromParent on new records if something
                                        // else within the record has also been changed (to avoid saving a blank record)
                                        if (in_array($relation, $this->savedNewChildRelations)) {
                                            // definately can apply these values now that the child has been created
                                        } elseif ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                            $hasChanges = $this->$relation->hasChanges(true);
                                        } elseif (method_exists($this->$relation, 'getDirtyAttributes')) {
                                            if (!$this->$relation->getDirtyAttributes()) {
                                                $hasChanges = false;
                                            }
                                        }
                                    }

                                } elseif ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                    $hasChanges = $this->$relation->hasChanges(true);
                                } elseif (method_exists($this->$relation, 'getDirtyAttributes')) {
                                    if (!$this->$relation->getDirtyAttributes()) {
                                        $hasChanges = false;
                                    }
                                }

                                if ($hasChanges) {
                                    $ok = false;
                                    if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                        $ok = $this->$relation->saveAll($runValidation, true, $push);
                                    } elseif (method_exists($this->$relation, 'save')) {
                                        $ok = $this->$relation->save($runValidation);
                                        if ($ok) {
                                            $this->$relation->setIsNewRecord(false);
                                        }
                                    }
                                }

                                if ($ok && $saveRelationType == 'fromChild') {

                                    if ($relationIsNew && $hasChanges) {
                                        // a record was saved
                                        $this->savedNewChildRelations[] = $relation;
                                    }

                                    $applyLinks = true;
                                    $applyLinksNewOnly = false;
                                    if ($relationInfo['autoLinkType'] == self::LINK_FROM_CHILD) {
                                        $applyLinksNewOnly = true;
                                    } elseif ($relationInfo['autoLinkType'] == self::LINK_FROM_CHILD_MAINT) {
                                        // apply
                                    } elseif (($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT) && (($limitAutoLinkType & self::LINK_FROM_CHILD) == self::LINK_FROM_CHILD)) {
                                        $applyLinksNewOnly = true;
                                    } elseif (($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT) && (($limitAutoLinkType & self::LINK_FROM_CHILD_MAINT) == self::LINK_FROM_CHILD_MAINT)) {
                                        // apply
                                    } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT_FROM_CHILD && ($limitAutoLinkType & self::LINK_BI_DIRECT_MAINT_FROM_CHILD) == self::LINK_BI_DIRECT_MAINT_FROM_CHILD) {
                                        // apply
                                    } elseif ($relationInfo['autoLinkType'] == self::LINK_BI_DIRECT_MAINT_FROM_PARENT && ($limitAutoLinkType & self::LINK_BI_DIRECT_MAINT_FROM_PARENT) == self::LINK_BI_DIRECT_MAINT_FROM_PARENT) {
                                        $applyLinksNewOnly = true;
                                    } else {
                                        $applyLinks = false;
                                    }

                                    if ($applyLinks && $applyLinksNewOnly && !$isActiveRecordArray) {
                                        $applyLinks = $relationIsNew;
                                    }


                                    if ($applyLinks) {

                                        if (isset($relationInfo['autoLink']['fromChild'])) {
                                            $autoLinkLink = $relationInfo['autoLink']['fromChild'];
                                        } else {
                                            $autoLinkLink = ($relationInfo['autoLink'] ? $relationInfo['autoLink'] : false);
                                        }

                                        if ($autoLinkLink) {
                                            foreach ($autoLinkLink as $k => $v) {
                                                if ($this->$relation->getAttribute($k) !== null) {
                                                    if ($this->getAttribute($v) != $this->$relation->getAttribute($k)) {
                                                        $this->setAttribute($v, $this->$relation->getAttribute($k));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (method_exists($this->$relation, 'hasActionErrors')) {
                                if ($this->$relation->hasActionErrors()) {
                                    $this->mergeActionErrors($this->$relation->getActionErrors());
                                }
                            }

                            if (method_exists($this->$relation, 'hasActionWarnings')) {
                                if ($this->$relation->hasActionWarnings()) {
                                    $this->mergeActionWarnings($this->$relation->getActionWarnings());
                                }
                            }

                            if (!$ok) {
                                $allOk = false;
                                break;
                            }

                        } else {

                            $message = 'Skipping saveAll (' . $saveRelationType . ') on ' . $relation . ' readOnly model';
                            $this->addActionWarning($message);

                        }

                    }
                }
            }
        }

        return $allOk;

    }

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
    public function beforeSaveAllInternal($runValidation = true, $hasParentModel = false, $push = false)
    {

        $this->clearActionErrors();
        $this->resetChildHasChanges();
        $transaction = null;

        $canSaveAll = true;

        if (!$hasParentModel) {
            $event = new ModelEvent;
            $this->trigger(self::EVENT_BEFORE_SAVE_ALL, $event);
            $canSaveAll = $event->isValid;
        }

        if ($this->getReadOnly()) {
            // will be ignored during saveAll()
        } else {

            /**
             * All saveAll() calls are treated as transactional and a transaction
             * will be started if one has not already been on the db connection
             */

            /** @var Connection $db */
            $db = static::getDb();
            $transaction = $db->getTransaction() === null ? $db->beginTransaction() : null;

            $canSaveAll = (!$canSaveAll ? $canSaveAll : $this->beforeSaveAll());

            if ($canSaveAll) {

                if ($runValidation) {

                    if ($this->hasChanges()) {

                        if (!$hasParentModel) {
                            $this->setChildHasChanges('this');
                            $this->setChildOldValues('this', $this->getResetDataForFailedSave());
                        }

                        $canSaveAll = $this->validate();
                        if (!$canSaveAll) {
                            $errors = $this->getErrors();
                            foreach ($errors as $errorField => $errorDescription) {
                                $this->addActionError($errorDescription, 0, $errorField);
                            }
                        }
                    }
                }

                $this->processModelMap();
                if ($this->modelRelationMap) {

                    foreach ($this->modelRelationMap as $relation => $relationInfo) {

                        if ($relationInfo['onSaveAll'] == self::SAVE_CASCADE && ($this->isRelationPopulated($relation) || ($push && isset($this->$relation)))) {

                            $isReadOnly = ($relationInfo['readOnly'] === null || !$relationInfo['readOnly'] ? false : true);
                            if ($this->$relation instanceof ActiveRecordReadOnlyInterface) {
                                $isReadOnly = $this->$relation->getReadOnly();
                            }

                            if (!$isReadOnly) {

                                $needsCheck = true;
                                $isActiveRecordArray = ($this->$relation instanceof ActiveRecordArray);

                                if (!$isActiveRecordArray) {
                                    if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                        $needsCheck = $this->$relation->hasChanges(true);
                                    } elseif (method_exists($this->$relation, 'getDirtyAttributes')) {
                                        if (!$this->$relation->getDirtyAttributes()) {
                                            $needsCheck = false;
                                        }
                                    }
                                }

                                if ($needsCheck) {
                                    $this->setChildHasChanges($relation);
                                    if (!$isActiveRecordArray) {
                                        if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                            $this->setChildOldValues($relation, $this->$relation->getResetDataForFailedSave());
                                        } else {
                                            $this->setChildOldValues(
                                                $relation,
                                                array(
                                                    'new' => $this->$relation->getIsNewRecord(),
                                                    'oldValues' => $this->$relation->getOldAttributes(),
                                                    'current' => $this->$relation->getAttributes()
                                                )
                                            );
                                        }
                                    }

                                    $canSaveThis = true;
                                    if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                        $canSaveThis = $this->$relation->beforeSaveAllInternal($runValidation, true, $push);
                                        if (!$canSaveThis) {
                                            if (method_exists($this->$relation, 'hasActionErrors')) {
                                                if ($this->$relation->hasActionErrors()) {
                                                    $this->mergeActionErrors($this->$relation->getActionErrors());
                                                }
                                            }
                                        }
                                    } elseif (method_exists($this->$relation, 'validate')) {
                                        $canSaveThis = $this->$relation->validate();
                                        if (!$canSaveThis) {
                                            $errors = $this->$relation->getErrors();
                                            foreach ($errors as $errorField => $errorDescription) {
                                                $this->addActionError($errorDescription, 0, $errorField, Tools::getClassName($this->$relation));
                                            }
                                        }
                                    }

                                    if (!$canSaveThis) {
                                        $canSaveAll = false;
                                    }

                                }
                            }
                        }
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
            if ($transaction !== null) {
                // cancel the started transaction
                $transaction->rollback();
            }
        } else {
            if ($transaction !== null) {
                $this->setChildOldValues('_transaction_', $transaction);
            }
        }

        return $canSaveAll;
    }

    /**
     * Called by beforeSaveAllInternal on the current model to determine if the whole of saveAll
     * can be processed - this is expected to be replaced in individual models when required
     *
     * @return boolean okay to continue with saveAll
     */
    public function beforeSaveAll()
    {
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
        /** @var \yii\db\Transaction $transaction */
        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->commit();
        }

        if ($this->getReadOnly()) {
            // will be ignored during saveAll()
        } else {

            $this->processModelMap();
            if ($this->modelRelationMap) {

                foreach ($this->modelRelationMap as $relation => $relationInfo) {

                    if ($relationInfo['onSaveAll'] == self::SAVE_CASCADE && ($this->isRelationPopulated($relation))) {

                        if ($this->getChildHasChanges($relation)) {

                            if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                $this->$relation->afterSaveAllInternal(true);
                            } elseif ($this->$relation instanceof YiiActiveRecord && method_exists($this->$relation, 'afterSaveAll')) {
                                $this->$relation->afterSaveAll();
                            }
                        }
                    }
                }
            }

            // any model specific actions to carry out
            $this->afterSaveAll();

        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            $this->trigger(self::EVENT_AFTER_SAVE_ALL);
        }
    }

    /**
     * Called by afterSaveAllInternal on the current model once the whole of the saveAll() has
     * been successfully processed
     */
    public function afterSaveAll()
    {

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
        /** @var \yii\db\Transaction $transaction */
        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->rollback();
        }

        if ($this->getReadOnly()) {
            // will be ignored during saveAll()
        } else {

            $this->processModelMap();
            if ($this->modelRelationMap) {

                foreach ($this->modelRelationMap as $relation => $relationInfo) {

                    if ($relationInfo['onSaveAll'] == self::SAVE_CASCADE && ($this->isRelationPopulated($relation))) {

                        if ($this->getChildHasChanges($relation)) {
                            if (!($this->$relation instanceof ActiveRecordArray)) {
                                if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                    $this->$relation->resetOnFailedSave($this->getChildOldValues($relation));
                                } elseif ($this->$relation instanceof YiiActiveRecord) {
                                    $this->$relation->setAttributes($this->getChildOldValues($relation, 'current'), false);
                                    $this->$relation->setIsNewRecord($this->getChildOldValues($relation, 'new'));
                                    $tempValue = $this->getChildOldValues($relation, 'oldValues');
                                    $this->$relation->setOldAttributes($tempValue ? $tempValue : null);
                                }
                            }

                            if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                $this->$relation->afterSaveAllFailedInternal(true);
                            } elseif ($this->$relation instanceof YiiActiveRecord && method_exists($this->$relation, 'afterSaveAllFailed')) {
                                $this->$relation->afterSaveAllFailed();
                            }
                        }
                    }
                }
            }

            if (!$hasParentModel) {
                if ($this->getChildHasChanges('this')) {
                    $this->resetOnFailedSave($this->getChildOldValues('this'));
                }
            }

            // any model specific actions to carry out
            $this->afterSaveAllFailed();

        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            $this->trigger(self::EVENT_AFTER_SAVE_ALL_FAILED);
        }
    }

    /**
     * Called by afterSaveAllInternal on the current model once saveAll() fails
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
        return array('new' => $this->getIsNewRecord(), 'oldValues' => $this->getOldAttributes(), 'current' => $this->getAttributes());
    }

    /**
     * Reset current record to state before saveAll() was called in the event
     * that saveAll() fails
     * @param array $data array of data required to rollback the current model
     */
    public function resetOnFailedSave($data)
    {
        $this->setAttributes($data['current'], false);
        $this->setIsNewRecord($data['new']);
        $tempValue = $data['oldValues'];
        $this->setOldAttributes($tempValue ? $tempValue : null);
    }

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
    public function delete($hasParentModel = false, $fromDeleteFull = false)
    {
        $ok = true;
        if (!$this->getReadOnly() && $this->getCanDelete()) {
            try {
                $ok = parent::delete();
            } catch (\Exception $e) {
                $ok = false;
                $this->addActionError($e->getMessage(), $e->getCode());
            }
            if ($ok && !$fromDeleteFull) {
                $this->deleteWrapUp();
            }
        } elseif (!$hasParentModel) {
            $message = 'Attempting to delete ' . Tools::getClassName($this) . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable');
            //$this->addActionError($message);
            throw new Exception($message);
        } else {
            $this->addActionWarning('Skipped delete of ' . Tools::getClassName($this) . ' which is ' . ($this->getReadOnly() ? 'read only' : 'flagged as not deletable'));
        }

        return $ok;
    }

    /**
     * reset the current record as much as possible after delete()
     */
    public function deleteWrapUp()
    {
        $attributes = $this->attributes();
        foreach ($attributes as $name) {
            $this->setAttribute($name, null);
        }
        //$this->setOldAttributes(null); // now done in Yii::ActiveRecord::deleteInternal()
        $this->setIsNewRecord(true);
        $this->defaultsApplied = false;
        $this->defaultsAppliedText = false;
        $relations = $this->getRelatedRecords();
        foreach ($relations as $name => $value) {
            $this->__unset($name);
        }
    }

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
    public function deleteFull($hasParentModel = false)
    {

        $this->clearActionErrors();

        if ($this->getIsNewRecord()) {

            // record does not exist yet anyway
            $allOk = true;

        } elseif (!$hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to amend or delete
            $message = 'Attempting to delete ' . Tools::getClassName($this) . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable');
            //$this->addActionError($message);
            throw new Exception($message);

        } elseif ($hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to amend or delete but is a child model so we will treat as okay without deleting the record
            $this->addActionWarning('Skipped delete of ' . Tools::getClassName($this) . ' which is ' . ($this->getReadOnly() ? 'read only' : 'flagged as not deletable'));
            $allOk = true;

        } else {

            if (!$hasParentModel) {
                // run beforeDeleteFull and abandon deleteFull() if it returns false
                if (!$this->beforeDeleteFullInternal($hasParentModel)) {
                    return false;
                }
            }

            try {

                $allOk = true;

                $this->processModelMap();
                if ($this->modelRelationMap) {

                    foreach ($this->modelRelationMap as $relation => $relationInfo) {

                        if ($relationInfo['onDeleteFull'] == self::DELETE_CASCADE) {

                            $isReadOnly = ($relationInfo['readOnly'] === null || !$relationInfo['readOnly'] ? false : true);
                            $canDelete = ($relationInfo['canDelete'] === null || $relationInfo['canDelete'] ? true : false);
                            if ($this->isRelationPopulated($relation)) {
                                if ($this->$relation instanceof ActiveRecordReadOnlyInterface) {
                                    $isReadOnly = $this->$relation->getReadOnly();
                                    $canDelete = $this->$relation->getCanDelete();
                                }
                            }

                            if (!$isReadOnly && $canDelete) {

                                $ok = true;

                                if (isset($this->$relation)) {

                                    if ($this->$relation instanceof ActiveRecordArray) {

                                        $ok = $this->$relation->deleteFull(true);

                                    } else {

                                        $ok = false;
                                        if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                            $ok = $this->$relation->deleteFull(true);
                                        } elseif (method_exists($this->$relation, 'delete')) {
                                            $ok = $this->$relation->delete();
                                        }

                                    }

                                    if (method_exists($this->$relation, 'hasActionErrors')) {
                                        if ($this->$relation->hasActionErrors()) {
                                            $this->mergeActionErrors($this->$relation->getActionErrors());
                                        }
                                    }

                                    if (method_exists($this->$relation, 'hasActionWarnings')) {
                                        if ($this->$relation->hasActionWarnings()) {
                                            $this->mergeActionWarnings($this->$relation->getActionWarnings());
                                        }
                                    }

                                }

                                if (!$ok) {
                                    $allOk = false;
                                }

                            } else {
                                $this->addActionWarning('Skipped delete of ' . $relation . ' which is ' . ($isReadOnly ? 'read only' : 'flagged as not deletable'));
                            }

                        }
                    }

                }

                if ($allOk) {
                    $allOk = $this->delete($hasParentModel, true);
                    if ($allOk) {
                        $allOk = true;
                    }
                }

            } catch (\Exception $e) {
                $allOk = false;
                $this->addActionError($e->getMessage(), $e->getCode());
            }

            if (!$hasParentModel) {
                if ($allOk) {
                    $this->afterDeleteFullInternal();
                } else {
                    $this->afterDeleteFullFailedInternal();
                }
            }

        }

        return $allOk;
    }

    /**
     * This method is called at the beginning of a deleteFull() request on a record or model map
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
        $transaction = null;

        $canDeleteFull = true;

        if (!$hasParentModel) {
            $event = new ModelEvent;
            $this->trigger(self::EVENT_BEFORE_DELETE_FULL, $event);
            $canDeleteFull = $event->isValid;
        }

        if ($this->getIsNewRecord()) {
            // will be ignored during deleteFull()
        } elseif ($this->getReadOnly()) {
            // will be ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will be ignored during deleteFull()
        } else {

            /**
             * All deleteFull() calls are treated as transactional and a transaction
             * will be started if one has not already been on the db connection
             */

            /** @var Connection $db */
            $db = static::getDb();
            $transaction = $db->getTransaction() === null ? $db->beginTransaction() : null;

            $canDeleteFull = (!$canDeleteFull ? $canDeleteFull : $this->beforeDeleteFull());

            if ($canDeleteFull) {

                if (!$hasParentModel) {
                    $this->setChildHasChanges('this');
                    $this->setChildOldValues('this', $this->getResetDataForFailedSave());
                }

                $this->processModelMap();
                if ($this->modelRelationMap) {

                    foreach ($this->modelRelationMap as $relation => $relationInfo) {

                        if ($relationInfo['onDeleteFull'] == self::DELETE_CASCADE) {

                            $isReadOnly = ($relationInfo['readOnly'] === null || !$relationInfo['readOnly'] ? false : true);
                            $canDelete = ($relationInfo['canDelete'] === null || $relationInfo['canDelete'] ? true : false);
                            if ($this->isRelationPopulated($relation)) {
                                if ($this->$relation instanceof ActiveRecordReadOnlyInterface) {
                                    $isReadOnly = $this->$relation->getReadOnly();
                                    $canDelete = $this->$relation->getCanDelete();
                                }
                            }

                            if (!$isReadOnly && $canDelete) {

                                if (isset($this->$relation)) {

                                    $isActiveRecordArray = ($this->$relation instanceof ActiveRecordArray);

                                    $this->setChildHasChanges($relation);
                                    if (!$isActiveRecordArray) {
                                        if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                            $this->setChildOldValues($relation, $this->$relation->getResetDataForFailedSave());
                                        } else {
                                            $this->setChildOldValues(
                                                $relation,
                                                array(
                                                    'new' => $this->$relation->getIsNewRecord(),
                                                    'oldValues' => $this->$relation->getOldAttributes(),
                                                    'current' => $this->$relation->getAttributes()
                                                )
                                            );
                                        }
                                    }

                                    $canDeleteThis = true;
                                    if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                        $canDeleteThis = $this->$relation->beforeDeleteFullInternal(true);
                                        if (!$canDeleteThis) {
                                            if (method_exists($this->$relation, 'hasActionErrors')) {
                                                if ($this->$relation->hasActionErrors()) {
                                                    $this->mergeActionErrors($this->$relation->getActionErrors());
                                                }
                                            }
                                        }
                                    } elseif (method_exists($this->$relation, 'beforeDeleteFull')) {
                                        $canDeleteThis = $this->$relation->beforeDeleteFull();
                                        if (!$canDeleteThis) {
                                            $errors = $this->$relation->getErrors();
                                            foreach ($errors as $errorField => $errorDescription) {
                                                $this->addActionError($errorDescription, 0, $errorField, Tools::getClassName($this->$relation));
                                            }
                                        }
                                    }

                                    if (!$canDeleteThis) {
                                        $canDeleteFull = false;
                                    }

                                }
                            }
                        }
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
            if ($transaction !== null) {
                // cancel the started transaction
                $transaction->rollback();
            }
        } else {
            if ($transaction !== null) {
                $this->setChildOldValues('_transaction_', $transaction);
            }
        }

        return $canDeleteFull;
    }

    /**
     * Called by beforeDeleteFullInternal on the current model to determine if the whole of deleteFull
     * can be processed - this is expected to be replaced in individual models when required
     *
     * @return boolean okay to continue with deleteFull
     */
    public function beforeDeleteFull()
    {
        return true;
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
        /** @var \yii\db\Transaction $transaction */
        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->commit();
        }

        if ($this->getIsNewRecord()) {
            // will have been ignored during deleteFull()
        } elseif ($this->getReadOnly()) {
            // will have been ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will have been ignored during deleteFull()
        } else {

            $this->processModelMap();
            if ($this->modelRelationMap) {
                foreach ($this->modelRelationMap as $relation => $relationInfo) {
                    if ($relationInfo['onDeleteFull'] == self::DELETE_CASCADE) {
                        if ($this->getChildHasChanges($relation)) {
                            if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                $this->$relation->afterDeleteFullInternal(true);
                            } elseif ($this->$relation instanceof YiiActiveRecord && method_exists($this->$relation, 'afterDeleteFull')) {
                                $this->$relation->afterDeleteFull();
                            }
                            $this->__unset($relation);
                        }
                    }
                }
            }

            if (!$hasParentModel) {
                $this->deleteWrapUp();
            }

            // any model specific actions to carry out
            $this->afterDeleteFull();

        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            $this->trigger(self::EVENT_AFTER_DELETE_FULL);
        }
    }

    /**
     * Called by afterDeleteFullInternal on the current model once the whole of the deleteFull() has
     * been successfully processed
     */
    public function afterDeleteFull()
    {

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
        /** @var \yii\db\Transaction $transaction */
        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->rollback();
        }

        if ($this->getIsNewRecord()) {
            // will have been ignored during deleteFull()
        } elseif ($this->getReadOnly()) {
            // will have been ignored during deleteFull()
        } elseif (!$this->getCanDelete()) {
            // will have been ignored during deleteFull()
        } else {

            $this->processModelMap();
            if ($this->modelRelationMap) {

                foreach ($this->modelRelationMap as $relation => $relationInfo) {
                    if ($relationInfo['onDeleteFull'] == self::DELETE_CASCADE) {

                        if ($this->getChildHasChanges($relation)) {
                            if (!($this->$relation instanceof ActiveRecordArray)) {
                                if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                    $this->$relation->resetOnFailedSave($this->getChildOldValues($relation));
                                } elseif ($this->$relation instanceof YiiActiveRecord) {
                                    $this->$relation->setAttributes($this->getChildOldValues($relation, 'current'), false);
                                    $this->$relation->setIsNewRecord($this->getChildOldValues($relation, 'new'));
                                    $tempValue = $this->getChildOldValues($relation, 'oldValues');
                                    $this->$relation->setOldAttributes($tempValue ? $tempValue : null);
                                }
                            }

                            if ($this->$relation instanceof ActiveRecordSaveAllInterface) {
                                $this->$relation->afterDeleteFullFailedInternal(true);
                            } elseif ($this->$relation instanceof YiiActiveRecord && method_exists($this->$relation, 'afterDeleteFullFailed')) {
                                $this->$relation->afterDeleteFullFailed();
                            }
                        }

                    }
                }
            }

            if (!$hasParentModel) {
                if ($this->getChildHasChanges('this')) {
                    $this->resetOnFailedSave($this->getChildOldValues('this'));
                }
            }

            // any model specific actions to carry out
            $this->afterDeleteFullFailed();

        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            $this->trigger(self::EVENT_AFTER_DELETE_FULL_FAILED);
        }
    }

    /**
     * Called by afterDeleteFullFailedInternal on the current model once deleteFull() has
     * failed processing
     */
    public function afterDeleteFullFailed()
    {

    }

    /**
     * Obtain list of fields that need to have string lengths checked as part of beforeSave()
     * @return array
     */
    public function beforeSaveStringFields()
    {
        return [];
    }

    /**
     * Obtain the model relation map array (this function should be overwritten inside AR classes)
     * @return array
     */
    public function modelRelationMap()
    {
        return [];
    }

    /**
     * Process model map to ensure all missing values have their defaults applied
     * (saves on isset() checking attributes when ever the model map is used
     */
    public function processModelMap()
    {
        if ($this->modelRelationMap) {
            // already processed
        } else {
            $this->modelRelationMap = $this->modelRelationMap();
            if ($this->modelRelationMap) {
                foreach ($this->modelRelationMap as $relation => $relationInfo) {
                    $this->modelRelationMap[$relation] = array_merge(
                        array(
                            'type' => 'hasOne',
                            'class' => '',
                            'link' => array(),
                            'config' => array(),
                            'onSaveAll' => self::SAVE_NO_ACTION,
                            'onDeleteFull' => self::DELETE_NO_ACTION,
                            'autoLinkType' => self::LINK_NONE,
                            'autoLink' => array(),
                            'allToArray' => false,
                            'skipNullLinkCheck' => false,
                            'readOnly' => null,
                            'canDelete' => null,
                            'activeAttributesInParent' => false,
                    ),
                        $relationInfo
                    );
                    // for now we do not want to cascade save or delete on belongsTo relations
                    if ($this->modelRelationMap[$relation]['type'] == 'belongsTo' && $this->modelRelationMap[$relation]['onSaveAll'] != self::SAVE_NO_ACTION) {
                        $this->modelRelationMap[$relation]['onSaveAll'] = self::SAVE_NO_ACTION;
                    }
                    if ($this->modelRelationMap[$relation]['type'] == 'belongsTo' && $this->modelRelationMap[$relation]['onDeleteFull'] != self::DELETE_NO_ACTION) {
                        $this->modelRelationMap[$relation]['onDeleteFull'] = self::DELETE_NO_ACTION;
                    }
                }
            }
        }
    }

    /**
     * Check if the model attribute name is a defined relation
     * @param string $name
     * @return boolean
     */
    public function isDefinedRelation($name) {
        $this->processModelMap();
        if ($this->modelRelationMap && array_key_exists($name, $this->modelRelationMap)) {
            if (is_array($this->modelRelationMap[$name]) && $this->modelRelationMap[$name]) {
                // relation is included in the defined array along with some setup information
                return true;
            }
        }
        return false;
    }

    /**
     * Get defined relation info by relation name or return false if the name is not a defined relation
     * @param string $name
     * @param string|false $key
     * @return array|string|false
     */
    public function getDefinedRelationInfo($name, $key = false)
    {
        $this->processModelMap();
        if ($this->modelRelationMap && array_key_exists($name, $this->modelRelationMap)) {
            if ($key) {
                if (is_array($this->modelRelationMap[$name]) && array_key_exists($key, $this->modelRelationMap[$name])) {
                    return $this->modelRelationMap[$name][$key];
                }
            } else {
                // return defined relation with default values for anything that is missing
                return $this->modelRelationMap[$name];
            }
        }
        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::toArray()
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        if ($this->getIsNewRecord() && $this->applyDefaults && !$this->defaultsApplied) {
            $this->applyDefaults(false);
        }
        $recursive = false;
        return parent::toArray($fields, $expand, $recursive);
    }

    /**
     * Return whole active record model including relationships as an array
     *
     * @param boolean $loadedOnly [OPTIONAL] only show populated relations default is false
     * @param boolean $excludeNewAndBlankRelations [OPTIONAL] exclude new blank records, default true
     * @return array
     */
    public function allToArray($loadedOnly = false, $excludeNewAndBlankRelations = true) {

        $data = $this->toArray();

        $this->processModelMap();
        if ($this->modelRelationMap) {

            foreach ($this->modelRelationMap as $relationName => $relationInfo) {

                if ($relationInfo['allToArray']) {

                    switch ($relationInfo['type'])
                    {
                        case 'hasMany':
                            if (($loadedOnly && $this->isRelationPopulated($relationName)) || (!$loadedOnly && isset($this->$relationName))) {

                                if ($this->$relationName instanceof ActiveRecordArray) {
                                    $data[$relationName] = $this->$relationName->allToArray($loadedOnly, $excludeNewAndBlankRelations);
                                } else {
                                    $data[$relationName] = array();
                                    foreach ($this->$relationName as $key => $value) {

                                        $excludeFromArray = false;
                                        if ($excludeNewAndBlankRelations && $value->getIsNewRecord()) {
                                            if ($value instanceof ActiveRecordSaveAllInterface) {
                                                if (!$value->hasChanges(true)) {
                                                    $excludeFromArray = true;
                                                }
                                            } elseif (method_exists($value, 'getDirtyAttributes')) {
                                                if (!$value->getDirtyAttributes()) {
                                                    $excludeFromArray = true;
                                                }
                                            }
                                        }

                                        if ($excludeFromArray) {
                                            // exclude
                                        } elseif (method_exists($value, 'allToArray')) {
                                            $data[$relationName][$key] = $value->allToArray($loadedOnly, $excludeNewAndBlankRelations);
                                        } elseif (method_exists($value, 'toArray')) {
                                            $data[$relationName][$key] = $value->toArray(array(), array(), false);
                                        }
                                    }
                                }

                            }
                            break;

                        case 'hasOne':
                        case 'hasEav':
                        case '__belongsTo': // disabled (can enable but be careful of recursion)

                            if (($loadedOnly && $this->isRelationPopulated($relationName)) || (!$loadedOnly && isset($this->$relationName))) {

                                if ($this->$relationName instanceof $this && $this->$relationName->getIsNewRecord()) {
                                    // would lead to infinite recursion
                                } else {

                                    $excludeFromArray = false;
                                    if ($excludeNewAndBlankRelations && $this->$relationName->getIsNewRecord()) {
                                        if (true && $relationInfo['type'] === 'hasEav') {
                                            // typically appear like a new record when first loaded until attributes are accessed or output
                                        } elseif ($this->$relationName instanceof ActiveRecordSaveAllInterface) {
                                            if (!$this->$relationName->hasChanges(true)) {
                                                $excludeFromArray = true;
                                            }
                                        } elseif (method_exists($this->$relationName, 'getDirtyAttributes')) {
                                            if (!$this->$relationName->getDirtyAttributes()) {
                                                $excludeFromArray = true;
                                            }
                                        }
                                    }

                                    if ($excludeFromArray) {
                                        // exclude
                                    } elseif (method_exists($this->$relationName, 'allToArray')) {
                                        $data[$relationName] = $this->$relationName->allToArray($loadedOnly, $excludeNewAndBlankRelations);
                                    } elseif (method_exists($this->$relationName, 'toArray')) {
                                        $data[$relationName] = $this->$relationName->toArray();
                                    }
                                }

                            }
                            break;

                        default:

                            // exclude from allToArray
                            break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Automatically establish the relationship if defined in the $modelRelationMap array
     *
     * @param string $name
     * @param boolean $new Is this a new record
     * @prarm boolean $fromGetCall Has request come from a get request via __call()
     * @return mixed NULL
     */
    public function getDefinedRelationship($name, $new = false, $fromGetCall = false)
    {
        if ($this->isDefinedRelation($name)) {

            $relationInfo = $this->getDefinedRelationInfo($name);

            if ($relationInfo) {

                if ($relationInfo['class'] && $relationInfo['link']) {

                    if ($new && !$fromGetCall) {

                        switch ($relationInfo['autoLinkType']) {

                            case self::LINK_ONLY:
                            case self::LINK_FROM_PARENT:
                            case self::LINK_FROM_CHILD:
                            case self::LINK_BI_DIRECT:
                            case self::LINK_FROM_PARENT_MAINT:
                            case self::LINK_FROM_CHILD_MAINT:
                            case self::LINK_BI_DIRECT_MAINT:
                            case self::LINK_BI_DIRECT_MAINT_FROM_PARENT:
                            case self::LINK_BI_DIRECT_MAINT_FROM_CHILD:

                                if (class_exists($relationInfo['class'])) {

                                    switch ($relationInfo['type']) {
                                        case 'hasOne':
                                        case 'hasEav':

                                            if ($relationInfo['config']) {
                                                $value = new $relationInfo['class']($relationInfo['config']);
                                            } else {
                                                $value = new $relationInfo['class']();
                                            }
                                            return $value;

                                        case 'hasMany':

                                            $value = new ActiveRecordArray();
                                            return $value;

                                        default:

                                            // we don't want to extend any others
                                    }

                                }

                                break;

                            default:
                        }
                    } else {

                        $canLoad = ($fromGetCall ? true : $this->getNullLinkCheckOk($name, $relationInfo));

                        if ($canLoad) {

                            $config = array();
                            $config['class'] = $relationInfo['class'];
                            $config['link'] = $relationInfo['link'];
                            if ($relationInfo['config']) {
                                $config['config'] = $relationInfo['config'];
                            }

                            $relationType = ($relationInfo['type'] == 'belongsTo' ? 'hasOne' : $relationInfo['type']);

                            $value = call_user_func_array(array(
                                $this,
                                $relationType
                            ), $config);

                            return $value;
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string $relationName
     */
    public function getNullLinkCheckOk($relationName, $relationInfo = null)
    {
        $success = true;
        if (is_null($relationInfo)) {
            if ($this->isDefinedRelation($relationName)) {
                $relationInfo = $this->getDefinedRelationInfo($relationName);
            } else {
                $success = false;
            }
        }

        if ($success) {

            if ($relationInfo['link'] && !$relationInfo['skipNullLinkCheck']) {

                foreach ($relationInfo['link'] as $remoteAttr => $localAttr) {
                    if (!$this->hasAttribute($localAttr)) {
                        $success = false;
                    } else {
                        $attrValue = $this->getAttribute($localAttr);
                        if (is_numeric($attrValue) && $attrValue > 0) {
                        } elseif (is_string($attrValue) && $attrValue != '') {
                        } else {
                            $success = false;
                        }
                    }
                    if (!$success) {
                        break;
                    }
                }

            }

        }

        return $success;
    }

    /**
     * Declares a `has-eav` relation.
     * @param string $class the class name of the related record (must not be 'attributes')
     * @param array $link the primary-foreign key constraint. The keys of the array refer to
     * the attributes of the record associated with the `$class` model, while the values of the
     * array refer to the corresponding attributes in **this** AR class.
     * @param array $config [OPTIONAL] array of config paramaters
     * @return ActiveAttributeRecord
     */
    public function hasEav($class, $link, $config=array())
    {
        if (!is_array($config)) {
            if ($config && is_numeric($config)) {
                $entityId = $config;
                $config = array();
                $config['entityId'] = $entityId;
            } else {
                $config = array();
            }
        }

        $config['link'] = $link;
        $config['parentModel'] = $this;

        return new $class($config);
    }

    /**
     * Declares a `has-many` relation.
     * @param string $class the class name of the related record
     * @param array $link the primary-foreign key constraint. The keys of the array refer to
     * the attributes of the record associated with the `$class` model, while the values of the
     * array refer to the corresponding attributes in **this** AR class.
     * adjust the result to use the primary key as the array key
     * @return ActiveQueryInterface the relational query object.
     */
    public function hasMany($class, $link)
    {
        /* @var $class ActiveRecordInterface */
        /* @var $query ActiveQuery */
        $query = parent::hasMany($class, $link);
        if ($query->multiple) {
            $keys = $class::primaryKey();
            if (count($keys) === 1) {
                $query->indexBy = $keys[0];
            } elseif (count($keys) === 2) {
                $query->indexBy = $keys[1];
            }
        }
        return $query;
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that AR attributes can be accessed like properties,
     * but only if the current model is not read only
     * @param string $name property name
     * @param mixed $value property value
     * @throws Exception if the current record is read only
     */
    public function __set($name, $value)
    {
        if ($this->getReadOnly()) {
            throw new Exception('Attempting to set attribute `' . $name . '` on a read only ' . Tools::getClassName($this) . ' model');
        }
        parent::__set($name, $value);
    }

    /**
     * PHP getter magic method. Override \yii\db\ActiveRecord so that we can automatically
     * setup any defined relationships if the method to set them up does not exist
     * as well as calling the method instead if it does exist (so we can push ActiveQueryInterface->multiple
     * values into ActiveRecordArray()
     * @param string $name property name
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {

        if (!$this->isRelationPopulated($name) && $this->isDefinedRelation($name)) {

            if ($this->getIsNewRecord()) {

                // quite possible the new sub record is also new - let's check
                // to see if we can support the auto creation of the empty sub record
                $value = $this->getDefinedRelationship($name, true);

            } else {

                if (method_exists($this, 'get' . $name)) {

                    $method = new \ReflectionMethod($this, 'get' . $name);
                    $realName = lcfirst(substr($method->getName(), 3));
                    if ($realName !== $name) {
                        throw new \yii\base\InvalidParamException('Relation names are case sensitive. ' . get_class($this) . " has a relation named \"$realName\" instead of \"$name\".");
                    }

                    $value = call_user_func(array($this, 'get' . $name));

                } else {

                    // we will automatically apply this relation now
                    $value = $this->getDefinedRelationship($name);

                }

            }

            if ($value !== null) {

                if ($value instanceof ActiveQueryInterface) {
                    if ($value->multiple) {
                        // put result into a special ArrayObject extended object
                        $value2 = new ActiveRecordArray($value->all());
                    } else {
                        $value2 = $value->one();
                        if (is_null($value2) && !$this->getIsNewRecord()) {
                            // relational record does not exist yet so we will create an empty object now allowing user to start to populate values
                            $value2 = $this->getDefinedRelationship($name, true);
                        }
                    }
                    if ($value2 instanceof ActiveRecordArray) {
                        $value2->setDefaultObjectClass($this->getDefinedRelationInfo($name, 'class'));
                    }
                    if ($value2 instanceof ActiveRecordParentalInterface) {
                        $value2->setParentModel($this);
                    }
                    if ($value2 instanceof ActiveRecordReadOnlyInterface) {
                        $readOnly = $this->getDefinedRelationInfo($name, 'readOnly');
                        if ($readOnly !== null) {
                            $value2->setReadOnly($readOnly);
                        }
                        $canDelete = $this->getDefinedRelationInfo($name, 'canDelete');
                        if ($canDelete !== null) {
                            $value2->setCanDelete($canDelete);
                        }
                    }
                    $this->populateRelation($name, $value2);
                    return $value2;

                } elseif ($value instanceof ActiveRecordParentalInterface) {

                    $value->setParentModel($this);
                    if ($value instanceof ActiveRecordArray) {
                        $defaultClass = $this->getDefinedRelationInfo($name, 'class');
                        if ($defaultClass) {
                            $value->setDefaultObjectClass($defaultClass);
                        }
                    }
                    if ($value instanceof ActiveRecordReadOnlyInterface) {
                        $readOnly = $this->getDefinedRelationInfo($name, 'readOnly');
                        if ($readOnly !== null) {
                            $value->setReadOnly($readOnly);
                        }
                        $canDelete = $this->getDefinedRelationInfo($name, 'canDelete');
                        if ($canDelete !== null) {
                            $value->setCanDelete($canDelete);
                        }
                    }
                    $this->populateRelation($name, $value);

                } elseif ($value instanceof YiiActiveRecord) {

                    $this->populateRelation($name, $value);

                }

                return $value;

            }

        }

        return parent::__get($name);
    }

    /**
     * PHP magic method to handle calls to functions that do not exist
     * Initially here to allow successful self::getRelation() call when using the model
     * map array but also used to allow AR variables to get set/get via a set/get call
     * if the method does not already exist
     * @see \yii\base\Component::__call()
     */
    public function __call($methodName, $args) {
        if (preg_match('~^(set|get)(.*)$~', $methodName, $matches)) {
            $name = lcfirst($matches[2]);
            if ($this->isDefinedRelation($name)) {
                if ($matches[1] == 'get') {
                    return $this->getDefinedRelationship($name, true, true);
                } else {
                    throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$methodName()");
                }
            } else {

                $allow = false;
                if ($this->hasAttribute($name)) {
                    $allow = true;
                } else {
                    $name = ucfirst($name);
                    if ($this->hasAttribute($name)) {
                        $allow = true;
                    }
                }

                if ($allow) {
                    switch($matches[1]) {
                        case 'set':
                            $this->checkArguments($args, 1, 1, $methodName);
                            $this->setAttribute($name, $args[0]);
                            return $this;
                        case 'get':
                            $this->checkArguments($args, 0, 0, $methodName);
                            return $this->getAttribute($name);
                    }
                }
            }
        }
        return parent::__call($methodName, $args);
    }

    /**
     * Quickly validate method argument count matches expectations else throw exception
     *
     * @param array $args
     * @param integer $min
     * @param integer $max
     * @param string $methodName
     * @throws InvalidParamException
     */
    protected function checkArguments(array $args, $min, $max, $methodName) {
        $argc = count($args);
        if ($argc < $min || $argc > $max) {
            throw new InvalidParamException('Method ' . $methodName . ' needs min ' . $min . ' and max ' . $max . ' arguments. ' . $argc . ' arguments given.');
        }
    }

    /**
     * @inheritdoc
     */
    public function __clone()
    {
        parent::__clone();
        $attributes = $this->getAttributes();
        $this->deleteWrapUp(); // helps set up the new active record again
        $this->setOldAttributes(null);
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        $keys = self::primaryKey();
        $keysCount = count($keys);
        if ($keysCount == 1) {
            $this->setAttribute($keys[0], null);
        }
    }

    /**
     * Call the debugTest method on all objects in the model map (used for testing)
     *
     * @param boolean $loadedOnly [OPTIONAL] only include populated relations default is false
     * @param boolean $excludeNewAndBlankRelations [OPTIONAL] exclude new blank records, default true
     * @return array
     */
    public function callDebugTestOnAll($loadedOnly=false, $excludeNewAndBlankRelations = true) {

        $data = $this->debugTest();

        $this->processModelMap();
        if ($this->modelRelationMap) {

            foreach ($this->modelRelationMap as $relationName => $relationInfo) {

                if ($relationInfo['allToArray']) {

                    switch ($relationInfo['type'])
                    {
                        case 'hasMany':

                            if (($loadedOnly && $this->isRelationPopulated($relationName)) || (!$loadedOnly && isset($this->$relationName))) {

                                if ($this->$relationName instanceof ActiveRecordArray) {
                                    $data[$relationName] = $this->$relationName->callDebugTestOnAll($loadedOnly, $excludeNewAndBlankRelations);
                                } else {
                                    $data[$relationName] = array();
                                    foreach ($this->$relationName as $key => $value) {

                                        $excludeFromArray = false;
                                        if ($excludeNewAndBlankRelations && $value->getIsNewRecord()) {
                                            if ($value instanceof ActiveRecordSaveAllInterface) {
                                                if (!$value->hasChanges(true)) {
                                                    $excludeFromArray = true;
                                                }
                                            } elseif (method_exists($value, 'getDirtyAttributes')) {
                                                if (!$value->getDirtyAttributes()) {
                                                    $excludeFromArray = true;
                                                }
                                            }
                                        }

                                        if ($excludeFromArray) {
                                            // exclude
                                        } elseif (method_exists($value, 'callDebugTestOnAll')) {
                                            $data[$relationName][$key] = $value->callDebugTestOnAll($loadedOnly, $excludeNewAndBlankRelations);
                                        }
                                    }
                                }

                            }
                            break;

                        case 'hasOne':
                        case 'hasEav':
                        case '__belongsTo': // disabled (can enable but be careful of recursion)

                            if (($loadedOnly && $this->isRelationPopulated($relationName)) || (!$loadedOnly && isset($this->$relationName))) {

                                if ($this->$relationName instanceof $this && $this->$relationName->getIsNewRecord()) {
                                    // would lead to infinite recursion
                                } else {

                                    $excludeFromArray = false;
                                    if ($excludeNewAndBlankRelations && $this->$relationName->getIsNewRecord()) {
                                        if (true && $relationInfo['type'] === 'hasEav') {
                                            // typically appear like a new record when first loaded until attributes are accessed or output
                                        } elseif ($this->$relationName instanceof ActiveRecordSaveAllInterface) {
                                            if (!$this->$relationName->hasChanges(true)) {
                                                $excludeFromArray = true;
                                            }
                                        } elseif (method_exists($this->$relationName, 'getDirtyAttributes')) {
                                            if (!$this->$relationName->getDirtyAttributes()) {
                                                $excludeFromArray = true;
                                            }
                                        }
                                    }

                                    if ($excludeFromArray) {
                                        // exclude
                                    } elseif (method_exists($this->$relationName, 'callDebugTestOnAll')) {
                                        $data[$relationName] = $this->$relationName->callDebugTestOnAll($loadedOnly, $excludeNewAndBlankRelations);
                                    } elseif (method_exists($this->$relationName, 'debugTest')) {
                                        $data[$relationName] = $this->$relationName->debugTest();
                                    }
                                }

                            }
                            break;

                        default:

                            // exclude from allToArray
                            break;
                    }
                }
            }
        }

        return $data;
    }

}
