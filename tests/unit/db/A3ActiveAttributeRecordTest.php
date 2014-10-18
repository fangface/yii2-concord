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
use fangface\concord\tests\models\CustomerAttributes;
use fangface\concord\tests\models\DbTestCase;

/**
 * Test Concord Active Attribute Record add-on for Yii2
 * (much of which is already tested elsewhere)
 */
class A3ActiveAttributeRecordTest extends DbTestCase
{
    use \fangface\concord\base\traits\ServiceGetter;

    /**
     * Test ability to get attribute record schema
     */
    public function testGetEntityAttributeListAsStructure()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerAttributes = New CustomerAttributes();
        $stru = $customerAttributes->GetEntityAttributeListAsStructure();
        $this->assertInternalType('array', $stru);
        $this->assertEquals(6, count($stru));
        $this->assertTrue(array_key_exists('field1', $stru));
        $this->assertTrue(array_key_exists('field2', $stru));
        $this->assertTrue(array_key_exists('createdAt', $stru));
        $this->assertTrue(array_key_exists('createdBy', $stru));
        $this->assertTrue(array_key_exists('modifiedAt', $stru));
        $this->assertTrue(array_key_exists('modifiedBy', $stru));
        $field = $stru['field1'];
        $this->assertInternalType('array', $field);
        $this->assertTrue(array_key_exists('columnDefault', $field));
    }

    /**
     * Test loadLazyAttribute method
     */
    public function testLoadLazyAttribute()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertTrue($customerAttributes->hasAttribute('field1'));
        $this->assertTrue($customerAttributes->hasAttribute('field2'));
        $customerAttributes->field1 = 'CAField1';
        $customerAttributes->field2 = 'CAField2';
        $this->assertTrue($customerAttributes->isAttributeChanged('field2'));
        $dirtyAttributes = $customerAttributes->getDirtyAttributes();
        $this->assertInternalType('array', $dirtyAttributes);
        $dirtyAttributes = $customerAttributes->getDirtyAttributes(array('field2'));
        $this->assertInternalType('array', $dirtyAttributes);
        $data = $customerAttributes->toArray();
        $this->assertInternalType('array', $data);
        $data = $customerAttributes->toArray(array('field1'));
        $this->assertInternalType('array', $data);
        $this->assertTrue($customerAttributes->save());

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertFalse($customerAttributes->isLoaded());
        $customerAttributes->loadAttributeValues(true);
        $this->assertTrue($customerAttributes->isLoaded());
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field1', $data));
        $this->assertTrue(array_key_exists('field2', $data));
        $this->assertEquals('CAField2', $customerAttributes->field2);
        $this->assertEquals('CAField1', $customerAttributes->field1);

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertFalse($customerAttributes->isLoaded());
        $customerAttributes->loadAttributeValues();
        $this->assertTrue($customerAttributes->isLoaded());
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field1', $data));
        $this->assertFalse(array_key_exists('field2', $data));
        $this->assertEquals('CAField2', $customerAttributes->field2);
        $this->assertEquals('CAField1', $customerAttributes->field1);
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field2', $data));

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertFalse($customerAttributes->isLoaded());
        $a = $customerAttributes->field1;
        $this->assertTrue($customerAttributes->isLoaded());
        $this->assertEquals('CAField1', $a);
        $this->assertEquals('CAField1', $customerAttributes->field1);
        $data = $customerAttributes->allToArray(true);
        $this->assertFalse(array_key_exists('field2', $data));
        $this->assertTrue($customerAttributes->loadLazyAttribute('field2'));
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field2', $data));
        $this->assertEquals('CAField2', $data['field2']);

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertEquals('CAField1', $customerAttributes->field1);
        $data = $customerAttributes->allToArray(true);
        $this->assertFalse(array_key_exists('field2', $data));
        $this->assertTrue($customerAttributes->loadLazyAttribute(array('field2')));
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field2', $data));
        $this->assertEquals('CAField2', $data['field2']);

        $customerAttributes = New CustomerAttributes(array(
            'objectId' => 1
        ));
        $this->assertEquals('CAField1', $customerAttributes->field1);
        $data = $customerAttributes->allToArray(true);
        $this->assertFalse(array_key_exists('field2', $data));
        $this->assertTrue($customerAttributes->loadLazyAttribute());
        $data = $customerAttributes->allToArray(true);
        $this->assertTrue(array_key_exists('field2', $data));
        $this->assertEquals('CAField2', $data['field2']);

        $customerAttributes = New CustomerAttributes(1);
        $data = $customerAttributes->allToArray(true);
        $this->assertFalse(array_key_exists('field2', $data));
        $this->assertTrue($customerAttributes->loadLazyAttribute('field2'));
        $data = $customerAttributes->allToArray(true);
        $this->assertEquals('CAField2', $data['field2']);
        $this->assertFalse($customerAttributes->loadLazyAttribute('field2'));

    }

    /**
     * Test getEntityAttributeMap method
     */
    public function testGetEntityAttributeMap()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $customerAttributes = New CustomerAttributes();
        $data = $customerAttributes->GetEntityAttributeMap();
        $this->assertInternalType('array', $data);

        $data2 = $customerAttributes->getEntityAttributeMap();
        $this->assertInternalType('array', $data2);
        $this->assertEquals($data, $data2);

    }

    /**
     * Test getEntityAttributeIdByName method
     */
    public function testGetEntityAttributeIdByName()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);
        $customerAttributes = New CustomerAttributes();
        $id = $customerAttributes->getEntityAttributeIdByName('field1');
        $this->assertEquals(1, $id);
        $id2 = $customerAttributes->getAttributeIdByName('field1');
        $this->assertEquals($id, $id2);
    }


    /**
     * Test getEntityAttributeNameById method
     */
    public function testGetEntityAttributeNameById()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);
        $customerAttributes = New CustomerAttributes();
        $id = $customerAttributes->getEntityAttributeNameById(2);
        $this->assertEquals('field2', $id);
    }

    /**
     * Test setValuesByArray method
     */
    public function testSetValuesByArray()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);
        $customerAttributes = New CustomerAttributes();
        $customerAttributes->setValuesByArray(array('field1'=>'a', 'field2'=>'b'), array('modifiedBy'=>54));
        $this->assertEquals('a', $customerAttributes->field1);
        $this->assertEquals('b', $customerAttributes->field2);
        $this->assertEquals(54, $customerAttributes->modifiedBy);

    }

}
