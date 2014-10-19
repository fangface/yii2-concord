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

use fangface\concord\tests\models\Address;
use fangface\concord\tests\models\ConnectionTestCase;
use fangface\concord\tests\models\Country;
use fangface\concord\tests\models\Customer;
use fangface\concord\tests\models\Item;
use fangface\concord\tests\models\TestCase;
use fangface\concord\tests\models\Order;
use fangface\concord\tests\models\Product;
use fangface\concord\tests\models\eav\AttributeValues;

/**
 * This is the base class for all Concord database unit tests
 */
abstract class DbTestCase extends ConnectionTestCase
{

    /**
     * @var string default sub directory from which to take /migrations
     */
    public $dbSetupType = 'general';


    /**
     * (non-PHPdoc)
     * @see \fangface\concord\tests\TestCase::setUp()
     * @see \fangface\concord\tests\TestCase::localSetUp()
     */
    protected function localSetUp()
    {
        $this->runMigrations($this->dbSetupType);
        $this->runFixtures($this->dbSetupType);
    }


    /**
     * (non-PHPdoc)
     * @see \fangface\concord\tests\TestCase::tearDown()
     * @see \fangface\concord\tests\TestCase::localTearDown()
     */
    protected function localTearDown()
    {
        $this->runMigrations($this->dbSetupType, true);
    }


    /**
     * Obtain the db connection from yii components
     *
     * @param string $db component name for db
     * @return \yii\db\Connection
     */
    protected function getDbConnection($db = 'db')
    {
        return \Yii::$app->get($db);
    }


    /**
     * Prepare db tables required to perform the tests
     *
     * @param string $dbSetupType migration directory name within /migrations to run
     * @param boolean $tearDown [OPTIONAL] should the migration be ran as down()
     */
    protected function runMigrations($dbSetupType, $tearDown = false)
    {

        if ($dbSetupType) {

            $migrationPath = \Yii::getAlias('@fangface/concord/tests/migrations');
            $migrations = [];
            $files = glob($migrationPath . DIRECTORY_SEPARATOR . ($dbSetupType ? $dbSetupType . DIRECTORY_SEPARATOR : '') . '*.php');
            foreach ($files as $path) {
                $file = basename($path);
                if (preg_match('/^(.*)\.(.*?)\.php$/', $file, $matches) && is_file($path)) {
                    $migrations[] = array(
                        'db' => $matches[1],
                        'classFull' => '\\fangface\concord\\tests\\migrations\\' . ($dbSetupType ? $dbSetupType . '\\' : '') . $matches[1] . ucfirst($matches[2]),
                        'class' => $matches[1] . ucfirst($matches[2]),
                        'file' => $file,
                        'path' => $path,
                    );
                }
            }

            ob_start();
            foreach ($migrations as $migration) {

                require_once ($migration['path']);
                $object = new $migration['classFull']([
                    'db' => $this->getDbConnection($migration['db'])
                ]);

                if ($tearDown) {
                    $result = $object->down();
                } else {
                    $result = $object->up();
                }

                if ($result === false) {
                    $ob = ob_get_contents();
                    ob_end_clean();
                    echo "Something went wrong with tests " . $migration['class'] . " migration" . ($tearDown ? ' (tearDown)' : '') . "!\n";
                    echo $ob . "\n";
                    trigger_error('Error in test migration data of ' . $migration['class'] . ($tearDown ? ' (tearDown)' : ''), E_USER_WARNING);
                    ob_start();
                } else {
                    $this->getDbConnection($migration['db'])->close();
                }
            }
            ob_end_clean();
        }
    }


