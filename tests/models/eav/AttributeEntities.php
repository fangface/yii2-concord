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

use fangface\models\eav\AttributeEntities as BaseAttributeEntities;

/**
 * Active Record class for the clients dbClient.{prefix}attributeEntities table
 *
 * @method AttributeEntities findOne($condition = null) static
 * @method AttributeEntities[] findAll($condition = null) static
 * @method AttributeEntities[] findByCondition($condition, $one) static
 */
class AttributeEntities extends BaseAttributeEntities
{

    protected static $dbResourceName     = 'dbClient';

}
