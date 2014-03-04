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
 * Active Record class for the clients dbClient.{prefix}customers table
 *
 * @property integer $id primary key
 * @property integer $addressId ID for the address
 * @property integer $phoneId ID for the phone
 * @property integer $extraField Extra field only present in dbCLIENT for CLIENT2
 * @property datetime $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property datetime $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 * @property Concord\Tests\Models\CustomerAttributes $customerAttributes
 * @property Concord\Tests\Models\Address $address
 * @property Concord\Tests\Models\Phone $phone
 * @property Concord\Tests\Models\Order[] $orders
 */
class Customer extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    protected $modelRelationMap = array(

        'customerAttributes' => array(
            'type' => 'hasEav',
            'class' => 'Concord\Tests\Models\CustomerAttributes',
            'link' => array(
                'objectId' => 'id' // child->var => parent->var (remote => local)
            ),
            'onSaveAll' => ActiveRecord::SAVE_CASCADE,
            'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
            'autoLinkType' => ActiveRecord::LINK_FROM_PARENT_MAINT,
            'allToArray' => true,
            'activeAttributesInParent' => true
        ),

        'address' => array(
            'type' => 'hasOne',
            'class' => 'Concord\Tests\Models\Address',
            'link' => array(
                'id' => 'addressId' // child->var => parent->var (remote => local)
            ),
            'onSaveAll' => ActiveRecord::SAVE_CASCADE,
            'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
            'autoLinkType' => ActiveRecord::LINK_BI_DIRECT_MAINT_FROM_PARENT,
            'autoLink' => array( // used in the case of LINK_BI_DIRECT, LINK_BI_DIRECT_MAINT, LINK_BI_DIRECT_MAINT_FROM_PARENT and LINK_BI_DIRECT_MAINT_FROM_CHILD or when 'link' alone does not specify the keys that need to be updated
                'fromParent' => array(
                    'customerId' => 'id' // child->var => parent->var (remote => local)
                ),
                'fromChild' => array(
                    'id' => 'addressId' // child->var => parent->var (remote => local)
                )
            ),
            'allToArray' => true,
        ),

        'phone' => array( // standard yii\db\ActiveRecord
            'type' => 'hasOne',
            'class' => 'Concord\Tests\Models\Phone',
            'link' => array(
                'id' => 'phoneId' // child->var => parent->var (remote => local)
            ),
            'onSaveAll' => ActiveRecord::SAVE_CASCADE,
            'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
            'autoLinkType' => ActiveRecord::LINK_BI_DIRECT_MAINT_FROM_PARENT,
            'autoLink' => array( // used in the case of LINK_BI_DIRECT, LINK_BI_DIRECT_MAINT, LINK_BI_DIRECT_MAINT_FROM_PARENT and LINK_BI_DIRECT_MAINT_FROM_CHILD or when 'link' alone does not specify the keys that need to be updated
                'fromParent' => array(
                    'customerId' => 'id' // child->var => parent->var (remote => local)
                ),
                'fromChild' => array(
                    'id' => 'phoneId' // child->var => parent->var (remote => local)
                )
            ),
            'allToArray' => true,
        ),

        'orders' => array(
            'type' => 'hasMany',
            'class' => 'Concord\Tests\Models\Order',
            'link' => array(
                'customerId' => 'id' // child->var => parent->var (remote => local)
            ),
            'onSaveAll' => ActiveRecord::SAVE_CASCADE,
            'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
            'autoLinkType' => ActiveRecord::LINK_FROM_PARENT_MAINT,
            'allToArray' => true,
        ),

    );

}
