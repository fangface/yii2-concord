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

use Yii;
use Concord\Tests\Models\TestCase as TestCase;
use Concord\Db\ConnectionManager;
use PDO;

/**
 * Test Concord Connection Manager class and methods
 */
class A1ConnectionManagerTest extends TestCase
{

    /**
     * Test that the dbFactory component is available and is of the expected object type
     */
    public function testGetConnectionManager()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');
        $this->assertInstanceOf('\Concord\Db\ConnectionManager', new ConnectionManager);
        $this->assertEquals(0, $dbFactory->getResourceCount());
    }


    /**
     * Obtain the default db connection via the connection manager (even though it has not
     * been manually added to the connectiot manager)
     */
    public function testDefaultConnectionUsage()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');
        $db = $dbFactory->getConnection();
        $this->assertInstanceOf('\yii\db\Connection', $db);

        // the default resource has been fired up now (and should be the only one)
        $this->assertEquals(1, $dbFactory->getResourceCount());

        // we should be able to get a connection
        $this->assertTrue($dbFactory->connectResource());
        $this->assertTrue($dbFactory->isResourceConnected());
        $this->assertTrue($dbFactory->isResource());

        // the connection should be a PDO instance
        $this->assertInstanceOf('\PDO', $db->pdo);

        // check connection has the correct dsn
        $this->assertEquals($this->getDbParam($dbFactory->getDefaultResourceName(), 'dsn'), $db->dsn);

        // we should be able to disconnect
        $this->assertTrue($dbFactory->disconnectResource());
        $this->assertFalse($dbFactory->isResourceConnected());
        $this->assertNull($db->pdo);

        // remove resource
        $this->assertTrue($dbFactory->removeResource());
        $this->assertFalse($dbFactory->isResource());
        $this->assertEquals(0, $dbFactory->getResourceCount());
    }


    /**
     * Fail to obtain the 'db' connection via the connection manager because it has not been
     * defined in the connection manager and the parameters specify to not attempt setting
     * it up or obtaining it from any existing components
     */
    public function testGetDefaultUndefinedConnectionFails()
    {
        $db = Yii::$app->getComponent('dbFactory')->getConnection('db', false, false);
        // should get false returned
        $this->assertFalse($db);
    }


    /**
     * Fail to obtain the 'db' connection via the connection manager because it has not been
     * defined in the connection manager and the parameters specify to attempt to add the
     * resource and not to use anything defined in components and no dbResources table exists
     * @expectedException        \Concord\Db\Exception
     * @expectedExceptionMessage dbResources table not found
     */
    public function testGetUndefinedDbConnectionWithNoDbResourcesFails()
    {
        $db = Yii::$app->getComponent('dbFactory')->getConnection('dbX2', true, false);
    }


    /**
     * Test switching default resource names
     */
    public function testSwitchDefaultConnections()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');

        // no resources should exist yet
        $this->assertEquals(0, $dbFactory->getResourceCount());

        $newResourceLabel = 'db2';
        $dbFactory->setDefaultResourceName($newResourceLabel);
        $this->assertEquals($newResourceLabel, $dbFactory->getDefaultResourceName());

        $this->assertEquals(0, $dbFactory->getResourceCount());

        $newResourceLabel = 'db';
        $dbFactory->setDefaultResourceName($newResourceLabel);
        $this->assertEquals($newResourceLabel, $dbFactory->getDefaultResourceName());

        // have still not setup or called for any resources to be populated
        $this->assertEquals(0, $dbFactory->getResourceCount());

        // whilst default is 'db' we will get and check the 'db2' connection
        $db = $dbFactory->getConnection('db2');
        $this->assertInstanceOf('\yii\db\Connection', $db);

        // 1 resource has been fired up now
        $this->assertEquals(1, $dbFactory->getResourceCount());
        $this->assertTrue($dbFactory->isResource('db2'));

        // we should be able to get a connection
        $this->assertTrue($dbFactory->connectResource('db2'));
        $this->assertTrue($dbFactory->isResourceConnected('db2'));

        // the connection should be a PDO instance
        $this->assertInstanceOf('\PDO', $db->pdo);

        // check connection has the correct dsn
        $this->assertEquals($this->getDbParam('db2', 'dsn'), $db->dsn);

        // we should be able to disconnect
        $this->assertTrue($dbFactory->disconnectResource('db2'));
        $this->assertFalse($dbFactory->isResourceConnected('db2'));
        $this->assertNull($db->pdo);

        // remove resource
        $this->assertTrue($dbFactory->removeResource('db2'));
        $this->assertFalse($dbFactory->isResource('db2'));
        $this->assertEquals(0, $dbFactory->getResourceCount());
    }

    /*
     * Test functions relating to adding/removing resources manually
     * along with aliases
     */
    public function testAddManualResourcesAndAliases()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');
        $dbConfig = $this->getDbParam('db', null, false);
        $this->assertNotEmpty($dbConfig);
        if ($dbConfig && is_array($dbConfig)) {

            $this->assertTrue($dbFactory->addResource('db', false, false, $dbConfig));

            $this->assertTrue($dbFactory->addResourceAlias('db', 'myAlias'));

            $db = $dbFactory->getConnection('myAlias');
            $this->assertInstanceOf('\yii\db\Connection', $db);

            // 1 resource has been fired up now
            $this->assertEquals(1, $dbFactory->getResourceCount());
            $this->assertEquals(1, $dbFactory->getAliasCount());
            $this->assertTrue($dbFactory->isResource('myAlias'));
            $this->assertTrue($dbFactory->isResource('db'));

            // try to add the same alias again (should not be allowed)
            $this->assertFalse($dbFactory->addResourceAlias('db', 'myAlias'));

            // and another alias to the same resource
            $this->assertTrue($dbFactory->addResourceAlias('db', 'anotherAlias'));
            $this->assertEquals(1, $dbFactory->getResourceCount());
            $this->assertEquals(2, $dbFactory->getAliasCount());

            // remove the extra alias
            $this->assertTrue($dbFactory->removeResourceAlias('anotherAlias'));

            // attempt to remove a resource that does not exist
            $this->assertFalse($dbFactory->removeResourceAlias('whoAreYou'));

            // we should be able to get a connection
            $this->assertTrue($dbFactory->connectResource('myAlias'));
            $this->assertTrue($dbFactory->isResourceConnected('myAlias'));

            // the connection should be a PDO instance
            $this->assertInstanceOf('\PDO', $db->pdo);

            // check connection has the correct dsn
            $this->assertEquals($dbConfig['dsn'], $db->dsn);

            // disconnect from the db via the alias
            $this->assertTrue($dbFactory->disconnectResource('myAlias'));
            $this->assertFalse($dbFactory->isResourceConnected('myAlias'));
            $this->assertFalse($dbFactory->isResourceConnected('db'));
            $this->assertNull($db->pdo);

            // resource and alias should remain in place
            $this->assertEquals(1, $dbFactory->getResourceCount());
            $this->assertEquals(1, $dbFactory->getAliasCount());

            // re-connect
            $this->assertTrue($dbFactory->connectResource('myAlias'));
            $this->assertTrue($dbFactory->isResourceConnected('myAlias'));
            $this->assertTrue($dbFactory->isResourceConnected('db'));
            $this->assertInstanceOf('\yii\db\Connection', $db);

            // remove the resource via the alias
            $this->assertTrue($dbFactory->removeResource('myAlias'));
            $this->assertFalse($dbFactory->isResource('myAlias'));
            $this->assertFalse($dbFactory->isResource('db'));

            // resource and alias should be gone
            $this->assertEquals(0, $dbFactory->getResourceCount());
            $this->assertEquals(0, $dbFactory->getAliasCount());

        }
    }

    /*
     * Test removing all resources excluding the core 'db' resource
     */
    public function testRemoveAllResourcesExceptCore()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');

        $db = $dbFactory->getConnection('db');
        $this->assertInstanceOf('\yii\db\Connection', $db);

        $db2 = $dbFactory->getConnection('db2');
        $this->assertInstanceOf('\yii\db\Connection', $db2);

        $this->assertEquals(2, $dbFactory->getResourceCount());

        $this->assertTrue($dbFactory->removeAllResources());
        $this->assertEquals(1, $dbFactory->getResourceCount());
    }

    /*
     * Test removing all resources including the core 'db' resource
     */
    public function testRemoveAllResourcesIncludingCore()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');

        $db = $dbFactory->getConnection('db');
        $this->assertInstanceOf('\yii\db\Connection', $db);

        $db2 = $dbFactory->getConnection('db2');
        $this->assertInstanceOf('\yii\db\Connection', $db2);

        $this->assertEquals(2, $dbFactory->getResourceCount());

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());
    }

    /*
     * Test functions relating to adding/removing resources manually
     * along with aliases
     */
    public function testCheckMethodsWhereResourceDoesNotExist()
    {
        $dbFactory = Yii::$app->getComponent('dbFactory');

        $this->assertFalse($dbFactory->isResource('dbRandom'));
        $this->assertFalse($dbFactory->isResourceConnected('dbRandom'));
        $this->assertFalse($dbFactory->connectResource('dbRandom'));
        $this->assertFalse($dbFactory->disconnectResource('dbRandom'));
        $this->assertFalse($dbFactory->removeResource('dbRandom'));
        $this->assertFalse($dbFactory->removeResourceAlias('dbRandom'));
        $this->assertFalse($dbFactory->getResourceSettings('dbRandom'));
        $this->assertFalse($dbFactory->addResourceAlias('dbRandom', 'dbRandom2'));
        $this->assertFalse($dbFactory->addResourceAlias('dbRandom', 'dbRandom'));
        $this->assertFalse($dbFactory->getConnection('dbRandom'));
        $this->assertFalse($dbFactory->getResourceNameByAlias('dbRandom'));

    }


}
