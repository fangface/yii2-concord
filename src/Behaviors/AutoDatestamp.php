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

namespace Concord\Behaviors;

use Concord\Db\ActiveRecord;
use Concord\Tools;
use yii\base\Behavior;
use yii\db\Expression;

/**
 * AutoDatestamp will automatically fill the relevant attributes during creation and update
 *
 * ~~~
 * public function behaviors()
 * {
 *     return [
 *         'datestamp' => ['class' => 'Concord\Behaviors\AutoDatestamp'],
 *     ];
 * }
 * ~~~
 *
 * By default, AutoDatestamp will fill the `createdAt` attribute with the current datetime
 * when the associated AR object is being inserted; it will fill the `modifiedAt` attribute
 * with the datetime when the AR object is being updated.
 *
 */
class AutoDatestamp extends Behavior
{
	/**
	 * @var array list of attributes that are to be automatically filled with datestamps.
	 * The array keys are the ActiveRecord events upon which the attributes are to be filled with datestamps,
	 * and the array values are the corresponding attribute to be updated. You can use a string to represent
	 * a single attribute, or an array to represent a list of attributes.
	 * The default setting is to update the `createdAt` attribute upon AR insertion,
	 * and update the `modifiedAt` attribute upon AR updating.
	 */
	public $attributes = [
		ActiveRecord::EVENT_BEFORE_INSERT => ['createdAt', 'modifiedAt'],
		ActiveRecord::EVENT_BEFORE_UPDATE => 'modifiedAt',
	];

	/**
	 * @var \Closure|Expression The expression that will be used for generating the datestamp.
	 * This can be either an anonymous function that returns the datestamp value,
	 * or an [[Expression]] object representing a DB expression (e.g. `new Expression('NOW()')`).
	 * If not set, it will use the value of `date('Y-m-d H:i:s)` to fill the attributes.
	 */
	public $datestamp;


	/**
	 * Declares event handlers for the [[owner]]'s events.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events()
	{
		$events = $this->attributes;
		foreach ($events as $i => $event) {
		    $events[$i] = 'updateDatestamp';
		}
		return $events;
	}

	/**
	 * Updates the attributes with the current datetime.
	 * @param \yii\base\Event $event
	 */
	public function updateDatestamp($event)
	{
	    $attributes = isset($this->attributes[$event->name]) ? (array)$this->attributes[$event->name] : [];
		if (!empty($attributes)) {
			$datestamp = $this->evaluateDatestamp();
			foreach ($attributes as $attribute) {
				$this->owner->$attribute = $datestamp;
			}
		}
	}

	/**
	 * Gets the current datetime
	 * @return mixed the datetime value
	 */
	protected function evaluateDatestamp()
	{
		if ($this->datestamp instanceof Expression) {
			return $this->datestamp;
		} elseif ($this->datestamp !== null) {
			return call_user_func($this->datestamp);
		} else {
			return date(Tools::DATETIME_DATABASE);
		}
	}
}
