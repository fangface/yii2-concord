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

namespace fangface\tests\models\eav;

use fangface\models\eav\AttributeValues as BaseAttributeValues;

/**
 * Active Record class for the clients dbClient.{prefix}attributeValues table
 *
 * @method AttributeValues findOne($condition = null) static
 * @method AttributeValues[] findAll($condition = null) static
 * @method AttributeValues[] findByCondition($condition, $one) static
 */
class AttributeValues extends BaseAttributeValues
{

    protected static $dbResourceName     = 'dbClient';

}
