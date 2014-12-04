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
use fangface\tests\models\Note;
use fangface\tests\models\Pick;

/**
 * Test Concord Active Record add-on for Yii2
 */
class A4ActiveRecordTest extends DbTestCase
{

    use \fangface\base\traits\ServiceGetter;

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Note model
     */
    function testEmptyActiveRecordSaveFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Note model
     */
    function testEmptyActiveRecordSave2Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $note->applyDefaults();
        //$note->loadDefaultValues();
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Note model
     */
    function testEmptyActiveRecordSave3Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $note->loadDefaultValues();
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Note model
     */
    function testEmptyActiveRecordSave4Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $note->anotherString = '';
        $x = $note->allToArray();
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Pick model
     */
    function testEmptyActiveRecordSave5Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Pick model
     */
    function testEmptyActiveRecordSave6Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->applyDefaults();
        //$note->loadDefaultValues();
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     *
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Attempting to save an empty Pick model
     */
    function testEmptyActiveRecordSave7Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->anotherString = '';
        $note->anotherText = '';
        $x = $note->allToArray();
        $note->save();
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecordSaveSuccess()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->anotherText = 'A';
        $success = $note->save();
        $this->assertTrue($success);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecord2SaveSuccess()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->loadDefaultValues();
        $note->anotherText = 'A';
        $success = $note->save();
        $this->assertTrue($success);
        $note->refresh();
        $this->assertEquals('', $note->anotherString);
        $this->assertEquals('A', $note->anotherText);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecord3SaveSuccess()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->loadDefaultValues();
        $note->anotherString = 'A';
        $success = $note->save();
        $this->assertTrue($success);
        $note->refresh();
        $this->assertEquals('A', $note->anotherString);
        $this->assertEquals('', $note->anotherText);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testEmptyActiveRecordSaveAllFails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $success = $note->saveAll();
        $this->assertFalse($success);
        $actionErrors = $note->getActionErrors();
        $this->assertEquals('Attempting to save an empty Note model', $actionErrors[0]['message'][0]);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     */
    function testEmptyActiveRecordSaveAll2Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Note;
        $note->loadDefaultValues();
        $note->anotherString = '';
        $x = $note->allToArray();
        $success = $note->saveAll();
        $this->assertFalse($success);
        $actionErrors = $note->getActionErrors();
        $this->assertEquals('Attempting to save an empty Note model', $actionErrors[0]['message'][0]);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testEmptyActiveRecordSaveAll3Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $success = $note->saveAll();
        $this->assertFalse($success);
        $actionErrors = $note->getActionErrors();
        $this->assertEquals('Attempting to save an empty Pick model', $actionErrors[0]['message'][0]);
    }

    /**
     * Test creating an empty active record causes an error
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testEmptyActiveRecordSaveAll4Fails()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->loadDefaultValues();
        $note->anotherString = '';
        $note->anotherText = '';
        $x = $note->allToArray();
        $success = $note->saveAll();
        $this->assertFalse($success);
        $actionErrors = $note->getActionErrors();
        $this->assertEquals('Attempting to save an empty Pick model', $actionErrors[0]['message'][0]);
    }

    /**
     * Test creating a non empty active record is a success
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecordSaveAllSuccess()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->anotherText = 'B';
        $success = $note->saveAll();
        $this->assertTrue($success);
    }

    /**
     * Test creating a non empty active record is a success
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecordSaveAll2Success()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->loadDefaultValues();
        $note->anotherText = 'B';
        $success = $note->saveAll();
        $this->assertTrue($success);
        $note->refresh();
        $this->assertEquals('', $note->anotherString);
        $this->assertEquals('B', $note->anotherText);
    }

    /**
     * Test creating a non empty active record is a success
     * this model has a mixture of field types with default values
     * including a long text which has no default value and does not allow nulls
     */
    function testNonEmptyActiveRecordSaveAll3Success()
    {
        $client = Client::findOne(1);
        $this->assertInstanceOf(Client::className(), $client);
        $this->setService('client', $client);

        $note = New Pick;
        $note->loadDefaultValues();
        $note->anotherString = 'B';
        $success = $note->saveAll();
        $this->assertTrue($success);
        $note->refresh();
        $this->assertEquals('B', $note->anotherString);
        $this->assertEquals('', $note->anotherText);
    }

}
