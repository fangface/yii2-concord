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

namespace Concord\Tests\Models;

use Concord\Db\ActiveRecord;

/**
 * Active Record class for the clients dbClient.{prefix}orders table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the order belongs to
 * @property string $field1
 * @property string $field2
 * @property string $field3
 * @property datetime $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property datetime $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 * @property \Concord\Tests\Models\Item[] $items
 * @property \Concord\Tests\Models\Customer $customer
 */
class Order extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    protected $modelRelationMap = array(

        'items' => array(
            'type' => 'hasMany',
            'class' => 'Concord\Tests\Models\Item',
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
            'class' => 'Concord\Tests\Models\Customer',
            'link' => array(
                'id' => 'customerId'
            ),
        ),

    );

}
