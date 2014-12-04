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

use fangface\tests\models\DbTestCase;
use fangface\models\db\DbResource;
use fangface\models\db\client\DbResource as ClientDBResource;
use fangface\base\traits\ServiceGetter;
use fangface\models\db\Client;
use fangface\tests\models\Customer;
use fangface\tests\models\Robot;
use yii\db\Connection;

/**
 * Test Concord Active Record add-on for Yii2
 */
class A2MultiDbResourcesTest extends DbTestCase
{

    use ServiceGetter;

    /**
     * Test multiple db resources specified in a local dbResources table
     * loop through each connection specified and request connection info
     * via the connection manager
     */
    public function testLoopThroughDifferentDbResources()
    {

        $dbFactory = \Yii::$app->get('dbFactory');

        $dbResources = DbResource::find()
            ->orderBy('id')
            ->all();

        $counter = 1;
        foreach ($dbResources as $dbResource) {
            $counter++;

            $dbClient = $dbFactory->getConnection($dbResource->resourceName, true);

            $this->assertInstanceOf(Connection::className(), $dbClient);
            $this->assertEquals($dbResource->dbDsn, $dbClient->dsn);
            $this->assertEquals($counter, $dbFactory->getResourceCount());

            // models want to make use of a dbClient connection so add an alias to it
            $this->assertTrue($dbFactory->addResourceAlias($dbResource->resourceName, 'dbClient'));

            // we should be able to get a connection
            $this->assertTrue($dbFactory->connectResource('dbClient'));
            $this->assertTrue($dbFactory->isResourceConnected('dbClient'));
            $this->assertInstanceOf('\PDO', $dbClient->pdo);

            if ($dbResource->resourceName == 'dbClient1') {
                $this->assertRegExp('/dbTestClient1/', $dbClient->dsn);
            } else {
                $this->assertRegExp('/dbTestClient2/', $dbClient->dsn);
            }

            $customer = new Customer;
            if ($dbResource->resourceName == 'dbClient1') {
                $this->assertTrue(!$customer->hasAttribute('extraField'));
            } else {
                // the customer table for CLIENT2 has an extra extraField attribute
                $this->assertTrue($customer->hasAttribute('extraField'));
            }

            $dbRemoteResource = ClientDbResource::find()
                ->where(['resourceName' => 'dbRemote'])
                ->one();

            $dbParams = array(
                'class'                => $dbRemoteResource->dbClass,
                'dsn'                  => $dbRemoteResource->dbDsn,
                'username'             => $dbRemoteResource->dbUser,
                'password'             => $dbRemoteResource->dbPass,
                'charset'              => $dbRemoteResource->dbCharset,
                'tablePrefix'          => $dbRemoteResource->dbPrefix,
                'connect'              => false,
                'enableSchemaCache'    => $dbClient->enableSchemaCache,
                'schemaCacheDuration'  => $dbClient->schemaCacheDuration,
                'schemaCacheExclude'   => array(), // $connection->schemaCacheExclude,
                'schemaCache'          => $dbClient->schemaCache,
                'enableQueryCache'     => $dbClient->enableQueryCache,
                'queryCacheDuration'   => $dbClient->queryCacheDuration,
                'queryCache'           => $dbClient->queryCache,
                'emulatePrepare'       => NULL, // $connection->emulatePrepare,
            );

            $this->assertTrue($dbFactory->addResource('dbRemote', false, false, $dbParams));

            $dbRemote = $dbFactory->getConnection('dbRemote');

            $this->assertInstanceOf(Connection::className(), $dbRemote);
            $this->assertEquals($dbRemoteResource->dbDsn, $dbRemote->dsn);
            $this->assertEquals(($counter+1), $dbFactory->getResourceCount());

            // we should be able to get a connection
            $this->assertTrue($dbFactory->connectResource('dbRemote'));
            $this->assertTrue($dbFactory->isResourceConnected('dbRemote'));
            $this->assertInstanceOf('\PDO', $dbRemote->pdo);

            if ($dbResource->resourceName == 'dbClient1') {
                $this->assertRegExp('/dbTestRemote1/', $dbRemote->dsn);
            } else {
                $this->assertRegExp('/dbTestRemote2/', $dbRemote->dsn);
            }

            $robot = Robot::findOne(1);
            if ($dbResource->resourceName == 'dbClient1') {
                $this->assertTrue(!$robot->hasAttribute('extraField'));
            } else {
                // the robot table for CLIENT2s dbRemote connection has an extra extraField attribute
                $this->assertTrue($robot->hasAttribute('extraField'));
            }

            $this->assertTrue($dbFactory->removeResourceAlias('dbClient'));
            $this->assertTrue($dbFactory->removeResource('dbRemote'));
        }

        $this->assertTrue($dbFactory->removeAllResources());
        $this->assertEquals(1, $dbFactory->getResourceCount());

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }


