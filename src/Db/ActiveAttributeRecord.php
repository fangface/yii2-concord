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

use Yii;
use yii\base\ModelEvent;
use Concord\Base\Traits\ActionErrors;
use Concord\Db\Exception;
use Concord\Db\ActiveRecordParentalInterface;
use Concord\Db\ActiveRecordParentalTrait;
use Concord\Db\ActiveRecordReadOnlyInterface;
use Concord\Db\ActiveRecordReadOnlyTrait;
use Concord\Db\ActiveRecordSaveAllInterface;
use Concord\Tools;

class ActiveAttributeRecord implements ActiveRecordParentalInterface, ActiveRecordReadOnlyInterface, ActiveRecordSaveAllInterface
{

    use ActionErrors;
    use ActiveRecordParentalTrait;
    use ActiveRecordReadOnlyTrait;
    use \Concord\Base\Traits\ServiceGetter;

    protected $attributeEntitiesClass = false;

    protected $attributeDefinitionsClass = false;

    protected $attributeValuesClass = false;

    /**
     * Provided by $config or by class extension
     *
     * @var array The attribtue mapping details to use against the $parentModel to obtain the $objectId
     */
    protected $link = false;

    /**
     * Provided by $config or by class extension
     *
     * @var integer The entityId for this attribute class if it us using a shared set of attribute tables
     */
    protected $entityId = false;

    /**
     * Provided by $config or $parentModel
     *
     * @var integer The objectId for which attributes will be loaded, saved or deleted
     */
    protected $objectId = false;

    /**
     * Array of existing attribute value models ready to use during delete or save
     *
     * @var \Concord\Models\AttributeValues[]
     */
    private $attributeValues = array();

    /**
     *
     * @var boolean Indicates if this attribute class has been loaded with the current entityId and objectId attributes
     */
    private $loaded = false;

    /**
     *
     * @var boolean Indicates if this new record has ahd the default attributes setup ready for population
     */
    private $isNewPrepared = false;

    /**
     * Array of existing attribute values including defaults that will be interacted with until time to save, delete or create
     *
     * @var array
     */
    private $data = array();

    /**
     * Array of attributes that have not been loaded up yet because they have been specified as only requiring lazy loading at time of use
     *
     * @var array
     */
    private $lazyAttributes = array();

    /**
     * Array of changed attributes and their original values
     *
     * @var array
     */
    private $changedData = array();


    /**
     * Note if a loaded attribute class is assigned a new objectId for propagation during saveAll()
     * to all attributes not just those that have changes
     *
     * @var boolean|integer
     */
    private $newObjectId = false;

    /**
     * Canonical name of the class
     * each time a definitions model is required
     *
     * @var string
     */
    private $cleanDefinitionsClassName = '';

    /**
     * Holds an array of attribute definitions so that they do not need to be reloaded from the database
     * each time a definitions model is required
     *
     * @var array
     */
    private static $attributeDefinitions = array();

    /**
     * Holds an array of attribute definition maps so that they do not need to be reprocessed
     * each time a definitions model is required
     *
     * @var array
     */
    private static $attributeDefinitionsMap = array();

    /**
     * @var array validation errors (attribute name => array of errors)
     */
    private $errors;

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
     * @event Event an event that is triggered after saveAll() fails
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


    public function __construct($config = array())
    {
        if (!empty($config)) {

            if (is_array($config)) {

                $this->configure($config);
            } elseif (is_numeric($config)) {

                $this->objectId = $config;
            }
        }

        $this->init();

        if (false) {
            if ($this->objectId && $this->entityId !== false) {
                if (!$this->loaded && !$this->isNewPrepared) {
                    $this->loadAttributeValues();
                }
            }
        }
    }


    public function configure($config)
    {
        foreach ($config as $name => $value) {
            switch ($name) {
                case 'entityId':
                case 'attributeEntitiesClass':
                case 'attributeDefinitionsClass':
                case 'attributeValuesClass':
                case 'link':
                case 'objectId':
                case 'parentModel':

                    if (!is_null($value)) {
                        $this->$name = $value;
                    }

                    break;

                default:
                    break;
            }
        }
    }


    public function init()
    {
        if (!$this->attributeEntitiesClass) {
            $this->attributeEntitiesClass = \Concord\Models\AttributeEntities::className();
        }

        if (!$this->attributeDefinitionsClass) {
            $this->attributeDefinitionsClass = \Concord\Models\AttributeDefinitions::className();
        }

        if (!$this->attributeValuesClass) {
            $this->attributeValuesClass = \Concord\Models\AttributeValues::className();
        }

        if (!class_exists($this->attributeEntitiesClass)) {
            throw new Exception('Attribute entity class ' . $this->attributeEntitiesClass . ' not found in ' . __METHOD__ . '()');
        }

        if (!class_exists($this->attributeDefinitionsClass)) {
            throw new Exception('Attribute definition class ' . $this->attributeDefinitionsClass . ' not found in ' . __METHOD__ . '()');
        }

        if (!class_exists($this->attributeValuesClass)) {
            throw new Exception('Attribute value class ' . $this->attributeValuesClass . ' not found in ' . __METHOD__ . '()');
        }

        if ($this->entityId === false) {
            throw new Exception('No entity id available after ' . __METHOD__ . '()');
        }

        if (!$this->objectId && $this->parentModel && $this->parentModel instanceof \yii\db\ActiveRecord && is_array($this->link) && $this->link) {
            if (isset($this->link['objectId']) && $this->link['objectId']) {
                $this->objectId = $this->parentModel->getAttribute($this->link['objectId']);
            } else {
                $this->objectId = $this->parentModel->getAttribute($this->link[0]);
            }
        }
    }


