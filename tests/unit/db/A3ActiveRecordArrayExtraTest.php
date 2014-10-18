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

namespace fangface\concord\tests\unit\db;

use fangface\concord\models\db\Client;
use fangface\concord\tests\models\DbTestCase;
use fangface\concord\tests\models\Customer;
use fangface\concord\tests\models\Order;
use fangface\concord\tests\models\Item;

/**
 * Test Concord Active Record array add-on for Yii2 hasMany/multi active record arrays
 * many tests are take place in other test classes, here we look at some of the features
 * not yet tested
 */
class A3ActiveRecordArrayExtraTest extends DbTestCase
{

    use \fangface\concord\base\traits\ServiceGetter;


    /**
     * Test add invalid object to new active record object array
     *
     * @expectedException        \fangface\concord\db\ActiveRecordArrayException
     * @expectedExceptionMessage Item added to array not of type `fangface\concord\tests\models\Item` it is of type `fangface\concord\tests\models\Customer`
     */
    function testActiveRecordArrayAddInvalidObjectToNewArray()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $order = new Order();
        $order->items[] = new Customer();
    }


    /**
     * Test add invalid object to existing active record object array
     *
     * @expectedException        \fangface\concord\db\ActiveRecordArrayException
     * @expectedExceptionMessage Item added to array not of type `fangface\concord\tests\models\Item` it is of type `fangface\concord\tests\models\Customer`
     */
    function testActiveRecordArrayAddInvalidObjectToExistingArray()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);
        $this->assertEquals(3, $customer->orders[1]->items->count());

        $customer->orders[1]->items[] = new Customer();
    }


    /**
     * Test add valid object to existing active record object array, saved and reloads as expected
     */
    function testActiveRecordArrayAddValidObjectToExistingArray()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);
        $this->assertEquals(3, $customer->orders[1]->items->count());

        $customer->orders[1]->items[] = new Item(array(
            'productCode'   => 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : ''),
            'quantity'      => 1,
            'totalValue'    => 2.40,
            'field1'        => 'Item-Field-1',
            'field2'        => 'Item-Field-2',
            'field3'        => 'Item-Field-3',
        ));

        $this->assertEquals(4, $customer->orders[1]->items->count());

        $this->assertTrue($customer->saveAll());

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);
        $this->assertEquals(4, $customer->orders[1]->items->count());
    }


    /**
     * Test active record array set attribute via magic method for a readOnly array
     *
     * @expectedException        \fangface\concord\db\Exception
     * @expectedExceptionMessage Attempting to set attribute `productCode` on a read only Item model
     */
    function testActiveRecordSetAttributeViaMagicSetOnReadOnlyFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->orders[1]->items->setReadOnly(true);

        // check attribute can be read first
        $this->assertEquals('CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : ''), $customer->orders[1]->items[1]->productCode);

        $customer->orders[1]->items[1]->productCode = 'NEWCODE';
    }


    /**
     * Test active record array saveAll() where readOnly has been set
     *
     * @expectedException        \fangface\concord\db\Exception
     * @expectedExceptionMessage Attempting to saveAll on Item(s) which is read only
     */
    function testActiveRecordSaveAllOnReadOnlyArrayFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->orders[1]->items->setReadOnly(true);
        $customer->orders[1]->items->saveAll();
    }


    /**
     * Test active record array deleteFull() where readOnly has been set
     *
     * @expectedException        \fangface\concord\db\Exception
     * @expectedExceptionMessage Attempting to delete Item(s) readOnly model
     */
    function testActiveRecordDeleteFullOnReadOnlyArrayFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->orders[1]->items->setReadOnly(true);
        $customer->orders[1]->items->deleteFull();
    }


    /**
     * Test active record array deleteFull() where canDelete has been set to false
     *
     * @expectedException        \fangface\concord\db\Exception
     * @expectedExceptionMessage Attempting to delete Item(s) model flagged as not deletable
     */
    function testActiveRecordDeleteFullOnNonCanDeleteArrayFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->orders[1]->items->setCanDelete(false);
        $customer->orders[1]->items->deleteFull();
    }
}