    /**
     * Test multiple db resources by looping over 2 clients and connecting
     * to their and their own specified dbResources which will share the same
     * connection name across both clients
     */
    public function testLoopThroughDifferentClients()
    {

        $dbFactory = \Yii::$app->get('dbFactory');

        $clients = Client::find()
            ->orderBy('id')
            ->all();

        foreach ($clients as $client) {

            $this->assertInstanceOf(Client::className(), $client);
            $this->setService('client', $client);

            // get the dbClient connection (should be based on the 'client' component setup above
            //$db = $dbFactory->getConnection('dbClient', true);
            $db = $dbFactory->getClientConnection(true); // command equivalent to the above

            // $db should now be the connection to the current client (not connected)
            $this->assertInstanceOf(Connection::className(), $db);
            $this->assertEquals($client->dbDsn, $db->dsn);
            $this->assertEquals(2, $dbFactory->getResourceCount());

            // we should be able to get a connection
            $this->assertTrue($dbFactory->connectResource('dbClient'));
            $this->assertTrue($dbFactory->isResourceConnected('dbClient'));
            $this->assertInstanceOf('\PDO', $db->pdo);


            $customer = new Customer;
            if ($client->clientCode == 'CLIENT1') {
                $this->assertTrue(!$customer->hasAttribute('extraField'));
            } else {
                // the customer table for CLIENT2 has an extra extraField attribute
                $this->assertTrue($customer->hasAttribute('extraField'));
            }

            // find details of the current clients dbRemote connection
            $dbResource = ClientDbResource::find()
                ->where(['resourceName' => 'dbRemote'])
                ->one();

            // now attempt to get a connection to the specific clients dbRemote resource as defined
            // in the clients.dbResources table
            //$dbRemote = $dbFactory->getConnection('dbClient', true, false, true);
            $dbRemote = $dbFactory->getClientResourceConnection('dbRemote', true); // identical to the getConnection() call above

            // $dbRemote should now be the connection to the current clients dbRemote resource (not connected)
            $this->assertInstanceOf(Connection::className(), $dbRemote);
            $this->assertEquals($dbResource->dbDsn, $dbRemote->dsn);
            $this->assertEquals(3, $dbFactory->getResourceCount());

            // we should be able to get a connection
            $this->assertTrue($dbFactory->connectResource('dbRemote'));
            $this->assertTrue($dbFactory->isResourceConnected('dbRemote'));
            $this->assertInstanceOf('\PDO', $dbRemote->pdo);

            if ($client->clientCode == 'CLIENT1') {
                $this->assertRegExp('/dbTestRemote1/', $dbRemote->dsn);
            } else {
                $this->assertRegExp('/dbTestRemote2/', $dbRemote->dsn);
            }

            $robot = Robot::findOne(1);
            if ($client->clientCode == 'CLIENT1') {
                $this->assertTrue(!$robot->hasAttribute('extraField'));
            } else {
                // the robot table for CLIENT2s dbRemote connection has an extra extraField attribute
                $this->assertTrue($robot->hasAttribute('extraField'));
            }

            $this->assertTrue($dbFactory->removeAllResources());
            $this->assertEquals(1, $dbFactory->getResourceCount());

        }

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }


    /**
     * Test client db resources auto load by looping over 2 clients and pulling in
     * a remote active active record belonging to a client db resource without
     * having first defined the connection in the connection manager
     */
    public function testLoopThroughDifferentClientsAutoActiveRecordDbResource()
    {

        $dbFactory = \Yii::$app->get('dbFactory');

        $clients = Client::find()
            ->orderBy('id')
            ->all();

        foreach ($clients as $client) {

            $this->assertInstanceOf(Client::className(), $client);

            // setting the cient component up in yii will make the client connection available
            // when required to determine the clients dbRemote connection for the Robot model
            $this->setService('client', $client);

            $robot = Robot::findOne(1);

            if ($client->clientCode == 'CLIENT1') {
                $this->assertTrue(!$robot->hasAttribute('extraField'));
            } else {
                // the robot table for CLIENT2s dbRemote connection has an extra extraField attribute
                $this->assertTrue($robot->hasAttribute('extraField'));
            }

            // remove all resources other than core db (typical action taken when switching from one client to another)
            $this->assertTrue($dbFactory->removeAllResources());
            $this->assertEquals(1, $dbFactory->getResourceCount());

        }

        $this->assertTrue($dbFactory->removeAllResources(true));
        $this->assertEquals(0, $dbFactory->getResourceCount());

    }


    /**
     * Fail to obtain the 'db' connection via the connection manager because it has not been
     * defined in the connection manager and the parameters specify to attempt to add the
     * resource and not to use anything defined in components
     * @expectedException        \fangface\db\Exception
     * @expectedExceptionMessage Missing dbParams on addResource
     */
    public function testGetUndefinedDbConnectionFails()
    {
        $db = \Yii::$app->get('dbFactory')->getConnection('dbX', true, false);
    }

}

