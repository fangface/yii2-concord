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
use fangface\concord\tests\models\Product;
use fangface\concord\tests\models\Order;
use fangface\concord\tests\models\Customer;

/**
 * Active Record class for the clients dbCLIENT.{prefix}items table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the item belongs to
 * @property integer $orderId ID of order that the item belongs to
 * @property string $productCode
 * @property integer $quantity
 * @property double $totalValue
 * @property string $field1
 * @property string $field2
 * @property string $field3
 * @property \fangface\concord\tests\models\Product $product
 * @property \fangface\concord\tests\models\Order $order
 * @property \fangface\concord\tests\models\Customer $customer
 *
 * @method Item findOne($condition = null) static
 * @method Item[] findAll($condition = null) static
 * @method Item[] findByCondition($condition, $one) static
 */
class Item extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';


    public function modelRelationMap()
    {
        return [

            'product' => array(
                'type' => 'hasOne',
                'class' => Product::className(),
                'link' => array(
                    'productCode' => 'productCode' // child->var => parent->var (remote => local)
                ),
                'allToArray' => true,
            ),

            'order' => array(
                'type' => 'belongsTo',
                'class' => Order::className(),
                'link' => array(
                    'id' => 'orderId'
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
