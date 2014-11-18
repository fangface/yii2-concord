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
use fangface\concord\tests\models\Item;
use fangface\concord\tests\models\Customer;

/**
 * Active Record class for the clients dbClient.{prefix}orders table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the order belongs to
 * @property string $field1
 * @property string $field2
 * @property string $field3
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 * @property \fangface\concord\tests\models\Item[] $items
 * @property \fangface\concord\tests\models\Customer $customer
 *
 * @method Order findOne($condition = null) static
 * @method Order[] findAll($condition = null) static
 * @method Order[] findByCondition($condition, $one) static
 */
class Order extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    public function modelRelationMap()
    {
        return [

            'items' => array(
                'type' => 'hasMany',
                'class' => Item::className(),
                'link' => array(
                    'orderId' => 'id' // child->var => parent->var (remote => local)
                ),
                'onSaveAll' => ActiveRecord::SAVE_CASCADE,
                'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
                'autoLinkType' => ActiveRecord::LINK_FROM_PARENT_MAINT,
                'allToArray' => true,
                'autoLink' => array(
                    'fromParent' => array(
                        'orderId' => 'id', // child->var => parent->var (remote => local)
                        'customerId' => 'customerId', // child->var => parent->var (remote => local)
                    ),
                ),
            ),

            'customer' => array(
                'type' => 'belongsTo',
                'class' => Customer::className(),
                'link' => array(
                    'id' => 'customerId'
                ),
            ),

        ];
    }

}
