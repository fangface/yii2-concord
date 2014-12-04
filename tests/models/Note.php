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

namespace fangface\tests\models;

use fangface\db\ActiveRecord;
use fangface\tests\models\CustomerAttributes;
use fangface\tests\models\Address;
use fangface\tests\models\Phone;
use fangface\tests\models\Order;

/**
 * Active Record class for the clients dbClient.{prefix}notes table
 *
 * @property integer $id primary key
 * @property integer $anotherId A simple integer
 * @property string $anotherString A simple string
 * @property decimal $anotherDec A decimal value
 * @property string $created_at Date and time record was created
 * @property integer $created_by	User id of user that created record
 * @property string $modified_at Date and time record was last modified
 * @property integer $modified_by User id of last user to modify record
 *
 * @method Note findOne($condition = null) static
 * @method Note[] findAll($condition = null) static
 * @method Note[] findByCondition($condition, $one) static
 */
class Note extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

}
