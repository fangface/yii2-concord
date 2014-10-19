<?php
/**
 * @link https://github.com/creocoder/yii2-nested-set-behavior
 * @copyright Copyright (c) 2013 Alexander Kochetov
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace fangface\concord\behaviors;

use fangface\concord\Tools;
use fangface\concord\db\ActiveRecord;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\Expression;

/**
 * @author Alexander Kochetov <creocoder@gmail.com>
 */
class NestedSet extends Behavior
{
	/**
	 * @var ActiveRecord the owner of this behavior.
	 */
	public $owner;
	/**
	 * @var boolean
	 */
	public $hasManyRoots = false;
	/**
	 * @var string
	 */
	public $rootAttribute = 'root';
	/**
	 * @var string
	 */
	public $leftAttribute = 'lft';
	/**
	 * @var string
	 */
	public $rightAttribute = 'rgt';
	/**
	 * @var string
	 */
	public $levelAttribute = 'level';
	/**
	 * @var boolean
	 */
	private $_ignoreEvent = false;
	/**
	 * @var boolean
	 */
	private $_deleted = false;
	/**
	 * @var integer
	 */
	private $_id;
	/**
	 * @var array
	 */
	private static $_cached;
	/**
	 * @var integer
	 */
	private static $_c = 0;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attach($owner)
	{
		parent::attach($owner);
		self::$_cached[get_class($this->owner)][$this->_id = self::$_c++] = $this->owner;
	}

	/**
	 * Gets descendants for node
	 * @param integer $depth the depth
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @param integer $limit [optional] limit results (typically used when only after limited number of immediate children)
	 * @return ActiveQuery
	 */
	public function descendants($depth = null, $object = null, $limit = 0)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    $query = $object->find()->orderBy([$this->levelAttribute => SORT_ASC, $this->leftAttribute => SORT_ASC]);
		$db = $object->getDb();
		$query->andWhere($db->quoteColumnName($this->leftAttribute) . '>'
			. $object->getAttribute($this->leftAttribute));
		$query->andWhere($db->quoteColumnName($this->rightAttribute) . '<'
			. $object->getAttribute($this->rightAttribute));
		$query->addOrderBy($db->quoteColumnName($this->leftAttribute));

		if ($depth !== null) {
			$query->andWhere($db->quoteColumnName($this->levelAttribute) . '<='
				. ($object->getAttribute($this->levelAttribute) + $depth));
		}

		if ($this->hasManyRoots) {
			$query->andWhere(
				$db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
				[':' . $this->rootAttribute => $object->getAttribute($this->rootAttribute)]
			);
		}

		if ($limit) {
		    $query->limit($limit);
		}

