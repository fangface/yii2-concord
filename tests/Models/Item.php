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
use Concord\Tests\Models\Product;
use Concord\Tests\Models\Order;
use Concord\Tests\Models\Customer;

/**
 * Active Record class for the clients dbCLIENT.{prefix}items table
 *
 * @property integer $id primary key
 * @property integer $customerId ID of customer that the item belongs to
 * @property integer $orderId ID of order that the item belongs to
 * @property string $productCode
 * @property integer $quantity
 * @property decimal $totalValue
 * @property string $field1
 * @property string $field2
 * @property string $field3
 * @property \Concord\Tests\Models\Product $product
 * @property \Concord\Tests\Models\Order $order
 * @property \Concord\Tests\Models\Customer $customer
 */
class Item extends ActiveRecord
{

    protected static $dbResourceName    = 'dbClient';

    protected $disableCreatedUpd  = true;
    protected $disableModifiedUpd = true;

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
