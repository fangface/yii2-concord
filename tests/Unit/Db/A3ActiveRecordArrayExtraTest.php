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

namespace Concord\Tests\Unit\Db;

use Concord\Tests\Models\DbTestCase as DbTestCase;
use Concord\Models\Db\Client;
use Concord\Tests\Models\Customer;
use Concord\Tests\Models\Order;
use Concord\Tests\Models\Item;
use Yii;

/**
 * Test Concord Active Record array add-on for Yii2 hasMany/multi active record arrays
 * many tests are take place in other test classes, here we look at some of the features
 * not yet tested
 */
class A3ActiveRecordArrayExtraTest extends DbTestCase
{

    use \Concord\Base\Traits\ServiceGetter;


    /**
     * Test add invalid object to new active record object array
     *
     * @expectedException        \Concord\Db\ActiveRecordArrayException
     * @expectedExceptionMessage Item added to array not of type `Concord\Tests\Models\Item` it is of type `Concord\Tests\Models\Customer`
     */
    function testActiveRecordArrayAddInvalidObjectToNewArray()
    {
        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $order = new Order();
        $order->items[] = new Customer();
    }


    /**
     * Test add invalid object to existing active record object array
     *
     * @expectedException        \Concord\Db\ActiveRecordArrayException
     * @expectedExceptionMessage Item added to array not of type `Concord\Tests\Models\Item` it is of type `Concord\Tests\Models\Customer`
     */
    function testActiveRecordArrayAddInvalidObjectToExistingArray()
    {
        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);
        $this->assertEquals(3, $customer->orders[1]->items->count());

        $customer->orders[1]->items[] = new Customer();
    }


    /**
     * Test add valid object to existing active record object array, saved and reloads as expected
     */
    function testActiveRecordArrayAddValidObjectToExistingArray()
    {
        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);
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

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);
        $this->assertEquals(4, $customer->orders[1]->items->count());
    }


    /**
     * Test active record array set attribute via magic method for a readOnly array
     *
     * @expectedException        \Concord\Db\Exception
     * @expectedExceptionMessage Attempting to set attribute `productCode` on a read only Item model
     */
    function testActiveRecordSetAttributeViaMagicSetOnReadOnlyFails()
    {

        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);

        $customer->orders[1]->items->setReadOnly(true);

        // check attribute can be read first
        $this->assertEquals('CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : ''), $customer->orders[1]->items[1]->productCode);

        $customer->orders[1]->items[1]->productCode = 'NEWCODE';
    }


    /**
     * Test active record array saveAll() where readOnly has been set
     *
     * @expectedException        \Concord\Db\Exception
     * @expectedExceptionMessage Attempting to saveAll on Item(s) which is read only
     */
    function testActiveRecordSaveAllOnReadOnlyArrayFails()
    {

        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);

        $customer->orders[1]->items->setReadOnly(true);
        $customer->orders[1]->items->saveAll();
    }


    /**
     * Test active record array deleteFull() where readOnly has been set
     *
     * @expectedException        \Concord\Db\Exception
     * @expectedExceptionMessage Attempting to delete Item(s) readOnly model
     */
    function testActiveRecordDeleteFullOnReadOnlyArrayFails()
    {

        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);

        $customer->orders[1]->items->setReadOnly(true);
        $customer->orders[1]->items->deleteFull();
    }


    /**
     * Test active record array deleteFull() where canDelete has been set to false
     *
     * @expectedException        \Concord\Db\Exception
     * @expectedExceptionMessage Attempting to delete Item(s) model flagged as not deletable
     */
    function testActiveRecordDeleteFullOnNonCanDeleteArrayFails()
    {

        $client = Client::find(1);
        $this->assertInstanceOf('Concord\Models\Db\Client', $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::find($customerId);
        $this->assertInstanceOf('Concord\Tests\Models\Customer', $customer);

        $customer->orders[1]->items->setCanDelete(false);
        $customer->orders[1]->items->deleteFull();
    }
}
