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

namespace fangface\concord\behaviors;

use Yii;
use fangface\concord\db\ActiveRecord;
use yii\base\Behavior;
use yii\db\Expression;

/**
 * AutoSavedBy will automatically fill the relevant attributes during creation and update
 *
 * ~~~
 * public function behaviors()
 * {
 *     return [
 *         'savedby' => ['class' => 'fangface\concord\behaviors\AutoSavedBy'],
 *     ];
 * }
 * ~~~
 *
 * By default, AutoSavedBy will fill the `createdBy` attribute with the current user id
 * when the associated AR object is being inserted; it will fill the `modifiedBy` attribute
 * with the user ud when the AR object is being updated.
 *
 */
class AutoSavedBy extends Behavior
{
	/**
	 * @var array list of attributes that are to be automatically filled with the current user id.
	 * The array keys are the ActiveRecord events upon which the attributes are to be filled with the user id,
	 * and the array values are the corresponding attribute to be updated. You can use a string to represent
	 * a single attribute, or an array to represent a list of attributes.
	 * The default setting is to update the `createdBy` attribute upon AR insertion,
	 * and update the `modifiedBy` attribute upon AR updating.
	 */
	public $attributes = [
		ActiveRecord::EVENT_BEFORE_INSERT => ['createdBy', 'modifiedBy'],
		ActiveRecord::EVENT_BEFORE_UPDATE => 'modifiedBy',
	];

	/**
	 * @var \Closure|Expression The expression that will be used for generating the current user id.
	 * This can be either an anonymous function that returns the user id,
	 * or an [[Expression]] object representing a DB expression (e.g. `new Expression('1')`).
	 * If not set, it will use the value of Yii::$app->user->getId() to fill the attributes.
	 */
	public $userId;


	/**
	 * Declares event handlers for the [[owner]]'s events.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events()
	{
		$events = $this->attributes;
		foreach ($events as $i => $event) {
		    $events[$i] = 'updateSavedBy';
		}
		return $events;
	}

	/**
	 * Updates the attributes with the current user id.
	 * @param \yii\base\Event $event
	 */
	public function updateSavedBy($event)
	{
	    $attributes = isset($this->attributes[$event->name]) ? (array)$this->attributes[$event->name] : [];
		if (!empty($attributes)) {
			$savedBy = $this->evaluateSavedBy();
			foreach ($attributes as $attribute) {
			    if (isset($this->owner->$attribute)) {
			        $this->owner->$attribute = $savedBy;
			    } elseif ($attribute == 'createdBy' && isset($this->owner->created_by)) {
			        $this->owner->created_by = $savedBy;
			    } elseif ($attribute == 'modifiedBy' && isset($this->owner->modified_by)) {
			        $this->owner->modified_by = $savedBy;
			    }
			}
		}
	}

	/**
	 * Gets the current datetime
	 * @return mixed the datetime value
	 */
	protected function evaluateSavedBy()
	{
        if ($this->userId instanceof Expression) {
            return $this->userId;
        } elseif ($this->userId !== null) {
            return call_user_func($this->userId);
        } else {
            if (Yii::$app->has('user')) {
                try {
                    if (Yii::$app->user->isGuest) {
                        return 0;
                    } else {
                        return Yii::$app->user->getId();
                    }
                } catch (\yii\base\InvalidConfigException $e) {
                    if ($e->getMessage() == 'User::identityClass must be set.') {
                        return 0;
                    }
                    throw $e;
                }
            }
        }
        return 0;
	}
}
