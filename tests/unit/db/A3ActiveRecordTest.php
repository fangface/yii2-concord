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

namespace fangface\tests\unit\db;

use fangface\models\db\Client;
use fangface\tests\models\DbTestCase;
use fangface\tests\models\Customer;
use fangface\tests\models\Order;
use fangface\tests\models\Item;
use fangface\tests\models\Note;
use fangface\tests\models\Pick;

/**
 * Test Concord Active Record add-on for Yii2
 */
class A3ActiveRecordTest extends DbTestCase
{

    use \fangface\base\traits\ServiceGetter;

    /**
     * Test active record extensions across the two test clients
     */
    public function testActiveRecordExtensions()
    {
        $dbFactory = \Yii::$app->get('dbFactory');

        $clients = Client::find()
            ->orderBy('id')
            ->all();

        $cnt = 0;
        foreach ($clients as $client) {

            $this->assertInstanceOf(Client::className(), $client);
            $this->setService('client', $client);


            for($x=1;$x<5;$x++) {

                $cnt++;
                $cntLocal = 0;

                $customer = new Customer();

                $customer->address->title       = 'Mr' . $cnt;
                $customer->address->forename    = 'A' . $cnt;
                $customer->address->surname     = 'Sample' . $cnt;
                $customer->address->jobTitle    = 'Job' . $cnt;
                $customer->address->company     = 'Company' . $cnt;
                $customer->address->address1    = 'Address1-' . $cnt;
                $customer->address->address2    = 'Address2-' . $cnt;
                $customer->address->address3    = 'Address3-' . $cnt;
                $customer->address->city        = 'City' . $cnt;
                $customer->address->region      = 'Region' . $cnt;
                $customer->address->countryCode = 'GBR';

                if ($client->clientCode == 'CLIENT2') {
                    $customer->extraField = 'Extra' . $cnt;
                }

                $customer->phone->telno = '0123456789';

                $customer->customerAttributes->field1 = 'CAField1-' . $cnt;
                $customer->customerAttributes->field2 = 'CAField2-' . $cnt;

                if ($client->clientCode == 'CLIENT2') {
                    $customer->customerAttributes->field3 = 'CAField3-' . $cnt;
                }

                $ok = $customer->saveAll();

                if (!$ok) {
                    print_r($customer->getActionErrors());
                }

                $this->assertTrue($ok, 'Failed to run saveAll');

                if ($ok) {
                    $this->assertEquals($x, $customer->id);
                    $customerId = $customer->id;

                    $customer = Customer::findOne($customerId);
                    $this->assertInstanceOf(Customer::className(), $customer);

                    /////// new order - using calculated new element keys - with assortment of array access, get row functions

                    $cntLocal++;

                    $key = $customer->orders->newElement();
                    $customer->orders[$key]->field1 = 'Order-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get($key)->field2 = 'Order-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row($key)->field3 = 'Order-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders[$key]->items->newElement();
                    $customer->orders[$key]->items[$key2]->productCode = 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders[$key]->items[$key2]->quantity = 2;
                    $customer->orders[$key]->items[$key2]->totalValue = 1.50;
                    $customer->orders[$key]->items[$key2]->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get($key)->items->get($key2)->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row($key)->items->row($key2)->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders[$key]->items->newElement();
                    $customer->orders[$key]->items[$key2]->productCode = 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders[$key]->items[$key2]->quantity = 1;
                    $customer->orders[$key]->items[$key2]->totalValue = 1.59;
                    $customer->orders[$key]->items[$key2]->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get($key)->items->get($key2)->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row($key)->items->row($key2)->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders[$key]->items->newElement();
                    $customer->orders[$key]->items[$key2]->productCode = 'POST';
                    $customer->orders[$key]->items[$key2]->quantity = 1;
                    $customer->orders[$key]->items[$key2]->totalValue = 3.50;
                    $customer->orders[$key]->items[$key2]->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get($key)->items->get($key2)->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row($key)->items->row($key2)->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    /////// new order - using specified element keys - with assortment of array access, get row functions

                    $cntLocal++;

                    $key = $customer->orders->newElement('xyz');
                    $customer->orders['xyz']->field1 = 'Order-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get('xyz')->field2 = 'Order-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row('xyz')->field3 = 'Order-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders['xyz']->items->newElement('qwe0');
                    $customer->orders['xyz']->items['qwe0']->productCode = 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders['xyz']->items['qwe0']->quantity = 3;
                    $customer->orders['xyz']->items['qwe0']->totalValue = 3.33;
                    $customer->orders['xyz']->items['qwe0']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get('xyz')->items->get('qwe0')->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row('xyz')->items->row('qwe0')->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders['xyz']->items->newElement('qwe1');
                    $customer->orders['xyz']->items['qwe1']->productCode = 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders['xyz']->items['qwe1']->quantity = 2;
                    $customer->orders['xyz']->items['qwe1']->totalValue = 4;
                    $customer->orders['xyz']->items['qwe1']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get('xyz')->items->get('qwe1')->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row('xyz')->items->row('qwe1')->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $key2 = $customer->orders['xyz']->items->newElement('qwe2');
                    $customer->orders['xyz']->items['qwe2']->productCode = 'POST';
                    $customer->orders['xyz']->items['qwe2']->quantity = 1;
                    $customer->orders['xyz']->items['qwe2']->totalValue = 3.50;
                    $customer->orders['xyz']->items['qwe2']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders->get('xyz')->items->get('qwe2')->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders->row('xyz')->items->row('qwe2')->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    /////// new order - using array appending of objects with no keys specified

                    $cntLocal++;

                    $newOrder = new Order();
                    $newOrder->field1 = 'Order-Field-1-' . $cnt . '-' . $cntLocal;
                    $newOrder->field2 = 'Order-Field-2-' . $cnt . '-' . $cntLocal;
                    $newOrder->field3 = 'Order-Field-3-' . $cnt . '-' . $cntLocal;

                    $newItem = new Item();
                    $newItem->productCode = 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $newItem->quantity = 3;
                    $newItem->totalValue = 3.36;
                    $newItem->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $newItem->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $newItem->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;
                    $newOrder->items[] = $newItem;

                    $newItem = new Item();
                    $newItem->productCode = 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $newItem->quantity = 2;
                    $newItem->totalValue = 6;
                    $newItem->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $newItem->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $newItem->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;
                    $newOrder->items[] = $newItem;

                    $newItem = new Item();
                    $newItem->productCode = 'POST';
                    $newItem->quantity = 1;
                    $newItem->totalValue = 3.52;
                    $newItem->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $newItem->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $newItem->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;
                    $newOrder->items->append($newItem);

                    $customer->orders[] = $newOrder;

                    /////// new order - using array appending of objects with initial temporary keys specified

                    $cntLocal++;

                    $newOrder = new Order();
                    $newOrder->field1 = 'Order-Field-1-' . $cnt . '-' . $cntLocal;
                    $newOrder->field2 = 'Order-Field-2-' . $cnt . '-' . $cntLocal;
                    $newOrder->field3 = 'Order-Field-3-' . $cnt . '-' . $cntLocal;

                    $newItem = new Item();
                    $newItem->productCode = 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $newItem->quantity = 3;
                    $newItem->totalValue = 3.36;
                    $newItem->field1 = 'Item-Field-1-' . $cnt;
                    $newItem->field2 = 'Item-Field-2-' . $cnt;
                    $newItem->field3 = 'Item-Field-3-' . $cnt;
                    $newOrder->items['m1'] = $newItem;

                    $newItem = new Item();
                    $newItem->productCode = 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $newItem->quantity = 2;
                    $newItem->totalValue = 6;
                    $newItem->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $newItem->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $newItem->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;
                    $newOrder->items->appendWithKey($newItem, 'm2');

                    $newItem = new Item();
                    $newItem->productCode = 'POST';
                    $newItem->quantity = 1;
                    $newItem->totalValue = 3.52;
                    $newItem->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $newItem->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $newItem->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;
                    $newOrder->items->offsetSet('m3', $newItem);

                    $customer->orders['m'] = $newOrder;

                    /////// new order - using array appending of pre set objects with initial no keys specified

                    $cntLocal++;

                    $newOrder = new Order(array(
                        'field1' => 'Order-Field-1-' . $cnt . '-' . $cntLocal,
                        'field2' => 'Order-Field-2-' . $cnt . '-' . $cntLocal,
                        'field3' => 'Order-Field-3-' . $cnt . '-' . $cntLocal,
                    ));

                    $newOrder->items[] = new Item(array(
                        'productCode'   => 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : ''),
                        'quantity'      => 3,
                        'totalValue'    => 3.36,
                        'field1'        => 'Item-Field-1-' . $cnt . '-' . $cntLocal,
                        'field2'        => 'Item-Field-2-' . $cnt . '-' . $cntLocal,
                        'field3'        => 'Item-Field-3-' . $cnt . '-' . $cntLocal,
                    ));

                    $newOrder->items[] = new Item(array(
                        'productCode'   => 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : ''),
                        'quantity'      => 2,
                        'totalValue'    => 4.80,
                        'field1'        => 'Item-Field-1-' . $cnt . '-' . $cntLocal,
                        'field2'        => 'Item-Field-2-' . $cnt . '-' . $cntLocal,
                        'field3'        => 'Item-Field-3-' . $cnt . '-' . $cntLocal,
                    ));

                    $newOrder->items[] = new Item(array(
                        'productCode'   => 'POST',
                        'quantity'      => 1,
                        'totalValue'    => 3.98,
                        'field1'        => 'Item-Field-1-' . $cnt . '-' . $cntLocal,
                        'field2'        => 'Item-Field-2-' . $cnt . '-' . $cntLocal,
                        'field3'        => 'Item-Field-3-' . $cnt . '-' . $cntLocal,
                    ));

                    $customer->orders[] = $newOrder;

                    /////// new order - using objects within arrays that have not needed to be initiated (default objects are automatically assumed when
                    /////// a new offset is accessed

                    $cntLocal++;

                    $customer->orders['x']->field1 = 'Order-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->field2 = 'Order-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->field3 = 'Order-Field-3-' . $cnt . '-' . $cntLocal;

                    $customer->orders['x']->items['a']->productCode = 'CODE1' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders['x']->items['a']->quantity = 4;
                    $customer->orders['x']->items['a']->totalValue = 4.12;
                    $customer->orders['x']->items['a']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['a']->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['a']->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $customer->orders['x']->items['b']->productCode = 'CODE2' . ($client->clientCode == 'CLIENT2' ? 'B' : '');
                    $customer->orders['x']->items['b']->quantity = 6;
                    $customer->orders['x']->items['b']->totalValue = 4.12;
                    $customer->orders['x']->items['b']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['b']->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['b']->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    $customer->orders['x']->items['c']->productCode = 'POST';
                    $customer->orders['x']->items['c']->quantity = 1;
                    $customer->orders['x']->items['c']->totalValue = 2.56;
                    $customer->orders['x']->items['c']->field1 = 'Item-Field-1-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['c']->field2 = 'Item-Field-2-' . $cnt . '-' . $cntLocal;
                    $customer->orders['x']->items['c']->field3 = 'Item-Field-3-' . $cnt . '-' . $cntLocal;

                    //////// save all new order and changes

                    $ok = $customer->saveAll();

                    if (!$ok) {
                        print_r($customer->getActionErrors());
                    }

                    $this->assertTrue($ok, 'Failed to run saveAll - adding orders');

                } else {
                    //foreach ($customer->getActionErrors() as $actionError) {
                    //    print_r($actionError);
                    //}
                }
            }

