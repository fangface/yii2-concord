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
 * Active Record class for the clients dbClient.{prefix}customers table
 *
 * @property integer $id primary key
 * @property integer $addressId ID for the address
 * @property integer $phoneId ID for the phone
 * @property integer $extraField Extra field only present in dbCLIENT for CLIENT2
 * @property string $createdAt Date and time record was created
 * @property integer $createdBy	User id of user that created record
 * @property string $modifiedAt Date and time record was last modified
 * @property integer $modifiedBy User id of last user to modify record
 * @property fangface\tests\models\CustomerAttributes $customerAttributes
 * @property fangface\tests\models\Address $address
 * @property fangface\tests\models\Phone $phone
 * @property fangface\tests\models\Order[] $orders
 *
 * @method Customer findOne($condition = null) static
 * @method Customer[] findAll($condition = null) static
 * @method Customer[] findByCondition($condition, $one) static
 */
class Customer extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    public function modelRelationMap()
    {
        return [

            'customerAttributes' => array(
                'type' => 'hasEav',
                'class' => CustomerAttributes::className(),
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
                'class' => Address::className(),
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
                'class' => Phone::className(),
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
                'class' => Order::className(),
                'link' => array(
                    'customerId' => 'id' // child->var => parent->var (remote => local)
                ),
                'onSaveAll' => ActiveRecord::SAVE_CASCADE,
                'onDeleteFull' => ActiveRecord::DELETE_CASCADE,
                'autoLinkType' => ActiveRecord::LINK_FROM_PARENT_MAINT,
                'allToArray' => true,
            ),

        ];
    }
}