    /**
     * Obtain class name
     *
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }


    /**
     * Reset current attribute list
     *
     * @param boolean $resetObject
     *        [OPTIONAL] reset objectId and parent model
     * @param boolean $resetAll
     *        [OPTIONAL] reset all
     */
    public function reset($resetObject = false, $resetAll = false)
    {
        if ($resetAll) {
            $this->attributeEntitiesClass = false;
            $this->attributeDefinitionsClass = false;
            $this->attributeValuesClass = false;
            $this->entityId = false;
            $this->link = false;
        }

        if ($resetObject) {
            $this->parentModel = false;
            $this->objectId = false;
        }

        $this->attributeValues = array();
        $this->loaded = false;
        $this->isNewRecord = true;
        $this->isNewPrepared = false;
        $this->data = array();
        $this->lazyAttributes = array();
        $this->changedData = array();
        $this->newObjectId = false;
    }


    public function loadAttributeValues($forceLoadLazyLoad = false)
    {
        $this->reset();

        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if (!$this->objectId && !$this->isNewRecord) {
            throw new Exception('No object id available for ' . __METHOD__ . '()');
        } elseif (!$this->objectId) {
            // setup all attributes with their default values ready for this new record
            $forceLoadLazyLoad = true;
        }

        $excludeAttributeIDList = array();
        $tempMap = array();

        $attributeDefs = $this->getEntityAttributeList();

        foreach ($attributeDefs as $k => $v) {
            if ($v['lazyLoad'] && !$forceLoadLazyLoad) {
                // we don't want to load up the values from these fields until they are explicitly accessed
                $this->lazyAttributes[$k] = $v['id'];
                $excludeAttributeIDList[] = $v['id'];
            } else {
                $this->data[$k] = $v['defaultValue'];
            }
            $tempMap[$v['id']] = $k;
        }

        if ($this->objectId) {

            $attributeValuesClass = $this->attributeValuesClass;
            $query = $attributeValuesClass::find();

            if ($this->entityId) {
                $query->where(array(
                    'entityId' => $this->entityId,
                    'objectId' => $this->objectId
                ));
            } else {
                $query->where(array(
                    'objectId' => $this->objectId
                ));
            }

            if ($excludeAttributeIDList) {
                if (count($excludeAttributeIDList) > 1) {
                    $query->andWhere(array(
                        'NOT IN',
                        'attributeId',
                        $excludeAttributeIDList
                    ));
                } else {
                    $query->andWhere(array(
                        'attributeId' => $excludeAttributeIDList[0]
                    ));
                }
            }

            $rows = $query->all();

            if ($rows) {
                foreach ($rows as $k => $v) {
                    $this->attributeValues[$tempMap[$v->attributeId]] = $v;
                    $this->data[$tempMap[$v->attributeId]] = $v->value;
                }
            }

            $this->loaded = true;
            $this->isNewRecord = false;

        } else {

            $this->isNewPrepared = true;
        }

        foreach ($this->data as $k => $v) {
            // now apply value formatters because all values are trpically stored in string fields at the moment
            $this->data[$k] = \Concord\Tools::formatAttributeValue($v, $attributeDefs[$k]);
        }
    }


    public function loadLazyAttribute($attributeNames = null)
    {
        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if (!$this->objectId) {
            throw new Exception('No object id available for ' . __METHOD__ . '()');
        }

        $singleAttributeName = false;
        $newAttributes = array();

        if (is_null($attributeNames) && $this->lazyAttributes) {
            $attributeNames = array_flip($this->lazyAttributes);
        } elseif (!is_array($attributeNames) && is_string($attributeNames) && $attributeNames) {
            $singleAttributeName = $attributeNames;
            $attributeNames = array(
                $attributeNames
            );
        } elseif (is_array($attributeNames) && count($attributeNames) == 1) {
            $singleAttributeName = $attributeNames[0];
        }

        if ($attributeNames) {

            $attributeIdList = array();
            $leftOverAttributeIdToNameMap = array();
            foreach ($attributeNames as $k => $v) {
                if ($this->lazyAttributes && array_key_exists($v, $this->lazyAttributes)) {
                    $attributeIdList[] = $this->lazyAttributes[$v];
                    $leftOverAttributeIdToNameMap[$this->lazyAttributes[$v]] = $v;
                }
            }

            if ($attributeIdList) {

                $attributeValuesClass = $this->attributeValuesClass;
                $query = $attributeValuesClass::find();

                if ($this->entityId) {
                    $query->where(array(
                        'entityId' => $this->entityId,
                        'objectId' => $this->objectId
                    ));
                } else {
                    $query->where(array(
                        'objectId' => $this->objectId
                    ));
                }

                if (count($attributeIdList) > 1) {
                    $query->andWhere(array(
                        'attributeId' => $attributeIdList
                    ));
                } else {
                    $query->andWhere(array(
                        'attributeId' => $attributeIdList[0]
                    ));
                }

                $rows = $query->all();

                if ($rows) {
                    foreach ($rows as $k => $v) {
                        if (isset($leftOverAttributeIdToNameMap[$v->attributeId])) {
                            $this->attributeValues[$leftOverAttributeIdToNameMap[$v->attributeId]] = $v;
                            $newAttributes[$leftOverAttributeIdToNameMap[$v->attributeId]] = $v->value;
                            unset($leftOverAttributeIdToNameMap[$v->attributeId]);
                        }
                    }
                }

                if ($newAttributes || $leftOverAttributeIdToNameMap) {

                    $attributeDefs = $this->getEntityAttributeList();

                    if ($attributeDefs) {

                        if ($leftOverAttributeIdToNameMap) {
                            foreach ($leftOverAttributeIdToNameMap as $k => $v) {
                                $newAttributes[$v] = $attributeDefs[$v]['defaultValue'];
                            }
                        }

                        // now apply value formatters because all values are trpically stores in string fields
                        foreach ($newAttributes as $k => $v) {
                            $this->data[$k] = \Concord\Tools::formatAttributeValue($v, $attributeDefs[$k]);
                            unset($this->lazyAttributes[$k]);
                        }
                    }
                }
            }

            if ($singleAttributeName && isset($newAttributes[$singleAttributeName])) {
                return true;
            } elseif (!$singleAttributeName && $newAttributes) {
                return true;
            }
        }
        return false;
    }