		return $query;
	}

	/**
	 * Gets children for node (direct descendants only)
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @param integer $limit [optional] limit results (typically used when only after limited number of immediate children)
	 * @return ActiveQuery
	 */
	public function children($object = null, $limit = 0)
	{
		return $this->descendants(1, $object, $limit);
	}

	/**
	 * Gets one child for node (first direct descendant only).
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return ActiveQuery
	 */
	public function oneChild($object = null)
	{
	    return $this->children($object, 1);
	}

	/**
	 * Gets ancestors for node
	 * @param integer $depth the depth
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @param boolean $reverse Should the result be in reverse order i.e. root first
	 * @param boolean $idOnly Should an array of IDs be returned only
	 * @return ActiveQuery
	 */
	public function ancestors($depth = null, $object = null, $reverse = false, $idOnly = false)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    $query = $object->find();

	    if ($idOnly) {
	        $query->select($object->primaryKey());
	    }

        if ($reverse) {
            $query->orderBy([$this->levelAttribute => SORT_ASC, $this->leftAttribute => SORT_ASC]);;
        } else {
            $query->orderBy([$this->levelAttribute => SORT_DESC, $this->leftAttribute => SORT_ASC]);;
        }

        $db = $object->getDb();

		$query->andWhere($db->quoteColumnName($this->leftAttribute) . '<'
			. $object->getAttribute($this->leftAttribute));
		$query->andWhere($db->quoteColumnName($this->rightAttribute) . '>'
			. $object->getAttribute($this->rightAttribute));
		$query->addOrderBy($db->quoteColumnName($this->leftAttribute));

		if ($depth !== null) {
			$query->andWhere($db->quoteColumnName($this->levelAttribute) . '>='
				. ($object->getAttribute($this->levelAttribute) - $depth));
		}

		if ($this->hasManyRoots) {
			$query->andWhere(
				$db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
				[':' . $this->rootAttribute => $object->getAttribute($this->rootAttribute)]
			);
		}

        return $query;
	}

	/**
	 * Gets parent of node
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @param boolean $idOnly Should only the id be returned
	 * @return ActiveQuery
	 */
	public function parentOnly($object = null, $idOnly = false)
	{
		return $this->ancestors(1, $object, false, $idOnly);
	}

	/**
	 * Gets previous sibling of node
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return ActiveQuery
	 */
	public function prev($object = null)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    $query = $object->find();
		$db = $object->getDb();
		$query->andWhere($db->quoteColumnName($this->rightAttribute) . '='
			. ($object->getAttribute($this->leftAttribute) - 1));

		if ($this->hasManyRoots) {
			$query->andWhere(
				$db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
				[':' . $this->rootAttribute => $object->getAttribute($this->rootAttribute)]
			);
		}

		return $query;
	}

	/**
	 * Gets next sibling of node
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return ActiveQuery
	 */
	public function next($object = null)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    $query = $object->find();
		$db = $object->getDb();
		$query->andWhere($db->quoteColumnName($this->leftAttribute) . '='
			. ($object->getAttribute($this->rightAttribute) + 1));

		if ($this->hasManyRoots) {
			$query->andWhere(
				$db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
				[':' . $this->rootAttribute => $object->getAttribute($this->rootAttribute)]
			);
		}

		return $query;
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new
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

	    if ($this->owner->getReadOnly() && !$hasParentModel) {

	        // return failure if we are at the top of the tree and should not be asking to saveAll
	        // not allowed to amend or delete
	        $message = 'Attempting to save on ' . Tools::getClassName($this->owner) . ' readOnly model';
	        //$this->addActionError($message);
	        throw new \fangface\concord\db\Exception($message);

	    } elseif ($this->owner->getReadOnly() && $hasParentModel) {

	        $message = 'Skipping save on ' . Tools::getClassName($this->owner) . ' readOnly model';
	        $this->addActionWarning($message);
	        return true;

	    } else {

	        if ($runValidation && !$this->owner->validate($attributes)) {
                return false;
            }

    		if ($this->owner->getIsNewRecord()) {
    			return $this->makeRoot($attributes);
    		}

    		$this->_ignoreEvent = true;
    		$result = $this->owner->update(false, $attributes);
    		//$result = $this->owner->save(false, $attributes, $hasParentModel, $fromSaveAll);
    		$this->_ignoreEvent = false;

	    }

		return $result;
	}

	/**
	 * Create root node if multiple-root tree mode. Update node if it's not new
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the saving succeeds
	 */
	public function saveNode($runValidation = true, $attributes = null)
	{
		return $this->save($runValidation, $attributes);
	}

	/**
	 * Deletes node and it's descendants
	 * @throws Exception.
	 * @throws \Exception.
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $fromDeleteFull
     *        has the delete() call come from deleteFull() or not
     * @param boolean $deleteFull
     *        should deleteFull be performed if available
     * @return boolean
     *        did delete() successfully process
	 */
	public function delete($hasParentModel = false, $fromDeleteFull = false, $deleteFull = false)
	{
		if ($this->owner->getIsNewRecord()) {
			throw new Exception('The node can\'t be deleted because it is new.');
		}

		if ($this->getIsDeletedRecord()) {
			throw new Exception('The node can\'t be deleted because it is already deleted.');
		}

		$db = $this->owner->getDb();

		if ($db->getTransaction() === null) {
			$transaction = $db->beginTransaction();
		}

		try {

		    $result = true;

		    if (!$this->owner->isLeaf()) {

		        $condition = $db->quoteColumnName($this->leftAttribute) . '>='
		            . $this->owner->getAttribute($this->leftAttribute) . ' AND '
		                . $db->quoteColumnName($this->rightAttribute) . '<='
		                    . $this->owner->getAttribute($this->rightAttribute);
		        $params = [];

		        if ($this->hasManyRoots) {
		            $condition .= ' AND ' . $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute;
		            $params[':' . $this->rootAttribute] = $this->owner->getAttribute($this->rootAttribute);
		        }

		        //wlhere - how to handle deleteAll - if not already supported
		        $result = $this->owner->deleteAll($condition, $params) > 0;
		    }

		    if ($result) {
		        $this->shiftLeftRight(
                    $this->owner->getAttribute($this->rightAttribute) + 1,
                    $this->owner->getAttribute($this->leftAttribute) - $this->owner->getAttribute($this->rightAttribute) - 1
                );
                $this->correctCachedOnDelete();

                if ($this->owner->isLeaf()) {
                	$this->_ignoreEvent = true;
                	if ($deleteFull && method_exists($this->owner, 'deleteFull')) {
                	    $result = $this->owner->deleteFull($hasParentModel);
                	} else {
                	    $result = $this->owner->delete($hasParentModel, $fromDeleteFull);
                	}
                	$this->_ignoreEvent = false;
                }

		    }

			if (!$result) {
				if (isset($transaction)) {
					$transaction->rollback();
				}

				return false;
			}

			if (isset($transaction)) {
			    $transaction->commit();
			}

		} catch (\Exception $e) {
			if (isset($transaction)) {
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Deletes node and it's descendants.
	 *
     * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @param boolean $fromDeleteFull
     *        has the delete() call come from deleteFull() or not
     * @return boolean
     *        did deleteNode() successfully process

	 */
	public function deleteNode($hasParentModel = false, $fromDeleteFull = false)
	{
		return $this->delete($hasParentModel, $fromDeleteFull);
	}

	/**
	 * Deletes node and it's descendants and relations.
	 *
	 * @param boolean $hasParentModel
     *        whether this method was called from the top level or by a parent
     *        If false, it means the method was called at the top level
     * @return boolean
     *        did deleteNodeFull() successfully process
	 */
	public function deleteNodeFull($hasParentModel = false)
	{
	    return $this->delete($hasParentModel, false, true);
	}

	/**
	 * Prepends node to target as first child
	 * @param ActiveRecord $target the target
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the prepending succeeds
	 */
	public function prependTo($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode(
			$target,
			$target->getAttribute($this->leftAttribute) + 1,
			1,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Prepends target to node as first child
	 * @param ActiveRecord $target the target
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the prepending succeeds
	 */
	public function prepend($target, $runValidation = true, $attributes = null)
	{
		return $target->prependTo(
			$this->owner,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Appends node to target as last child
	 * @param ActiveRecord $target the target
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the appending succeeds
	 */
	public function appendTo($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode(
			$target,
			$target->getAttribute($this->rightAttribute),
			1,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Appends target to node as last child
	 * @param ActiveRecord $target the target
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the appending succeeds
	 */
	public function append($target, $runValidation = true, $attributes = null)
	{
		return $target->appendTo(
			$this->owner,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Inserts node as previous sibling of target.
	 * @param ActiveRecord $target the target.
	 * @param boolean $runValidation whether to perform validation.
	 * @param array $attributes list of attributes.
	 * @return boolean whether the inserting succeeds.
	 */
	public function insertBefore($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode(
			$target,
			$target->getAttribute($this->leftAttribute),
			0,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Inserts node as next sibling of target
	 * @param ActiveRecord $target the target
	 * @param boolean $runValidation whether to perform validation
	 * @param array $attributes list of attributes
	 * @return boolean whether the inserting succeeds
	 */
	public function insertAfter($target, $runValidation = true, $attributes = null)
	{
		return $this->addNode(
			$target,
			$target->getAttribute($this->rightAttribute) + 1,
			0,
			$runValidation,
			$attributes
		);
	}

	/**
	 * Move node as previous sibling of target
	 * @param ActiveRecord $target the target
	 * @return boolean whether the moving succeeds
	 */
	public function moveBefore($target)
	{
		return $this->moveNode(
			$target,
			$target->getAttribute($this->leftAttribute),
			0
		);
	}

	/**
	 * Move node as next sibling of target
	 * @param ActiveRecord $target the target
	 * @return boolean whether the moving succeeds
	 */
	public function moveAfter($target)
	{
		return $this->moveNode(
			$target,
			$target->getAttribute($this->rightAttribute) + 1,
			0
		);
	}

	/**
	 * Move node as first child of target
	 * @param ActiveRecord $target the target
	 * @return boolean whether the moving succeeds
	 */
	public function moveAsFirst($target)
	{
		return $this->moveNode(
			$target,
			$target->getAttribute($this->leftAttribute) + 1,
			1
		);
	}

	/**
	 * Move node as last child of target
	 * @param ActiveRecord $target the target
	 * @return boolean whether the moving succeeds
	 */
	public function moveAsLast($target)
	{
		return $this->moveNode(
			$target,
			$target->getAttribute($this->rightAttribute),
			1
		);
	}

	/**
	 * Move node as new root
	 * @throws Exception
	 * @return boolean whether the moving succeeds
	 */
	public function moveAsRoot()
	{
		if (!$this->hasManyRoots) {
			throw new Exception('Many roots mode is off.');
		}

		if ($this->owner->getIsNewRecord()) {
			throw new Exception('The node should not be new record.');
		}

		if ($this->getIsDeletedRecord()) {
			throw new Exception('The node should not be deleted.');
		}

		if ($this->owner->isRoot()) {
			throw new Exception('The node already is root node.');
		}

		$db = $this->owner->getDb();

		if ($db->getTransaction() === null) {
			$transaction = $db->beginTransaction();
		}

		try {
			$left = $this->owner->getAttribute($this->leftAttribute);
			$right = $this->owner->getAttribute($this->rightAttribute);
			$levelDelta = 1 - $this->owner->getAttribute($this->levelAttribute);
			$delta = 1 - $left;
			$this->owner->updateAll(
				[
					$this->leftAttribute => new Expression($db->quoteColumnName($this->leftAttribute)
						. sprintf('%+d', $delta)),
					$this->rightAttribute => new Expression($db->quoteColumnName($this->rightAttribute)
						. sprintf('%+d', $delta)),
					$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute)
						. sprintf('%+d', $levelDelta)),
					$this->rootAttribute => $this->owner->getPrimaryKey(),
				],
				$db->quoteColumnName($this->leftAttribute) . '>=' . $left . ' AND '
					. $db->quoteColumnName($this->rightAttribute) . '<=' . $right . ' AND '
					. $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
				[':' . $this->rootAttribute => $this->owner->getAttribute($this->rootAttribute)]
			);
			$this->shiftLeftRight($right + 1, $left - $right - 1);

			if (isset($transaction)) {
				$transaction->commit();
			}

			$this->correctCachedOnMoveBetweenTrees(1, $levelDelta, $this->owner->getPrimaryKey());
		} catch (\Exception $e) {
			if (isset($transaction)) {
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Determines if node is descendant of subject node
	 * @param ActiveRecord $subj the subject node
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return boolean whether the node is descendant of subject node
	 */
	public function isDescendantOf($subj, $object = null)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
		$result = ($object->getAttribute($this->leftAttribute) > $subj->getAttribute($this->leftAttribute))
			&& ($object->getAttribute($this->rightAttribute) < $subj->getAttribute($this->rightAttribute));

		if ($this->hasManyRoots) {
			$result = $result && ($object->getAttribute($this->rootAttribute)
				=== $subj->getAttribute($this->rootAttribute));
		}

		return $result;
	}

	/**
	 * Determines if node is leaf
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return boolean whether the node is leaf
	 */
	public function isLeaf($object = null)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    return $object->getAttribute($this->rightAttribute)
			- $object->getAttribute($this->leftAttribute) === 1;
	}

	/**
	 * Determines if node is root
	 * @param ActiveRecord $object [optional] defaults to $this->owner
	 * @return boolean whether the node is root
	 */
	public function isRoot($object = null)
	{
	    $object = (!is_null($object) ? $object : $this->owner);
	    return $object->getAttribute($this->leftAttribute) == 1;
	}

	/**
	 * Returns if the current node is deleted
	 * @return boolean whether the node is deleted
	 */
	public function getIsDeletedRecord()
	{
		return $this->_deleted;
	}

	/**
	 * Sets if the current node is deleted
	 * @param boolean $value whether the node is deleted
	 */
	public function setIsDeletedRecord($value)
	{
		$this->_deleted = $value;
	}

	/**
	 * Handle 'afterFind' event of the owner
	 * @param Event $event event parameter
	 */
	public function afterFind($event)
	{
		self::$_cached[get_class($this->owner)][$this->_id = self::$_c++] = $this->owner;
	}

	/**
	 * Handle 'beforeInsert' event of the owner
	 * @param Event $event event parameter
	 * @throws Exception
	 * @return boolean
	 */
	public function beforeInsert($event)
	{
		if ($this->_ignoreEvent) {
			return true;
		} else {
			throw new Exception('You should not use ActiveRecord::save() or ActiveRecord::insert() methods when NestedSet behavior attached.');
		}
	}

	/**
	 * Handle 'beforeUpdate' event of the owner
	 * @param Event $event event parameter
	 * @throws Exception
	 * @return boolean
	 */
	public function beforeUpdate($event)
	{
		if ($this->_ignoreEvent) {
			return true;
		} else {
			throw new Exception('You should not use ActiveRecord::save() or ActiveRecord::update() methods when NestedSet behavior attached.');
		}
	}

	/**
	 * Handle 'beforeDelete' event of the owner
	 * @param Event $event event parameter
	 * @throws Exception
	 * @return boolean
	 */
	public function beforeDelete($event)
	{
		if ($this->_ignoreEvent) {
			return true;
		} else {
			throw new Exception('You should not use ActiveRecord::delete() method when NestedSet behavior attached.');
		}
	}

	/**
	 * @param integer $key.
	 * @param integer $delta.
	 */
	private function shiftLeftRight($key, $delta)
	{
		$db = $this->owner->getDb();

		foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
			$condition = $db->quoteColumnName($attribute) . '>=' . $key;
			$params = [];

			if ($this->hasManyRoots) {
				$condition .= ' AND ' . $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute;
				$params[':' . $this->rootAttribute] = $this->owner->getAttribute($this->rootAttribute);
			}

			$this->owner->updateAll(
				[$attribute => new Expression($db->quoteColumnName($attribute) . sprintf('%+d', $delta))],
				$condition,
				$params
			);
		}
	}

	/**
	 * @param ActiveRecord $target
	 * @param int $key
	 * @param int $levelUp
	 * @param boolean $runValidation
	 * @param array $attributes
	 * @throws Exception
	 * @return boolean
	 */
	private function addNode($target, $key, $levelUp, $runValidation, $attributes)
	{
		if (!$this->owner->getIsNewRecord()) {
			throw new Exception('The node can\'t be inserted because it is not new.');
		}

		if ($this->getIsDeletedRecord()) {
			throw new Exception('The node can\'t be inserted because it is deleted.');
		}

		if ($target->getIsDeletedRecord()) {
			throw new Exception('The node can\'t be inserted because target node is deleted.');
		}

		if ($this->owner->equals($target)) {
			throw new Exception('The target node should not be self.');
		}

		if (!$levelUp && $target->isRoot()) {
			throw new Exception('The target node should not be root.');
		}

		if ($runValidation && !$this->owner->validate()) {
			return false;
		}

		if ($this->hasManyRoots) {
			$this->owner->setAttribute($this->rootAttribute, $target->getAttribute($this->rootAttribute));
		}

		$db = $this->owner->getDb();

		if ($db->getTransaction() === null) {
			$transaction = $db->beginTransaction();
		}

		try {
			$this->shiftLeftRight($key, 2);
			$this->owner->setAttribute($this->leftAttribute, $key);
			$this->owner->setAttribute($this->rightAttribute, $key + 1);
			$this->owner->setAttribute($this->levelAttribute, $target->getAttribute($this->levelAttribute) + $levelUp);
			$this->_ignoreEvent = true;
			$result = $this->owner->insert(false, $attributes);
			$this->_ignoreEvent = false;

			if (!$result) {
				if (isset($transaction)) {
					$transaction->rollback();
				}

				return false;
			}

    	    $this->owner->setIsNewRecord(false);

			if (isset($transaction)) {
				$transaction->commit();
			}

			$this->correctCachedOnAddNode($key);
		} catch (\Exception $e) {
			if (isset($transaction)) {
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * @param array $attributes
	 * @throws Exception
	 * @return boolean
	 */
	private function makeRoot($attributes)
	{
		$this->owner->setAttribute($this->leftAttribute, 1);
		$this->owner->setAttribute($this->rightAttribute, 2);
		$this->owner->setAttribute($this->levelAttribute, 1);

		if ($this->hasManyRoots) {
			$db = $this->owner->getDb();

			if ($db->getTransaction() === null) {
				$transaction = $db->beginTransaction();
			}

			try {
				$this->_ignoreEvent = true;
				$result = $this->owner->insert(false, $attributes);
				$this->_ignoreEvent = false;

				if (!$result) {
					if (isset($transaction)) {
						$transaction->rollback();
					}

					return false;
				}

				$this->owner->setIsNewRecord(false);

				$this->owner->setAttribute($this->rootAttribute, $this->owner->getPrimaryKey());
				$primaryKey = $this->owner->primaryKey();

				if (!isset($primaryKey[0])) {
					throw new Exception(get_class($this->owner) . ' must have a primary key.');
				}

				$this->owner->updateAll(
					[$this->rootAttribute => $this->owner->getAttribute($this->rootAttribute)],
					[$primaryKey[0] => $this->owner->getAttribute($this->rootAttribute)]
				);

				if (isset($transaction)) {
					$transaction->commit();
				}
			} catch (\Exception $e) {
				if (isset($transaction)) {
					$transaction->rollback();
				}

				throw $e;
			}
		} else {
			if ($this->owner->find()->roots()->exists()) {
				throw new Exception('Can\'t create more than one root in single root mode.');
			}

			$this->_ignoreEvent = true;
			$result = $this->owner->insert(false, $attributes);
			$this->_ignoreEvent = false;

			if (!$result) {
				return false;
			}

			$this->owner->setIsNewRecord(false);
		}

		return true;
	}

	/**
	 * @param ActiveRecord $target
	 * @param int $key
	 * @param int $levelUp
	 * @throws Exception
	 * @return boolean
	 */
	private function moveNode($target, $key, $levelUp)
	{
		if ($this->owner->getIsNewRecord()) {
			throw new Exception('The node should not be new record.');
		}

		if ($this->getIsDeletedRecord()) {
			throw new Exception('The node should not be deleted.');
		}

		if ($target->getIsDeletedRecord()) {
			throw new Exception('The target node should not be deleted.');
		}

		if ($this->owner->equals($target)) {
			throw new Exception('The target node should not be self.');
		}

		if ($target->isDescendantOf($this->owner)) {
			throw new Exception('The target node should not be descendant.');
		}

		if (!$levelUp && $target->isRoot()) {
			throw new Exception('The target node should not be root.');
		}

		$db = $this->owner->getDb();

		if ($db->getTransaction() === null) {
			$transaction = $db->beginTransaction();
		}

		try {
			$left = $this->owner->getAttribute($this->leftAttribute);
			$right = $this->owner->getAttribute($this->rightAttribute);
			$levelDelta = $target->getAttribute($this->levelAttribute) - $this->owner->getAttribute($this->levelAttribute)
				+ $levelUp;

			if ($this->hasManyRoots && $this->owner->getAttribute($this->rootAttribute) !==
				$target->getAttribute($this->rootAttribute)) {

				foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
					$this->owner->updateAll(
						[$attribute => new Expression($db->quoteColumnName($attribute)
							. sprintf('%+d', $right - $left + 1))],
						$db->quoteColumnName($attribute) . '>=' . $key . ' AND '
							. $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
						[':' . $this->rootAttribute => $target->getAttribute($this->rootAttribute)]
					);
				}

				$delta = $key - $left;
				$this->owner->updateAll(
					[
						$this->leftAttribute => new Expression($db->quoteColumnName($this->leftAttribute)
							. sprintf('%+d', $delta)),
						$this->rightAttribute => new Expression($db->quoteColumnName($this->rightAttribute)
							. sprintf('%+d', $delta)),
						$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute)
							. sprintf('%+d', $levelDelta)),
						$this->rootAttribute => $target->getAttribute($this->rootAttribute),
					],
					$db->quoteColumnName($this->leftAttribute) . '>=' . $left . ' AND '
						. $db->quoteColumnName($this->rightAttribute) . '<=' . $right . ' AND '
						. $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute,
					[':' . $this->rootAttribute => $this->owner->getAttribute($this->rootAttribute)]
				);
				$this->shiftLeftRight($right + 1, $left - $right - 1);

				if (isset($transaction)) {
					$transaction->commit();
				}

				$this->correctCachedOnMoveBetweenTrees($key, $levelDelta, $target->getAttribute($this->rootAttribute));
			} else {
				$delta = $right - $left + 1;
				$this->shiftLeftRight($key, $delta);

				if ($left >= $key) {
					$left += $delta;
					$right += $delta;
				}

				$condition = $db->quoteColumnName($this->leftAttribute) . '>=' . $left . ' AND '
					. $db->quoteColumnName($this->rightAttribute) . '<=' . $right;
				$params = [];

				if ($this->hasManyRoots) {
					$condition .= ' AND ' . $db->quoteColumnName($this->rootAttribute) . '=:' . $this->rootAttribute;
					$params[':' . $this->rootAttribute] = $this->owner->getAttribute($this->rootAttribute);
				}

				$this->owner->updateAll(
					[
						$this->levelAttribute => new Expression($db->quoteColumnName($this->levelAttribute)
							. sprintf('%+d', $levelDelta)),
					],
					$condition,
					$params
				);

				foreach ([$this->leftAttribute, $this->rightAttribute] as $attribute) {
					$condition = $db->quoteColumnName($attribute) . '>=' . $left . ' AND '
						. $db->quoteColumnName($attribute) . '<=' . $right;
					$params = [];

					if ($this->hasManyRoots) {
						$condition .= ' AND ' . $db->quoteColumnName($this->rootAttribute) . '=:'
							. $this->rootAttribute;
						$params[':' . $this->rootAttribute] = $this->owner->getAttribute($this->rootAttribute);
					}

					$this->owner->updateAll(
						[$attribute => new Expression($db->quoteColumnName($attribute)
							. sprintf('%+d', $key - $left))],
						$condition,
						$params
					);
				}

				$this->shiftLeftRight($right + 1, -$delta);

				if (isset($transaction)) {
					$transaction->commit();
				}

				$this->correctCachedOnMoveNode($key, $levelDelta);
			}
		} catch (\Exception $e) {
			if (isset($transaction)) {
				$transaction->rollback();
			}

			throw $e;
		}

		return true;
	}

	/**
	 * Correct cache for [[delete()]] and [[deleteNode()]].
	 */
	private function correctCachedOnDelete()
	{
		$left = $this->owner->getAttribute($this->leftAttribute);
		$right = $this->owner->getAttribute($this->rightAttribute);
		$key = $right + 1;
		$delta = $left - $right - 1;

		foreach (self::$_cached[get_class($this->owner)] as $node) {
			/** @var $node ActiveRecord */
			if ($node->getIsNewRecord() || $node->getIsDeletedRecord()) {
				continue;
			}

			if ($this->hasManyRoots && $this->owner->getAttribute($this->rootAttribute)
				!== $node->getAttribute($this->rootAttribute)) {
				continue;
			}

			if ($node->getAttribute($this->leftAttribute) >= $left
				&& $node->getAttribute($this->rightAttribute) <= $right) {
				$node->setIsDeletedRecord(true);
			} else {
				if ($node->getAttribute($this->leftAttribute) >= $key) {
					$node->setAttribute(
						$this->leftAttribute,
						$node->getAttribute($this->leftAttribute) + $delta
					);
				}

				if ($node->getAttribute($this->rightAttribute) >= $key) {
					$node->setAttribute(
						$this->rightAttribute,
						$node->getAttribute($this->rightAttribute) + $delta
					);
				}
			}
		}
	}

	/**
	 * Correct cache for [[addNode()]]
	 * @param int $key
	 */
	private function correctCachedOnAddNode($key)
	{
		foreach (self::$_cached[get_class($this->owner)] as $node) {
			/** @var $node ActiveRecord */
			if ($node->getIsNewRecord() || $node->getIsDeletedRecord()) {
				continue;
			}

			if ($this->hasManyRoots && $this->owner->getAttribute($this->rootAttribute)
				!== $node->getAttribute($this->rootAttribute)) {
				continue;
			}

			if ($this->owner === $node) {
				continue;
			}

			if ($node->getAttribute($this->leftAttribute) >= $key) {
				$node->setAttribute(
					$this->leftAttribute,
					$node->getAttribute($this->leftAttribute) + 2
				);
			}

			if ($node->getAttribute($this->rightAttribute) >= $key) {
				$node->setAttribute(
					$this->rightAttribute,
					$node->getAttribute($this->rightAttribute) + 2
				);
			}
		}
	}

	/**
	 * Correct cache for [[moveNode()]]
	 * @param int $key
	 * @param int $levelDelta
	 */
	private function correctCachedOnMoveNode($key, $levelDelta)
	{
		$left = $this->owner->getAttribute($this->leftAttribute);
		$right = $this->owner->getAttribute($this->rightAttribute);
		$delta = $right - $left + 1;

		if ($left >= $key) {
			$left += $delta;
			$right += $delta;
		}

		$delta2 = $key - $left;

		foreach (self::$_cached[get_class($this->owner)] as $node) {
			/** @var $node ActiveRecord */
			if ($node->getIsNewRecord() || $node->getIsDeletedRecord()) {
				continue;
			}

			if ($this->hasManyRoots && $this->owner->getAttribute($this->rootAttribute)
				!== $node->getAttribute($this->rootAttribute)) {
				continue;
			}

			if ($node->getAttribute($this->leftAttribute) >= $key) {
				$node->setAttribute(
					$this->leftAttribute,
					$node->getAttribute($this->leftAttribute) + $delta
				);
			}

			if ($node->getAttribute($this->rightAttribute) >= $key) {
				$node->setAttribute(
					$this->rightAttribute,
					$node->getAttribute($this->rightAttribute) + $delta
				);
			}

			if ($node->getAttribute($this->leftAttribute) >= $left
				&& $node->getAttribute($this->rightAttribute) <= $right) {
				$node->setAttribute(
					$this->levelAttribute,
					$node->getAttribute($this->levelAttribute) + $levelDelta
				);
			}

			if ($node->getAttribute($this->leftAttribute) >= $left
				&& $node->getAttribute($this->leftAttribute) <= $right) {
				$node->setAttribute(
					$this->leftAttribute,
					$node->getAttribute($this->leftAttribute) + $delta2
				);
			}

			if ($node->getAttribute($this->rightAttribute) >= $left
				&& $node->getAttribute($this->rightAttribute) <= $right) {
				$node->setAttribute(
					$this->rightAttribute,
					$node->getAttribute($this->rightAttribute) + $delta2
				);
			}

			if ($node->getAttribute($this->leftAttribute) >= $right + 1) {
				$node->setAttribute(
					$this->leftAttribute,
					$node->getAttribute($this->leftAttribute) - $delta
				);
			}

			if ($node->getAttribute($this->rightAttribute) >= $right + 1) {
				$node->setAttribute(
					$this->rightAttribute,
					$node->getAttribute($this->rightAttribute) - $delta
				);
			}
		}
	}

	/**
	 * Correct cache for [[moveNode()]]
	 * @param int $key
	 * @param int $levelDelta
	 * @param int $root
	 */
	private function correctCachedOnMoveBetweenTrees($key, $levelDelta, $root)
	{
		$left = $this->owner->getAttribute($this->leftAttribute);
		$right = $this->owner->getAttribute($this->rightAttribute);
		$delta = $right - $left + 1;
		$delta2 = $key - $left;
		$delta3 = $left - $right - 1;

		foreach (self::$_cached[get_class($this->owner)] as $node) {
			/** @var $node ActiveRecord */
			if ($node->getIsNewRecord() || $node->getIsDeletedRecord()) {
				continue;
			}

			if ($node->getAttribute($this->rootAttribute) === $root) {
				if ($node->getAttribute($this->leftAttribute) >= $key) {
					$node->setAttribute(
						$this->leftAttribute,
						$node->getAttribute($this->leftAttribute) + $delta
					);
				}

				if ($node->getAttribute($this->rightAttribute) >= $key) {
					$node->setAttribute(
						$this->rightAttribute,
						$node->getAttribute($this->rightAttribute) + $delta
					);
				}
			} elseif ($node->getAttribute($this->rootAttribute)
				=== $this->owner->getAttribute($this->rootAttribute)) {
				if ($node->getAttribute($this->leftAttribute) >= $left
					&& $node->getAttribute($this->rightAttribute) <= $right) {
					$node->setAttribute(
						$this->leftAttribute,
						$node->getAttribute($this->leftAttribute) + $delta2
					);
					$node->setAttribute(
						$this->rightAttribute,
						$node->getAttribute($this->rightAttribute) + $delta2
					);
					$node->setAttribute(
						$this->levelAttribute,
						$node->getAttribute($this->levelAttribute) + $levelDelta
					);
					$node->setAttribute($this->rootAttribute, $root);
				} else {
					if ($node->getAttribute($this->leftAttribute) >= $right + 1) {
						$node->setAttribute(
							$this->leftAttribute,
							$node->getAttribute($this->leftAttribute) + $delta3
						);
					}

					if ($node->getAttribute($this->rightAttribute) >= $right + 1) {
						$node->setAttribute(
							$this->rightAttribute,
							$node->getAttribute($this->rightAttribute) + $delta3
						);
					}
				}
			}
		}
	}

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		unset(self::$_cached[get_class($this->owner)][$this->_id]);
	}
}