    /**
     * Populate db tables with initial test data
     *
     * @param string $dbSetupType fixtures directory name within /Fixtures to run
     */
    protected function runFixtures($dbSetupType)
    {

        if ($dbSetupType) {

            $fixturesPath = \Yii::getAlias('@fangface/concord/tests/fixtures');
            $fixtures = [];

            $files = glob($fixturesPath . DIRECTORY_SEPARATOR . ($dbSetupType ? $dbSetupType . DIRECTORY_SEPARATOR : '') . '*.php');
            foreach ($files as $path) {
                $file = basename($path);
                if (preg_match('/^(.*)\.(.*?)\.php$/', $file, $matches) && is_file($path)) {
                    if (substr($matches[2], -6) == '-local') {
                        // ignore
                    } else {
                        $fixtures[] = array(
                            'db' => $matches[1],
                            'table' => $matches[2],
                            'file' => $file,
                            'path' => $path,
                        );
                    }
                }
            }

            foreach ($fixtures as $fixture) {
                $fixtureData = require ($fixture['path']);
                $localFixtureFile = str_replace('.php', '-local.php', $fixture['path']);
                if (file_exists($localFixtureFile)) {
                    $fixtureData = array_replace_recursive($fixtureData, (require ($localFixtureFile)));
                }
                if (is_array($fixtureData) && $fixtureData) {
                    $connection = $this->getDbConnection($fixture['db']);
                    $prefix = $connection->tablePrefix;
                    foreach ($fixtureData as $row) {
                        $success = $connection
                            ->createCommand()
                            ->insert($prefix.$fixture['table'], $row)
                            ->execute();
                    }
                }
            }
        }
    }