    /**
     * Determine if model has been loaded
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->loaded;
    }


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
    public function hasChanges($checkRelations=false)
    {
        if ($this->loaded && $this->newObjectId) {
            return true;
        }
        return ($this->changedData ? true : false);
    }


    /**
     * Determine if attribute has chanegd and not been saved
     *
     * @return boolean
     */
    public function isAttributeChanged($attributeName)
    {
        return (array_key_exists($attributeName, $this->changedData) ? true : false);
    }


    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     *
     * @param string[]|null $names
     *        the names of the attributes whose values may be returned if they are
     *        changed recently. If null all current changed attribtues will be returned.
     * @return array the changed attribute values
     */
    public function getDirtyAttributes($names = null)
    {
        if ($names === null) {
            return $this->changedData;
        }
        $names = array_flip($names);
        $attributes = array();
        foreach ($this->changedData as $name => $value) {
            if (isset($names[$name])) {
                $attributes[$name] = $value;
            }
        }
        return $attributes;
    }


    /**
     * Returns array of attributes as they were before changes were made
     *
     * @return array
     */
    public function getOldAttributes()
    {
        return ($this->changedData ? $this->changedData : array());
    }


    /**
     * Returns array of attributes as they were before changes were made
     *
     * @return array
     */
    public function setOldAttributes($values)
    {
        if (is_array($values) && $values) {
            $this->changedData = $values;
        } else {
            $this->changedData = array();
        }
    }


    /**
     * Check to see if the attributes have been loaded and if not trigger the loading process
     *
     * @param string $forceLoadLazyLoad
     *        [OPTIONAL] should lazy load attributes be forced to load, default is false
     */
    public function checkAndLoad($forceLoadLazyLoad = false)
    {
        if (!$this->loaded && !$this->isNewPrepared) {
            $this->loadAttributeValues($forceLoadLazyLoad);
        } elseif ($forceLoadLazyLoad) {
            $this->forceLoadLazyLoad(true);
        }
    }


    /**
     * Return list of attributes as an array (forcing the load of any attributes that have not been loaded yet)
     *
     * @return array:
     */
    public function toArray()
    {
        $this->checkAndLoad(true);
        return $this->data;
    }


    /**
     * Return list of attributes as an array (by default forcing the load of any attributes that have not been loaded yet)
     *
     * @param boolean $loadedOnly
     *        [OPTIONAL] default false
     * @param boolean $excludeNewAndBlankRelations
     *        [OPTIONAL] exclude new blank records, default true
     * @return array:
     */
    public function allToArray($loadedOnly=false, $excludeNewAndBlankRelations=true)
    {
        $this->checkAndLoad(!$loadedOnly);
        return $this->data;
    }


    /**
     * Check if lazy loaded attributes exist and should be fully loaded and if so load them
     *
     * @param string $forceLoadLazyLoad
     *        [OPTIONAL] default is false
     */
    public function forceLoadLazyLoad($forceLoadLazyLoad = false)
    {
        if ($forceLoadLazyLoad && $this->lazyAttributes) {
            $this->loadLazyAttribute();
        }
    }


    public function getEntityAttributeList($attributeName = '', $entityId = null)
    {
        $entityId = (is_null($entityId) ? $this->entityId : $entityId);

        if ($entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if (isset(self::$attributeDefinitions[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId])) {
            if ($attributeName) {
                if (isset(self::$attributeDefinitions[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId][$attributeName])) {
                    return self::$attributeDefinitions[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId][$attributeName];
                }
                return false;
            }
            return self::$attributeDefinitions[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId];
        }

        $conditions = array();
        if ($this->entityId) {
            $conditions['entityId'] = $this->entityId;
        }

        $attributeDefinitionsClass = $this->attributeDefinitionsClass;
        $attributeDefinitions = $attributeDefinitionsClass::find()->where($conditions)
            ->orderBy('sortOrder')
            ->asArray()
            ->all();

        $ok = ($attributeDefinitions ? true : false);

        if ($ok) {
            $result = array();
            foreach ($attributeDefinitions as $k => $v) {
                $result[$v['attributeName']] = $v;
            }
            self::$attributeDefinitions[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId] = $result;
        }

        if ($attributeName) {
            if (isset($result[$attributeName])) {
                return $result[$attributeName];
            }
            return false;
        }

        return ($ok ? $result : false);
    }


    /**
     * Obtain a mapping array that tells us which attribute name belongs to which id and vice versa
     *
     * @param integer $entityId
     * @return array false
     */
    public function getEntityAttributeMap($entityId = null)
    {
        $entityId = (is_null($entityId) ? $this->entityId : $entityId);

        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if (isset(self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId])) {
            return self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId];
        }

