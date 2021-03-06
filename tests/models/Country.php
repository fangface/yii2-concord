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

/**
 * Active Record class for the main db.{prefix}countries table
 *
 * @property integer $id primary key
 * @property string $countryCode 3 character ISO code for the country
 * @property string $shortName Short name of the country e.g. UK
 * @property string $longName Long name of the country e.g. United Kingdom
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 *
 * @method Country findOne($condition = null) static
 * @method Country[] findAll($condition = null) static
 * @method Country[] findByCondition($condition, $one) static
 */
class Country extends ActiveRecord
{

    //protected static $dbResourceName    = 'db'; // (default)

}