    /**
     * Clean active record allToArray() dates to a unified time so that new tests can be compared
     * to expected results from previous successful testing
     *
     * @param array $fullDataCheck
     * @return array:
     */
    protected function cleanDatesForComparison($fullDataCheck)
    {
        $data = array();
        foreach ($fullDataCheck as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $this->cleanDatesForComparison($v);
            } else {
                switch ($k)
                {
                	case 'createdAt':
                	case 'created_at':
            	    case 'modifiedAt':
            	    case 'modified_at':
            	        $v = '2050-12-31 23:59:59';
                	    break;
                	case 'createdBy':
                	case 'created_by':
            	    case 'modifiedBy':
            	    case 'modified_by':
            	        $v = 99;
                	    break;
            	    default:
                	    break;
                }
                $data[$k] = $v;
            }
        }
        return $data;
    }


    /**
     * Update customerId throughout active record allToArray() so that new tests on table data
     * can be compared to expected results
     *
     * @param array $fullDataCheck
     * @param integer $customerId new customer id
     * @return array:
     */
    protected function updateCustomerIdForComparison($fullDataCheck, $customerId = 0)
    {
        $data = array();
        foreach ($fullDataCheck as $k => $v) {
            if (is_array($v)) {
                $data[$k] = $this->updateCustomerIdForComparison($v);
            } else {
                if ($customerId > 0) {
                    switch ($k)
                    {
                    	case 'customerId':
                    	    $v = $customerId;
                    	    break;
                    	default:
                    	    break;
                    }
                }
                $data[$k] = $v;
            }
        }
        return $data;
    }


    /**
     * Create a test set of temp data against which subsequent testing
     * can be performed
     *
     * @param string $clientCode
     * @return integer Id of customer created
     */
    public function createTestCustomerAndOrder($clientCode='')
    {
        $customer = new Customer();

        $customer->address->setAttributes(array(
            'title'       => 'Mr',
            'forename'    => 'A',
            'surname'     => 'Sample',
            'jobTitle'    => 'Job',
            'company'     => 'Company',
            'address1'    => 'Address1',
            'address2'    => 'Address2',
            'address3'    => 'Address3',
            'city'        => 'City',
            'region'      => 'Region',
            'countryCode' => 'GBR',
        ),false);

        if ($clientCode == 'CLIENT2') {
            $customer->extraField = 'Extra';
        }

        $customer->phone->telno = '0123456789';

        $customer->customerAttributes->field1 = 'CAField1';
        $customer->customerAttributes->field2 = 'CAField2';

        if ($clientCode == 'CLIENT2') {
            $customer->customerAttributes->field3 = 'CAField3';
        }

        $newOrder = new Order(array(
            'field1' => 'Order-Field-1',
            'field2' => 'Order-Field-2',
            'field3' => 'Order-Field-3',
        ));

        $newOrder->items[] = new Item(array(
            'productCode'   => 'CODE1' . ($clientCode == 'CLIENT2' ? 'B' : ''),
            'quantity'      => 3,
            'totalValue'    => 3.36,
            'field1'        => 'Item-Field-1',
            'field2'        => 'Item-Field-2',
            'field3'        => 'Item-Field-3',
        ));

        $newOrder->items[] = new Item(array(
            'productCode'   => 'CODE2' . ($clientCode == 'CLIENT2' ? 'B' : ''),
            'quantity'      => 2,
            'totalValue'    => 4.80,
            'field1'        => 'Item-Field-1',
            'field2'        => 'Item-Field-2',
            'field3'        => 'Item-Field-3',
        ));

        $newOrder->items[] = new Item(array(
            'productCode'   => 'POST',
            'quantity'      => 1,
            'totalValue'    => 3.98,
            'field1'        => 'Item-Field-1',
            'field2'        => 'Item-Field-2',
            'field3'        => 'Item-Field-3',
        ));

        $customer->orders[] = $newOrder;

        $ok = $customer->saveAll();

        if (!$ok) {
            return false;
        }

        $customerId = $customer->id;

        $fullDataCheck = array();
        $fullDataCheck[$customerId] = $customer->allToArray();

        $fullDataCheck = $this->cleanDatesForComparison($fullDataCheck);

        $resultsPath = \Yii::getAlias('@fangface/concord/tests/data/results');
        $resultsFile = $resultsPath . '/ar-test2-' . strtolower($clientCode) . '.json';
        if (false) {
            // for use when comparing future tests
            file_put_contents($resultsFile, json_encode($fullDataCheck));
            // readable results
            file_put_contents(str_replace('.json', '.txt', $resultsFile), print_r($fullDataCheck, true));
        } else {
            $expectedResult = json_decode(file_get_contents($resultsFile), true);
            //if ($fullDataCheck != $expectedResult) {
            //    file_put_contents(str_replace('.json', '-testing.txt', $resultsFile), print_r($fullDataCheck, true));
            //}
            $this->assertEquals($expectedResult, $fullDataCheck, 'Failed to match createTestCustomerAndOrder() result for ' . strtolower($clientCode));
        }

        return $customerId;
    }

    function checkBaseCounts($type = '', $clientCode = '', $customers = 0, $addresses = 0, $phones = 0, $orders = 0, $items = 0, $attributes = 0, $countries = 2, $products = 4)
    {
        if ($type != '') {
            switch ($type)
            {
            	case 'full':
            	    $customers = 1;
            	    $addresses = 1;
            	    $phones = 1;
            	    $orders = 1;
            	    $items = 3;
            	    $attributes = ($clientCode == 'CLIENT2' ? 5 : 4);
            	    break;
            	case 'lessoneitem':
            	    $customers = 1;
            	    $addresses = 1;
            	    $phones = 1;
            	    $orders = 1;
            	    $items = 2;
            	    $attributes = ($clientCode == 'CLIENT2' ? 5 : 4);
            	    break;
            	case 'lessaddress':
            	    $customers = 1;
            	    $addresses = 0;
            	    $phones = 1;
            	    $orders = 1;
            	    $items = 3;
            	    $attributes = ($clientCode == 'CLIENT2' ? 5 : 4);
            	    break;
            }
        }

        $this->assertEquals($customers, Customer::find()->count());
        $this->assertEquals($addresses, Address::find()->count());
        $this->assertEquals($attributes, AttributeValues::find()->count());
        $this->assertEquals($orders, Order::find()->count());
        $this->assertEquals($items, Item::find()->count());
        $this->assertEquals($countries, Country::find()->count());
        $this->assertEquals($products, Product::find()->count());
    }

}