        $attributeList = $this->getEntityAttributeList('', $entityId);

        self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId] = array(
            'id' => array(),
            'name' => array()
        );

        if ($attributeList) {
            foreach ($attributeList as $k => $v) {
                self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId]['id'][$v['attributeId']] = $v['attributeName'];
                self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId]['name'][$v['attributeName']] = $v['attributeId'];
                self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName]['__ALL__'][$v['attributeId']] = array(
                    'name' => $v['attributeName'],
                    'entity' => $v['entityId']
                );
            }
        }

        return self::$attributeDefinitionsMap[\Concord\Tools::getClientId()][$this->cleanDefinitionsClassName][$entityId];
    }


    /**
     * Return attribute list structure as an array compatible with the response from DB::getTableMetaData()
     *
     * @param integer $entityId
     *        [OPTIONAL] defaults to current initialised entityId
     * @return array
     */
    public function getEntityAttributeListAsStructure($entityId = null)
    {
        $entityId = (is_null($entityId) ? $this->entityId : $entityId);

        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        $attributeList = $this->getEntityAttributeList('', $entityId);

        $structure = array();
        foreach ($attributeList as $k => $v) {

            $type = trim($v['dataType']);
            $characterlength = null;
            $numericPrecision = null;
            $numericScale = null;

            switch ($type) {
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'bigint':
                case 'double':
                case 'float':
                case 'decimal':
                case 'serial':
                case 'numeric':
                case 'dec':
                case 'fixed':
                    $numericPrecision = $v['length'];
                    $numericScale = $v['decimals'];
                    break;
                default:
                    $characterlength = $v['length'];
                    break;
            }

            $structure[$k] = array(
                'columnDefault' => $v['defaultValue'],
                'isNullable' => ($v['isNullable'] ? true : false),
                'dataType' => $type,
                'characterMaximumLength' => $characterlength,
                'isNumeric' => (!is_null($numericPrecision) && $numericPrecision ? true : false),
                'numericPrecision' => $numericPrecision,
                'numericScale' => $numericScale,
                'numericUnsigned' => ($v['unsigned'] ? true : false),
                'autoIncrement' => false,
                'primaryKey' => false,
                'zeroFill' => ($v['zerofill'] ? true : false)
            );
        }

        return $structure;
    }


    /**
     * Return an attribute id for a given attribute name (assumes entityId not in use in this model
     *
     * @param string $attributeName
     * @return integer false
     */
    public function getAttributeIdByName($attributeName)
    {
        return $this->getEntityAttributeIdByName($attributeName);
    }


    /**
     * Return an attribute id for a given entity and attribute name
     *
     * @param string $attributeName
     * @param integer $entityId
     *        [OPTIONAL] defaults to current initialised entityId
     * @return integer false
     */
    public function getEntityAttributeIdByName($attributeName, $entityId = null)
    {
        $entityId = (is_null($entityId) ? $this->entityId : $entityId);

        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if ($attributeName) {
            $attributeMap = $this->getEntityAttributeMap($entityId);
            if (isset($attributeMap['name'][$attributeName])) {
                return $attributeMap['name'][$attributeName];
            }
        }
        return false;
    }


    /**
     * Return an attribute name for a given entity and attribute id
     *
     * @param integer $attributeId
     * @param integer $entityId
     *        [OPTIONAL] defaults to current initialised entityId
     * @return string false
     */
    public function getEntityAttributeNameById($attributeId, $entityId = null)
    {
        $entityId = (is_null($entityId) ? $this->entityId : $entityId);

        if ($this->entityId === false) {
            throw new Exception('No entity id available for ' . __METHOD__ . '()');
        }

        if ($attributeId) {
            $attributeMap = $this->getEntityAttributeMap($entityId);
            if (isset($attributeMap['id'][$attributeId])) {
                return $attributeMap['id'][$attributeId];
            }
        }
        return false;
    }


    /**
     * Reads an attribute value by its name
     * <code>
     * echo $attributes->getAttribute('name');
     * </code>
     *
     * @param string $attributeName
     * @return mixed
     */
    public function getAttribute($attributeName)
    {
        if ($attributeName == 'objectId') {
            return $this->objectId;
        }
        return $this->__get($attributeName);
    }


    /**
     * Writes an attribute value by its name
     * <code>
     * $attributes->setAttribute('name', 'Rosey');
     * </code>
     *
     * @param string $attributeName
     * @param mixed $value
     */
    public function setAttribute($attributeName, $value)
    {
        if ($attributeName == 'objectId') {
            if ($this->loaded && $this->objectId && $this->objectId != $value) {
                $this->newObjectId = $value;
            } else {
                $this->objectId = $value;
            }
        } else {
            $this->__set($attributeName, $value);
        }
    }


    /**
     * Check to see if this attribute model includes the specified field name
     *
     * @param string $attributeName
     * @return boolean
     */
    public function hasAttribute($attributeName)
    {
        return $this->__isset($attributeName);
    }


    /**
     * Returns attribute values.
     *
     * @param array $names
     *        list of attributes whose value needs to be returned.
     *        Defaults to null, meaning all attributes listed in [[attributes()]] will be returned.
     *        If it is an array, only the attributes in the array will be returned.
     * @param array $except
     *        list of attributes whose value should NOT be returned.
     * @param boolean $loadedOnly
     *        [OPTIONAL] default true
     * @return array attribute values (name => value).
     */
    public function getAttributes($names = null, $except = [], $loadedOnly = true)
    {
        $valuesAll = $this->allToArray($loadedOnly);

        $values = array();
        if ($names === null) {
            $names = array_keys($valuesAll);
        }
        foreach ($names as $name) {
            $values[$name] = $valuesAll[$name];
        }
        foreach ($except as $name) {
            unset($values[$name]);
        }

        return $values;
    }


    /**
     * Sets the attribute values in a massive way.
     *
     * @param array $values
     *        attribute values (name => value) to be assigned to the model.
     * @param boolean $safeOnly
     *        whether the assignments should only be done to the safe attributes.
     *        A safe attribute is one that is associated with a validation rule in the current [[scenario]].
     * @see safeAttributes()
     * @see attributes()
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values)) {
            foreach ($values as $name => $value) {
                $this->__set($name, $value);
            }
        }
    }


    /**
     * Magic get method to return attributes from the data array
     *
     * @param string $property
     *        property name
     * @return mixed
     */
    public function &__get($property)
    {
        if (!$this->loaded && !$this->isNewPrepared) {

            $this->loadAttributeValues();
            return $this->__get($property);
        } elseif (array_key_exists($property, $this->data)) {

            return $this->data[$property];
        } elseif (array_key_exists($property, $this->lazyAttributes)) {

            if ($this->loadLazyAttribute($property)) {
                return $this->__get($property);
            }
        }

        $trace = debug_backtrace();
        trigger_error('Undefined relation property via __get() in ' . get_class($this) . '::' . $property . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);

        $null = null;
        return $null;
    }


    /**
     * Magic get method to return attributes from the data array
     *
     * @param string $property
     *        property name
     * @param mixed $value
     *        property value
     */
    public function __set($property, $value)
    {
        if (!$this->loaded && !$this->isNewPrepared) {

            $this->loadAttributeValues();
            $this->__set($property, $value);

        } elseif (array_key_exists($property, $this->data)) {

            if (!array_key_exists($property, $this->changedData) && $value != $this->data[$property]) {
                // changed for the first time since loaded
                $this->changedData[$property] = $this->data[$property];
                $this->data[$property] = $value;
            } elseif (array_key_exists($property, $this->changedData) && $value == $this->changedData[$property]) {
                // reverting back to what it was when loaded
                $this->data[$property] = $this->changedData[$property];
                unset($this->changedData[$property]);
            } elseif (array_key_exists($property, $this->changedData) && $value != $this->data[$property]) {
                // previously changed and changing again
                $this->data[$property] = $value;
            }

        } elseif (array_key_exists($property, $this->lazyAttributes)) {

            if ($this->loadLazyAttribute($property)) {
                $this->__set($property, $value);
            }

        } else {

            $trace = debug_backtrace();
            trigger_error('Undefined relation property via __set() in ' . get_class($this) . '::' . $property . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        }
    }


    /**
     * Magic isset method to check if attribute exists
     *
     * @param string $property
     *        property name
     * @return boolean
     */
    public function __isset($property)
    {
        $attributeDefs = $this->getEntityAttributeList();

        if (array_key_exists($property, $attributeDefs)) {
            return true;
        }

        return false;
    }


    /**
     * Set multiple attributes by array
     *
     * @param array $inputData
     * @param array $inputDataOnChange
     *        [OPTIONAL] extra attributes to apply if $inputData causes changes
     * @return boolean
     */
    public function setValuesByArray($inputData, $inputDataOnChange = false)
    {
        $hasChanges = false;

        if (is_array($inputData) && $inputData) {

            if (!$this->loaded && !$this->isNewPrepared) {

                $this->loadAttributeValues();
                $this->setValuesByArray($inputData, $inputDataOnChange);
            } else {

                foreach ($inputData as $k => $v) {
                    if (array_key_exists($k, $this->data)) {
                        if ($this->data[$k] != $v) {
                            $hasChanges = true;
                            $this->__set($k, $v);
                        }
                    } elseif ($this->lazyAttributes && array_key_exists($k, $this->lazyAttributes)) {
                        $currentValue = $this->__get($k);
                        if ($currentValue != $v) {
                            $hasChanges = true;
                            $this->__set($k, $v);
                        }
                    }
                }

                if ($hasChanges) {

                    if (is_array($inputDataOnChange) && $inputDataOnChange) {

                        foreach ($inputDataOnChange as $k => $v) {
                            $this->__set($k, $v);
                        }
                    }
                }
            }
        }

        return $hasChanges;
    }


    /**
     * deletes the current objects attributesrd but also loops through defined
     * relationships (if appropriate) to delete those as well
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

        $allOk = false;

        if ($this->getIsNewRecord()) {

            // record does not exist yet anyway
            $allOk = true;

        } elseif (!$hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to amend or delete
            $message = 'Attempting to delete ' . \Concord\Tools::getClassName($this) . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable');
            $this->addActionError($message);
            throw new \Concord\Db\Exception($message);

        } elseif ($hasParentModel && ($this->getReadOnly() || !$this->getCanDelete())) {

            // not allowed to amend or delete
            $message = 'Attempting to delete ' . \Concord\Tools::getClassName($this) . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable');
            $this->addActionError($message);
            throw new \Concord\Db\Exception($message);

        } else {

            if (!$hasParentModel) {
                // run beforeSaveAll and abandon saveAll() if it returns false
                if (!$this->beforeDeleteFullInternal($hasParentModel)) {
                    return false;
                }
            }

            try {
                $allOk = $this->delete($hasParentModel, true);
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
            //$event = new ModelEvent;
            //$this->trigger(self::EVENT_BEFORE_SAVE_ALL, $event);
            //$canDeleteFull = $event->isValid;
        }

        if ($canDeleteFull) {
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
                $attributeValuesClass = $this->attributeValuesClass;
                $db = $attributeValuesClass::getDb();
                $transaction = $db->getTransaction() === null ? $db->beginTransaction() : null;

                $canDeleteFull = (!$canDeleteFull ? $canDeleteFull : $this->beforeDeleteFull());
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
            $this->reset();
            $this->afterDeleteFull();
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_DELETE_FULL);
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

        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->rollback();
        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_DELETE_FULL_FAILED);
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
     * Delete the current objects attributes
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

            if ($this->entityId === false) {
                throw new Exception('No entity id available for ' . __METHOD__ . '()');
            }

            if (!$this->objectId) {
                throw new Exception('No object id available for ' . __METHOD__ . '()');
            }

            $attributeValuesClass = $this->attributeValuesClass;

            try {
                $ok = $attributeValuesClass::deleteAll(array(
                    'entityId' => $this->entityId,
                    'objectId' => $this->objectId
                ));
                if (!$ok) {
                    // no exception thrown and data may no longer exist in the table
                    // for this entity, so we are happy to return a good delete
                    $ok = true;
                }
            } catch (\Exception $e) {
                $ok = false;
                $this->addActionError($e->getMessage(), $e->getCode());
            }

            if ($ok) {
                if (!$fromDeleteFull) {
                    $this->reset();
                }
            }

        } elseif (!$hasParentModel) {
            $message = 'Attempting to delete ' . \Concord\Tools::getClassName($this) . ($this->getReadOnly() ? ' readOnly model' : ' model flagged as not deletable');
            //$this->addActionError($message);
            throw new \Concord\Db\Exception($message);
        } else {
            $this->addActionWarning('Skipped delete of ' . \Concord\Tools::getClassName($this) . ' which is ' . ($this->getReadOnly() ? 'read only' : 'flagged as not deletable'));
        }

        return $ok;
    }

    public function validate($attributes = null, $clearErrors = true) {
        if ($clearErrors) {
            $this->clearErrors();
        }
        if (false) {
            $this->addError('fieldx', 'Forced error on fieldx as proof of concept on activeattributerecord');
            $this->addError('fieldx', 'Another error value');
        }
        return !$this->hasErrors();
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
            //$event = new ModelEvent;
            //$this->trigger(self::EVENT_BEFORE_SAVE_ALL, $event);
            //$canSaveAll = $event->isValid;
        }

        if ($this->getReadOnly()) {
            // will be ignored during saveAll() and should have been caught by saveAll() if called directly
        } else {

            /**
             * All saveAll() calls are treated as transactional and a transaction
             * will be started if one has not already been on the db connection
             */
            $attributeValuesClass = $this->attributeValuesClass;
            $db = $attributeValuesClass::getDb();
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

                foreach ($this->attributeValues as $attributeName => $attributeValue) {
                    $this->setChildHasChanges($attributeName);
                    $this->setChildOldValues($attributeName, $attributeValue->getResetDataForFailedSave());
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
    public function saveAll($runValidation = true, $hasParentModel = false, $push = false)
    {

        $this->clearActionErrors();

        if ($this->getReadOnly() && !$hasParentModel) {

            // return failure if we are at the top of the tree and should not be asking to saveAll
            // not allowed to amend or delete
            $message = 'Attempting to saveAll on ' . \Concord\Tools::getClassName($this) . ' readOnly model';
            //$this->addActionError($message);
            throw new \Concord\Db\Exception($message);

        } elseif ($this->getReadOnly() && $hasParentModel) {

            $message = 'Skipping saveAll on ' . \Concord\Tools::getClassName($this) . ' readOnly model';
            $this->addActionWarning($message);
            return true;

        } elseif (!$this->getReadOnly()) {

            if ($this->hasChanges()) {

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

                try {
                    $ok = $this->save($runValidation, $hasParentModel, $push, true);
                } catch (\Exception $e) {
                    $ok = false;
                    $this->addActionError($e->getMessage(), $e->getCode());
                    //throw $e;
                }

                if (!$hasParentModel) {
                    if ($ok) {
                        $this->afterSaveAllInternal();
                    } else {
                        $this->afterSaveAllFailedInternal();
                    }
                }

                return $ok;
            }

            return true;

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
     *        did saveAll() successfully process
     */
    public function push($runValidation = true)
    {
        return $this->saveAll($runValidation, false, true);
    }


    /**
     * Save the current objects attributes
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
    public function save($runValidation = true, $hasParentModel = false, $push = false, $fromSaveAll = false)
    {

        if ($this->getReadOnly() && !$hasParentModel) {

            // return failure if we are at the top of the tree and should not be asking to saveAll
            // not allowed to amend or delete
            $message = 'Attempting to save on ' . \Concord\Tools::getClassName($this) . ' readOnly model';
            //$this->addActionError($message);
            throw new \Concord\Db\Exception($message);

        } elseif ($this->getReadOnly() && $hasParentModel) {

            $message = 'Skipping save on ' . \Concord\Tools::getClassName($this) . ' readOnly model';
            $this->addActionWarning($message);
            return true;

        }

        $allOk = true;

        if (($this->loaded || ($this->isNewRecord && $this->isNewPrepared)) && $this->changedData) {

            if ($this->entityId === false) {
                throw new Exception('No entity id available for ' . __METHOD__ . '()');
            }

            if (!$this->objectId) {
                throw new Exception('No object id available for ' . __METHOD__ . '()');
            }

            $thisTime = time();

            $attributeDefs = $this->getEntityAttributeList();

            // we do not record modified, modifiedBy, created or createdBy against individual attributes but we will support
            // automatically updating them if these attributeNames have been setup as their own attributes for this entity

            if (Yii::$app->hasComponent('user')) {
                try {
                    if (Yii::$app->user->isGuest) {
                        $userId = 0;
                    } else {
                        $userId = Yii::$app->user->getId();
                    }
                } catch (\yii\base\InvalidConfigException $e) {
                    if ($e->getMessage() == 'User::identityClass must be set.') {
                        $userId = 0;
                    } else {
                        throw $e;
                    }
                }
            }

            $extraChangeFields = array();
            if (array_key_exists('modifiedAt', $attributeDefs)) {
                if (!array_key_exists('modifiedAt', $this->changedData)) {
                    $exists = array_key_exists('modifiedAt', $this->data);
                    $this->changedData['modifiedAt'] = (array_key_exists('modifiedAt', $this->data) ? $this->data['modifiedAt'] : Tools::DATE_TIME_DB_EMPTY);
                    $this->data['modifiedAt'] = date(Tools::DATETIME_DATABASE, $thisTime);
                    if ($this->lazyAttributes && array_key_exists('modifiedAt', $this->lazyAttributes)) {
                        unset($this->lazyAttributes['modifiedAt']);
                    }
                }
            }

            if (array_key_exists('modifiedBy', $attributeDefs)) {
                if (!array_key_exists('modifiedBy', $this->changedData)) {
                    if (!isset($this->data['modifiedBy']) || $this->data['modifiedBy'] != $userId) {
                        $this->changedData['modifiedBy'] = (array_key_exists('modifiedBy', $this->data) ? $this->data['modifiedBy'] : 0);
                        $this->data['modifiedBy'] = $userId;
                        if ($this->lazyAttributes && array_key_exists('modifiedBy', $this->lazyAttributes)) {
                            unset($this->lazyAttributes['modifiedBy']);
                        }
                    }
                }
            }

            if (array_key_exists('createdAt', $attributeDefs)) {
                if (!array_key_exists('createdAt', $this->changedData)) {
                    $exists = array_key_exists('createdAt', $this->data);
                    if (!$exists || ($exists && $this->data['createdAt'] == Tools::DATE_TIME_DB_EMPTY)) {
                        $this->changedData['createdAt'] = (array_key_exists('createdAt', $this->data) ? $this->data['createdAt'] : Tools::DATE_TIME_DB_EMPTY);
                        $this->data['createdAt'] = date(Tools::DATETIME_DATABASE, $thisTime);
                        if ($this->lazyAttributes && array_key_exists('created', $this->lazyAttributes)) {
                            unset($this->lazyAttributes['createdAt']);
                        }
                    }
                }
            }

            if (array_key_exists('createdBy', $attributeDefs)) {
                if (!array_key_exists('createdBy', $this->changedData)) {
                    $exists = array_key_exists('createdBy', $this->data);
                    if (!$exists || ($exists && $this->data['createdBy'] != $userId)) {
                        $this->changedData['createdBy'] = (array_key_exists('createdBy', $this->data) ? $this->data['createdBy'] : 0);
                        $this->data['createdBy'] = $userId;
                        if ($this->lazyAttributes && array_key_exists('createdBy', $this->lazyAttributes)) {
                            unset($this->lazyAttributes['createdBy']);
                        }
                    }
                }
            }

            if (!$this->changedData) {
                $updateColumns = $this->data;
            } else {
                $updateColumns = array();
                foreach ($this->changedData as $field => $value) {
                    $updateColumns[$field] = $this->data[$field];
                }
            }

            foreach ($updateColumns as $attributeName => $attributeValue) {

                $attributeId = 0;
                $attributeDef = (isset($attributeDefs[$attributeName]) ? $attributeDefs[$attributeName] : false);
                if ($attributeDef) {
                    $attributeId = $attributeDef['id'];
                }

                $ok = false;

                if ($attributeId) {

                    $attributeValue = \Concord\Tools::formatAttributeValue($attributeValue, $attributeDef);

                    if ($attributeDef['deleteOnDefault'] && $attributeValue === \Concord\Tools::formatAttributeValue($attributeDef['defaultValue'], $attributeDef)) {

                        // value is default so we will remove it from the attribtue table as not required
                        $ok = true;
                        if (array_key_exists($attributeName, $this->attributeValues)) {
                            $ok = $this->attributeValues[$attributeName]->deleteFull(true);
                            if ($ok) {
                                $this->setChildOldValues($attributeName, true, 'deleted');
                            } else {
                                if ($this->attributeValues[$attributeName]->hasActionErrors()) {
                                    $this->mergeActionErrors($this->attributeValues[$attributeName]->getActionErrors());
                                } else {
                                    $this->addActionError('Failed to delete attribute', 0, $attributeName);
                                }
                            }
                        }

                    } else {

                        switch (strtolower($attributeDef['dataType'])) {
                            case 'boolean':
                                $attributeValue = ($attributeValue ? '1' : '0');
                                break;
                            default:
                                break;
                        }

                        if (is_null($attributeValue)) {
                            // typically where null is permitted it will be the default value with deleteOnDefault set, so should have been caught in the deleteOnDefault
                            if ($attributeDef['isNullable']) {
                                $attributeValue = '__NULL__'; // we do not want to allow null in the attribute database so use this string to denote null when it is permitted
                            } else {
                                $attributeValue = '__NULL__'; // needs to be caught elsewhere
                            }
                        }

                        if (!array_key_exists($attributeName, $this->attributeValues)) {

                            $this->attributeValues[$attributeName] = new $this->attributeValuesClass();
                            $this->attributeValues[$attributeName]->entityId = $this->entityId;
                            $this->attributeValues[$attributeName]->attributeId = $attributeId;

                            // this is a new entry that has not been included in the childHasChanges array yet
                            $this->setChildHasChanges($attributeName);
                            $this->setChildOldValues($attributeName, $this->attributeValues[$attributeName]->getResetDataForFailedSave());
                        }

                        if ($this->newObjectId) {
                            $this->attributeValues[$attributeName]->objectId = $this->newObjectId;
                        } else {
                            $this->attributeValues[$attributeName]->objectId = $this->objectId;
                        }
                        $this->attributeValues[$attributeName]->value = $attributeValue;
                        $ok = $this->attributeValues[$attributeName]->save(false, null, true, true);
                        if (!$ok) {
                            if ($this->attributeValues[$attributeName]->hasActionErrors()) {
                                $this->mergeActionErrors($this->attributeValues[$attributeName]->getActionErrors());
                            } else {
                                $this->addActionError('Failed to save attribute', 0, $attributeName);
                            }
                        }

                    }
                }

                if (!$ok) {
                    $allOk = false;
                }
            }

            if ($allOk) {
                $this->changedData = array();
                $this->loaded = true;
                $this->isNewRecord = false;
            }

        }

        if ($allOk && $this->loaded && $this->newObjectId) {
            // we need to update the objectId for all attributes belonging to
            // the current object to a new value taking into account that not
            // all attributes might have been loaded yet, if any.
            foreach ($this->attributeValues as $attributeName => $attributeValue) {
                $this->attributeValues[$attributeName]->objectId = $this->newObjectId;
                $this->attributeValues[$attributeName]->setOldAttribute('objectId', $this->newObjectId);
            }
            $attributeValuesClass = $this->attributeValuesClass;
            $ok = $attributeValuesClass::updateAll(array('objectId' => $this->newObjectId), array('objectId' => $this->objectId));
            $this->objectId = $this->newObjectId;
            $this->newObjectId = false;
        }

        return $allOk;

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

        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->commit();
        }

        if ($this->getReadOnly()) {
            // will have been ignored during saveAll()
        } else {

            // remove any deleted attributeValues
            foreach ($this->attributeValues as $attributeName => $attributeValue) {
                if ($this->getChildHasChanges($attributeName)) {
                    if ($this->getChildOldValues($attributeName, 'deleted')) {
                        unset($this->attributeValues[$attributeName]);
                    }
                }
            }

            $this->afterSaveAll();

        }

        $this->resetChildHasChanges();

        if (!$hasParentModel) {
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL);
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

        $transaction = $this->getChildOldValues('_transaction_');
        if ($transaction) {
            $transaction->rollback();
        }

        if ($this->getReadOnly()) {
            // will have been ignored during saveAll()
        } else {

            foreach ($this->attributeValues as $attributeName => $attributeValue) {
                if ($this->getChildHasChanges($attributeName)) {
                    $attributeValue->resetOnFailedSave($this->getChildOldValues($attributeName));
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
            //$this->trigger(self::EVENT_AFTER_SAVE_ALL_FAILED);
        }
    }


    /**
     * Called by afterSaveAllFailedInternal on the current model once saveAll() has failed
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

        if ($data['new']) {
            $this->loaded = false;
            $this->attributeValues = array();
        }

    }


    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->errors) : isset($this->errors[$attribute]);
    }


    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->errors === null ? array() : $this->errors;
        } else {
            return isset($this->errors[$attribute]) ? $this->errors[$attribute] : array();
        }
    }


    public function getFirstErrors()
    {
        if (empty($this->errors)) {
            return array();
        } else {
            $errors = array();
            foreach ($this->errors as $attributeErrors) {
                if (isset($attributeErrors[0])) {
                    $errors[] = $attributeErrors[0];
                }
            }
        }
        return $errors;
    }


    public function getFirstError($attribute)
    {
        return isset($this->errors[$attribute]) ? reset($this->errors[$attribute]) : null;
    }


    /**
     * Adds a new error for the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error = '')
    {
        $this->errors[$attribute][] = $error;
    }


    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. null will remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->errors = array();
        } else {
            unset($this->errors[$attribute]);
        }
    }

}