            $fullDataCheck = array();
            $customers = Customer::find()
                ->orderBy('id')
                ->all();

            foreach ($customers as $customerId => $customer) {
                $fullDataCheck[$customerId] = $customer->allToArray();
            }

            $fullDataCheck = $this->cleanDatesForComparison($fullDataCheck);

            $resultsPath = \Yii::getAlias('@fangface/tests/data/results');
            $resultsFile = $resultsPath . '/ar-test-' . strtolower($client->clientCode) . '.json';
            if (false) {
                // for use when comparing future tests
                file_put_contents($resultsFile, json_encode($fullDataCheck));
                // readable results
                file_put_contents(str_replace('.json', '.txt', $resultsFile), print_r($fullDataCheck, true));
            } else {
                $expectedResult = json_decode(file_get_contents($resultsFile), true);
                if ($fullDataCheck != $expectedResult) {
                    file_put_contents(str_replace('.json', '-testing.txt', $resultsFile), print_r($fullDataCheck, true));
                }
                $this->assertEquals($expectedResult, $fullDataCheck, 'Failed to match results for ' . strtolower($client->clientCode));
            }

            $this->assertTrue($dbFactory->removeAllResources());
            $this->assertEquals(1, $dbFactory->getResourceCount());

        }

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }

    /**
     * Test active record extensions fullDelete() across the two test clients
     */
    function testActiveRecordFullDelete()
    {
        $dbFactory = \Yii::$app->get('dbFactory');

        $this->assertEquals(0, $dbFactory->getResourceCount());

        $clients = Client::find()
            ->orderBy('id')
            ->all();

        $this->assertEquals(1, $dbFactory->getResourceCount());

        $cnt = 0;
        foreach ($clients as $client) {

            $this->assertInstanceOf(Client::className(), $client);
            $this->setService('client', $client);

            $this->checkBaseCounts();

            $customerId = $this->createTestCustomerAndOrder($client->clientCode);

            $this->checkBaseCounts('full', $client->clientCode);

            $this->assertTrue($customerId !== false && $customerId > 0);

            $customer = Customer::findOne($customerId);
            $this->assertInstanceOf(Customer::className(), $customer);

            // should be able to do a full delete which will exclude any relations that have
            // been defined as read only
            $this->assertTrue($customer->deleteFull());

            $this->checkBaseCounts();

            $this->assertTrue($dbFactory->removeAllResources());
            $this->assertEquals(1, $dbFactory->getResourceCount());

        }

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }


    /**
     * Test active record extensions fullDelete() on partial relation to check warnings
     * across the two test clients
     */
    function testActiveRecordFullDeleteOnPartialRelation()
    {
        $dbFactory = \Yii::$app->get('dbFactory');

        $this->assertEquals(0, $dbFactory->getResourceCount());

        $clients = Client::find()
            ->orderBy('id')
            ->all();

        $this->assertEquals(1, $dbFactory->getResourceCount());

        $cnt = 0;
        foreach ($clients as $client) {

            $this->assertInstanceOf(Client::className(), $client);
            $this->setService('client', $client);

            $this->checkBaseCounts();

            $customerId = $this->createTestCustomerAndOrder($client->clientCode);

            $this->checkBaseCounts('full', $client->clientCode);

            $this->assertTrue($customerId !== false && $customerId > 0);

            $customer = Customer::findOne($customerId);
            $this->assertInstanceOf(Customer::className(), $customer);

            // should be able to do a full delete which will exclude any relations that have
            // been defined as read only
            $this->assertTrue($customer->address->deleteFull());

            $this->checkBaseCounts('lessaddress', $client->clientCode);

            $this->assertTrue($dbFactory->removeAllResources());
            $this->assertEquals(1, $dbFactory->getResourceCount());

        }

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }


    /**
     * Test active record extensions direct deleteFull() fails on readOnly relation
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Country readOnly model
     */
    function testActiveRecordDirectDeleteFullOnReadOnlyFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->address->country->deleteFull());
    }


    /**
     * Test active record extensions direct delete() fails on readOnly relation
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Country readOnly model
     */
    function testActiveRecordDirectDeleteOnReadOnlyFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->address->country->delete());
    }


    /**
     * Test active record extensions direct fullDelete() fails on !canDelete relation
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Country model flagged as not deletable
     */
    function testActiveRecordDirectDeleteFullOnNonCanDeleteFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // bypass read only to allow testing of !canDelete
        $customer->address->country->setReadOnly(false);

        $this->assertFalse($customer->address->country->deleteFull());
    }


    /**
     * Test active record extensions direct delete() fails on !canDelete relation
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Country model flagged as not deletable
     */
    function testActiveRecordDirectDeleteOnNonCanDeleteFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // bypass read only to allow testing of !canDelete
        $customer->address->country->setReadOnly(false);

        $this->assertFalse($customer->address->country->delete());
    }


    /**
     * Test active record extensions direct saveAll() fails on !readOnly
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to saveAll on Country readOnly model
     */
    function testActiveRecordDirectSaveAllOnReadOnlyFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->address->country->saveAll());
    }


    /**
     * Test active record extensions direct save() fails on !readOnly
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save on Country readOnly model
     */
    function testActiveRecordDirectSaveOnReadOnlyFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->address->country->save());
    }


    /**
     * Test active record extensions set attribute for a readOnly model
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to set attribute `longName` on a read only Country model
     */
    function testActiveRecordSetAttributeOnReadOnlyFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $this->assertEquals('United Kingdom', $customer->address->country->longName);

        $customer->address->country->setAttribute('longName', 'Attempt to change longName');
    }


    /**
     * Test active record extensions set attribute via magic method for a readOnly model
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to set attribute `longName` on a read only Country model
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

        // check attribute can be read first
        $this->assertEquals('United Kingdom', $customer->address->country->longName);

        $customer->address->country->longName = 'Attempt to change longName';
    }


    /**
     * Test active record extensions set attributes for a readOnly model
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to set attributes on a read only Country model
     */
    function testActiveRecordSetAttributesOnReadOnlyFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $this->assertEquals('United Kingdom', $customer->address->country->longName);

        $customer->address->country->setAttributes(array('longName' => 'Attempt to change longName'));
    }


    /**
     * Test invalid active record property being set
     *
     * @expectedException        \yii\base\UnknownPropertyException
     * @expectedExceptionMessage Setting unknown property: fangface\tests\models\Customer::fieldNotExist
     */
    function testActiveRecordSetNonExistAttributeFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $customer->fieldNotExist = 1;
    }


    /**
     * Test invalid active record property being set via setAttribute()
     *
     * @expectedException        \yii\base\InvalidParamException
     * @expectedExceptionMessage fangface\tests\models\Customer has no attribute named "fieldNotExist".
     */
    function testActiveRecordSetNonExistSetAttributeFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $customer->setAttribute('fieldNotExist', 1);
    }


    /**
     * Test normal AR save() method works
     */
    function testActiveRecordDirectSave()
    {
        $client = Client::findOne(2);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $customer->extraField = 'New Value ' . time();

        $customerPreSave = $this->cleanDatesForComparison($customer->toArray());

        $customer->save();

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);
        $customerReloaded = $this->cleanDatesForComparison($customer->toArray());

        $this->assertEquals($customerPreSave, $customerReloaded, 'Failed to match save() data after reload');
    }


    /**
     * Test normal AR delete() method works
     */
    function testActiveRecordDirectDelete()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->orders[1]->items[3]->delete();

        $this->checkBaseCounts('lessoneitem', $client->clientCode);
    }


    /**
     * Test active record extensions direct deleteFull() fails on readOnly relation
     * where readOnly is defined in the model class itself
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Product readOnly model
     */
    function testActiveRecordDirectDeleteFullOnClassDefinedReadOnlyFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->orders[1]->items[1]->product->deleteFull());
    }


    /**
     * Test active record extensions direct deleteFull() fails on !canDelete relation
     * where !canDelete is defined in the model class itself
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Product model flagged as not deletable
     */
    function testActiveRecordDirectDeleteFullOnClassDefinedNonCanDeleteFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // override so we can test non canDelete
        $customer->orders[1]->items[1]->product->setReadOnly(false);
        $this->assertFalse($customer->orders[1]->items[1]->product->deleteFull());
    }


    /**
     * Test active record extensions direct delete() fails on readOnly relation
     * where readOnly is defined in the model class itself
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Product readOnly model
     */
    function testActiveRecordDirectDeleteOnClassDefinedReadOnlyFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $this->assertFalse($customer->orders[1]->items[1]->product->delete());
    }


    /**
     * Test active record extensions direct delete() fails on !canDelete relation
     * where !canDelete is defined in the model class itself
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to delete Product model flagged as not deletable
     */
    function testActiveRecordDirectDeleteOnClassDefinedNonCanDeleteFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // override so we can test non canDelete
        $customer->orders[1]->items[1]->product->setReadOnly(false);
        $this->assertFalse($customer->orders[1]->items[1]->product->delete());
    }


    /**
     * Test active record extensions set attribute via magic method for a readOnly model
     * where readOnly is defined in the model class itself
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to set attribute `description` on a read only Product model
     */
    function testActiveRecordSetAttributeViaMagicSetOnClassDefinedReadOnlyFails()
    {

        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        // check attribute can be read first
        $this->assertEquals('Description for productCode CODE1', $customer->orders[1]->items[1]->product->description);

        $customer->orders[1]->items[1]->product->description = 'Attempt to change description';
    }


    /**
     * Amend customer id at top level of a relation and test that a push()
     * request forces relations that have customerId set are updated even though
     * no other changes have been made
     */
    function testActiveRecordChangeTopLevelIdFeedsDownToRelations()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerId = $this->createTestCustomerAndOrder($client->clientCode);
        $this->assertTrue($customerId !== false && $customerId > 0);

        $this->checkBaseCounts('full', $client->clientCode);

        $customer = Customer::findOne($customerId);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customer->id = 95;
        $customerPreChangeWithNewId = $this->updateCustomerIdForComparison($this->cleanDatesForComparison($customer->toArray()), 95);

        $customer->push();

        $customer = Customer::findOne(95);
        $this->assertInstanceOf(Customer::className(), $customer);

        $customerReloaded = $this->cleanDatesForComparison($customer->toArray());

        $this->assertEquals($customerPreChangeWithNewId, $customerReloaded, 'Failed to push new customerId into relations after reload');

    }

}
