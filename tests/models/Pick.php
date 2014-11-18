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

namespace fangface\concord\tests\models;

use fangface\concord\db\ActiveRecord;
use fangface\concord\tests\models\CustomerAttributes;
use fangface\concord\tests\models\Address;
use fangface\concord\tests\models\Phone;
use fangface\concord\tests\models\Order;

/**
 * Active Record class for the clients dbClient.{prefix}picks table
 *
 * @property integer $id primary key
 * @property integer $anotherId A simple integer
 * @property string $anotherText A longtext field
 * @property string $anotherString A simple string
 * @property decimal $anotherDec A decimal value
 * @property string $created_at Date and time record was created
 * @property integer $created_by	User id of user that created record
 * @property string $modified_at Date and time record was last modified
 * @property integer $modified_by User id of last user to modify record
 *
 * @method Pick findOne($condition = null) static
 * @method Pick[] findAll($condition = null) static
 * @method Pick[] findByCondition($condition, $one) static
 */
class Pick extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

}
