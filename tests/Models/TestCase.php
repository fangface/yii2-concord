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

use yii\helpers\ArrayHelper;

/**
 * This is the base class for all Concord unit tests
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{

    protected static $params;


    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        spl_autoload_unregister([
            'Yii',
            'autoload'
        ]);

        spl_autoload_register([
            'Yii',
            'autoload'
        ]); // put yii's autoloader at the end
    }


    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->setupApplication();
        $this->localSetUp();
    }


    /**
     * Typicaly used by individual test classes to extend the
     * setUp method
     * @see \Concord\Tests\TestCase::setUp()
     */
    protected function localSetUp()
    {

    }


    /**
     * Clean up after test.
     * By default the application created with [[mockApplication]] will be destroyed.
     */
    protected function tearDown()
    {
        $this->localTearDown();
        $this->destroyApplication();
        parent::tearDown();
    }


    /**
     * Typicaly used by individual test classes to extend the
     * tearDown method
     * @see \Concord\Tests\TestCase::tearDown()
     */
    protected function localTearDown()
    {

    }


    /**
     * Returns a test configuration param from /config.php
     * and/or /config-local.php if present
     *
     * @param string $name
     *        params name
     * @param mixed $default
     *        default value to use when param is not set.
     * @return mixed the value of the configuration param
     */
    public function getParam($name, $default = null)
    {
        if (static::$params === null) {
            if (file_exists(__DIR__ . '/../config-local.php')) {
                static::$params = ArrayHelper::merge(
                    require(__DIR__ . '/../config.php'),
                    require(__DIR__ . '/../config-local.php')
                );
            } else {
                static::$params = require (__DIR__ . '/../config.php');
            }
        }
        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }


    /**
     * Returns a db config value
     *
     * @param string $dbName
     *        db name in config
     * @param string $paramName [OPTIONAL]
     *        params name (default null means return all config values for that connection)
     * @param mixed $default
     *        default value to use when db config param is not set.
     * @return mixed the value of the db config param
     */
    public function getDbParam($dbName, $paramName = null, $default = null)
    {
        $appConfig = $this->getParam('app', $default = null);
        if ($paramName === null) {
            if (isset($appConfig['components'][$dbName])) {
                return $appConfig['components'][$dbName];
            }
        } elseif (isset($appConfig['components'][$dbName][$paramName])) {
            return $appConfig['components'][$dbName][$paramName];
        }
        return $default;
    }


    /**
     * Create the Yii application
     */
    protected function setupApplication()
    {
        $this->mockApplication($this->getParam('app'), '\yii\web\Application');
    }


    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     *
     * @param array $config
     *        The application configuration, if needed
     * @param string $appClass
     *        name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        $application = new $appClass($config);
    }


    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        \Yii::$app = null;
    }

}
